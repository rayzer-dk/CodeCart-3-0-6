<?php
namespace CodeCart\Core;

/**
 * Runtime compatibility framework for legacy OpenCart/ocStore contracts and
 * third-party themes/extensions. Core exposes stable contracts; adapters
 * translate those contracts without patching modern Core implementations.
 */
final class CompatibilityFramework {
    private $registry;
    private $adapters = array();
    private $loaded = false;
    private $diagnostics = array();

    public function __construct($registry) {
        $this->registry = $registry;
    }

    public function apply(string $contract, array $payload = array()): array {
        if (!preg_match('/^[a-z][a-z0-9_.-]{2,120}$/', $contract)) {
            throw new \InvalidArgumentException('Invalid compatibility contract.');
        }

        $this->load();
        foreach ($this->adapters as $adapter) {
            $started = microtime(true);
            try {
                if (!$adapter->active()) {
                    continue;
                }
                $result = $adapter->adapt($contract, $payload);
                if (is_array($result)) {
                    $payload = $result;
                }
                $this->diagnostics[] = array(
                    'contract' => $contract,
                    'adapter' => $adapter->id(),
                    'status' => 'ok',
                    'ms' => (int)round((microtime(true) - $started) * 1000)
                );
            } catch (\Throwable $e) {
                // Compatibility adapters must never take the storefront down.
                $this->diagnostics[] = array(
                    'contract' => $contract,
                    'adapter' => $adapter->id(),
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'ms' => (int)round((microtime(true) - $started) * 1000)
                );
                $this->log('Compatibility adapter ' . $adapter->id() . ' failed for ' . $contract . ': ' . $e->getMessage());
            }
        }
        return $payload;
    }

    public function ocmodSatisfied(string $code, string $file, string $search): bool {
        $this->load();
        $file = str_replace('\\', '/', $file);
        foreach ($this->adapters as $adapter) {
            if (!method_exists($adapter, 'satisfiesOcmod')) {
                continue;
            }
            try {
                if ($adapter->satisfiesOcmod($code, $file, $search)) {
                    return true;
                }
            } catch (\Throwable $e) {
                $this->log('OCMOD compatibility check failed for ' . $adapter->id() . ': ' . $e->getMessage());
            }
        }
        return false;
    }

    public function adapters(): array {
        $this->load();
        $items = array();
        foreach ($this->adapters as $adapter) {
            $active = false;
            try { $active = $adapter->active(); } catch (\Throwable $e) { $active = false; }
            $items[] = array(
                'id' => $adapter->id(),
                'priority' => $adapter->priority(),
                'active' => $active,
                'class' => get_class($adapter)
            );
        }
        return $items;
    }

    public function diagnostics(): array {
        return $this->diagnostics;
    }

    private function load(): void {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        // Built-in adapters are maintained and release-tested together with Core.
        $this->register(new Unishop2Compatibility($this->registry), 'builtin');

        // Installable adapters are declared by a modern extension manifest.
        try {
            $extensions = (new ModernExtensionRegistry($this->registry))->all();
            foreach ($extensions as $extension) {
                $compatibility = $extension['capabilities']['compatibility'] ?? array();
                if (!is_array($compatibility)) {
                    continue;
                }
                $declared = $compatibility['adapters'] ?? array();
                if (!is_array($declared)) {
                    continue;
                }
                $namespace = isset($extension['namespace']) ? (string)$extension['namespace'] : '';
                foreach ($declared as $definition) {
                    $class = is_string($definition) ? $definition : (is_array($definition) ? (string)($definition['class'] ?? '') : '');
                    $class = ltrim(trim($class), '\\');
                    if ($class === '' || $namespace === '' || strpos($class . '\\', $namespace) !== 0) {
                        $this->log('Rejected compatibility adapter outside extension namespace: ' . $class);
                        continue;
                    }
                    if (!class_exists($class)) {
                        $this->log('Compatibility adapter class was not found: ' . $class);
                        continue;
                    }
                    $adapter = new $class($this->registry);
                    $this->register($adapter, (string)($extension['code'] ?? 'extension'));
                }
            }
        } catch (\Throwable $e) {
            $this->log('Installable compatibility adapters could not be loaded: ' . $e->getMessage());
        }

        usort($this->adapters, static function ($a, $b) {
            if ($a->priority() === $b->priority()) {
                return strcmp($a->id(), $b->id());
            }
            return $a->priority() <=> $b->priority();
        });
    }

    private function register($adapter, string $source): void {
        if (!$adapter instanceof CompatibilityAdapterInterface) {
            $this->log('Rejected compatibility adapter from ' . $source . ': interface is not implemented.');
            return;
        }
        $id = trim($adapter->id());
        if (!preg_match('/^[a-z][a-z0-9_.-]{2,96}$/', $id)) {
            $this->log('Rejected compatibility adapter from ' . $source . ': invalid id.');
            return;
        }
        foreach ($this->adapters as $registered) {
            if ($registered->id() === $id) {
                $this->log('Duplicate compatibility adapter ignored: ' . $id);
                return;
            }
        }
        $this->adapters[] = $adapter;
    }

    private function log(string $message): void {
        try {
            $log = $this->registry->get('log');
            if ($log) {
                $log->write('[CodeCart Compatibility] ' . $message);
            }
        } catch (\Throwable $ignored) {
        }
    }
}
