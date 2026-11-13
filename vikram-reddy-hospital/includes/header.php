<?php
$user = current_user();
$activePage = $_GET['page'] ?? 'dashboard';
$flashMessages = consume_flashes();
$navGroups = [
    'Overview' => ['dashboard'],
    'People & Care' => ['patients','doctors','departments','appointments','reception','admissions','rooms','beds','prescriptions','prescription_items','medical_certificates'],
    'Hospital Operations' => ['staff','attendance','doctor_schedules','laboratory_tests','lab_orders','lab_results','medicines','suppliers','pharmacy_sales','ambulance','blood_bank','equipment'],
    'Finance' => ['bills','payments'],
    'Administration' => ['users','announcements','holidays','feedback','notifications','reports'],
];
$navLabels = ['dashboard'=>'Dashboard','patients'=>'Patients','doctors'=>'Doctors','departments'=>'Departments','appointments'=>'Appointments','reception'=>'Reception & Queue','admissions'=>'Admissions','rooms'=>'Rooms / Wards','beds'=>'Beds','prescriptions'=>'Prescriptions','prescription_items'=>'Prescription Items','medical_certificates'=>'Medical Certificates','staff'=>'Staff','attendance'=>'Attendance','doctor_schedules'=>'Doctor Schedules','laboratory_tests'=>'Lab Tests','lab_orders'=>'Lab Orders','lab_results'=>'Lab Results','medicines'=>'Pharmacy & Stock','suppliers'=>'Suppliers','pharmacy_sales'=>'Pharmacy Sales','ambulance'=>'Ambulance','blood_bank'=>'Blood Bank','equipment'=>'Equipment','bills'=>'Billing','payments'=>'Payments','users'=>'User Accounts','announcements'=>'Announcements','holidays'=>'Holidays','feedback'=>'Feedback','notifications'=>'Notifications','reports'=>'Reports & Analytics'];
$navIcons = ['dashboard'=>'fa-chart-pie','patients'=>'fa-hospital-user','doctors'=>'fa-user-doctor','departments'=>'fa-hospital','appointments'=>'fa-calendar-check','reception'=>'fa-clipboard-list','admissions'=>'fa-bed-pulse','rooms'=>'fa-bed','beds'=>'fa-bed','prescriptions'=>'fa-file-prescription','prescription_items'=>'fa-capsules','medical_certificates'=>'fa-file-circle-check','staff'=>'fa-users','attendance'=>'fa-user-clock','doctor_schedules'=>'fa-calendar-days','laboratory_tests'=>'fa-flask','lab_orders'=>'fa-vials','lab_results'=>'fa-file-medical','medicines'=>'fa-pills','suppliers'=>'fa-truck-field','pharmacy_sales'=>'fa-prescription-bottle-medical','ambulance'=>'fa-truck-medical','blood_bank'=>'fa-droplet','equipment'=>'fa-screwdriver-wrench','bills'=>'fa-file-invoice-dollar','payments'=>'fa-money-bill-transfer','users'=>'fa-user-shield','announcements'=>'fa-bullhorn','holidays'=>'fa-calendar-xmark','feedback'=>'fa-comment-dots','notifications'=>'fa-bell','reports'=>'fa-chart-line'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($navLabels[$activePage] ?? 'Dashboard') ?> · <?= e(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark"><i class="fa-solid fa-heart-pulse"></i></span><span><strong>Vikram Reddy</strong><small>HOSPITAL</small></span></a>
    <div class="sidebar-scroll">
      <?php foreach ($navGroups as $group=>$items): $visible = array_values(array_filter($items, fn($item)=>can_access($item))); if (!$visible) continue; ?>
        <div class="nav-caption"><?= e($group) ?></div>
        <?php foreach ($visible as $item): ?>
          <a class="nav-link-custom <?= $activePage === $item ? 'active' : '' ?>" href="<?= e(url('index.php?page=' . $item)) ?>"><i class="fa-solid <?= e($navIcons[$item] ?? 'fa-circle') ?>"></i><span><?= e($navLabels[$item] ?? ucfirst($item)) ?></span></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
    <div class="sidebar-bottom"><div class="support-card"><i class="fa-solid fa-shield-heart"></i><div><strong>Care with confidence</strong><small>Hospital operations portal</small></div></div><div class="sidebar-version">Vikram Reddy Hospital · v1.0</div></div>
  </aside>
  <main class="main-area">
    <header class="topbar">
      <div class="d-flex align-items-center gap-3"><button class="btn btn-light d-lg-none" id="sidebarToggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button><div><div class="topbar-kicker">HOSPITAL MANAGEMENT SYSTEM</div><div class="topbar-title"><?= e($navLabels[$activePage] ?? 'Dashboard') ?></div></div></div>
      <div class="d-flex align-items-center gap-3"><div class="topbar-date d-none d-md-block"><i class="fa-regular fa-calendar me-1"></i><?= date('D, d M Y') ?></div><div class="dropdown"><button class="profile-button dropdown-toggle" data-bs-toggle="dropdown"><span class="avatar-circle"><?= e(strtoupper(substr($user['name'] ?? 'U',0,1))) ?></span><span class="d-none d-md-block text-start"><strong><?= e($user['name'] ?? 'User') ?></strong><small><?= e($user['role_display'] ?? 'Staff') ?></small></span></button><ul class="dropdown-menu dropdown-menu-end shadow border-0"><li><a class="dropdown-item" href="<?= e(url('index.php?page=profile')) ?>"><i class="fa-regular fa-user me-2"></i>My profile</a></li><li><hr class="dropdown-divider"></li><li><form method="post" action="<?= e(url('logout.php')) ?>" class="m-0"><?= csrf_field() ?><button class="dropdown-item text-danger" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</button></form></li></ul></div></div>
    </header>
    <div class="page-content">
      <?php foreach ($flashMessages as $message): ?><div class="alert alert-<?= e($message['type']) ?> alert-dismissible fade show" role="alert"><?= e($message['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endforeach; ?>
