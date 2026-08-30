<?php
// =====================================================
// reschedule-appointment.php — Patient Appointment Rescheduling
// =====================================================
// Allows a patient to select a new date and time for an existing
// PENDING or CONFIRMED appointment.
//
// FEATURES & SECURITY:
//   1. Restricted to logged-in patients who own the appointment
//   2. Ownership verified in both GET and POST requests
//   3. Past date/time selection is rejected
//   4. DOUBLE-BOOKING CHECK: Ensures doctor does not already have an active
//      appointment (Pending or Confirmed) at the requested exact time slot
//   5. Resets appointment status to 'Pending' so doctor can re-confirm
//   6. Protected by CSRF token on POST form
// =====================================================

require 'db.php';
requireLogin();
requireRole('patient');

// Get patient_id for current user
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient_row = $stmt->get_result()->fetch_assoc();
$patient_id = $patient_row['patient_id'] ?? 0;
$stmt->close();

$error = '';
$appointment_id = intval($_GET['id'] ?? $_POST['appointment_id'] ?? 0);

if ($patient_id <= 0 || $appointment_id <= 0) {
    $_SESSION['flash_error'] = "Invalid appointment or patient profile.";
    header("Location: my-appointments.php");
    exit();
}

// Fetch appointment details and verify ownership + status
$stmt = $conn->prepare(
    "SELECT a.appointment_id, a.doctor_id, a.severity_level, a.appointment_time, a.status,
            u.full_name AS doctor_name, d.specialization
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.doctor_id
     JOIN users u ON d.user_id = u.user_id
     WHERE a.appointment_id = ? AND a.patient_id = ?"
);
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If appointment does not exist or does not belong to patient
if (!$appt) {
    $_SESSION['flash_error'] = "Appointment not found or access denied.";
    header("Location: my-appointments.php");
    exit();
}

// Only Pending or Confirmed appointments can be rescheduled
if (!in_array($appt['status'], ['Pending', 'Confirmed'])) {
    $_SESSION['flash_error'] = "Only Pending or Confirmed appointments can be rescheduled.";
    header("Location: my-appointments.php");
    exit();
}

// =====================================================
// 1-HOUR MINIMUM NOTICE PERIOD CHECK
// Patients must reschedule at least 1 hour before the
// current scheduled appointment time.
// =====================================================
$appt_timestamp = strtotime($appt['appointment_time']);
$seconds_until_appt = $appt_timestamp - time();
$reschedule_blocked = ($seconds_until_appt < 3600); // Less than 1 hour away or already passed

// =====================================================
// HANDLE POST — Reschedule Submission
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Step 0: Server-side re-check of 1-hour notice period ---
    // (prevents bypass via direct POST even if form was somehow submitted)
    if ($reschedule_blocked) {
        $error = "Appointments can only be rescheduled at least 1 hour before the scheduled time. This appointment is too close to proceed with rescheduling.";
    // --- Step 1: CSRF Check ---
    } elseif (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {

        $new_time = $_POST['new_appointment_time'] ?? '';

        // --- Step 2: Validate new date/time input ---
        if (empty($new_time)) {
            $error = "Please select a new appointment date and time.";
        } else {
            $new_timestamp = strtotime($new_time);

            // Reject past dates/times
            if ($new_timestamp <= time()) {
                $error = "The new appointment date and time must be in the future.";
            } else {

                // Format datetime string for MySQL (YYYY-MM-DD HH:MM:SS)
                $formatted_new_time = date('Y-m-d H:i:s', $new_timestamp);

                // --- Step 3: DOUBLE-BOOKING CHECK ---
                // Check if the SAME doctor has another Pending/Confirmed appointment at this exact slot
                $check_slot = $conn->prepare(
                    "SELECT appointment_id FROM appointments 
                     WHERE doctor_id = ? 
                       AND appointment_time = ? 
                       AND appointment_id != ? 
                       AND status IN ('Pending', 'Confirmed')"
                );
                $check_slot->bind_param("isi", $appt['doctor_id'], $formatted_new_time, $appointment_id);
                $check_slot->execute();
                $slot_result = $check_slot->get_result();

                if ($slot_result->num_rows > 0) {
                    $error = "This time slot is already booked for Dr. " . htmlspecialchars($appt['doctor_name']) . ". Please choose another time.";
                } else {

                    // --- Step 4: UPDATE appointment time & reset status to 'Pending' ---
                    // Why reset status to 'Pending'?
                    // Because changing the appointment date/time means the doctor needs to review
                    // and re-confirm availability for the newly chosen schedule slot.
                    $update_stmt = $conn->prepare(
                        "UPDATE appointments 
                         SET appointment_time = ?, status = 'Pending' 
                         WHERE appointment_id = ? AND patient_id = ?"
                    );
                    $update_stmt->bind_param("sii", $formatted_new_time, $appointment_id, $patient_id);

                    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                        logActivity($_SESSION['user_id'], 'Rescheduled Appointment', (string)$appointment_id);
                        $_SESSION['flash_success'] = "Appointment #{$appointment_id} rescheduled successfully to " . date('D, M j, Y \a\t h:i A', $new_timestamp) . ". Status set to Pending confirmation.";
                        unset($_SESSION['csrf_token']);
                        header("Location: my-appointments.php");
                        exit();
                    } else {
                        $error = "No changes were made to the appointment schedule.";
                    }
                    $update_stmt->close();
                }
                $check_slot->close();
            }
        }
    }
    unset($_SESSION['csrf_token']);
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment — Smart Healthcare System</title>
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

