<?php
// =====================================================
// billing.php — Auto-Calculated Billing System
// =====================================================
// This page handles billing for appointments.
// 
// FOR ADMIN/DOCTOR:
//   - Select an unbilled appointment (NOT IN subquery)
//   - Consultation fee is AUTO-PULLED from the doctor's record
//   - Enter test charges manually
//   - If patient has insurance_number, auto-apply 20% discount
//   - Calculate: subtotal, discount, total_amount
//   - Save to billing table and show receipt-style breakdown
//
// FOR PATIENT:
//   - View their own bills in a receipt-style format
//
// KEY SQL CONCEPT: The NOT IN subquery finds appointments
// that don't already have a bill, preventing duplicate billing.
// =====================================================

require 'db.php';
requireLogin();

$role = $_SESSION['role'];
$error = '';
$success = '';
$receipt = null; // Will hold receipt data after bill creation

// =====================================================
// PATIENT VIEW — Show their bills
// =====================================================
if ($role === 'patient') {

    // Get the patient_id
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $patient_row = $stmt->get_result()->fetch_assoc();
    $patient_id = $patient_row['patient_id'] ?? 0;
    $stmt->close();

    // Fetch all bills for this patient
    // JOIN with appointments, doctors, and users to get full details
    $stmt = $conn->prepare(
        "SELECT b.bill_id, b.consultation_fee, b.test_charges, 
                b.insurance_discount_percent, b.total_amount, b.created_at,
                a.appointment_time, a.severity_level,
                u.full_name AS doctor_name, d.specialization
         FROM billing b
         JOIN appointments a ON b.appointment_id = a.appointment_id
         JOIN doctors d ON a.doctor_id = d.doctor_id
         JOIN users u ON d.user_id = u.user_id
         WHERE a.patient_id = ?
         ORDER BY b.created_at DESC"
    );
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient_bills = $stmt->get_result();
    $stmt->close();
}

