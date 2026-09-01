<?php
// =====================================================
// my-appointments.php — Patient's Appointment List
// =====================================================
// Shows all appointments for the logged-in patient.
// Features:
//   - Joins with doctors and users tables to show doctor name + specialization
//   - Status badges (Pending, Confirmed, Completed, Cancelled)
//   - Cancel button for Pending or Confirmed appointments (soft cancellation)
//   - Visual distinction (greyed out + strikethrough) for Cancelled appointments
// =====================================================

require 'db.php';
requireLogin();
requireRole('patient'); // Only patients can view this page

// Get the patient_id for the logged-in user
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient_row = $stmt->get_result()->fetch_assoc();
$patient_id = $patient_row['patient_id'] ?? 0;
$stmt->close();

// Retrieve and clear flash messages from session
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Generate CSRF token for the cancel form
$csrf_token = generateCsrfToken();

// Fetch all appointments for this patient
$stmt = $conn->prepare(
    "SELECT a.appointment_id, a.token_number, a.payment_method, a.payment_tid, a.payment_screenshot_path,
            a.severity_level, a.appointment_time, a.status, a.created_at,
            u.full_name AS doctor_name, d.specialization, d.clinic_address, d.city, d.consultation_fee
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.doctor_id
     JOIN users u ON d.user_id = u.user_id
     WHERE a.patient_id = ?
     ORDER BY a.appointment_time DESC"
);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Visual styling for cancelled appointment rows */
        tr.status-cancelled {
            background-color: #f8fafc !important;
            color: #94a3b8 !important;
        }
        tr.status-cancelled td {
            color: #94a3b8 !important;
        }
        tr.status-cancelled td.doctor-name,
        tr.status-cancelled td.appt-time {
            text-decoration: line-through;
        }
    </style>
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

    <div class="flex-between mb-3">
        <h1 class="page-title" style="margin-bottom: 0;">📅 My Appointments</h1>
        <a href="book-appointment.php" class="btn btn-primary">+ Book New Appointment</a>
    </div>

    <!-- Flash Messages -->
    <?php if ($flash_success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($flash_success); ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($flash_error); ?></div>
    <?php endif; ?>

    <div class="card">
        <?php if ($appointments->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Token #</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Clinic Location</th>
                            <th>Date & Time</th>
                            <th>Severity</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Booked On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($appt = $appointments->fetch_assoc()): 
                            $is_cancelled = ($appt['status'] === 'Cancelled');
                            $can_cancel   = in_array($appt['status'], ['Pending', 'Confirmed']);
                            $token_display = $appt['token_number'] ?? ('TK-' . str_pad($appt['appointment_id'], 4, '0', STR_PAD_LEFT));
                        ?>
                            <tr class="<?php echo $is_cancelled ? 'status-cancelled' : ''; ?>">
                                <td><?php echo $count++; ?></td>

                                <td>
                                    <span class="badge" style="font-family: 'Courier New', monospace; font-size: 0.82rem; font-weight: 700; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                        <?php echo htmlspecialchars($token_display); ?>
                                    </span>
                                </td>

                                <!-- Doctor name with "Dr." prefix -->
                                <td class="doctor-name">Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></td>

                                <td><?php echo htmlspecialchars($appt['specialization']); ?></td>

                                <!-- Clinic location address & city -->
                                <td>
                                    <?php 
                                    $location = trim(($appt['clinic_address'] ?? '') . (!empty($appt['city']) ? ', ' . $appt['city'] : ''));
                                    if ($location !== ''): 
                                    ?>
                                        📍 <?php echo htmlspecialchars($location); ?>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Not specified</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Format date: "Mon, Aug 8, 2026 at 02:30 PM" -->
                                <td class="appt-time"><?php echo date('D, M j, Y \a\t h:i A', strtotime($appt['appointment_time'])); ?></td>

                                <!-- Severity badge with color coding -->
                                <td>
                                    <?php
                                    $sev = $appt['severity_level'];
                                    $sev_class = 'badge-blue';
                                    if ($sev === 'Emergency') $sev_class = 'badge-red';
                                    elseif ($sev === 'Follow-up') $sev_class = 'badge-grey';
                                    ?>
                                    <span class="badge <?php echo $sev_class; ?>">
                                        <?php echo htmlspecialchars($sev); ?>
                                    </span>
                                </td>

                                <!-- Consultation fee from doctor record -->
                                <td>Rs. <?php echo number_format($appt['consultation_fee']); ?></td>

                                <!-- Status badge with color coding and payment info -->
                                <td>
                                    <span class="badge badge-<?php echo strtolower($appt['status']); ?>">
                                        <?php echo htmlspecialchars($appt['status']); ?>
                                    </span>
                                    <div style="margin-top: 0.35rem; font-size: 0.78rem; color: #475569;">
                                        <strong>Paid:</strong> <?php echo htmlspecialchars($appt['payment_method'] ?? 'Cash at Reception'); ?>
                                    </div>
                                    <?php if (!empty($appt['payment_tid'])): ?>
                                        <div style="font-size: 0.74rem; color: #0284c7; font-family: monospace;">
                                            <strong>TID:</strong> <?php echo htmlspecialchars($appt['payment_tid']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($appt['payment_screenshot_path'])): ?>
                                        <div style="margin-top: 0.2rem;">
                                            <a href="<?php echo htmlspecialchars($appt['payment_screenshot_path']); ?>" target="_blank" style="font-size: 0.74rem; color: #059669; font-weight: 700; text-decoration: underline;">
                                                🖼️ My Proof
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- When the appointment was booked -->
                                <td><?php echo date('M j, Y', strtotime($appt['created_at'])); ?></td>

                                <!-- Action column: Reschedule & Cancel buttons for Pending/Confirmed appointments -->
                                <td>
                                    <?php if ($can_cancel): ?>
                                        <div style="display: flex; gap: 0.35rem; align-items: center;">
                                            <?php
                                            $appt_ts = strtotime($appt['appointment_time']);
                                            $secs_remaining = $appt_ts - time();
                                            $can_reschedule = ($secs_remaining >= 3600); // At least 1 hour away
                                            ?>
                                            <?php if ($can_reschedule): ?>
                                                <a href="reschedule-appointment.php?id=<?php echo $appt['appointment_id']; ?>" 
                                                   class="btn btn-secondary" style="padding: 0.35rem 0.65rem; font-size: 0.85rem;">
                                                    ✏️ Reschedule
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-secondary" 
                                                      style="padding: 0.35rem 0.65rem; font-size: 0.85rem; opacity: 0.5; cursor: not-allowed;"
                                                      title="Cannot reschedule — less than 1 hour before scheduled time">
                                                    ✏️ Reschedule
                                                </span>
                                            <?php endif; ?>
                                            <form method="POST" action="cancel-appointment.php" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to cancel this appointment with Dr. <?php echo htmlspecialchars($appt['doctor_name'], ENT_QUOTES); ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.85rem;">
                                                    ✖ Cancel
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- No appointments message -->
            <div style="text-align: center; padding: 3.5rem 1.5rem; color: #64748b;">
                <p style="font-size: 3.5rem; margin-bottom: 1rem;">📅</p>
                <h3 style="color: var(--gray-800); margin-bottom: 0.5rem;">You have no appointments yet.</h3>
                <p style="margin-bottom: 1.5rem;">Book your first appointment to get started with our system.</p>
                <a href="book-appointment.php" class="btn btn-primary">Book Your First Appointment</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'footer.php'; ?>
<?php $stmt->close(); ?>
