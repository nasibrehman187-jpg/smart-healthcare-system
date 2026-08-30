<?php
// =====================================================
// admin-panel.php — System Administrator Overview
// =====================================================
// Accessible ONLY by users with role = 'admin'.
// Features:
//   - Summary stat cards (Patients, Doctors, Appointments count)
//   - User Suspension / Reactivation (Soft status toggle, admin protected)
//   - Warning System (Send warnings to patients / doctors)
//   - Activity Log link
//   - Client-side search filters & empty state handling
// =====================================================

require 'db.php';
requireLogin();
requireRole('admin'); // Only Admin role allowed

$success_msg = '';
$error_msg   = '';

// =====================================================
// HANDLE POST ACTIONS (Suspend/Reactivate & Send Warning)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error_msg = "Security validation failed. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        // 1. TOGGLE USER STATUS (Suspend / Reactivate)
        if ($action === 'toggle_status') {
            $target_user_id = intval($_POST['user_id'] ?? 0);
            $new_status     = $_POST['new_status'] ?? 'active';

            if (!in_array($new_status, ['active', 'suspended'])) {
                $error_msg = "Invalid status requested.";
            } elseif ($target_user_id === $_SESSION['user_id']) {
                $error_msg = "You cannot suspend your own admin account!";
            } else {
                // Ensure target is not an admin
                $chk = $conn->prepare("SELECT role, full_name FROM users WHERE user_id = ?");
                $chk->bind_param("i", $target_user_id);
                $chk->execute();
                $target_user = $chk->get_result()->fetch_assoc();
                $chk->close();

                if (!$target_user) {
                    $error_msg = "Target user not found.";
                } elseif ($target_user['role'] === 'admin') {
                    $error_msg = "Admin accounts cannot be suspended.";
                } else {
                    $upd = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role != 'admin'");
                    $upd->bind_param("si", $new_status, $target_user_id);
                    if ($upd->execute()) {
                        $success_msg = "User '{$target_user['full_name']}' status updated to " . strtoupper($new_status) . ".";
                        logActivity($_SESSION['user_id'], 'Updated User Status', "User #{$target_user_id} set to {$new_status}");
                    } else {
                        $error_msg = "Failed to update user status.";
                    }
                    $upd->close();
                }
            }

        // 2. SEND WARNING MESSAGE
        } elseif ($action === 'send_warning') {
            $target_user_id  = intval($_POST['user_id'] ?? 0);
            $warning_message = trim($_POST['warning_message'] ?? '');

            if ($target_user_id <= 0 || empty($warning_message)) {
                $error_msg = "Please enter a valid warning message.";
            } else {
                $warn_stmt = $conn->prepare("INSERT INTO warnings (user_id, message) VALUES (?, ?)");
                $warn_stmt->bind_param("is", $target_user_id, $warning_message);
                if ($warn_stmt->execute()) {
                    $success_msg = "Warning message sent successfully.";
                    logActivity($_SESSION['user_id'], 'Sent Warning', "User #{$target_user_id}");
                } else {
                    $error_msg = "Failed to send warning message.";
                }
                $warn_stmt->close();
            }
        }
    }
    unset($_SESSION['csrf_token']);
}

// Fetch data for admin tables
$patients = $conn->query(
    "SELECT p.patient_id, p.user_id, p.age, p.weight, p.cnic, p.insurance_number,
            u.full_name, u.email, u.phone, u.status, u.created_at
     FROM patients p
     JOIN users u ON p.user_id = u.user_id
     ORDER BY p.patient_id DESC"
);

$doctors = $conn->query(
    "SELECT d.doctor_id, d.user_id, d.specialization, d.clinic_address, d.city, d.consultation_fee,
            d.available_from, d.available_to,
            u.full_name, u.email, u.phone, u.status, u.created_at
     FROM doctors d
     JOIN users u ON d.user_id = u.user_id
     ORDER BY d.doctor_id DESC"
);

$appointments = $conn->query(
    "SELECT a.appointment_id, a.severity_level, a.appointment_time, a.status, a.created_at,
            u_p.full_name AS patient_name,
            u_d.full_name AS doctor_name, d.specialization
     FROM appointments a
     JOIN patients p ON a.patient_id = p.patient_id
     JOIN users u_p ON p.user_id = u_p.user_id
     JOIN doctors d ON a.doctor_id = d.doctor_id
     JOIN users u_d ON d.user_id = u_d.user_id
     ORDER BY a.appointment_time DESC"
);

