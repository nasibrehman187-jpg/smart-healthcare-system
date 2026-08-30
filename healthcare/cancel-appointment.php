<?php
// =====================================================
// cancel-appointment.php — Patient Appointment Cancellation
// =====================================================
// Handles cancellation of PENDING or CONFIRMED appointments by the patient.
//
// SECURITY & SAFETY RULES:
//   1. Must be a logged-in patient
//   2. Must use POST method + valid CSRF token
//   3. Must verify patient owns the appointment (patient_id check)
//   4. Only PENDING or CONFIRMED appointments can be cancelled
//   5. NO hard DELETE — updates status to 'Cancelled' to preserve history
// =====================================================

require 'db.php';
requireLogin();
requireRole('patient');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Step 1: CSRF Verification ---
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $_SESSION['flash_error'] = "Security validation failed. Please try again.";
        header("Location: my-appointments.php");
        exit();
    }

    $appointment_id = intval($_POST['appointment_id'] ?? 0);

    if ($appointment_id <= 0) {
        $_SESSION['flash_error'] = "Invalid appointment selected.";
        header("Location: my-appointments.php");
        exit();
    }

    // --- Step 2: Get patient_id for current user ---
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $patient_row = $stmt->get_result()->fetch_assoc();
    $patient_id = $patient_row['patient_id'] ?? 0;
    $stmt->close();

    if ($patient_id <= 0) {
        $_SESSION['flash_error'] = "Patient profile not found.";
        header("Location: my-appointments.php");
        exit();
    }

    // --- Step 3: Verify ownership & status before updating ---
    // Ensure the appointment belongs to this patient AND is Pending or Confirmed
    $check_stmt = $conn->prepare(
        "SELECT appointment_id, status FROM appointments 
         WHERE appointment_id = ? AND patient_id = ?"
    );
    $check_stmt->bind_param("ii", $appointment_id, $patient_id);
    $check_stmt->execute();
    $appt_result = $check_stmt->get_result();

    if ($appt_result->num_rows === 1) {
        $appt = $appt_result->fetch_assoc();

        if (in_array($appt['status'], ['Pending', 'Confirmed'])) {
            // --- Step 4: Soft-cancel (update status) ---
            $update_stmt = $conn->prepare(
                "UPDATE appointments SET status = 'Cancelled' 
                 WHERE appointment_id = ? AND patient_id = ?"
            );
            $update_stmt->bind_param("ii", $appointment_id, $patient_id);

            if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                logActivity($_SESSION['user_id'], 'Cancelled Appointment', (string)$appointment_id);
                $_SESSION['flash_success'] = "Appointment #{$appointment_id} has been cancelled successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to cancel appointment. Please try again.";
            }
            $update_stmt->close();
        } else {
            $_SESSION['flash_error'] = "This appointment cannot be cancelled because it is already '{$appt['status']}'.";
        }
    } else {
        // Ownership check failed or appointment doesn't exist
        $_SESSION['flash_error'] = "Unauthorized action or appointment not found.";
    }

    $check_stmt->close();
    unset($_SESSION['csrf_token']);
}

// Redirect back to my-appointments.php
header("Location: my-appointments.php");
exit();
