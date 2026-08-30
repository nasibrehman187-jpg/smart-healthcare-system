<?php
// =====================================================
// doctor-appointments.php — Doctor's Appointment View
// =====================================================
// Shows all appointments assigned to the logged-in doctor.
// Can be filtered to show today or all appointments.
// Sorted by severity priority: Emergency → Normal → Follow-up
// Uses: ORDER BY FIELD(severity_level, 'Emergency','Normal','Follow-up')
// Doctor can update appointment status (Confirm / Complete / Cancel).
// =====================================================

require 'db.php';
requireLogin();
requireRole('doctor'); // Only doctors can access this page

$error = '';
$success = '';

// Get the doctor_id for the logged-in doctor
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor_row = $stmt->get_result()->fetch_assoc();
$doctor_id = $doctor_row['doctor_id'] ?? 0;
$stmt->close();

// =====================================================
// HANDLE STATUS UPDATE (POST)
// Doctor can change appointment status
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed.";
    } else {
        $appt_id    = intval($_POST['appointment_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';

        // Validate the status value
        if (in_array($new_status, ['Confirmed', 'Completed', 'Cancelled'])) {
            // First fetch target appointment time and verify doctor ownership
            $chk = $conn->prepare("SELECT appointment_time, doctor_id FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
            $chk->bind_param("ii", $appt_id, $doctor_id);
            $chk->execute();
            $target_appt = $chk->get_result()->fetch_assoc();
            $chk->close();

            if (!$target_appt) {
                $error = "Appointment not found or unauthorized.";
            } else {
                $appt_timestamp = strtotime($target_appt['appointment_time']);
                $now_timestamp  = time();

                // Prevent marking completed if appointment_time is in the future
                if ($new_status === 'Completed' && $appt_timestamp > $now_timestamp) {
                    $formatted_time = date('M j, Y \a\t h:i A', $appt_timestamp);
                    $error = "This appointment is scheduled for {$formatted_time}. You cannot mark it as completed before that time.";
                } else {
                    $update = $conn->prepare(
                        "UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?"
                    );
                    $update->bind_param("sii", $new_status, $appt_id, $doctor_id);

                    if ($update->execute() && $update->affected_rows > 0) {
                        $success = "Appointment status updated to '$new_status'.";
                        logActivity($_SESSION['user_id'], 'Updated Appointment Status', "Appt #{$appt_id} set to {$new_status}");
                    } else {
                        $error = "Failed to update appointment status.";
                    }
                    $update->close();
                }
            }
        } else {
            $error = "Invalid status selected.";
        }
    }
    unset($_SESSION['csrf_token']);
}

$csrf_token = generateCsrfToken();

// =====================================================
// FILTER: Show today's or all appointments
// =====================================================
$filter = $_GET['filter'] ?? 'today'; // Default to today
// Symptom label map for friendly display
$symptom_labels = [
    'fever'                => '🌡️ Fever',
    'cough'                => '😷 Cough',
    'body_ache'            => '💪 Body Ache',
    'shortness_of_breath'  => '😮‍💨 Shortness of Breath',
    'headache'             => '🤕 Headache',
    'nausea'               => '🤢 Nausea',
    'sensitivity_to_light' => '💡 Sensitivity to Light',
    'stomach_pain'         => '🤧 Stomach Pain',
    'vomiting'             => '🤮 Vomiting',
    'diarrhea'             => '🚽 Diarrhea',
    'chest_pain'           => '💔 Chest Pain',
    'sweating'             => '💦 Sweating',
    'rash'                 => '🔴 Rash',
    'joint_pain'           => '🦴 Joint Pain',
    'sore_throat'          => '🗣️ Sore Throat',
    'swollen_glands'       => '😣 Swollen Glands'
];

if ($filter === 'all') {
    // Show ALL appointments for this doctor
    $stmt = $conn->prepare(
        "SELECT a.appointment_id, a.severity_level, a.appointment_time, a.status,
                a.symptoms_selected, a.symptoms_text, a.diagnosed_disease,
                u.full_name AS patient_name, p.age, p.cnic, p.insurance_number
         FROM appointments a
         JOIN patients p ON a.patient_id = p.patient_id
         JOIN users u ON p.user_id = u.user_id
         WHERE a.doctor_id = ?
         ORDER BY (CASE a.severity_level WHEN 'Emergency' THEN 1 WHEN 'Normal' THEN 2 WHEN 'Follow-up' THEN 3 ELSE 4 END), a.appointment_time ASC"
    );
} else {
    // Show only TODAY's appointments (default view)
    $stmt = $conn->prepare(
        "SELECT a.appointment_id, a.severity_level, a.appointment_time, a.status,
                a.symptoms_selected, a.symptoms_text, a.diagnosed_disease,
                u.full_name AS patient_name, p.age, p.cnic, p.insurance_number
         FROM appointments a
         JOIN patients p ON a.patient_id = p.patient_id
         JOIN users u ON p.user_id = u.user_id
         WHERE a.doctor_id = ? AND DATE(a.appointment_time) = CURRENT_DATE
         ORDER BY (CASE a.severity_level WHEN 'Emergency' THEN 1 WHEN 'Normal' THEN 2 WHEN 'Follow-up' THEN 3 ELSE 4 END), a.appointment_time ASC"
    );
}
$stmt->bind_param("i", $doctor_id);
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
        <li><a href="billing.php">💰 Billing</a></li>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <div class="flex-between mb-3">
        <h1 class="page-title" style="margin-bottom: 0;">
            📅 <?php echo ($filter === 'all') ? 'All' : "Today's"; ?> Appointments
        </h1>
        <!-- Filter Toggle Buttons -->
        <div>
            <a href="doctor-appointments.php?filter=today" 
               class="btn <?php echo ($filter === 'today') ? 'btn-primary' : 'btn-secondary'; ?>">
                Today's Appointments
            </a>
            <a href="doctor-appointments.php?filter=all" 
               class="btn <?php echo ($filter === 'all') ? 'btn-primary' : 'btn-secondary'; ?>">
                All Appointments
            </a>
        </div>
    </div>

    <!-- Auto-refresh indicator -->
    <p style="color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.75rem;">
        🔄 This page auto-refreshes every 30 seconds to show new appointments.
    </p>

    <!-- Alert Messages -->
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            📋 Patient List sorted by Priority (Emergency → Normal → Follow-up)
        </div>

        <?php if ($appointments->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient Info</th>
                            <th>Reported Symptoms & Assessment</th>
                            <th>Severity</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($appt = $appointments->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong>
                                    <br><small style="color: var(--gray-600);">Age: <?php echo htmlspecialchars($appt['age']); ?> | CNIC: <?php echo htmlspecialchars($appt['cnic']); ?></small>
                                    <?php if (!empty($appt['insurance_number'])): ?>
                                        <br><small style="color: #059669;">🛡️ Insured (<?php echo htmlspecialchars($appt['insurance_number']); ?>)</small>
                                    <?php endif; ?>
                                </td>

                                <!-- Reported Symptoms & Diagnosis -->
                                <td style="max-width: 320px;">
                                    <?php if (!empty($appt['diagnosed_disease'])): ?>
                                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--primary-dark); margin-bottom: 0.35rem;">
                                            🩺 <?php echo htmlspecialchars($appt['diagnosed_disease']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Parsed Symptom Badges -->
                                    <div style="margin-bottom: 0.3rem;">
                                        <?php 
                                        $sym_keys = !empty($appt['symptoms_selected']) ? explode(',', $appt['symptoms_selected']) : [];
                                        if (!empty($sym_keys)):
                                            foreach ($sym_keys as $sk):
                                                $sk = trim($sk);
                                                $label = $symptom_labels[$sk] ?? ucwords(str_replace('_', ' ', $sk));
                                        ?>
                                                <span class="badge badge-grey" style="font-size: 0.78rem; padding: 0.2rem 0.5rem; margin-right: 2px; margin-bottom: 3px; display: inline-block;">
                                                    <?php echo htmlspecialchars($label); ?>
                                                </span>
                                        <?php 
                                            endforeach;
                                        else:
                                        ?>
                                            <span style="color: #94a3b8; font-size: 0.85rem;">None recorded</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Patient's Raw Written Description -->
                                    <?php if (!empty($appt['symptoms_text'])): ?>
                                        <div style="font-size: 0.82rem; color: #334155; background: #f1f5f9; padding: 0.4rem 0.65rem; border-left: 3px solid var(--primary); border-radius: 4px; margin-top: 0.35rem;">
                                            <strong>Patient's own description:</strong><br>
                                            "<?php echo htmlspecialchars($appt['symptoms_text']); ?>"
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Severity badge -->
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

                                <td><?php echo date('D, M j \a\t h:i A', strtotime($appt['appointment_time'])); ?></td>

                                <!-- Status badge -->
                                <td>
                                    <span class="badge badge-<?php echo strtolower($appt['status']); ?>">
                                        <?php echo htmlspecialchars($appt['status']); ?>
                                    </span>
                                </td>

                                <!-- Action buttons — only show if appointment is not completed/cancelled -->
                                <td>
                                    <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                                        <?php if ($appt['status'] === 'Pending' || $appt['status'] === 'Confirmed'): ?>
                                            <form method="POST" action="" style="display: inline-flex; gap: 4px; margin: 0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                                <input type="hidden" name="update_status" value="1">

                                                <?php if ($appt['status'] === 'Pending'): ?>
                                                    <!-- Pending → can Confirm -->
                                                    <button type="submit" name="new_status" value="Confirmed" 
                                                            class="btn btn-success" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">
                                                        ✅ Confirm
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Mark as Completed button (only for past/current appointments) -->
                                                <?php if (strtotime($appt['appointment_time']) > time()): ?>
                                                    <button type="button" class="btn btn-secondary" 
                                                            style="padding: 0.3rem 0.6rem; font-size: 0.8rem; opacity: 0.55; cursor: not-allowed;" 
                                                            title="Cannot mark completed before scheduled time (<?php echo date('M j, h:i A', strtotime($appt['appointment_time'])); ?>)" 
                                                            disabled>
                                                        ⏳ Mark Complete
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" name="new_status" value="Completed" 
                                                            class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;"
                                                            onclick="return confirm('Mark this appointment as completed?');">
                                                        ✔️ Mark Complete
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Cancel is always available for Pending/Confirmed -->
                                                <button type="submit" name="new_status" value="Cancelled" 
                                                        class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;"
                                                        onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                                    ✖ Cancel
                                                </button>
                                            </form>
                                        <?php elseif ($appt['status'] === 'Completed'): ?>
                                            <a href="billing.php?appointment_id=<?php echo $appt['appointment_id']; ?>" 
                                               class="btn btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.8rem; text-decoration: none;">
                                                💰 Generate Bill
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #64748b;">
                <p style="font-size: 3rem; margin-bottom: 1rem;">📋</p>
                <h3>No Appointments <?php echo ($filter === 'all') ? 'Found' : 'Today'; ?></h3>
                <p><?php echo ($filter !== 'all') ? 'No appointments scheduled for today.' : 'No appointments assigned yet.'; ?></p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Auto-refresh every 30 seconds, paused while a confirm dialog is open -->
<script>
(function() {
    var refreshInterval = 30000;
    var timerId = null;

    function scheduleRefresh() {
        timerId = setTimeout(function() {
            window.location.reload();
        }, refreshInterval);
    }

    // Pause auto-refresh while a confirm() dialog is blocking the page.
    // We achieve this by wrapping the native confirm — if the doctor clicks
    // "Mark Complete" or "Cancel Appointment", the reload is deferred until
    // after they dismiss the dialog.
    var originalConfirm = window.confirm;
    window.confirm = function(msg) {
        clearTimeout(timerId);
        var result = originalConfirm.call(window, msg);
        if (!result) {
            // Doctor dismissed the dialog without proceeding — restart timer
            scheduleRefresh();
        }
        // If they confirmed, the form will submit and navigate away anyway
        return result;
    };

    scheduleRefresh();
})();
</script>

<?php require_once 'footer.php'; ?>
<?php $stmt->close(); ?>
