<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (current_patient()) redirect('patient_portal.php');
if (is_logged_in()) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT u.id,u.name,u.email,u.password_hash,u.is_active,r.name AS role_name,r.display_name AS role_display FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && ($user['role_name'] ?? '') === 'patient') {
        $error = 'This is a patient account. Please use the separate Patient Login page.';
    } elseif ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        // Keep staff and patient authentication contexts separate on shared devices.
        unset($_SESSION['patient']);
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        $pdo->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([$user['id']]);
        audit($pdo, 'LOGIN', 'auth', (int)$user['id'], 'User signed in');
        redirect('index.php');
    }
    $error = 'Email or password is incorrect, or the account is disabled.';
}
$flashes = consume_flashes();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · <?= e(APP_NAME) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet"></head>
<body class="login-page"><div class="login-wrap"><section class="login-visual"><div class="login-visual-overlay"></div><div class="login-brand"><span class="brand-mark"><i class="fa-solid fa-heart-pulse"></i></span><span><strong>Vikram Reddy</strong><small>HOSPITAL</small></span></div><div class="login-message"><span class="eyebrow">CARE · COMPASSION · COMMITMENT</span><h1>Better care.<br><em>Connected.</em></h1><p>A smarter workspace for your hospital team, patients and everyday operations.</p><div class="login-stat-row"><div><strong>24/7</strong><small>Care support</small></div><div><strong>15+</strong><small>Specialties</small></div><div><strong>One</strong><small>Connected system</small></div></div></div><div class="visual-footer">Your health. Our priority.</div></section><section class="login-panel"><div class="login-card"><div class="login-icon"><i class="fa-solid fa-shield-heart"></i></div><span class="eyebrow text-primary">WELCOME BACK</span><h2>Admin / Staff Login</h2><p class="text-muted mb-4">Secure sign-in for hospital administrators, doctors and authorized staff.</p>
<?php foreach ($flashes as $message): ?><div class="alert alert-<?= e($message['type']) ?>"><?= e($message['message']) ?></div><?php endforeach; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($error) ?></div><?php endif; ?>
<form method="post" novalidate><?= csrf_field() ?><div class="mb-3"><label class="form-label">Email address</label><div class="input-icon"><i class="fa-regular fa-envelope"></i><input class="form-control" type="email" name="email" placeholder="name@hospital.com" autocomplete="username" required></div></div><div class="mb-4"><label class="form-label">Password</label><div class="input-icon"><i class="fa-solid fa-lock"></i><input class="form-control" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required></div></div><button class="btn btn-primary w-100 py-3" type="submit">Sign in <i class="fa-solid fa-arrow-right ms-2"></i></button></form><div class="demo-login"><strong>Demo administrator login</strong><div>admin@vikramreddyhospital.in</div><div>Password: <code>Admin@12345</code></div><small>Change this password before using real patient data.</small></div><div class="text-center small mt-4"><a href="<?= e(url()) ?>">Back to home</a> · Patient? <a href="<?= e(url('patient_login.php')) ?>">Patient Login</a></div><div class="text-center small text-muted mt-2">Protected hospital staff access · <?= date('Y') ?></div></div></section></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
