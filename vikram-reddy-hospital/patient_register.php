<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (current_patient()) redirect('patient_portal.php');
if (is_logged_in()) redirect('index.php');
$error = '';
$old = ['name'=>'','email'=>'','phone'=>'','dob'=>'','gender'=>'','address'=>'','emergency_contact'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($old as $key => $_) $old[$key] = trim((string)($_POST[$key] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    try {
        if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 120) throw new RuntimeException('Enter your full name (2 to 120 characters).');
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
        if (!preg_match('/^[0-9+() .-]{7,20}$/', $old['phone'])) throw new RuntimeException('Enter a valid contact number.');
        if (!in_array($old['gender'], ['Male','Female','Other'], true)) throw new RuntimeException('Please select your gender.');
        if ($old['dob'] !== '') {
            $birth = DateTime::createFromFormat('Y-m-d', $old['dob']);
            if (!$birth || $birth->format('Y-m-d') !== $old['dob'] || $birth > new DateTime('today')) throw new RuntimeException('Enter a valid date of birth.');
        }
        if (strlen($password) < 10) throw new RuntimeException('Password must contain at least 10 characters.');
        if ($password !== $confirm) throw new RuntimeException('Password and confirmation do not match.');
        if (empty($_POST['consent'])) throw new RuntimeException('Please confirm that the information is correct and accept the privacy notice.');

        $roleId = (int)$pdo->query("SELECT id FROM roles WHERE name='patient' LIMIT 1")->fetchColumn();
        if ($roleId < 1) throw new RuntimeException('Patient portal database update is missing. Import database/patient_portal_upgrade.sql first.');
        $existingUser = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $existingUser->execute([$old['email']]);
        if ($existingUser->fetch()) throw new RuntimeException('This email already has a hospital account. Please use Patient Login or Forgot Password.');

        $pdo->beginTransaction();
        // Do not let a public sign-up claim an existing medical record using only email/phone.
        $findPatient = $pdo->prepare('SELECT id FROM patients WHERE email=? LIMIT 1 FOR UPDATE');
        $findPatient->execute([$old['email']]);
        if ($findPatient->fetch()) {
            throw new RuntimeException('A hospital patient record already uses this email. Please contact reception to verify your identity and activate portal access.');
        }
        $dob = $old['dob'] !== '' ? $old['dob'] : null;
        $age = $dob ? (int)(new DateTime($dob))->diff(new DateTime('today'))->y : null;
        $code = 'PAT' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        $insertPatient = $pdo->prepare('INSERT INTO patients (patient_code,name,age,gender,dob,blood_group,phone,email,address,emergency_contact,registration_date) VALUES (?,?,?,?,?,?,?,?,?,?,CURDATE())');
        $insertPatient->execute([$code,$old['name'],$age,$old['gender'],$dob,'Unknown',$old['phone'],$old['email'],$old['address'] ?: null,$old['emergency_contact'] ?: null]);
        $patientId = (int)$pdo->lastInsertId();
        $insertUser = $pdo->prepare('INSERT INTO users (name,email,password_hash,role_id,patient_id,is_active) VALUES (?,?,?,?,?,1)');
        $insertUser->execute([$old['name'],$old['email'],password_hash($password, PASSWORD_DEFAULT),$roleId,$patientId]);
        $userId = (int)$pdo->lastInsertId();
        $pdo->commit();
        session_regenerate_id(true);
        $_SESSION['patient'] = ['user_id'=>$userId,'patient_id'=>$patientId,'name'=>$old['name'],'email'=>$old['email']];
        flash('success', 'Your patient account has been created successfully. Welcome to Vikram Reddy Hospital.');
        redirect('patient_portal.php');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $exception instanceof PDOException && $exception->getCode() === '23000' ? 'This email or patient account is already registered. Please sign in or contact reception.' : $exception->getMessage();
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Patient Registration · <?= e(APP_NAME) ?></title><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link href="<?= e(url('assets/css/public.css')) ?>" rel="stylesheet"></head>
<body class="auth-page"><div class="auth-shell"><aside class="auth-side"><a class="brand-lockup" href="<?= e(url()) ?>"><span class="brand-symbol"><i class="fa-solid fa-heart-pulse"></i></span><span><strong>Vikram Reddy</strong><small>HOSPITAL</small></span></a><div class="auth-side-copy"><span class="eyebrow">WELCOME TO BETTER CARE</span><h1>Your care journey<br><span>starts here.</span></h1><p>Create your secure patient account to stay connected with your hospital care.</p><div class="auth-side-points"><span><i class="fa-solid fa-shield-heart"></i> Private patient access</span><span><i class="fa-solid fa-calendar-check"></i> View your appointments</span><span><i class="fa-solid fa-file-medical"></i> Access your care information</span></div></div><small class="auth-side-foot">Care · Compassion · Commitment</small></aside><main class="auth-main"><div class="auth-card auth-card-wide"><a class="back-link" href="<?= e(url()) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Back to home</a><span class="section-kicker">PATIENT ACCOUNT</span><h2>Create your account</h2><p class="auth-subtitle">Register once, then use Patient Login for future visits.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="row g-3" autocomplete="on"><?= csrf_field() ?>
<div class="col-md-6"><label class="form-label">Full name *</label><input class="form-control" name="name" maxlength="120" value="<?= e($old['name']) ?>" placeholder="Enter your full name" required></div>
<div class="col-md-6"><label class="form-label">Email address *</label><input class="form-control" type="email" name="email" maxlength="160" value="<?= e($old['email']) ?>" placeholder="you@example.com" required></div>
<div class="col-md-6"><label class="form-label">Mobile number *</label><input class="form-control" name="phone" maxlength="20" value="<?= e($old['phone']) ?>" placeholder="Your contact number" required></div>
<div class="col-md-3"><label class="form-label">Date of birth</label><input class="form-control" type="date" name="dob" max="<?= date('Y-m-d') ?>" value="<?= e($old['dob']) ?>"></div>
<div class="col-md-3"><label class="form-label">Gender *</label><select class="form-select" name="gender" required><option value="">Select</option><?php foreach (['Male','Female','Other'] as $g): ?><option value="<?= e($g) ?>" <?= $old['gender']===$g?'selected':'' ?>><?= e($g) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2" placeholder="House number, area, city"><?= e($old['address']) ?></textarea></div>
<div class="col-md-6"><label class="form-label">Emergency contact</label><input class="form-control" name="emergency_contact" maxlength="40" value="<?= e($old['emergency_contact']) ?>" placeholder="Emergency contact number"></div>
<div class="col-md-6"><label class="form-label">Create password * (minimum 10 characters)</label><input class="form-control" type="password" name="password" minlength="10" autocomplete="new-password" required></div>
<div class="col-12"><label class="form-label">Confirm password *</label><input class="form-control" type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></div>
<div class="col-12"><label class="consent-check"><input type="checkbox" name="consent" value="1" required><span>I confirm that the details provided are correct and understand that my information will be used to manage my hospital care.</span></label></div>
<div class="col-12"><button class="btn btn-primary btn-lg w-100" type="submit"><i class="fa-solid fa-user-plus me-2"></i>Create Patient Account</button></div></form>
<div class="auth-switch">Already have an account? <a href="<?= e(url('patient_login.php')) ?>">Patient Login</a></div></div></main></div></body></html>
