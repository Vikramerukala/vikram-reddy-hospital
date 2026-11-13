<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
if (current_patient()) redirect('patient_portal.php');
if (is_logged_in()) redirect('index.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Vikram Reddy Hospital — compassionate multi-specialty healthcare and convenient patient services.">
  <title>Vikram Reddy Hospital | Care that connects</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/public.css')) ?>" rel="stylesheet">
</head>
<body class="public-home">
<header class="site-header">
  <nav class="navbar navbar-expand-lg container py-3">
    <a class="navbar-brand brand-lockup" href="<?= e(url()) ?>"><span class="brand-symbol"><i class="fa-solid fa-heart-pulse"></i></span><span><strong>Vikram Reddy</strong><small>HOSPITAL</small></span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
        <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#services">Our services</a></li>
        <li class="nav-item"><a class="nav-link" href="#why-us">Why choose us</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
        <li class="nav-item"><a class="btn btn-outline-primary btn-sm px-3" href="<?= e(url('login.php')) ?>">Admin / Staff Login</a></li>
      </ul>
    </div>
  </nav>
</header>
<main>
<section class="hero-section" id="home">
  <div class="hero-orb orb-one"></div><div class="hero-orb orb-two"></div>
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow"><i class="fa-solid fa-heart-circle-check me-2"></i>CARE · COMPASSION · COMMITMENT</span>
      <h1>Your health deserves<br><span>expert care.</span></h1>
      <p class="hero-lead">Personal attention, trusted specialists and connected care — all in one place. Welcome to a healthier tomorrow at Vikram Reddy Hospital.</p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="<?= e(url('patient_register.php')) ?>"><i class="fa-solid fa-user-plus me-2"></i>Patient Registration</a>
        <a class="btn btn-light btn-lg patient-login-cta" href="<?= e(url('patient_login.php')) ?>"><i class="fa-solid fa-right-to-bracket me-2"></i>Patient Login</a>
      </div>
      <div class="hero-trust"><span><i class="fa-solid fa-shield-heart"></i> Secure patient portal</span><span><i class="fa-solid fa-calendar-check"></i> Easy appointment access</span></div>
    </div>
    <div class="hero-art" aria-label="Healthcare illustration">
      <div class="art-glow"></div>
      <div class="art-card art-card-top"><span class="art-icon"><i class="fa-solid fa-heart-pulse"></i></span><span><b>Connected care</b><small>Your care, in one place</small></span><i class="fa-solid fa-circle-check ms-auto check-icon"></i></div>
      <div class="doctor-illustration"><div class="doctor-halo"></div><div class="doctor-head"><div class="doctor-hair"></div><div class="doctor-face"><i class="eye eye-left"></i><i class="eye eye-right"></i><i class="smile"></i></div></div><div class="doctor-neck"></div><div class="doctor-coat"><div class="coat-shirt"></div><div class="coat-pocket"><i class="fa-solid fa-plus"></i></div><div class="stethoscope"></div></div></div>
      <div class="art-card art-card-bottom"><span class="mini-icon"><i class="fa-solid fa-calendar-check"></i></span><span><b>Patient first</b><small>Simple and convenient</small></span></div>
      <div class="floating-plus plus-one">+</div><div class="floating-plus plus-two">+</div>
    </div>
  </div>
  <div class="hero-wave"></div>
</section>
<section class="portal-strip container" aria-label="Portal options">
  <div class="portal-intro"><span class="section-kicker">YOUR HOSPITAL, ONLINE</span><h2>How can we help you today?</h2><p>Choose the secure portal that matches your needs.</p></div>
  <div class="portal-card register-card"><div class="portal-icon"><i class="fa-solid fa-user-plus"></i></div><div><h3>New Patient?</h3><p>Create your patient account to get started.</p><a href="<?= e(url('patient_register.php')) ?>">Register now <i class="fa-solid fa-arrow-right ms-1"></i></a></div></div>
  <div class="portal-card patient-card"><div class="portal-icon"><i class="fa-solid fa-user-heart"></i></div><div><h3>Already registered?</h3><p>Sign in to view your patient portal.</p><a href="<?= e(url('patient_login.php')) ?>">Patient login <i class="fa-solid fa-arrow-right ms-1"></i></a></div></div>
  <div class="portal-card staff-card"><div class="portal-icon"><i class="fa-solid fa-user-shield"></i></div><div><h3>Hospital team</h3><p>Secure access for doctors and staff.</p><a href="<?= e(url('login.php')) ?>">Admin / Staff login <i class="fa-solid fa-arrow-right ms-1"></i></a></div></div>
</section>
<section class="services-section" id="services"><div class="container"><div class="section-heading text-center"><span class="section-kicker">COMPREHENSIVE HEALTHCARE</span><h2>Care for every stage of life</h2><p>Multi-specialty services delivered by a dedicated team.</p></div>
  <div class="row g-4 service-grid">
    <div class="col-6 col-lg-3"><article class="service-card"><span class="service-icon blue"><i class="fa-solid fa-heart-pulse"></i></span><h3>Cardiology</h3><p>Heart health and cardiovascular care.</p></article></div>
    <div class="col-6 col-lg-3"><article class="service-card"><span class="service-icon teal"><i class="fa-solid fa-brain"></i></span><h3>Neurology</h3><p>Care for brain and nervous system.</p></article></div>
    <div class="col-6 col-lg-3"><article class="service-card"><span class="service-icon violet"><i class="fa-solid fa-bone"></i></span><h3>Orthopedics</h3><p>Bone, joint and mobility services.</p></article></div>
    <div class="col-6 col-lg-3"><article class="service-card"><span class="service-icon orange"><i class="fa-solid fa-baby"></i></span><h3>Pediatrics</h3><p>Thoughtful healthcare for children.</p></article></div>
  </div>
  <div class="text-center mt-4"><a href="<?= e(url('patient_register.php')) ?>" class="btn btn-outline-primary px-4">Get started as a patient</a></div>
</div></section>
<section class="why-section" id="why-us"><div class="container why-grid"><div><span class="section-kicker">WHY VIKRAM REDDY HOSPITAL</span><h2>Healthcare made more personal.</h2><p class="why-copy">We bring essential hospital services and patient information together, helping you stay informed and connected with your care team.</p><div class="why-point"><i class="fa-solid fa-user-shield"></i><div><b>Private patient access</b><small>Your portal is protected by your own login.</small></div></div><div class="why-point"><i class="fa-solid fa-calendar-check"></i><div><b>Convenient care journey</b><small>Access appointment and hospital information online.</small></div></div><div class="why-point"><i class="fa-solid fa-people-group"></i><div><b>Dedicated hospital team</b><small>Care supported by multiple specialties.</small></div></div></div><div class="why-visual"><div class="why-visual-inner"><span class="big-cross"><i class="fa-solid fa-plus"></i></span><span class="pulse-ring ring-a"></span><span class="pulse-ring ring-b"></span><div class="why-caption"><b>Care that connects</b><small>People · Expertise · Compassion</small></div></div></div></div></section>
<section class="contact-section" id="contact"><div class="container contact-panel"><div><span class="section-kicker">WE ARE HERE FOR YOU</span><h2>Need help getting started?</h2><p>For emergencies, please contact emergency services or visit the nearest emergency department. For portal access, contact the hospital reception team.</p></div><a href="<?= e(url('patient_register.php')) ?>" class="btn btn-primary btn-lg">Create patient account <i class="fa-solid fa-arrow-right ms-2"></i></a></div></section>
</main>
<footer class="site-footer"><div class="container d-flex flex-column flex-md-row justify-content-between gap-2"><span>© <?= date('Y') ?> Vikram Reddy Hospital. All rights reserved.</span><span><a href="<?= e(url('login.php')) ?>">Admin / Staff</a> · <a href="<?= e(url('patient_login.php')) ?>">Patient portal</a></span></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
