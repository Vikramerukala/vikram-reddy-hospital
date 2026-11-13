<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (current_patient()) redirect('patient_portal.php');
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare("SELECT u.id,u.name,u.email,u.password_hash,u.patient_id,u.is_active,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? AND r.name='patient' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && (int)$user['is_active'] === 1 && !empty($user['patient_id']) && password_verify($password, $user['password_hash'])) {
        // A browser session may be shared on reception computers; never keep a staff identity active with a patient login.
        unset($_SESSION['user']);
        session_regenerate_id(true);
        $_SESSION['patient'] = ['user_id'=>(int)$user['id'],'patient_id'=>(int)$user['patient_id'],'name'=>$user['name'],'email'=>$user['email']];
        $pdo->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([(int)$user['id']]);
        audit($pdo, 'PATIENT_LOGIN', 'patient_portal', (int)$user['patient_id'], 'Patient signed in');
        redirect('patient_portal.php');
    }
    $error = 'Email or password is incorrect, or this is not a patient account.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Patient Login · <?= e(APP_NAME) ?></title><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="<?= e(url('assets/css/public.css')) ?>" rel="stylesheet"></head>
<body class="auth-page"><div class="auth-shell"><aside class="auth-side"><a class="brand-lockup" href="<?= e(url()) ?>"><span class="brand-symbol"><i class="fa-solid fa-heart-pulse"></i></span><span><strong>Vikram Reddy</strong><small>HOSPITAL</small></span></a><div class="auth-side-copy"><span class="eyebrow">YOUR PERSONAL HEALTH SPACE</span><h1>Welcome<br><span>back.</span></h1><p>Sign in securely to view your hospital information and stay connected with your care journey.</p><div class="auth-side-points"><span><i class="fa-solid fa-calendar-check"></i> Appointment overview</span><span><i class="fa-solid fa-file-prescription"></i> Prescription information</span><span><i class="fa-solid fa-receipt"></i> Billing summary</span></div></div><small class="auth-side-foot">Your health. Our priority.</small></aside><main class="auth-main"><div class="auth-card"><a class="back-link" href="<?= e(url()) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Back to home</a><div class="auth-symbol"><i class="fa-solid fa-user-heart"></i></div><span class="section-kicker">PATIENT PORTAL</span><h2>Patient Login</h2><p class="auth-subtitle">Use the email and password you created during registration.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php foreach (consume_flashes() as $m): ?><div class="alert alert-<?= e($m['type']) ?>"><?= e($m['message']) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" value="<?= e($email) ?>" placeholder="you@example.com" autocomplete="username" required></div><div class="mb-2"><label class="form-label">Password</label><input class="form-control" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required></div><div class="text-end mb-4"><a class="small-link" href="<?= e(url('forgot_password.php')) ?>">Forgot password?</a></div><button class="btn btn-primary btn-lg w-100" type="submit"><i class="fa-solid fa-right-to-bracket me-2"></i>Sign in to Patient Portal</button></form>
<div class="auth-switch">New to Vikram Reddy Hospital? <a href="<?= e(url('patient_register.php')) ?>">Create an account</a></div><div class="staff-switch"><i class="fa-solid fa-lock me-1"></i> Hospital staff? <a href="<?= e(url('login.php')) ?>">Admin / Staff Login</a></div></div></main></div></body></html>
