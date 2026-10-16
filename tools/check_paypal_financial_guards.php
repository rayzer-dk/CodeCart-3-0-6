<?php
require_once __DIR__ . '/lib/php-source.php';

function paypalGuardMethods($source) {
    $tokens = ccpSourceTokens($source);
    $methods = array();
    $inClass = false;
    $depth = 0;
    for ($i = 0, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];
        if (!$inClass) {
            if (is_array($token) && $token[0] === T_CLASS &&
                isset($tokens[$i + 1][1]) && strtolower($tokens[$i + 1][1]) === 'controllerextensionpaymentpaypal') {
                while ($i < $count && $tokens[$i] !== '{') { $i++; }
                $inClass = true;
                $depth = 1;
            }
            continue;
        }
        if ($depth === 1 && is_array($token) && $token[0] === T_FUNCTION) {
            $name = $tokens[$i + 1];
            if (!is_array($name) || $name[0] !== T_STRING) { continue; }
            while ($i < $count && $tokens[$i] !== '{' && $tokens[$i] !== ';') { $i++; }
            if ($i >= $count || $tokens[$i] === ';') { continue; }
            $body = array();
            $methodDepth = 1;
            while (++$i < $count) {
                $part = $tokens[$i];
                if ($part === '{' || (is_array($part) && in_array($part[0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true))) { $methodDepth++; }
                if ($part === '}' && --$methodDepth === 0) { break; }
                $body[] = $part;
            }
            $methods[strtolower($name[1])] = $body;
            continue;
        }
        if ($token === '{') { $depth++; }
        elseif ($token === '}' && --$depth === 0) { break; }
    }
    return $methods;
}

function paypalGuardTokenValues(array $tokens) {
    $values = array();
    foreach ($tokens as $index => $token) {
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $literal = substr($token[1], 1, -1);
            $values[] = array(T_CONSTANT_ENCAPSED_STRING, $token[1][0] === '"' ? stripcslashes($literal) : str_replace(array("\\\\", "\\'"), array("\\", "'"), $literal));
        } elseif (is_array($token) && (in_array($token[0], array(T_IF, T_RETURN), true) ||
            ($token[0] === T_STRING && ($tokens[$index + 1] ?? null) === '('))) {
            $values[] = strtolower($token[1]);
        } else {
            $values[] = is_array($token) ? $token[1] : $token;
        }
    }
    return $values;
}

function paypalTokenSequence(array $tokens, array $sequence) {
    $values = paypalGuardTokenValues($tokens);
    for ($i = 0; $i + count($sequence) <= count($values); $i++) {
        if (array_slice($values, $i, count($sequence)) === $sequence) { return true; }
    }
    return false;
}

function paypalFinancialGuardErrors($source) {
    $methods = paypalGuardMethods($source);
    $errors = array();
    foreach (array('refundPayment', 'capturePayment', 'voidPayment', 'reauthorizePayment', 'createTracker', 'cancelTracker') as $method) {
        $body = $methods[strtolower($method)] ?? array();
        $prefix = paypalGuardTokenValues(array_slice($body, 0, 14));
        $guard = array('if', '(', '!', '$this', '->', 'validatefinancialactionrequest', '(', ')', ')');
        if (array_slice($prefix, 0, 9) !== $guard ||
            !(array_slice($prefix, 9, 4) === array('{', 'return', ';', '}') || array_slice($prefix, 9, 2) === array('return', ';'))) {
            $errors[] = $method . ': missing initial guard with early return';
        }
    }
    $validator = $methods['validatefinancialactionrequest'] ?? array();
    foreach (array(
        array('$this', '->', 'request', '->', 'server', '[', array(T_CONSTANT_ENCAPSED_STRING, 'REQUEST_METHOD'), ']'),
        array('$this', '->', 'user', '->', 'haspermission', '(', array(T_CONSTANT_ENCAPSED_STRING, 'modify'), ',', array(T_CONSTANT_ENCAPSED_STRING, 'extension/payment/paypal'), ')'),
        array('hash_equals', '(')
    ) as $sequence) {
        if (!paypalTokenSequence($validator, $sequence)) { $errors[] = 'validator missing executable token sequence: ' . json_encode($sequence); }
    }
    return $errors;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $path = $argv[1] ?? dirname(__DIR__) . '/upload/admin/controller/extension/payment/paypal.php';
    $source = is_file($path) ? file_get_contents($path) : false;
    if ($source === false) { fwrite(STDERR, "PayPal controller source is missing.\n"); exit(1); }
    $errors = paypalFinancialGuardErrors($source);
    if ($errors) { fwrite(STDERR, implode("\n", $errors) . "\n"); exit(1); }
    echo "PASS: PayPal source contract: six actions start with a guard and early return; validator contains request, permission and token checks.\n";
    echo "This static contract does not establish runtime authorization or provider behavior.\n";
}
