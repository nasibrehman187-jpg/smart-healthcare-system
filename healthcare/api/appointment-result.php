<?php
// =====================================================
// appointment-result.php — Diagnosis Result Display (PRG Target)
// =====================================================
// This page displays the symptom-based diagnosis result ONCE after
// a patient books an appointment via book-appointment.php.
//
// HOW IT WORKS (Post/Redirect/Get pattern):
//   1. Patient submits form on book-appointment.php (POST)
//   2. PHP processes the diagnosis engine + inserts appointment
//   3. Result is stored in $_SESSION['last_diagnosis']
//   4. Browser is redirected HERE (GET request)
//   5. We read the result from session, display it, and CLEAR it
//   6. If user refreshes or comes back later, session is empty → redirect to dashboard
//
// This ensures the result is shown EXACTLY ONCE and book-appointment.php
// always shows a fresh, empty form.
// =====================================================

require 'db.php';
requireLogin();
requireRole('patient'); // Only patients should see this page

// =====================================================
// CHECK FOR DIAGNOSIS RESULT IN SESSION
// =====================================================
if (isset($_SESSION['last_diagnosis'])) {
    // Retrieve the diagnosis data stored by book-appointment.php
    $diagnosis = $_SESSION['last_diagnosis'];

    // Clear it immediately so it won't show again on refresh
    unset($_SESSION['last_diagnosis']);
} else {
    // No diagnosis data in session — user navigated here directly
    // or already viewed the result. Redirect to dashboard.
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Result — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">
        <span class="icon">🏥</span> Smart Healthcare
    </div>
    <ul class="navbar-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="book-appointment.php">📋 Book Appointment</a></li>
        <li><a href="my-appointments.php">📅 My Appointments</a></li>
        <li><a href="billing.php">💰 My Bills</a></li>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <!-- Success Banner -->
    <div class="alert alert-success" style="font-size: 1.05rem;">
        ✅ <strong>Appointment booked successfully!</strong> Your appointment is pending confirmation.
    </div>

    <!-- =====================================================
         DIAGNOSIS RESULT — Preliminary Assessment
         Displayed exactly ONCE, then cleared from session
         ===================================================== -->
    <div class="diagnosis-result">
        <h3>📊 Preliminary Assessment Result</h3>

        <p style="font-size: 1.15rem; margin-bottom: 0.5rem;">
            <strong>Possible Concern:</strong> 
            <?php echo htmlspecialchars($diagnosis['disease']); ?>
        </p>

        <?php if ($diagnosis['found']): ?>
            <?php
            $conf = (int)$diagnosis['confidence'];
            $conf_badge_class = 'badge-green';
            if ($conf < 40) {
                $conf_badge_class = 'badge-grey';
            } elseif ($conf < 70) {
                $conf_badge_class = 'badge-orange';
            }
            ?>
            <p class="confidence" style="margin-top: 0.75rem;">
                <span class="badge <?php echo $conf_badge_class; ?>" style="font-size: 0.95rem; padding: 0.35rem 0.85rem;">
                    Confidence Level: <span id="confNum">0</span>%
                </span>
                <span style="color: var(--gray-600); margin-left: 0.5rem; font-size: 0.9rem;">
                    (<?php echo $diagnosis['matched']; ?> of <?php echo $diagnosis['total']; ?> symptoms matched)
                </span>
            </p>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var target = <?php echo $conf; ?>;
                var el = document.getElementById('confNum');
                if (!el || target <= 0) return;
                var current = 0;
                var stepTime = Math.max(12, Math.floor(800 / target));
                var timer = setInterval(function() {
                    current++;
                    el.textContent = current;
                    if (current >= target) {
                        el.textContent = target;
                        clearInterval(timer);
                    }
                }, stepTime);
            });
            </script>
        <?php else: ?>
            <p style="margin-top: 0.75rem;">
                <span class="badge badge-grey" style="font-size: 0.95rem; padding: 0.35rem 0.85rem;">
                    Confidence Level: Low / Unclear
                </span>
            </p>
        <?php endif; ?>

        <?php if (!empty($diagnosis['text_detected_symptoms'])): ?>
            <?php
            $formatted_text_syms = array_map(function($s) {
                return ucwords(str_replace('_', ' ', $s));
            }, $diagnosis['text_detected_symptoms']);
            ?>
            <div style="background-color: #f0f9ff; border: 1.5px solid #bae6fd; color: #0369a1; padding: 0.65rem 1rem; border-radius: var(--radius); margin-top: 0.85rem; margin-bottom: 0.85rem; font-size: 0.9rem;">
                💡 We also detected: <strong><?php echo htmlspecialchars(implode(', ', $formatted_text_syms)); ?></strong> from your written description.
            </div>
        <?php endif; ?>

        <?php if (!empty($diagnosis['is_emergency']) && !empty($diagnosis['first_aid_steps'])): ?>
            <div style="background-color: #fef2f2; border: 2px solid #fca5a5; border-left: 6px solid #dc2626; color: #991b1b; padding: 1.25rem; border-radius: var(--radius); margin-top: 1.25rem; margin-bottom: 1.25rem;">
                <h4 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
                    🚨 EMERGENCY — Seek immediate medical attention
                </h4>
                <p style="font-weight: 600; margin-bottom: 0.5rem; color: #7f1d1d;">Recommended First-Aid & Safety Steps:</p>
                <div style="font-size: 0.93rem; line-height: 1.6; color: #7f1d1d; white-space: pre-line;">
                    <?php echo htmlspecialchars($diagnosis['first_aid_steps']); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="advice">
            <strong>📝 Advice:</strong><br>
            <?php echo htmlspecialchars($diagnosis['advice']); ?>
        </div>
    </div>

    <!-- =====================================================
         MEDICAL SAFETY DISCLAIMER — REQUIRED on every result
         ===================================================== -->
    <div class="disclaimer">
        <strong>⚠️ Important Medical Disclaimer</strong>
        This is a preliminary assessment only and does not replace a qualified doctor's diagnosis.
        Please consult a healthcare professional for proper medical evaluation and treatment.
        This system is an educational prototype and should NOT be used for actual medical decisions.
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
        <a href="book-appointment.php" class="btn btn-primary" style="flex: 1; text-align: center; padding: 1rem;">
            🩺 Book Another Appointment
        </a>
        <a href="my-appointments.php" class="btn btn-success" style="flex: 1; text-align: center; padding: 1rem;">
            📅 View My Appointments
        </a>
    </div>

</div>

</body>
</html>
