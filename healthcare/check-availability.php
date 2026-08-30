<?php
// =====================================================
// check-availability.php — Real-Time Slot Availability API
// =====================================================
// Returns JSON indicating whether a doctor's time slot is available.
// Checks: exact duplicate, 5-minute buffer gap, and working hours.
//
// Response format:
//   {"available": true}
//   {"available": false, "reason": "exact_conflict", "next_available": "7:50 PM"}
//   {"available": false, "reason": "too_close", "next_available": "7:50 PM"}
//   {"available": false, "reason": "outside_hours", "message": "Doctor available 9:00 AM - 5:00 PM"}
// =====================================================

require 'db.php';
requireLogin(); // Fixed: was calling non-existent checkLogin()

header('Content-Type: application/json');

$doctor_id        = intval($_GET['doctor_id'] ?? $_POST['doctor_id'] ?? 0);
$appointment_time = trim($_GET['appointment_time'] ?? $_POST['appointment_time'] ?? '');

if ($doctor_id <= 0 || empty($appointment_time)) {
    echo json_encode(['available' => true]);
    exit();
}

// Convert HTML datetime-local format to MySQL DATETIME and Unix timestamp
$requested_ts    = strtotime($appointment_time);
$formatted_time  = date('Y-m-d H:i:s', $requested_ts);
$requested_time_only = date('H:i:s', $requested_ts); // e.g. "19:00:00"

// =====================================================
// CHECK 1: Doctor's Working Hours
// =====================================================
$doc_stmt = $conn->prepare("SELECT available_from, available_to FROM doctors WHERE doctor_id = ?");
$doc_stmt->bind_param("i", $doctor_id);
$doc_stmt->execute();
$doc_row = $doc_stmt->get_result()->fetch_assoc();
$doc_stmt->close();

if ($doc_row && !empty($doc_row['available_from']) && !empty($doc_row['available_to'])) {
    $avail_from = $doc_row['available_from']; // e.g. "09:00:00"
    $avail_to   = $doc_row['available_to'];   // e.g. "21:00:00"

    if ($requested_time_only < $avail_from || $requested_time_only > $avail_to) {
        echo json_encode([
            'available' => false,
            'reason'    => 'outside_hours',
            'message'   => 'Doctor available ' . date('g:i A', strtotime($avail_from)) . ' - ' . date('g:i A', strtotime($avail_to))
        ]);
        exit();
    }
}

// =====================================================
// CHECK 2: Exact Duplicate + 5-Minute Buffer Gap
// Find any Pending/Confirmed appointment for this doctor
// within 5 minutes (300 seconds) of the requested time.
// =====================================================
$buffer_seconds = 300; // 5 minutes
$window_start = date('Y-m-d H:i:s', $requested_ts - $buffer_seconds);
$window_end   = date('Y-m-d H:i:s', $requested_ts + $buffer_seconds);

$stmt = $conn->prepare(
    "SELECT appointment_time 
     FROM appointments 
     WHERE doctor_id = ? 
       AND appointment_time BETWEEN ? AND ? 
       AND status IN ('Pending', 'Confirmed')
     ORDER BY appointment_time ASC
     LIMIT 1"
);
$stmt->bind_param("iss", $doctor_id, $window_start, $window_end);
$stmt->execute();
$conflict = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($conflict) {
    $conflict_ts = strtotime($conflict['appointment_time']);
    // Next available = conflicting appointment time + 5 minutes
    $next_available_ts = $conflict_ts + $buffer_seconds;
    $next_available_formatted = date('g:i A', $next_available_ts);

    // Determine if it's an exact match or just within the buffer
    $is_exact = ($formatted_time === $conflict['appointment_time']);

    echo json_encode([
        'available'      => false,
        'reason'         => $is_exact ? 'exact_conflict' : 'too_close',
        'next_available' => $next_available_formatted
    ]);
    exit();
}

// All checks passed — slot is available
echo json_encode(['available' => true]);
exit();