// =====================================================
// ADMIN/DOCTOR — Bill Creation & Management Logic
// =====================================================
if ($role === 'admin' || $role === 'doctor') {

    // -----------------------------------------------------
    // 1. HANDLE BILL CREATION (POST)
    // -----------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bill'])) {

        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            $error = "Security validation failed. Please try again.";
        } else {

            $appointment_id = intval($_POST['appointment_id'] ?? 0);
            $test_charges   = floatval($_POST['test_charges'] ?? 0);

            if ($appointment_id <= 0) {
                $error = "Please select an appointment.";
            } elseif ($test_charges < 0) {
                $error = "Test charges cannot be negative.";
            } else {

                // --- Step 1: Fetch appointment data ---
                $stmt = $conn->prepare(
                    "SELECT d.consultation_fee, p.insurance_number,
                            u_p.full_name AS patient_name, u_d.full_name AS doctor_name,
                            d.specialization, a.appointment_time, a.severity_level
                     FROM appointments a
                     JOIN doctors d ON a.doctor_id = d.doctor_id
                     JOIN patients p ON a.patient_id = p.patient_id
                     JOIN users u_p ON p.user_id = u_p.user_id
                     JOIN users u_d ON d.user_id = u_d.user_id
                     WHERE a.appointment_id = ?"
                );
                $stmt->bind_param("i", $appointment_id);
                $stmt->execute();
                $appt_data = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$appt_data) {
                    $error = "Appointment not found.";
                } else {

                    // --- Step 2: Calculate billing amounts ---
                    $consultation_fee = $appt_data['consultation_fee'];
                    $has_insurance = !empty($appt_data['insurance_number']);
                    $insurance_discount_percent = $has_insurance ? 20.00 : 0.00;
                    $subtotal = $consultation_fee + $test_charges;
                    $discount_amount = ($subtotal * $insurance_discount_percent) / 100;
                    $total_amount = $subtotal - $discount_amount;

                    // --- Step 3: Insert bill into database ---
                    $insert_bill = $conn->prepare(
                        "INSERT INTO billing (appointment_id, consultation_fee, test_charges, insurance_discount_percent, total_amount)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $insert_bill->bind_param("idddd", $appointment_id, $consultation_fee, $test_charges, $insurance_discount_percent, $total_amount);

                    if ($insert_bill->execute()) {
                        $success = "Bill created successfully!";

                        // Build receipt data for display
                        $receipt = [
                            'bill_id'         => $conn->insert_id,
                            'patient_name'    => $appt_data['patient_name'],
                            'doctor_name'     => $appt_data['doctor_name'],
                            'specialization'  => $appt_data['specialization'],
                            'appointment_time'=> $appt_data['appointment_time'],
                            'severity'        => $appt_data['severity_level'],
                            'consultation_fee'=> $consultation_fee,
                            'test_charges'    => $test_charges,
                            'subtotal'        => $subtotal,
                            'has_insurance'   => $has_insurance,
                            'discount_percent'=> $insurance_discount_percent,
                            'discount_amount' => $discount_amount,
                            'total_amount'    => $total_amount
                        ];
                    } else {
                        $error = "Failed to create bill. It may already exist.";
                    }
                    $insert_bill->close();
                }
            }
        }
        unset($_SESSION['csrf_token']);
    }

    // -----------------------------------------------------
    // 2. HANDLE MARK AS PAID (POST)
    // -----------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            $error = "Security validation failed. Please try again.";
        } else {
            $bill_id = intval($_POST['bill_id'] ?? 0);
            if ($bill_id > 0) {
                if ($role === 'admin') {
                    $upd = $conn->prepare("UPDATE billing SET payment_status = 'Paid' WHERE bill_id = ?");
                    $upd->bind_param("i", $bill_id);
                } else {
                    $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $doc_id = $stmt->get_result()->fetch_assoc()['doctor_id'] ?? 0;
                    $stmt->close();

                    $upd = $conn->prepare(
                        "UPDATE billing b 
                         JOIN appointments a ON b.appointment_id = a.appointment_id 
                         SET b.payment_status = 'Paid' 
                         WHERE b.bill_id = ? AND a.doctor_id = ?"
                    );
                    $upd->bind_param("ii", $bill_id, $doc_id);
                }

                if ($upd->execute() && $upd->affected_rows > 0) {
                    $success = "Bill #{$bill_id} marked as PAID successfully.";
                    logActivity($_SESSION['user_id'], 'Marked Bill Paid', "Bill #{$bill_id}");
                } else {
                    $error = "Failed to update payment status or unauthorized.";
                }
                $upd->close();
            }
        }
        unset($_SESSION['csrf_token']);
    }

    // -----------------------------------------------------
    // 3. GENERATE FRESH CSRF TOKEN FOR FORM RENDERING
    // -----------------------------------------------------
    $csrf_token = generateCsrfToken();

    // -----------------------------------------------------
    // 4. FETCH UNBILLED APPOINTMENTS (AFTER POST PROCESSING)
    // -----------------------------------------------------
    if ($role === 'admin') {
        $unbilled_query = "SELECT a.appointment_id, a.appointment_time, a.severity_level, a.status,
                                  u_p.full_name AS patient_name, p.insurance_number,
                                  u_d.full_name AS doctor_name, d.consultation_fee
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.patient_id
                           JOIN users u_p ON p.user_id = u_p.user_id
                           JOIN doctors d ON a.doctor_id = d.doctor_id
                           JOIN users u_d ON d.user_id = u_d.user_id
                           WHERE a.appointment_id NOT IN (SELECT appointment_id FROM billing)
                           ORDER BY a.appointment_time DESC";
        $unbilled = $conn->query($unbilled_query);
    } else {
        $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $doc_row = $stmt->get_result()->fetch_assoc();
        $doc_id = $doc_row['doctor_id'] ?? 0;
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT a.appointment_id, a.appointment_time, a.severity_level, a.status,
                    u_p.full_name AS patient_name, p.insurance_number,
                    u_d.full_name AS doctor_name, d.consultation_fee
             FROM appointments a
             JOIN patients p ON a.patient_id = p.patient_id
             JOIN users u_p ON p.user_id = u_p.user_id
             JOIN doctors d ON a.doctor_id = d.doctor_id
             JOIN users u_d ON d.user_id = u_d.user_id
             WHERE a.doctor_id = ? AND a.appointment_id NOT IN (SELECT appointment_id FROM billing)
             ORDER BY a.appointment_time DESC"
        );
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();
        $unbilled = $stmt->get_result();
    }

    // -----------------------------------------------------
    // 5. FETCH EXISTING BILLS FOR DISPLAY (AFTER POST PROCESSING)
    // -----------------------------------------------------
    if ($role === 'admin') {
        $existing_bills = $conn->query(
            "SELECT b.bill_id, b.consultation_fee, b.test_charges, 
                    b.insurance_discount_percent, b.total_amount, b.payment_status, b.created_at,
                    u_p.full_name AS patient_name, u_d.full_name AS doctor_name,
                    a.appointment_time
             FROM billing b
             JOIN appointments a ON b.appointment_id = a.appointment_id
             JOIN patients p ON a.patient_id = p.patient_id
             JOIN users u_p ON p.user_id = u_p.user_id
             JOIN doctors d ON a.doctor_id = d.doctor_id
             JOIN users u_d ON d.user_id = u_d.user_id
             ORDER BY b.created_at DESC"
        );
    } else {
        $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $doc_id = $stmt->get_result()->fetch_assoc()['doctor_id'] ?? 0;
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT b.bill_id, b.consultation_fee, b.test_charges, 
                    b.insurance_discount_percent, b.total_amount, b.payment_status, b.created_at,
                    u_p.full_name AS patient_name, u_d.full_name AS doctor_name,
                    a.appointment_time
             FROM billing b
             JOIN appointments a ON b.appointment_id = a.appointment_id
             JOIN patients p ON a.patient_id = p.patient_id
             JOIN users u_p ON p.user_id = u_p.user_id
             JOIN doctors d ON a.doctor_id = d.doctor_id
             JOIN users u_d ON d.user_id = u_d.user_id
             WHERE a.doctor_id = ?
             ORDER BY b.created_at DESC"
        );
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();
        $existing_bills = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing — Smart Healthcare System</title>
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
        <?php if ($role === 'patient'): ?>
            <li><a href="book-appointment.php">📋 Book Appointment</a></li>
            <li><a href="my-appointments.php">📅 My Appointments</a></li>
            <li><a href="billing.php">💰 My Bills</a></li>
        <?php elseif ($role === 'doctor'): ?>
            <li><a href="doctor-appointments.php">📅 Today's Appointments</a></li>
            <li><a href="doctor-schedule.php">⏰ My Schedule</a></li>
            <li><a href="billing.php">💰 Billing</a></li>
        <?php elseif ($role === 'admin'): ?>
            <li><a href="admin-panel.php">⚙️ Admin Panel</a></li>
            <li><a href="billing.php">💰 Billing</a></li>
        <?php endif; ?>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <h1 class="page-title">💰 <?php echo ($role === 'patient') ? 'My Bills' : 'Billing Management'; ?></h1>

    <!-- Messages -->
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- =====================================================
         RECEIPT — Shown after a bill is created
         Styled like a printed receipt
         ===================================================== -->
    <?php if ($receipt !== null): ?>
        <div class="receipt">
            <div class="receipt-header">
                <h3>🏥 Smart Healthcare System</h3>
                <p>Bill #<?php echo $receipt['bill_id']; ?></p>
                <small><?php echo date('F j, Y \a\t h:i A'); ?></small>
            </div>

            <div class="receipt-row">
                <span class="label">Patient:</span>
                <span><?php echo htmlspecialchars($receipt['patient_name']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Doctor:</span>
                <span>Dr. <?php echo htmlspecialchars($receipt['doctor_name']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Specialization:</span>
                <span><?php echo htmlspecialchars($receipt['specialization']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Appointment:</span>
                <span><?php echo date('M j, Y h:i A', strtotime($receipt['appointment_time'])); ?></span>
            </div>

            <hr style="border: 1px dashed #cbd5e1; margin: 0.75rem 0;">

            <div class="receipt-row">
                <span class="label">Consultation Fee:</span>
                <span>Rs. <?php echo number_format($receipt['consultation_fee'], 2); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Test Charges:</span>
                <span>Rs. <?php echo number_format($receipt['test_charges'], 2); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Subtotal:</span>
                <span>Rs. <?php echo number_format($receipt['subtotal'], 2); ?></span>
            </div>

            <?php if ($receipt['has_insurance']): ?>
                <div class="receipt-row">
                    <span class="label">🛡️ Insurance Discount (<?php echo $receipt['discount_percent']; ?>%):</span>
                    <span class="discount">- Rs. <?php echo number_format($receipt['discount_amount'], 2); ?></span>
                </div>
            <?php else: ?>
                <div class="receipt-row">
                    <span class="label">Insurance Discount:</span>
                    <span>None (Not Insured)</span>
                </div>
            <?php endif; ?>

            <div class="receipt-row total">
                <span>TOTAL AMOUNT:</span>
                <span>Rs. <?php echo number_format($receipt['total_amount'], 2); ?></span>
            </div>
        </div>
    <?php endif; ?>


    <?php if ($role === 'admin' || $role === 'doctor'): ?>
    <!-- =====================================================
         BILL CREATION FORM (Admin / Doctor only)
         ===================================================== -->
    <div class="card">
        <div class="card-header">➕ Create New Bill</div>

        <?php if ($unbilled->num_rows > 0): ?>
            <form method="POST" action="" id="billingForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="create_bill" value="1">

                <!-- Select unbilled appointment -->
                <?php $selected_appt_id = intval($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0); ?>
                <div class="form-group">
                    <label for="appointment_id">Select Appointment (Unbilled Only) <span class="required">*</span></label>
                    <select id="appointment_id" name="appointment_id" required onchange="updateFeeDisplay(this)">
                        <option value="" data-fee="0" data-insurance="0">-- Select an Appointment --</option>
                        <?php 
                        $unbilled->data_seek(0);
                        while ($appt = $unbilled->fetch_assoc()): 
                            $is_selected = ($selected_appt_id === intval($appt['appointment_id']));
                        ?>
                            <option value="<?php echo $appt['appointment_id']; ?>"
                                    data-fee="<?php echo $appt['consultation_fee']; ?>"
                                    data-insurance="<?php echo !empty($appt['insurance_number']) ? '1' : '0'; ?>"
                                    <?php if ($is_selected) echo 'selected'; ?>>
                                #<?php echo $appt['appointment_id']; ?> — 
                                <?php echo htmlspecialchars($appt['patient_name']); ?> →
                                Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?> 
                                (<?php echo date('M j, Y', strtotime($appt['appointment_time'])); ?>)
                                [<?php echo $appt['status']; ?>]
                                <?php echo !empty($appt['insurance_number']) ? '🛡️ Insured' : ''; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <!-- Auto-pulled consultation fee (read-only display) -->
                    <div class="form-group">
                        <label>Consultation Fee (Auto-Pulled)</label>
                        <input type="text" id="display_fee" value="Rs. 0.00" readonly 
                               style="background-color: #f1f5f9; font-weight: 600;">
                        <small style="color: #059669;">Automatically pulled from doctor's record</small>
                    </div>

                    <!-- Test charges (manual entry) -->
                    <div class="form-group">
                        <label for="test_charges">Test / Lab Charges (PKR) <span class="required">*</span></label>
                        <input type="number" id="test_charges" name="test_charges" 
                               value="0" min="0" step="50" required
                               oninput="calculatePreview()">
                    </div>
                </div>

                <!-- Live preview of calculation -->
                <div id="calc_preview" style="background: #f0f9ff; border: 1px solid #dbeafe; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; display: none;">
                    <h4 style="color: #2563eb; margin-bottom: 0.5rem;">💡 Bill Preview</h4>
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
                        <span>Consultation Fee:</span> <span id="prev_fee">Rs. 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
                        <span>Test Charges:</span> <span id="prev_test">Rs. 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0;">
                        <span>Subtotal:</span> <span id="prev_subtotal">Rs. 0.00</span>
                    </div>
                    <div id="prev_discount_row" style="display: none; justify-content: space-between; padding: 0.25rem 0; color: #059669;">
                        <span>🛡️ Insurance Discount (20%):</span> <span id="prev_discount">- Rs. 0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-top: 2px solid #2563eb; margin-top: 0.5rem; font-weight: 700; color: #1d4ed8; font-size: 1.1rem;">
                        <span>Total:</span> <span id="prev_total">Rs. 0.00</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    💰 Generate Bill
                </button>
            </form>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 2rem;">
                No unbilled appointments found. All appointments have been billed. ✅
            </p>
        <?php endif; ?>
    </div>

    <!-- =====================================================
         EXISTING BILLS TABLE (Admin & Doctor view)
         ===================================================== -->
    <?php if (($role === 'admin' || $role === 'doctor') && isset($existing_bills) && $existing_bills->num_rows > 0): ?>
    <div class="card">
        <div class="card-header">📋 Generated Bills (<?php echo $existing_bills->num_rows; ?>)</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Consultation</th>
                        <th>Tests</th>
                        <th>Discount</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($bill = $existing_bills->fetch_assoc()): 
                        $is_paid = ($bill['payment_status'] === 'Paid');
                    ?>
                        <tr>
                            <td><strong>#<?php echo $bill['bill_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($bill['patient_name']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($bill['doctor_name']); ?></td>
                            <td>Rs. <?php echo number_format($bill['consultation_fee'], 2); ?></td>
                            <td>Rs. <?php echo number_format($bill['test_charges'], 2); ?></td>
                            <td>
                                <?php if ($bill['insurance_discount_percent'] > 0): ?>
                                    <span style="color: #059669;"><?php echo $bill['insurance_discount_percent']; ?>%</span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">0%</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>Rs. <?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="badge <?php echo $is_paid ? 'badge-green' : 'badge-orange'; ?>">
                                    <?php echo $is_paid ? 'Paid' : 'Unpaid'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($bill['created_at'])); ?></td>
                            <td>
                                <?php if (!$is_paid): ?>
                                    <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Confirm this payment has been received?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="bill_id" value="<?php echo $bill['bill_id']; ?>">
                                        <button type="submit" class="btn btn-success" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                            💳 Mark as Paid
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #059669; font-size: 0.85rem; font-weight: 600;">✅ Paid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($role === 'patient'): ?>
    <!-- =====================================================
         PATIENT BILLS VIEW — Receipt-style cards
         ===================================================== -->
    <?php if ($patient_bills->num_rows > 0): ?>
        <?php while ($bill = $patient_bills->fetch_assoc()): ?>
            <div class="receipt">
                <div class="receipt-header">
                    <h3>🏥 Smart Healthcare System</h3>
                    <p>Bill #<?php echo $bill['bill_id']; ?></p>
                    <small><?php echo date('F j, Y', strtotime($bill['created_at'])); ?></small>
                </div>

                <div class="receipt-row">
                    <span class="label">Doctor:</span>
                    <span>Dr. <?php echo htmlspecialchars($bill['doctor_name']); ?> (<?php echo htmlspecialchars($bill['specialization']); ?>)</span>
                </div>
                <div class="receipt-row">
                    <span class="label">Appointment:</span>
                    <span><?php echo date('M j, Y h:i A', strtotime($bill['appointment_time'])); ?></span>
                </div>

                <hr style="border: 1px dashed #cbd5e1; margin: 0.75rem 0;">

                <div class="receipt-row">
                    <span class="label">Consultation Fee:</span>
                    <span>Rs. <?php echo number_format($bill['consultation_fee'], 2); ?></span>
                </div>
                <div class="receipt-row">
                    <span class="label">Test Charges:</span>
                    <span>Rs. <?php echo number_format($bill['test_charges'], 2); ?></span>
                </div>

                <?php 
                $sub = $bill['consultation_fee'] + $bill['test_charges'];
                $disc = ($sub * $bill['insurance_discount_percent']) / 100;
                ?>
                <div class="receipt-row">
                    <span class="label">Subtotal:</span>
                    <span>Rs. <?php echo number_format($sub, 2); ?></span>
                </div>

                <?php if ($bill['insurance_discount_percent'] > 0): ?>
                    <div class="receipt-row">
                        <span class="label">🛡️ Insurance Discount (<?php echo $bill['insurance_discount_percent']; ?>%):</span>
                        <span class="discount">- Rs. <?php echo number_format($disc, 2); ?></span>
                    </div>
                <?php endif; ?>

                <div class="receipt-row total">
                    <span>TOTAL:</span>
                    <span>Rs. <?php echo number_format($bill['total_amount'], 2); ?></span>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 3rem; color: #64748b;">
            <p style="font-size: 3rem; margin-bottom: 1rem;">💰</p>
            <h3>No Bills Yet</h3>
            <p>No bills have been generated for your appointments yet.</p>
        </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

<!-- =====================================================
     JAVASCRIPT — Live bill preview calculation
     Updates the preview as user selects appointment / enters charges
     ===================================================== -->
<script>
/**
 * Update the fee display when an appointment is selected
 * Reads the consultation fee from the selected option's data attribute
 */
function updateFeeDisplay(select) {
    var selectedOption = select.options[select.selectedIndex];
    var fee = parseFloat(selectedOption.getAttribute('data-fee')) || 0;

    // Update the read-only fee display
    document.getElementById('display_fee').value = 'Rs. ' + fee.toFixed(2);

    // Recalculate the preview
    calculatePreview();
}

/**
 * Calculate and show a live preview of the bill
 * Called when appointment is selected or test charges change
 */
function calculatePreview() {
    var select = document.getElementById('appointment_id');
    var selectedOption = select.options[select.selectedIndex];

    // Don't show preview if no appointment selected
    if (!selectedOption || selectedOption.value === '') {
        document.getElementById('calc_preview').style.display = 'none';
        return;
    }

    // Get values from the form and data attributes
    var fee = parseFloat(selectedOption.getAttribute('data-fee')) || 0;
    var hasInsurance = selectedOption.getAttribute('data-insurance') === '1';
    var testCharges = parseFloat(document.getElementById('test_charges').value) || 0;

    // Calculate amounts
    var subtotal = fee + testCharges;
    var discountPercent = hasInsurance ? 20 : 0;
    var discountAmount = (subtotal * discountPercent) / 100;
    var total = subtotal - discountAmount;

    // Update preview display
    document.getElementById('prev_fee').textContent = 'Rs. ' + fee.toFixed(2);
    document.getElementById('prev_test').textContent = 'Rs. ' + testCharges.toFixed(2);
    document.getElementById('prev_subtotal').textContent = 'Rs. ' + subtotal.toFixed(2);
    document.getElementById('prev_total').textContent = 'Rs. ' + total.toFixed(2);

    // Show/hide discount row based on insurance
    var discountRow = document.getElementById('prev_discount_row');
    if (hasInsurance) {
        discountRow.style.display = 'flex';
        document.getElementById('prev_discount').textContent = '- Rs. ' + discountAmount.toFixed(2);
    } else {
        discountRow.style.display = 'none';
    }

    // Show the preview box
    document.getElementById('calc_preview').style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('appointment_id');
    if (select && select.value !== '') {
        updateFeeDisplay(select);
    }
});
</script>

<?php require_once 'footer.php'; ?>
