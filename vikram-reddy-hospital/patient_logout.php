<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('patient_portal.php');
verify_csrf();
unset($_SESSION['patient']);
session_regenerate_id(true);
flash('success', 'You have signed out of the patient portal.');
redirect('patient_login.php');
