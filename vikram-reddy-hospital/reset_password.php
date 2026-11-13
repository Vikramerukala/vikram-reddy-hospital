<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenValid = false;
$userId = 0;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = $pdo->prepare('SELECT user_id FROM patient_password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $userId = (int)$stmt->fetchColumn();
    $tokenValid = $userId > 0;
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (!$tokenValid) $error = 'This password reset link is invalid, expired or already used. Request a new link.';
    elseif (strlen($password) < 10) $error = 'Password must contain at least 10 characters.';
    elseif ($password !== $confirm) $error = 'Password and confirmation do not match.';
    else {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$userId]);
        $pdo->prepare('UPDATE patient_password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$userId]);
        $pdo->commit();
        $success = 'Your password has been changed. You can now sign in to the Patient Portal.';
        $tokenValid = false;
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="referrer" content="no-referrer"><title>Reset Password · <?= e(APP_NAME) ?></title><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="<?= e(url('assets/css/public.css')) ?>" rel="stylesheet"></head><body class="auth-page"><div class="auth-shell"><aside class="auth-side"><a class="brand-lockup" href="<?= e(url()) ?>"><span class="brand-symbol"><i class="fa-solid fa-heart-pulse"></i></span><span><strong>Vikram Reddy</strong><small>HOSPITAL</small></span></a><div class="auth-side-copy"><span class="eyebrow">ACCOUNT SECURITY</span><h1>Choose a new<br><span>password.</span></h1><p>Use a strong password that you do not use on another website.</p></div><small class="auth-side-foot">Care · Compassion · Commitment</small></aside><main class="auth-main"><div class="auth-card"><a class="back-link" href="<?= e(url('patient_login.php')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Patient Login</a><div class="auth-symbol"><i class="fa-solid fa-lock"></i></div><span class="section-kicker">PATIENT PORTAL</span><h2>Reset password</h2><p class="auth-subtitle">Create a new password for your patient account.</p><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><a class="btn btn-primary btn-lg w-100" href="<?= e(url('patient_login.php')) ?>">Go to Patient Login</a><?php elseif ($tokenValid): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><div class="mb-3"><label class="form-label">New password</label><input class="form-control" type="password" name="password" minlength="10" autocomplete="new-password" required></div><div class="mb-4"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></div><button class="btn btn-primary btn-lg w-100" type="submit">Save new password</button></form><?php else: ?><div class="alert alert-warning">This password reset link is invalid, expired or already used. Please request a new reset link.</div><a class="btn btn-primary btn-lg w-100" href="<?= e(url('forgot_password.php')) ?>">Request a new link</a><?php endif; ?></div></main></div></body></html>
