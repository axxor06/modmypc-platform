<?php
require_once __DIR__ . '/mailer.php';

/**
 * Email-based OTP verification (registration email verification).
 * Deliberately email-only — no SMS or WhatsApp OTP, per requirements.
 */

function generate_and_send_otp($conn, $user_id, $email, $name) {
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    $stmt = $conn->prepare("INSERT INTO email_verifications (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iss', $user_id, $otp_hash, $expires);
    $stmt->execute();
    $stmt->close();

    $subject = 'Your ModMyPC verification code';
    $body = "Hi {$name},\n\nYour ModMyPC verification code is: {$otp}\n\nThis code expires in 10 minutes. If you didn't request this, you can ignore this email.";
    send_email($email, $subject, $body);

    return true;
}

/**
 * Verify an OTP for a given user. Returns true/false. Marks the code used
 * on success so it can't be replayed, and marks the account verified.
 */
function verify_otp($conn, $user_id, $code) {
    $stmt = $conn->prepare(
        "SELECT id, token_hash FROM email_verifications
         WHERE user_id = ? AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($code, $row['token_hash'])) {
        return false;
    }

    $u = $conn->prepare("UPDATE email_verifications SET used = 1 WHERE id = ?");
    $u->bind_param('i', $row['id']);
    $u->execute();
    $u->close();

    $v = $conn->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
    $v->bind_param('i', $user_id);
    $v->execute();
    $v->close();

    return true;
}