$total_patients     = $patients->num_rows;
$total_doctors      = $doctors->num_rows;
$total_appointments = $appointments->num_rows;
$csrf_token         = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — Smart Healthcare System</title>
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
        <li><a href="admin-panel.php">⚙️ Admin Panel</a></li>
        <li><a href="analytics.php">📊 Analytics</a></li>
        <li><a href="activity-log.php">📜 Activity Log</a></li>
        <li><a href="billing.php">💰 Billing</a></li>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <div class="flex-between" style="margin-bottom: 1rem;">
        <h1 class="page-title" style="margin-bottom: 0;">⚙️ Admin Panel</h1>
        <a href="activity-log.php" class="btn btn-secondary">📜 View Activity Log</a>
    </div>

    <!-- Alert Banners -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- Summary Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_patients; ?></div>
            <div class="stat-label">Registered Patients</div>
        </div>
        <div class="stat-card green">
            <div class="stat-number"><?php echo $total_doctors; ?></div>
            <div class="stat-label">Registered Doctors</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number"><?php echo $total_appointments; ?></div>
            <div class="stat-label">Total Appointments</div>
        </div>
    </div>

    <!-- =====================================================
         TABLE 1: ALL PATIENTS
         ===================================================== -->
    <div class="card">
        <div class="flex-between" style="margin-bottom: 1rem; align-items: center;">
            <div class="card-header" style="margin-bottom: 0;">🧑‍🦱 All Patients (<?php echo $total_patients; ?>)</div>
            <?php if ($total_patients > 0): ?>
                <input type="text" placeholder="🔍 Search patients..." 
                       onkeyup="filterTable(this, 'patientsTable')" 
                       style="max-width: 250px; padding: 0.45rem 0.85rem; font-size: 0.88rem; border-radius: var(--radius); border: 1.5px solid var(--gray-300);">
            <?php endif; ?>
        </div>

        <?php if ($total_patients > 0): ?>
            <div class="table-responsive">
                <table id="patientsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>CNIC</th>
                            <th>Insurance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($p = $patients->fetch_assoc()): 
                            $is_suspended = ($p['status'] === 'suspended');
                        ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><strong><?php echo htmlspecialchars($p['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['email']); ?></td>
                                <td><?php echo htmlspecialchars($p['phone']); ?></td>
                                <td><?php echo htmlspecialchars($p['cnic']); ?></td>
                                <td>
                                    <?php if (!empty($p['insurance_number'])): ?>
                                        <span style="color: #059669;">🛡️ Insured</span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $is_suspended ? 'badge-red' : 'badge-green'; ?>">
                                        <?php echo $is_suspended ? 'Suspended' : 'Active'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.4rem;">
                                        <!-- Suspend / Reactivate Form -->
                                        <form method="POST" action="" style="margin:0;" 
                                              onsubmit="return confirm('Are you sure you want to <?php echo $is_suspended ? 'reactivate' : 'suspend'; ?> this patient?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $p['user_id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $is_suspended ? 'active' : 'suspended'; ?>">
                                            <button type="submit" class="btn <?php echo $is_suspended ? 'btn-primary' : 'btn-danger'; ?>" 
                                                    style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                                <?php echo $is_suspended ? '✅ Reactivate' : '🚫 Suspend'; ?>
                                            </button>
                                        </form>

                                        <!-- Send Warning Button -->
                                        <button type="button" class="btn btn-secondary" 
                                                onclick="openWarningModal(<?php echo $p['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($p['full_name'])); ?>')" 
                                                style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                            ⚠️ Warning
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 2.5rem;">No patients found yet.</p>
        <?php endif; ?>
    </div>

    <!-- =====================================================
         TABLE 2: ALL DOCTORS
         ===================================================== -->
    <div class="card">
        <div class="flex-between" style="margin-bottom: 1rem; align-items: center;">
            <div class="card-header" style="margin-bottom: 0;">👨‍⚕️ All Doctors (<?php echo $total_doctors; ?>)</div>
            <?php if ($total_doctors > 0): ?>
                <input type="text" placeholder="🔍 Search doctors..." 
                       onkeyup="filterTable(this, 'doctorsTable')" 
                       style="max-width: 250px; padding: 0.45rem 0.85rem; font-size: 0.88rem; border-radius: var(--radius); border: 1.5px solid var(--gray-300);">
            <?php endif; ?>
        </div>

        <?php if ($total_doctors > 0): ?>
            <div class="table-responsive">
                <table id="doctorsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Specialization</th>
                            <th>Fee (PKR)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($d = $doctors->fetch_assoc()): 
                            $is_suspended = ($d['status'] === 'suspended');
                        ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><strong>Dr. <?php echo htmlspecialchars($d['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($d['email']); ?></td>
                                <td><?php echo htmlspecialchars($d['specialization']); ?></td>
                                <td>Rs. <?php echo number_format($d['consultation_fee']); ?></td>
                                <td>
                                    <span class="badge <?php echo $is_suspended ? 'badge-red' : 'badge-green'; ?>">
                                        <?php echo $is_suspended ? 'Suspended' : 'Active'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.4rem;">
                                        <!-- Suspend / Reactivate Form -->
                                        <form method="POST" action="" style="margin:0;" 
                                              onsubmit="return confirm('Are you sure you want to <?php echo $is_suspended ? 'reactivate' : 'suspend'; ?> this doctor?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $d['user_id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $is_suspended ? 'active' : 'suspended'; ?>">
                                            <button type="submit" class="btn <?php echo $is_suspended ? 'btn-primary' : 'btn-danger'; ?>" 
                                                    style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                                <?php echo $is_suspended ? '✅ Reactivate' : '🚫 Suspend'; ?>
                                            </button>
                                        </form>

                                        <!-- Send Warning Button -->
                                        <button type="button" class="btn btn-secondary" 
                                                onclick="openWarningModal(<?php echo $d['user_id']; ?>, 'Dr. <?php echo htmlspecialchars(addslashes($d['full_name'])); ?>')" 
                                                style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                            ⚠️ Warning
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 2.5rem;">No doctors found yet.</p>
        <?php endif; ?>
    </div>

    <!-- =====================================================
         TABLE 3: ALL APPOINTMENTS
         ===================================================== -->
    <div class="card">
        <div class="flex-between" style="margin-bottom: 1rem; align-items: center;">
            <div class="card-header" style="margin-bottom: 0;">📅 All Appointments (<?php echo $total_appointments; ?>)</div>
            <?php if ($total_appointments > 0): ?>
                <input type="text" placeholder="🔍 Search appointments..." 
                       onkeyup="filterTable(this, 'appointmentsTable')" 
                       style="max-width: 250px; padding: 0.45rem 0.85rem; font-size: 0.88rem; border-radius: var(--radius); border: 1.5px solid var(--gray-300);">
            <?php endif; ?>
        </div>

        <?php if ($total_appointments > 0): ?>
            <div class="table-responsive">
                <table id="appointmentsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Severity</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($a = $appointments->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($a['patient_name']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($a['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($a['specialization']); ?></td>
                                <td>
                                    <?php
                                    $sev = $a['severity_level'];
                                    $sev_class = 'badge-blue';
                                    if ($sev === 'Emergency') $sev_class = 'badge-red';
                                    elseif ($sev === 'Follow-up') $sev_class = 'badge-grey';
                                    ?>
                                    <span class="badge <?php echo $sev_class; ?>">
                                        <?php echo htmlspecialchars($sev); ?>
                                    </span>
                                </td>
                                <td><?php echo date('D, M j, Y h:i A', strtotime($a['appointment_time'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($a['status']); ?>">
                                        <?php echo htmlspecialchars($a['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 2.5rem;">No appointments found yet.</p>
        <?php endif; ?>
    </div>

</div>

<!-- Send Warning Modal -->
<div id="warningModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 480px; margin: 1rem; position: relative;">
        <h3 style="margin-bottom: 1rem; color: var(--gray-900);">⚠️ Send Warning to <span id="modalUserName"></span></h3>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="send_warning">
            <input type="hidden" name="user_id" id="modalUserId" value="">

            <div class="form-group">
                <label for="warning_message">Warning Message <span class="required">*</span></label>
                <textarea id="warning_message" name="warning_message" rows="4" 
                          placeholder="Type warning message here..." required 
                          style="width:100%; padding: 0.75rem; border-radius: var(--radius); border: 1.5px solid var(--gray-300);"></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="closeWarningModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">✉️ Send Warning</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterTable(input, tableId) {
    var filter = input.value.toLowerCase();
    var table = document.getElementById(tableId);
    if (!table) return;
    var rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
    for (var i = 0; i < rows.length; i++) {
        var text = rows[i].textContent || rows[i].innerText;
        if (text.toLowerCase().indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}

function openWarningModal(userId, userName) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('warning_message').value = '';
    document.getElementById('warningModal').style.display = 'flex';
}

function closeWarningModal() {
    document.getElementById('warningModal').style.display = 'none';
}
</script>

<?php require_once 'footer.php'; ?>
