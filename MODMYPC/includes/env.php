<?php
/**
 * Minimal .env loader. No external dependency (composer/vlucas is not
 * guaranteed to be installable on shared hosting), so this just reads
 * KEY=VALUE lines and exposes them via env().
 */
function load_env($path) {
    static $loaded = false;
    static $values = [];
    if ($loaded) return $values;
    $loaded = true;

    if (is_file($path)) {
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\"'");
        }
    }
    return $values;
}

function env($key, $default = '') {
    static $values = null;
    if ($values === null) $values = load_env(__DIR__ . '/../.env');
    return $values[$key] ?? $default;
}
