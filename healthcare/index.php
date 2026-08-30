<?php
// =====================================================
// index.php — Landing Page / Site Home
// =====================================================
// Entry point for the Smart Healthcare System.
// If user is already logged in, redirect to dashboard.
// Otherwise, show a public-facing landing page with
// Login/Register buttons and feature highlights.
// =====================================================

session_start();

// If user is already logged in, skip landing page
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Smart Healthcare & Diagnostic Management System — Get symptom guidance, book appointments, and manage billing in one place.">
    <title>Smart Healthcare & Diagnostic Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Landing Page Navbar (no dashboard links — user is not logged in) -->
<nav class="navbar">
    <div class="navbar-brand">
        <span class="icon">🏥</span> HealthCare+
    </div>
    <ul class="navbar-links" style="gap: 0.75rem;">
        <li><a href="login.php" class="btn-nav-login">Login</a></li>
        <li><a href="register.php" class="btn-nav-register">Register</a></li>
    </ul>
</nav>

<!-- =====================================================
     HERO SECTION — Main call to action
     ===================================================== -->
<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Smart Healthcare, Simplified.</h1>
        <p class="hero-subtitle">
            Get preliminary symptom guidance, book appointments with real doctors, 
            and manage your healthcare — all in one place.
        </p>
        <a href="register.php" class="btn btn-primary hero-cta">
            🚀 Get Started — It's Free
        </a>
        <p style="margin-top: 1rem; font-size: 0.88rem; color: var(--gray-500);">
            Already have an account? <a href="login.php" style="font-weight: 600;">Log in here</a>
        </p>
    </div>
</section>

<!-- =====================================================
     FEATURE HIGHLIGHTS — 3 cards showing core capabilities
     ===================================================== -->
<div class="container" style="padding-bottom: 3rem;">

    <h2 class="text-center" style="font-size: 1.6rem; color: var(--gray-900); margin-bottom: 0.5rem;">
        Everything You Need
    </h2>
    <p class="text-center" style="color: var(--gray-500); margin-bottom: 2rem; font-size: 1rem;">
        A complete healthcare management platform for patients and doctors.
    </p>

    <div class="features-grid">
        <!-- Feature 1: Symptom Checker -->
        <div class="card feature-card">
            <div class="feature-icon">🩺</div>
            <h3>Symptom Checker</h3>
            <p>Get a preliminary assessment based on your symptoms using our weighted scoring engine, with confidence levels and doctor-reviewed advice.</p>
        </div>

        <!-- Feature 2: Easy Appointments -->
        <div class="card feature-card">
            <div class="feature-icon">📅</div>
            <h3>Easy Appointments</h3>
            <p>Book, reschedule, or cancel appointments with your preferred doctor. Real-time availability, severity-based priority, and location details included.</p>
        </div>

        <!-- Feature 3: Transparent Billing -->
        <div class="card feature-card">
            <div class="feature-icon">💰</div>
            <h3>Transparent Billing</h3>
            <p>Auto-calculated bills with consultation fees pulled from doctor records. Insurance discounts (20%) applied instantly — no surprises.</p>
        </div>
    </div>

    <!-- Secondary feature row -->
    <div class="features-grid" style="margin-top: 1.5rem;">
        <!-- Feature 4: Doctor Dashboard -->
        <div class="card feature-card">
            <div class="feature-icon">👨‍⚕️</div>
            <h3>Doctor Dashboard</h3>
            <p>Doctors can manage their schedule, view patient appointments sorted by severity, and update appointment statuses — all from one panel.</p>
        </div>

        <!-- Feature 5: Admin Analytics -->
        <div class="card feature-card">
            <div class="feature-icon">📊</div>
            <h3>Admin Analytics</h3>
            <p>Administrators get real-time charts showing top symptoms, disease distribution, and appointment status breakdowns powered by Chart.js.</p>
        </div>

        <!-- Feature 6: Secure & Private -->
        <div class="card feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Secure & Private</h3>
            <p>Bcrypt password encryption, CSRF-protected forms, prepared SQL statements, and secure Remember Me tokens keep your data safe.</p>
        </div>
    </div>
</div>

<!-- =====================================================
     FOOTER
     ===================================================== -->
<footer class="landing-footer">
    <p>© 2026 Smart Healthcare & Diagnostic Management System — DIT Final Project</p>
</footer>

</body>
</html>
