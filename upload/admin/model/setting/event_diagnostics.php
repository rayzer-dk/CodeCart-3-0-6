<?php
/** Read-only Event handler verifier. Invoked only by an explicit admin request. */
class ModelSettingEventDiagnostics extends Model {
    public function inspect(array $events, array $history = array()) {
        $rows = array();
        $summary = array('total' => 0, 'ready' => 0, 'disabled' => 0, 'issues' => 0, 'called' => 0, 'not_called' => 0);
        $seen = array();
        $cache = array();
        foreach ($events as $event) {
            $summary['total']++;
            $id = (int)$event['event_id'];
            $trigger = (string)$event['trigger'];
            $action = (string)$event['action'];
            $enabled = !empty($event['status']);
            $scope = strpos($trigger, 'admin/') === 0 ? 'admin' : (strpos($trigger, 'catalog/') === 0 ? 'catalog' : '');
            $file = '';
            $state = 'ready';

            if (!$enabled) { $state = 'disabled'; }
            elseif ($scope === '' || strlen($trigger) <= strlen($scope) + 1) { $state = 'invalid'; }
            elseif ($action === '' || !preg_match('~^[a-zA-Z0-9_/]+$~D', $action)) { $state = 'invalid'; }
            else {
                $resolved = $this->resolveAction($scope, $action);
                if (!$resolved) { $state = 'missing_file'; }
                else {
                    $file = $resolved['relative'];
                    $key = $resolved['path'] . ':' . $resolved['class'] . ':' . $resolved['method'];
                    if (!isset($cache[$key])) {
                        $cache[$key] = $this->methodVisibility($resolved['path'], $resolved['class'], $resolved['method']);
                    }
                    if ($cache[$key] !== 'public') { $state = 'method_unverified'; }
                }
            }
            $pair = $scope . '|' . $trigger . '|' . $action;
            if ($enabled && $state === 'ready' && isset($seen[$pair])) { $state = 'duplicate'; }
            if ($enabled) { $seen[$pair] = true; }
            $h = $history[$id] ?? array();
            if ($enabled && !empty($h['errors'])) { $state = 'runtime_error'; }
            if ($state === 'disabled') { $summary['disabled']++; }
            elseif ($state === 'ready') { $summary['ready']++; }
            else { $summary['issues']++; }
            if ($enabled) {
                if (!empty($h['calls'])) { $summary['called']++; } else { $summary['not_called']++; }
            }
            $rows[] = array(
                'id' => $id, 'code' => (string)$event['code'], 'trigger' => $trigger,
                'action' => $action, 'state' => $state, 'file' => $file,
                'calls' => (int)($h['calls'] ?? 0), 'errors' => (int)($h['errors'] ?? 0),
                'last_ms' => (float)($h['last_ms'] ?? 0),
                'exception' => (string)($h['exception'] ?? '')
            );
        }
        return array('summary' => $summary, 'rows' => $rows);
    }

    private function resolveAction($scope, $action) {
        $parts = explode('/', $action);
        $method = 'index';
        while ($parts) {
            $route = implode('/', $parts);
            $relative = $scope . '/controller/' . $route . '.php';
            $original = ($scope === 'admin' ? DIR_APPLICATION : DIR_CATALOG) . 'controller/' . $route . '.php';
            $modified = defined('DIR_MODIFICATION') ? rtrim(DIR_MODIFICATION, '/\\') . '/' . $relative : '';
            $path = is_file($original) ? $original : '';
            // Only use the generated copy when it belongs to the running build.
            if ($modified !== '' && is_file($modified)) {
                $marker = rtrim(DIR_MODIFICATION, '/\\') . '/.codecart-build';
                $expected = defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : '';
                if ($expected === '' || (is_file($marker) && hash_equals($expected, trim((string)@file_get_contents($marker))))) {
                    $path = $modified;
                }
            }
            if ($path !== '') {
                return array('path' => $path, 'relative' => $relative, 'method' => $method,
                    'class' => 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $route));
            }
            $method = array_pop($parts);
        }
        return array();
    }

    private function methodVisibility($file, $class, $method) {
        if ($method === '' || substr($method, 0, 2) === '__') { return 'unverified'; }
        $source = @file_get_contents($file);
        if (!is_string($source)) { return 'unverified'; }
        $tokens = token_get_all($source);
        $body = $this->classBodyStart($tokens, $class);
        if ($body === null) { return 'unverified'; }
        return $this->declaredVisibility($tokens, $body, $method);
    }

    /** Index of the first token after the opening brace of the named class, or null when it is absent. */
    private function classBodyStart(array $tokens, $class) {
        $wantClass = false;
        $awaitBody = false;
        foreach ($tokens as $i => $token) {
            $type = is_array($token) ? $token[0] : 0;
            $value = is_array($token) ? $token[1] : $token;
            if ($wantClass && $type === T_STRING) {
                $awaitBody = strcasecmp($value, $class) === 0;
                $wantClass = false;
            } elseif ($type === T_CLASS) {
                $wantClass = true;
            } elseif ($value === '{' && $awaitBody) {
                return $i + 1;
            }
        }
        return null;
    }

    /** Visibility of the method declared at the top level of a class body starting at $start. */
    private function declaredVisibility(array $tokens, $start, $method) {
        $count = count($tokens);
        $depth = 1;
        $visibility = 'public';
        for ($i = $start; $i < $count; $i++) {
            $token = $tokens[$i];
            $type = is_array($token) ? $token[0] : 0;
            $value = is_array($token) ? $token[1] : $token;
            if ($value === '{') { $depth++; continue; }
            if ($value === '}') {
                $depth--;
                if ($depth === 0) { return 'unverified'; }
                continue;
            }
            if ($depth !== 1) { continue; }
            if ($type === T_PRIVATE) { $visibility = 'private'; }
            elseif ($type === T_PROTECTED) { $visibility = 'protected'; }
            elseif ($type === T_PUBLIC) { $visibility = 'public'; }
            elseif ($type === T_FUNCTION) {
                if ($this->functionName($tokens, $i + 1) === strtolower($method)) { return $visibility; }
                $visibility = 'public';
            } elseif ($value === ';') { $visibility = 'public'; }
        }
        return 'unverified';
    }

    /** Lower-cased name of the function declared after token $from, or an empty string for closures. */
    private function functionName(array $tokens, $from) {
        $count = count($tokens);
        for ($j = $from; $j < $count; $j++) {
            $next = $tokens[$j];
            if (is_array($next) && $next[0] === T_STRING) { return strtolower($next[1]); }
            if ((is_array($next) ? $next[1] : $next) === '(') { break; }
        }
        return '';
    }
}
