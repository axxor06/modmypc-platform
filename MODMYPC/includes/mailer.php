<?php
/**
 * Minimal SMTP client for transactional email (OTP codes, password resets).
 * No external dependency - shared hosting can't always install Composer
 * packages, so this talks raw SMTP over a socket instead.
 *
 * Falls back to PHP's native mail() automatically if SMTP_HOST isn't set
 * in .env, so the site still works even before you configure this.
 */

/**
 * Send an email. Returns true/false. Never throws - a failed send should
 * never break the page that triggered it (registration, password reset).
 */
function send_email($to, $subject, $body) {
    $host = env('SMTP_HOST');
    $user = env('SMTP_USERNAME');
    $pass = env('SMTP_PASSWORD');

    if (empty($host) || empty($user) || empty($pass)) {
        // Not configured - fall back to native mail() (best-effort).
        $headers = 'From: ' . (env('SMTP_FROM_NAME', 'ModMyPC')) . ' <no-reply@modmypc.com>';
        return @mail($to, $subject, $body, $headers);
    }

    try {
        return smtp_send($host, (int)env('SMTP_PORT', '587'), $user, $pass, $to, $subject, $body);
    } catch (Throwable $e) {
        error_log('SMTP send failed: ' . $e->getMessage());
        // Fall back to native mail() as a last resort.
        $headers = 'From: ' . (env('SMTP_FROM_NAME', 'ModMyPC')) . ' <no-reply@modmypc.com>';
        return @mail($to, $subject, $body, $headers);
    }
}

function smtp_send($host, $port, $username, $password, $to, $subject, $body) {
    $from_email = env('SMTP_FROM_EMAIL') ?: $username;
    $from_name = env('SMTP_FROM_NAME', 'ModMyPC');

    $timeout = 12;
    $transport = ($port == 465) ? 'ssl://' : '';
    $sock = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, $timeout);
    if (!$sock) {
        error_log("SMTP connect failed: $errstr ($errno)");
        return false;
    }
    stream_set_timeout($sock, $timeout);

    $expect = function ($sock, $code) {
        $resp = '';
        while ($line = fgets($sock, 512)) {
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') break; // last line of a multi-line response
        }
        if (substr($resp, 0, 3) !== (string)$code) {
            throw new Exception("SMTP unexpected response (expected $code): $resp");
        }
        return $resp;
    };

    $send = function ($sock, $cmd) { fwrite($sock, $cmd . "\r\n"); };

    $expect($sock, 220);
    $send($sock, 'EHLO modmypc.com');
    $expect($sock, 250);

    if ($port == 587) {
        $send($sock, 'STARTTLS');
        $expect($sock, 220);
        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception('STARTTLS negotiation failed');
        }
        $send($sock, 'EHLO modmypc.com');
        $expect($sock, 250);
    }

    $send($sock, 'AUTH LOGIN');
    $expect($sock, 334);
    $send($sock, base64_encode($username));
    $expect($sock, 334);
    $send($sock, base64_encode($password));
    $expect($sock, 235);

    $send($sock, "MAIL FROM:<$from_email>");
    $expect($sock, 250);
    $send($sock, "RCPT TO:<$to>");
    $expect($sock, 250);
    $send($sock, 'DATA');
    $expect($sock, 354);

    $headers = "From: {$from_name} <{$from_email}>\r\n" .
               "To: <{$to}>\r\n" .
               "Subject: {$subject}\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";

    // Dot-stuff any line that starts with a period, per SMTP spec.
    $escaped_body = preg_replace('/^\./m', '..', $body);

    $send($sock, $headers . "\r\n" . $escaped_body . "\r\n.");
    $expect($sock, 250);
    $send($sock, 'QUIT');
    fclose($sock);

    return true;
}
