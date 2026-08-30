<?php
// =====================================================
// dashboard.php — Role-Based Dashboard
// =====================================================
// This is the main page users see after login.
// It shows DIFFERENT content based on the user's role:
//   - Patient: appointment count, quick links to book/view appointments
//   - Doctor: today's appointments sorted by severity (Emergency first)
//   - Admin: total stats (patients, doctors, appointments)
//
// All three roles share the same file — we use if/elseif to render
// different sections based on $_SESSION['role']
// =====================================================

require 'db.php';
requireLogin(); // Redirect to login.php if not logged in

// Get logged-in user info from session
$role      = $_SESSION['role'];
$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// =====================================================
// FETCH DATA BASED ON ROLE
// Each role needs different stats and data
// =====================================================

if ($role === 'patient') {
    // --- PATIENT DATA ---

    // Step 1: Get the patient_id linked to this user
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $patient_row = $stmt->get_result()->fetch_assoc();
    $patient_id = $patient_row['patient_id'] ?? 0;
    $stmt->close();

    // Step 2: Count total appointments for this patient
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $total_appointments = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Step 3: Count pending appointments
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM appointments WHERE patient_id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $pending_appointments = $stmt->get_result()->fetch_assoc()['pending'];
    $stmt->close();

    // Step 4: Count completed appointments
    $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM appointments WHERE patient_id = ? AND status = 'Completed'");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $completed_appointments = $stmt->get_result()->fetch_assoc()['completed'];
    $stmt->close();

    // Step 5: Count bills for this patient
    $stmt = $conn->prepare(
        "SELECT COUNT(*) as total_bills FROM billing b 
         JOIN appointments a ON b.appointment_id = a.appointment_id 
         WHERE a.patient_id = ?"
    );
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $total_bills = $stmt->get_result()->fetch_assoc()['total_bills'];
    $stmt->close();

} elseif ($role === 'doctor') {
    // --- DOCTOR DATA ---

    // Step 1: Get the doctor_id linked to this user
    $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $doctor_row = $stmt->get_result()->fetch_assoc();
    $doctor_id = $doctor_row['doctor_id'] ?? 0;
    $stmt->close();

    // Step 2: Fetch TODAY's appointments, sorted by severity priority
    // ANSI SQL compatible priority sort (Emergency -> Normal -> Follow-up)
    $stmt = $conn->prepare(
        "SELECT a.appointment_id, a.severity_level, a.appointment_time, a.status,
                u.full_name AS patient_name, p.age, p.cnic
         FROM appointments a
         JOIN patients p ON a.patient_id = p.patient_id
         JOIN users u ON p.user_id = u.user_id
         WHERE a.doctor_id = ? AND DATE(a.appointment_time) = CURRENT_DATE
         ORDER BY (CASE a.severity_level WHEN 'Emergency' THEN 1 WHEN 'Normal' THEN 2 WHEN 'Follow-up' THEN 3 ELSE 4 END), a.appointment_time ASC"
    );
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $today_appointments = $stmt->get_result();
    $stmt->close();

    // Step 3: Count total appointments for this doctor
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $total_doctor_appointments = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

} elseif ($role === 'admin') {
    // --- ADMIN DATA ---

    // Count total patients
    $total_patients = $conn->query("SELECT COUNT(*) as c FROM patients")->fetch_assoc()['c'];

    // Count total doctors
    $total_doctors = $conn->query("SELECT COUNT(*) as c FROM doctors")->fetch_assoc()['c'];

    // Count total appointments
    $total_all_appointments = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'];

    // Count pending appointments
    $pending_all = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status = 'Pending'")->fetch_assoc()['c'];

    // Count total bills generated
    $total_all_bills = $conn->query("SELECT COUNT(*) as c FROM billing")->fetch_assoc()['c'];
}

// Handle warning dismissal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dismiss_warning') {
    if (isset($_POST['csrf_token']) && verifyCsrfToken($_POST['csrf_token'])) {
        $warning_id = intval($_POST['warning_id'] ?? 0);
        if ($warning_id > 0) {
            $dismiss_stmt = $conn->prepare("UPDATE warnings SET is_read = 1 WHERE warning_id = ? AND user_id = ?");
            $dismiss_stmt->bind_param("ii", $warning_id, $user_id);
            $dismiss_stmt->execute();
            $dismiss_stmt->close();
        }
    }
    unset($_SESSION['csrf_token']);
    header("Location: dashboard.php");
    exit();
}

// Fetch unread warnings for current user
$warnings_stmt = $conn->prepare("SELECT warning_id, message, created_at FROM warnings WHERE user_id = ? AND is_read = 0 ORDER BY warning_id DESC");
$warnings_stmt->bind_param("i", $user_id);
$warnings_stmt->execute();
$unread_warnings = $warnings_stmt->get_result();
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- =====================================================
     NAVBAR — Shows different links based on role
     ===================================================== -->