<div class="container container-narrow">

    <div class="card">
        <div class="card-header">✏️ Reschedule Appointment #<?php echo $appt['appointment_id']; ?></div>

        <!-- Show error alert if validation or double-booking check fails -->
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Appointment Details Summary -->
        <div style="background-color: var(--gray-50); border: 1px solid var(--gray-200); padding: 1.25rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
            <p style="margin-bottom: 0.5rem;">
                <strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?> 
                <span style="color: var(--gray-500);">(<?php echo htmlspecialchars($appt['specialization']); ?>)</span>
            </p>
            <p style="margin-bottom: 0.5rem;">
                <strong>Current Status:</strong> 
                <span class="badge badge-<?php echo strtolower($appt['status']); ?>">
                    <?php echo htmlspecialchars($appt['status']); ?>
                </span>
            </p>
            <p style="margin-bottom: 0;">
                <strong>Current Scheduled Date & Time:</strong><br>
                <span style="color: var(--primary-dark); font-weight: 700; font-size: 1.05rem;">
                    📅 <?php echo date('D, M j, Y \a\t h:i A', strtotime($appt['appointment_time'])); ?>
                </span>
            </p>
        </div>

        <?php if ($reschedule_blocked): ?>
            <!-- Reschedule blocked: appointment is within 1 hour or already passed -->
            <div class="alert alert-error" style="text-align: center; padding: 1.5rem;">
                <p style="font-size: 1.6rem; margin-bottom: 0.5rem;">🚫</p>
                <strong>Rescheduling Not Available</strong><br>
                Appointments can only be rescheduled at least 1 hour before the scheduled time.
                <?php if ($seconds_until_appt <= 0): ?>
                    This appointment's scheduled time has already passed.
                <?php else: ?>
                    This appointment is only <?php echo round($seconds_until_appt / 60); ?> minutes away.
                <?php endif; ?>
            </div>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="my-appointments.php" class="btn btn-secondary">
                    🔙 Back to My Appointments
                </a>
            </div>
        <?php else: ?>
            <!-- Reschedule Form -->
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">

                <div class="form-group">
                    <label for="new_appointment_time">Select New Date & Time <span class="required">*</span></label>
                    <input type="datetime-local" id="new_appointment_time" name="new_appointment_time" 
                           value="<?php echo htmlspecialchars($_POST['new_appointment_time'] ?? ''); ?>" required>
                    <small style="color: var(--gray-500); display: block; margin-top: 0.3rem;">
                        Note: Rescheduling resets your appointment status to Pending for doctor re-confirmation.
                    </small>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        💾 Confirm Reschedule
                    </button>
                    <a href="my-appointments.php" class="btn btn-secondary" style="flex: 1; text-align: center;">
                        🔙 Cancel / Back
                    </a>
                </div>
            </form>
        <?php endif; ?>

    </div>

</div>

</body>
</html>
