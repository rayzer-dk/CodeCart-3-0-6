<?php
namespace CodeCart\Core;

final class SearchAdapter {
    private $providers = array();
    private $active = '';

    public function register(string $code, callable $handler, int $priority = 100): void {
        $code = strtolower(trim($code));
        if (!preg_match('/^[a-z][a-z0-9_-]{1,31}$/', $code)) throw new \InvalidArgumentException('Invalid search provider code.');
        $this->providers[$code] = array('handler'=>$handler, 'priority'=>$priority);
    }

    public function use(string $code): void { $this->active = strtolower(trim($code)); }
    public function active(): string { return $this->active; }
    public function providers(): array { return array_keys($this->providers); }

    public function search(array $criteria, callable $fallback): array {
        if ($this->active !== '' && isset($this->providers[$this->active])) {
            try {
                $result = call_user_func($this->providers[$this->active]['handler'], $criteria);
                if (is_array($result)) return array('provider'=>$this->active, 'fallback'=>false, 'items'=>$result);
            } catch (\Throwable $e) {
                // External search is optional by design. Fall back to the native catalog path.
            }
        }
        $result = call_user_func($fallback, $criteria);
        return array('provider'=>'native', 'fallback'=>true, 'items'=>is_array($result) ? $result : array());
    }
}
