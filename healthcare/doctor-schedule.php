<?php
// =====================================================
// doctor-schedule.php — Doctor Updates Availability
// =====================================================
// Allows a doctor to set their available_from and available_to times.
// These times are shown in the appointment booking dropdown
// so patients know when the doctor is available.
// =====================================================

require 'db.php';
requireLogin();
requireRole('doctor'); // Only doctors can access this page

$error = '';
$success = '';

// Get the doctor_id and current schedule for the logged-in doctor
$stmt = $conn->prepare(
    "SELECT d.doctor_id, d.specialization, d.available_from, d.available_to, d.consultation_fee
     FROM doctors d WHERE d.user_id = ?"
);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor['doctor_id'] ?? 0;
$stmt->close();

// =====================================================
// HANDLE FORM SUBMISSION — Update schedule
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {

        $available_from   = $_POST['available_from'] ?? '';
        $available_to     = $_POST['available_to'] ?? '';
        $consultation_fee = floatval($_POST['consultation_fee'] ?? 500);

        // Validate times are not empty
        if (empty($available_from) || empty($available_to)) {
            $error = "Please set both available from and available to times.";
        } elseif ($available_from >= $available_to) {
            $error = "Available From time must be before Available To time.";
        } elseif ($consultation_fee < 0) {
            $error = "Consultation fee cannot be negative.";
        } else {
            // Update the doctor's schedule in the database
            $update = $conn->prepare(
                "UPDATE doctors SET available_from = ?, available_to = ?, consultation_fee = ? WHERE doctor_id = ?"
            );
            $update->bind_param("ssdi", $available_from, $available_to, $consultation_fee, $doctor_id);

            if ($update->execute()) {
                $success = "Schedule updated successfully!";
                // Refresh the doctor data to show updated values
                $doctor['available_from'] = $available_from;
                $doctor['available_to'] = $available_to;
                $doctor['consultation_fee'] = $consultation_fee;
            } else {
                $error = "Failed to update schedule. Please try again.";
            }
            $update->close();
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
    <title>My Schedule — Smart Healthcare System</title>
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
        <li><a href="doctor-appointments.php">📅 Today's Appointments</a></li>
        <li><a href="doctor-schedule.php">⏰ My Schedule</a></li>
        <li><a href="doctor-profile.php">👤 My Profile</a></li>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <h1 class="page-title">⏰ My Schedule</h1>

    <!-- Messages -->
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Current Schedule Display -->
    <div class="card">
        <div class="card-header">📋 Current Schedule</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="font-size: 1.3rem;">
                    <?php echo date('h:i A', strtotime($doctor['available_from'])); ?>
                </div>
                <div class="stat-label">Available From</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number" style="font-size: 1.3rem;">
                    <?php echo date('h:i A', strtotime($doctor['available_to'])); ?>
                </div>
                <div class="stat-label">Available To</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number" style="font-size: 1.3rem;">
                    Rs. <?php echo number_format($doctor['consultation_fee']); ?>
                </div>
                <div class="stat-label">Consultation Fee</div>
            </div>
        </div>
    </div>

    <!-- Update Schedule Form -->
    <div class="card">
        <div class="card-header">✏️ Update Schedule</div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="available_from">Available From <span class="required">*</span></label>
                    <input type="time" id="available_from" name="available_from" 
                           value="<?php echo htmlspecialchars($doctor['available_from']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="available_to">Available To <span class="required">*</span></label>
                    <input type="time" id="available_to" name="available_to" 
                           value="<?php echo htmlspecialchars($doctor['available_to']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="consultation_fee">Consultation Fee (PKR)</label>
                <input type="number" id="consultation_fee" name="consultation_fee" 
                       value="<?php echo htmlspecialchars($doctor['consultation_fee']); ?>" 
                       min="0" step="50">
            </div>

            <button type="submit" class="btn btn-primary">💾 Update Schedule</button>
        </form>
    </div>

</div>

</body>
</html>
