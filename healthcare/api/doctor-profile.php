<?php
require 'db.php';
requireLogin();
requireRole('doctor');

$user_id = $_SESSION['user_id'];
$error   = '';
$success = '';

// =====================================================
// HANDLE PROFILE UPDATE (POST)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {
        $specialization   = trim($_POST['specialization'] ?? '');
        $clinic_address   = trim($_POST['clinic_address'] ?? '');
        $city             = trim($_POST['city'] ?? '');
        $available_from   = trim($_POST['available_from'] ?? '');
        $available_to     = trim($_POST['available_to'] ?? '');
        $consultation_fee = floatval($_POST['consultation_fee'] ?? 0);

        if (empty($specialization) || empty($clinic_address) || empty($city) || empty($available_from) || empty($available_to)) {
            $error = "Please fill in all required fields.";
        } elseif ($consultation_fee <= 0) {
            $error = "Consultation fee must be a positive number.";
        } elseif (strtotime($available_from) >= strtotime($available_to)) {
            $error = "Available From time must be earlier than Available To time.";
        } else {
            $update_stmt = $conn->prepare(
                "UPDATE doctors 
                 SET specialization = ?, clinic_address = ?, city = ?, available_from = ?, available_to = ?, consultation_fee = ?
                 WHERE user_id = ?"
            );
            $update_stmt->bind_param("sssssdi", $specialization, $clinic_address, $city, $available_from, $available_to, $consultation_fee, $user_id);

            if ($update_stmt->execute()) {
                $success = "Profile updated successfully.";
                logActivity($user_id, 'Updated Profile', 'Doctor clinic profile updated');
            } else {
                $error = "Failed to update profile. Please try again.";
            }
            $update_stmt->close();
        }
    }
    unset($_SESSION['csrf_token']);
}

$csrf_token = generateCsrfToken();

// Fetch current doctor details from database
$stmt = $conn->prepare(
    "SELECT d.doctor_id, d.specialization, d.clinic_address, d.city, d.available_from, d.available_to, d.consultation_fee,
            u.full_name, u.email, u.phone
     FROM doctors d
     JOIN users u ON d.user_id = u.user_id
     WHERE d.user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doctor) {
    die("Doctor profile record not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit My Profile — Smart Healthcare System</title>
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
        <li><a href="billing.php">💰 Billing</a></li>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container" style="max-width: 650px;">
    
    <div class="flex-between mb-3">
        <h1 class="page-title" style="margin-bottom: 0;">👤 Edit My Profile</h1>
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <!-- Alert Messages -->
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            🩺 Dr. <?php echo htmlspecialchars($doctor['full_name']); ?> — Practice Details
        </div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- Doctor Personal Info (Read-Only) -->
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" disabled style="background-color: #f1f5f9;">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" disabled style="background-color: #f1f5f9;">
                </div>
            </div>

            <!-- Specialization -->
            <div class="form-group">
                <label for="specialization">Medical Specialization <span class="required">*</span></label>
                <input type="text" id="specialization" name="specialization" 
                       value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required
                       placeholder="e.g. Cardiologist, General Physician, Neurologist">
            </div>

            <!-- Clinic Address & City -->
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label for="clinic_address">Clinic Address <span class="required">*</span></label>
                    <input type="text" id="clinic_address" name="clinic_address" 
                           value="<?php echo htmlspecialchars($doctor['clinic_address']); ?>" required
                           placeholder="e.g. Suite 402, Medical Complex, Main Boulevard">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="city">City <span class="required">*</span></label>
                    <input type="text" id="city" name="city" 
                           value="<?php echo htmlspecialchars($doctor['city']); ?>" required
                           placeholder="e.g. Lahore, Karachi, Islamabad">
                </div>
            </div>

            <!-- Available Working Hours -->
            <div class="form-row">
                <div class="form-group">
                    <label for="available_from">Available From <span class="required">*</span></label>
                    <input type="time" id="available_from" name="available_from" 
                           value="<?php echo htmlspecialchars(date('H:i', strtotime($doctor['available_from']))); ?>" required>
                </div>
                <div class="form-group">
                    <label for="available_to">Available To <span class="required">*</span></label>
                    <input type="time" id="available_to" name="available_to" 
                           value="<?php echo htmlspecialchars(date('H:i', strtotime($doctor['available_to']))); ?>" required>
                </div>
            </div>

            <!-- Consultation Fee -->
            <div class="form-group">
                <label for="consultation_fee">Consultation Fee (Rs.) <span class="required">*</span></label>
                <input type="number" id="consultation_fee" name="consultation_fee" step="50" min="1" 
                       value="<?php echo htmlspecialchars($doctor['consultation_fee']); ?>" required
                       placeholder="e.g. 1500">
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-3" style="padding: 0.85rem; font-size: 1.05rem;">
                💾 Save Profile Changes
            </button>
        </form>
    </div>
</div>

</body>
</html>
