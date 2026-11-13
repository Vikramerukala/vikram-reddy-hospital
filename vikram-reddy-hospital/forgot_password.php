<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        // Always show a generic response to avoid revealing whether an account exists.
        $stmt = $pdo->prepare("SELECT u.id,u.name,u.email FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? AND r.name='patient' AND u.is_active=1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + 1800);
            $pdo->prepare('UPDATE patient_password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([(int)$user['id']]);
            $pdo->prepare('INSERT INTO patient_password_resets (user_id,token_hash,expires_at) VALUES (?,?,?)')->execute([(int)$user['id'],$tokenHash,$expires]);
            $resetUrl = url('reset_password.php?token=' . urlencode($token));
            $subject = 'Reset your Vikram Reddy Hospital patient portal password';
            $body = "Hello {$user['name']},\n\nA password reset was requested for your patient portal. Use this one-time link within 30 minutes:\n{$resetUrl}\n\nIf you did not request this, you can ignore this email.\n\nVikram Reddy Hospital";
            $headers = "From: Vikram Reddy Hospital <no-reply@localhost>\r\nContent-Type: text/plain; charset=UTF-8";
            $sent = @mail($user['email'], $subject, $body, $headers);
            if (!$sent) {
                // Do not expose a reset token on-screen. A local XAMPP setup needs mail configured.
                $pdo->prepare('UPDATE patient_password_resets SET used_at=NOW() WHERE user_id=? AND token_hash=?')->execute([(int)$user['id'],$tokenHash]);
                $_SESSION['reset_mail_not_configured'] = true;
            }
        }
        $message = 'If an active patient account matches that email, password reset instructions will be sent. Check your inbox and spam folder. If no email arrives, contact hospital reception to verify your identity and help reset your access.';
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Forgot Password · <?= e(APP_NAME) ?></title><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="<?= e(url('assets/css/public.css')) ?>" rel="stylesheet"></head><body class="auth-page"><div class="auth-shell"><aside class="auth-side"><a class="brand-lockup" href="<?= e(url()) ?>"><span class="brand-symbol"><i class="fa-solid fa-heart-pulse"></i></span><span><strong>Vikram Reddy</strong><small>HOSPITAL</small></span></a><div class="auth-side-copy"><span class="eyebrow">ACCOUNT RECOVERY</span><h1>A secure way<br><span>back in.</span></h1><p>We will send a one-time password reset link to the email registered with your patient account.</p></div><small class="auth-side-foot">Your health. Our priority.</small></aside><main class="auth-main"><div class="auth-card"><a class="back-link" href="<?= e(url('patient_login.php')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Back to Patient Login</a><div class="auth-symbol"><i class="fa-solid fa-key"></i></div><span class="section-kicker">PATIENT PORTAL</span><h2>Forgot password?</h2><p class="auth-subtitle">Enter the email address associated with your patient account.</p><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if ($message): ?><div class="alert alert-info"><?= e($message) ?></div><?php endif; ?><?php if (!empty($_SESSION['reset_mail_not_configured'])): unset($_SESSION['reset_mail_not_configured']); ?><div class="alert alert-warning">Email delivery is not configured on this XAMPP computer yet. Configure PHP mail/SMTP before using self-service password reset, or contact reception.</div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="mb-4"><label class="form-label">Registered email address</label><input class="form-control" type="email" name="email" placeholder="you@example.com" required></div><button class="btn btn-primary btn-lg w-100" type="submit">Send reset link <i class="fa-solid fa-paper-plane ms-2"></i></button></form><div class="staff-switch mt-4">Need a new account? <a href="<?= e(url('patient_register.php')) ?>">Patient Registration</a></div></div></main></div></body></html>
