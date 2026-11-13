<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
    $stmt->execute([(int)current_user()['id']]);
    $hash = (string)$stmt->fetchColumn();
    if (!password_verify($current,$hash)) $message = 'Your current password is incorrect.';
    elseif (strlen($new) < 10) $message = 'New password must be at least 10 characters long.';
    elseif ($new !== $confirm) $message = 'The new passwords do not match.';
    else {
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),(int)current_user()['id']]);
        audit($pdo,'PASSWORD_CHANGE','users',(int)current_user()['id'],'User changed their password');
        flash('success','Your password has been changed.');
        redirect('index.php?page=profile');
    }
}
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow text-primary">ACCOUNT SETTINGS</span><h1>My profile</h1><p>Manage your account details and sign-in security.</p></div></div>
<div class="row g-4"><div class="col-xl-5"><section class="panel-card profile-summary"><span class="profile-large-avatar"><?= e(strtoupper(substr(current_user()['name'],0,1))) ?></span><h3><?= e(current_user()['name']) ?></h3><p><?= e(current_user()['role_display']) ?></p><div class="profile-detail"><i class="fa-regular fa-envelope"></i><?= e(current_user()['email']) ?></div><div class="profile-detail"><i class="fa-solid fa-shield-halved"></i>Role-based access enabled</div><div class="profile-detail"><i class="fa-solid fa-lock"></i>Secure session</div></section></div><div class="col-xl-7"><section class="panel-card"><div class="panel-header"><div><h3>Change password</h3><p>Use a strong password that you do not use elsewhere.</p></div></div><?php if($message): ?><div class="alert alert-danger"><?= e($message) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" required autocomplete="current-password"></div><div class="mb-3"><label class="form-label">New password</label><input class="form-control" type="password" name="new_password" minlength="10" required autocomplete="new-password"><div class="form-text">At least 10 characters.</div></div><div class="mb-4"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="confirm_password" minlength="10" required autocomplete="new-password"></div><button class="btn btn-primary" type="submit"><i class="fa-solid fa-key me-2"></i>Update password</button></form></section></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
