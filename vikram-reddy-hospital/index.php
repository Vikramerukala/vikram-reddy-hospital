<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// Public visitors see the hospital welcome page. Patient and staff accounts use separate portals.
if (!empty($_SESSION['patient'])) {
    redirect('patient_portal.php');
}
if (!is_logged_in()) {
    require __DIR__ . '/home.php';
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/modules.php';
require_login();
$page = (string)($_GET['page'] ?? 'dashboard');
if ($page === 'profile') { require __DIR__ . '/pages/profile.php'; exit; }
if ($page === 'print') { require __DIR__ . '/pages/print.php'; exit; }
if ($page !== 'dashboard' && $page !== 'reports' && !isset($modules[$page])) $page = 'dashboard';
require_access($page);
require __DIR__ . '/includes/header.php';
if ($page === 'dashboard') require __DIR__ . '/pages/dashboard.php';
elseif ($page === 'reports') require __DIR__ . '/pages/reports.php';
else { $moduleKey = $page; require __DIR__ . '/pages/module.php'; }
require __DIR__ . '/includes/footer.php';
