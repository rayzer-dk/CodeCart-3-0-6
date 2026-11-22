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
        $wantClass = false; $awaitBody = false; $inClass = false;
        $depth = 0; $visibility = 'public';
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            $type = is_array($t) ? $t[0] : 0;
            $v = is_array($t) ? $t[1] : $t;
            if (!$inClass) {
                if ($wantClass && $type === T_STRING) {
                    $awaitBody = strcasecmp($v, $class) === 0;
                    $wantClass = false;
                } elseif ($type === T_CLASS) { $wantClass = true; }
                elseif ($v === '{' && $awaitBody) { $inClass = true; $depth = 1; $awaitBody = false; }
                continue;
            }
            if ($v === '{') { $depth++; continue; }
            if ($v === '}') {
                $depth--;
                if ($depth === 0) { return 'unverified'; }
                if ($depth === 1) { $visibility = 'public'; }
                continue;
            }
            if ($depth !== 1) { continue; }
            if ($type === T_PRIVATE) { $visibility = 'private'; }
            elseif ($type === T_PROTECTED) { $visibility = 'protected'; }
            elseif ($type === T_PUBLIC) { $visibility = 'public'; }
            elseif ($type === T_FUNCTION) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $next = $tokens[$j];
                    $nt = is_array($next) ? $next[0] : 0;
                    $nv = is_array($next) ? $next[1] : $next;
                    if ($nt === T_STRING) {
                        if (strcasecmp($nv, $method) === 0) { return $visibility; }
                        break;
                    }
                    if ($nv === '(') { break; }
                }
                $visibility = 'public';
            } elseif ($v === ';') { $visibility = 'public'; }
        }
        return 'unverified';
    }
}