<nav class="navbar">
    <div class="navbar-brand">
        <span class="icon">🏥</span> Smart Healthcare
    </div>
    <ul class="navbar-links">
        <li><a href="dashboard.php">Dashboard</a></li>

        <?php if ($role === 'patient'): ?>
            <!-- Patient-specific navigation links -->
            <li><a href="book-appointment.php">📋 Book Appointment</a></li>
            <li><a href="my-appointments.php">📅 My Appointments</a></li>
            <li><a href="my-bills.php">💰 My Bills</a></li>
        <?php elseif ($role === 'doctor'): ?>
            <!-- Doctor-specific navigation links -->
            <li><a href="doctor-appointments.php">📅 Today's Appointments</a></li>
            <li><a href="doctor-schedule.php">⏰ My Schedule</a></li>
            <li><a href="doctor-profile.php">👤 My Profile</a></li>
        <?php elseif ($role === 'admin'): ?>
            <!-- Admin-specific navigation links -->
            <li><a href="admin-panel.php">⚙️ Admin Panel</a></li>
            <li><a href="analytics.php">📊 Analytics</a></li>
            <li><a href="activity-log.php">📜 Activity Log</a></li>
        <?php endif; ?>

        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <!-- Unread Warnings Banner -->
    <?php if ($unread_warnings && $unread_warnings->num_rows > 0): ?>
        <?php while ($w = $unread_warnings->fetch_assoc()): ?>
            <div class="alert alert-warning" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <strong>⚠️ Message from Admin:</strong>
                    <span style="margin-left: 0.5rem; color: #78350f;"><?php echo htmlspecialchars($w['message']); ?></span>
                    <small style="display: block; opacity: 0.8; font-size: 0.8rem; margin-top: 0.25rem;">Sent on <?php echo date('M j, Y h:i A', strtotime($w['created_at'])); ?></small>
                </div>
                <form method="POST" action="" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="dismiss_warning">
                    <input type="hidden" name="warning_id" value="<?php echo $w['warning_id']; ?>">
                    <button type="submit" class="btn btn-secondary" style="padding: 0.35rem 0.85rem; font-size: 0.85rem;">Dismiss</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <!-- Welcome Banner — shown for all roles -->
    <div class="welcome-banner">
        <h2>👋 Welcome, <?php echo htmlspecialchars($full_name); ?>!</h2>
        <p>You are logged in as <strong><?php echo htmlspecialchars(ucfirst($role)); ?></strong> 
           — <?php echo date('l, F j, Y'); ?></p>
    </div>

    <!-- =====================================================
         PATIENT DASHBOARD
         Shows: stats cards + quick action links
         ===================================================== -->
    <?php if ($role === 'patient'): ?>

        <!-- Stats Cards Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $pending_appointments; ?></div>
                <div class="stat-label">Pending Appointments</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?php echo $completed_appointments; ?></div>
                <div class="stat-label">Completed Visits</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_bills; ?></div>
                <div class="stat-label">Bills Generated</div>
            </div>
        </div>

        <!-- Quick Action Links -->
        <div class="card">
            <div class="card-header">🚀 Quick Actions</div>
            <div class="quick-links">
                <a href="book-appointment.php" class="quick-link-card">
                    <span class="link-icon">🩺</span>
                    <span class="link-text">Book Appointment</span>
                </a>
                <a href="my-appointments.php" class="quick-link-card">
                    <span class="link-icon">📅</span>
                    <span class="link-text">My Appointments</span>
                </a>
                <a href="my-bills.php" class="quick-link-card">
                    <span class="link-icon">💰</span>
                    <span class="link-text">View Bills</span>
                </a>
            </div>
        </div>

    <?php elseif ($role === 'doctor'): ?>
    <!-- =====================================================
         DOCTOR DASHBOARD
         Shows: stats + today's appointments sorted by severity
         ===================================================== -->

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_doctor_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?php echo $today_appointments->num_rows; ?></div>
                <div class="stat-label">Today's Appointments</div>
            </div>
        </div>

        <!-- Today's Appointments Table (sorted by severity: Emergency → Normal → Follow-up) -->
        <div class="card">
            <div class="card-header">📅 Today's Appointments (Priority Order)</div>

            <?php if ($today_appointments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient Name</th>
                                <th>Age</th>
                                <th>Severity</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            // Reset the result pointer since we used num_rows above
                            $today_appointments->data_seek(0);
                            while ($appt = $today_appointments->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appt['age']); ?></td>
                                    <td>
                                        <?php
                                        // Show colored badge based on severity
                                        $severity = $appt['severity_level'];
                                        $badge_class = 'badge-normal';
                                        if ($severity === 'Emergency') $badge_class = 'badge-emergency';
                                        elseif ($severity === 'Follow-up') $badge_class = 'badge-followup';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($severity); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($appt['status']); ?>">
                                            <?php echo htmlspecialchars($appt['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #64748b; text-align: center; padding: 2rem;">
                    No appointments scheduled for today. 🎉
                </p>
            <?php endif; ?>
        </div>

        <!-- Quick Links for Doctor -->
        <div class="card">
            <div class="card-header">🚀 Quick Actions</div>
            <div class="quick-links">
                <a href="doctor-appointments.php" class="quick-link-card">
                    <span class="link-icon">📋</span>
                    <span class="link-text">All Appointments</span>
                </a>
                <a href="doctor-schedule.php" class="quick-link-card">
                    <span class="link-icon">⏰</span>
                    <span class="link-text">Update Schedule</span>
                </a>
            </div>
        </div>

    <?php elseif ($role === 'admin'): ?>
    <!-- =====================================================
         ADMIN DASHBOARD
         Shows: overview stats for the entire system
         ===================================================== -->

        <!-- Stats Cards Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_patients; ?></div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?php echo $total_doctors; ?></div>
                <div class="stat-label">Total Doctors</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $total_all_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number"><?php echo $pending_all; ?></div>
                <div class="stat-label">Pending Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_all_bills; ?></div>
                <div class="stat-label">Bills Generated</div>
            </div>
        </div>

        <!-- Quick Links for Admin -->
        <div class="card">
            <div class="card-header">🚀 Quick Actions</div>
            <div class="quick-links">
                <a href="admin-panel.php" class="quick-link-card">
                    <span class="link-icon">⚙️</span>
                    <span class="link-text">Admin Panel</span>
                </a>
                <a href="analytics.php" class="quick-link-card">
                    <span class="link-icon">📊</span>
                    <span class="link-text">System Analytics</span>
                </a>
            </div>
    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>
