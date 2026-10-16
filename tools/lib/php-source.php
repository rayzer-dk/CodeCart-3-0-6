<?php

/** PHP tokens with comments and whitespace removed; string literals stay intact. */
function ccpSourceTokens($source) {
    return array_values(array_filter(token_get_all($source), function ($token) {
        return !is_array($token) || !in_array($token[0], array(T_COMMENT, T_DOC_COMMENT, T_WHITESPACE), true);
    }));
}

function ccpLanguageAssignmentKeys($source) {
    $tokens = ccpSourceTokens($source);
    $keys = array();
    for ($i = 0, $count = count($tokens); $i + 4 < $count; $i++) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_VARIABLE && $tokens[$i][1] === '$_' &&
            $tokens[$i + 1] === '[' && is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING &&
            $tokens[$i + 3] === ']' && $tokens[$i + 4] === '=') {
            $literal = $tokens[$i + 2][1];
            $value = substr($literal, 1, -1);
            $keys[] = $literal[0] === "'" ? str_replace(array("\\\\", "\\'"), array("\\", "'"), $value) : stripcslashes($value);
        }
    }
    return $keys;
}

function ccpExtensionControllerHasMetadata($source, $type) {
    $tokens = ccpSourceTokens($source);
    $found = array();
    for ($i = 0, $count = count($tokens); $i + 3 < $count; $i++) {
        $token = $tokens[$i];
        if (is_array($token) && $token[0] === T_STRING && in_array($token[1], array('getInstallDates','getUploadDates'), true) &&
            $tokens[$i + 1] === '(' && is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING &&
            trim($tokens[$i + 2][1], "'\"") === $type && $tokens[$i + 3] === ')') {
            $found[$token[1]] = true;
        }
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && trim($token[1], "'\"") === 'code' &&
            is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_DOUBLE_ARROW &&
            is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === T_VARIABLE && $tokens[$i + 2][1] === '$extension') {
            $found['code'] = true;
        }
    }
    return isset($found['getInstallDates'], $found['getUploadDates'], $found['code']);
}

/** Static language loads and object aliases; dynamic routes/keys are outside this check. */
function ccpLanguageReferences($source) {
    $code = '';
    foreach (ccpSourceTokens($source) as $token) { $code .= is_array($token) ? $token[1] : $token; }
    $routes = array();
    $aliases = array();
    preg_match_all('~(?:\$this->load->language|\$language->load)\(([\'"])([a-z0-9_/]+)\1(?:,([\'"])([a-z0-9_]+)\3)?\)~', $code, $loads, PREG_SET_ORDER);
    foreach ($loads as $load) {
        if (isset($load[4]) && $load[4] !== '') { $aliases[$load[4]][] = $load[2]; }
        else { $routes[] = $load[2]; }
    }
    $variables = array();
    preg_match_all('~(\$[a-zA-Z_][a-zA-Z0-9_]*)=\$this->language->get\(([\'"])([a-z0-9_]+)\2\)~', $code, $assignments, PREG_SET_ORDER);
    foreach ($assignments as $assignment) {
        if (isset($aliases[$assignment[3]])) { $variables[$assignment[1]] = $assignment[3]; }
    }
    $needed = array();
    $aliasKeys = array();
    preg_match_all('~(\$this->language|\$[a-zA-Z_][a-zA-Z0-9_]*)->get\(([\'"])([a-z0-9_]+)\2\)~', $code, $gets, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach ($gets as $get) {
        $receiver = $get[1][0];
        $key = $get[3][0];
        if (isset($variables[$receiver])) { $aliasKeys[$variables[$receiver]][$key] = true; }
        elseif ($receiver === '$this->language' && isset($aliases[$key])) {
            $tail = substr($code, $get[0][1] + strlen($get[0][0]));
            if (preg_match('~^->get\(([\'"])([a-z0-9_]+)\1\)~', $tail, $chain)) { $aliasKeys[$key][$chain[2]] = true; }
        } elseif ($receiver === '$this->language' || $receiver === '$language') {
            // Chained access can retrieve an object registered by a dynamic route.
            // Its dictionary cannot be resolved statically; do not call it a phrase.
            $tail = substr($code, $get[0][1] + strlen($get[0][0]));
            if (strpos($tail, '->') !== 0) { $needed[$key] = true; }
        }
    }
    return array('code'=>$code, 'routes'=>array_values(array_unique($routes)), 'aliases'=>$aliases, 'alias_keys'=>$aliasKeys, 'needed'=>$needed);
}

/** The loader returns null for missing files and a dictionary for existing routes. */
function ccpLanguageAliasErrors(array $references, callable $loadRoute) {
    $errors = array();
    foreach ($references['aliases'] as $alias=>$routes) {
        $available = array();
        foreach (array_unique($routes) as $route) {
            $values = $loadRoute($route);
            if ($values === null) { $errors[] = 'alias ' . $alias . ' loads missing language route ' . $route; }
            else { $available += $values; }
        }
        foreach ($references['alias_keys'][$alias] ?? array() as $key=>$unused) {
            if (!array_key_exists($key, $available)) { $errors[] = 'alias ' . $alias . ' uses missing key ' . $key; }
        }
    }
    return $errors;
}
