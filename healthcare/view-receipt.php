<?php
// =====================================================
// view-receipt.php — Printable / Downloadable Receipt View
// =====================================================
// Shows a clean receipt for a specific bill with a native
// browser "Print / Save as PDF" button.
// Includes strict ownership verification for patients.
// =====================================================

require 'db.php';
requireLogin();

$bill_id = intval($_GET['bill_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

if ($bill_id <= 0) {
    die("<div style='padding: 2rem; font-family: sans-serif; color: #dc2626;'>Invalid bill ID requested.</div>");
}

// Fetch complete bill details with patient and doctor user IDs for security check
$stmt = $conn->prepare(
    "SELECT b.bill_id, b.consultation_fee, b.test_charges, 
            b.insurance_discount_percent, b.total_amount, b.payment_status, b.created_at,
            a.appointment_time, a.severity_level, a.token_number, a.payment_method,
            COALESCE(b.payment_tid, a.payment_tid) AS payment_tid,
            COALESCE(b.payment_screenshot_path, a.payment_screenshot_path) AS payment_screenshot_path,
            u_p.user_id AS patient_user_id, u_p.full_name AS patient_name, p.insurance_number,
            u_d.user_id AS doctor_user_id, u_d.full_name AS doctor_name, d.specialization
     FROM billing b
     JOIN appointments a ON b.appointment_id = a.appointment_id
     JOIN patients p ON a.patient_id = p.patient_id
     JOIN users u_p ON p.user_id = u_p.user_id
     JOIN doctors d ON a.doctor_id = d.doctor_id
     JOIN users u_d ON d.user_id = u_d.user_id
     WHERE b.bill_id = ?"
);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bill) {
    die("<div style='padding: 2rem; font-family: sans-serif; color: #dc2626;'>Bill record not found.</div>");
}

// SECURITY OWNERSHIP VERIFICATION:
// Patients can ONLY view their own bills.
if ($role === 'patient' && $bill['patient_user_id'] != $user_id) {
    die("<div style='padding: 2rem; font-family: sans-serif; color: #dc2626;'>Unauthorized: You do not have permission to view this receipt.</div>");
}
// Doctors can ONLY view bills for their own appointments.
if ($role === 'doctor' && $bill['doctor_user_id'] != $user_id) {
    die("<div style='padding: 2rem; font-family: sans-serif; color: #dc2626;'>Unauthorized: You do not have permission to view this receipt.</div>");
}

// Calculations
$consultation_fee = floatval($bill['consultation_fee']);
$test_charges     = floatval($bill['test_charges']);
$subtotal         = $consultation_fee + $test_charges;
$discount_percent = floatval($bill['insurance_discount_percent']);
$discount_amount  = ($subtotal * $discount_percent) / 100;
$total_amount     = floatval($bill['total_amount']);
$has_insurance    = ($discount_percent > 0);

// Back button destination
$back_url = ($role === 'patient') ? 'my-bills.php' : 'billing.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $bill['bill_id']; ?> — HealthCare+</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* Print-specific CSS: hides action buttons and cleans layout when printing/saving to PDF */
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background-color: #ffffff !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .receipt-container {
            padding: 0 !important;
            margin: 0 !important;
        }
        .receipt {
            box-shadow: none !important;
            border: 1.5px solid #cbd5e1 !important;
            margin: 0 auto !important;
            max-width: 100% !important;
            width: 100% !important;
        }
    }
    </style>
</head>
<body style="background-color: var(--primary-bg); padding: 2rem 1rem;">

<div class="container receipt-container" style="max-width: 600px;">

    <!-- Top Action Bar (Hidden when printing/saving PDF) -->
    <div class="no-print flex-between" style="margin-bottom: 1.5rem; align-items: center;">
        <a href="<?php echo $back_url; ?>" class="btn btn-secondary" style="font-size: 0.9rem;">
            ← Back to <?php echo ($role === 'patient') ? 'My Bills' : 'Billing'; ?>
        </a>
        <button onclick="window.print()" class="btn btn-primary" style="font-size: 0.9rem; padding: 0.5rem 1.25rem;">
            🖨️ Print / Save as PDF
        </button>
    </div>

    <!-- Printable Receipt Card -->
    <div class="receipt" style="background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 2.25rem; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        
        <!-- Header -->
        <div class="receipt-header" style="text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.25rem;">
            <h2 style="color: var(--primary); font-size: 1.5rem; margin-bottom: 0.2rem;">🏥 HealthCare+</h2>
            <p style="font-size: 0.9rem; color: var(--gray-500); margin: 0;">Smart Healthcare & Diagnostic Management System</p>
            <div style="margin-top: 0.75rem; font-weight: 700; color: var(--gray-900); font-size: 1.1rem;">
                Official Receipt #<?php echo $bill['bill_id']; ?>
            </div>
            <small style="color: var(--gray-500);"><?php echo date('F j, Y \a\t h:i A', strtotime($bill['created_at'])); ?></small>
        </div>

        <!-- Receipt Metadata Rows -->
        <div class="receipt-row">
            <span class="label">Patient Name:</span>
            <strong><?php echo htmlspecialchars($bill['patient_name']); ?></strong>
        </div>

        <div class="receipt-row">
            <span class="label">Attending Doctor:</span>
            <span>Dr. <?php echo htmlspecialchars($bill['doctor_name']); ?></span>
        </div>

        <div class="receipt-row">
            <span class="label">Specialization:</span>
            <span><?php echo htmlspecialchars($bill['specialization']); ?></span>
        </div>

        <div class="receipt-row">
            <span class="label">Appointment Schedule:</span>
            <span><?php echo date('D, M j, Y \a\t h:i A', strtotime($bill['appointment_time'])); ?></span>
        </div>

        <?php if (!empty($bill['token_number'])): ?>
        <div class="receipt-row">
            <span class="label">Appointment Token:</span>
            <span style="font-family: monospace; font-weight: 800; color: #047857; font-size: 1.05rem;">
                <?php echo htmlspecialchars($bill['token_number']); ?>
            </span>
        </div>
        <?php endif; ?>

        <?php if (!empty($bill['payment_method'])): ?>
        <div class="receipt-row">
            <span class="label">Payment Option:</span>
            <span><?php echo htmlspecialchars($bill['payment_method']); ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($bill['payment_tid'])): ?>
        <div class="receipt-row">
            <span class="label">Transaction ID (TID):</span>
            <code style="font-family: monospace; font-weight: 700; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">
                <?php echo htmlspecialchars($bill['payment_tid']); ?>
            </code>
        </div>
        <?php endif; ?>

        <?php if (!empty($bill['payment_screenshot_path'])): ?>
        <div class="receipt-row no-print">
            <span class="label">Payment Proof:</span>
            <a href="<?php echo htmlspecialchars($bill['payment_screenshot_path']); ?>" target="_blank" style="color: #0284c7; font-weight: 600; text-decoration: underline;">
                🖼️ View Uploaded Screenshot
            </a>
        </div>
        <?php endif; ?>

        <hr style="border: 0; border-top: 1px dashed #cbd5e1; margin: 1rem 0;">

        <!-- Itemized Charges -->
        <div class="receipt-row">
            <span class="label">Consultation Fee:</span>
            <span>Rs. <?php echo number_format($consultation_fee, 2); ?></span>
        </div>

        <div class="receipt-row">
            <span class="label">Test / Lab Charges:</span>
            <span>Rs. <?php echo number_format($test_charges, 2); ?></span>
        </div>

        <div class="receipt-row" style="font-weight: 600; color: var(--gray-700);">
            <span class="label" style="color: var(--gray-700);">Subtotal:</span>
            <span>Rs. <?php echo number_format($subtotal, 2); ?></span>
        </div>

        <?php if ($has_insurance): ?>
            <div class="receipt-row" style="color: #059669;">
                <span class="label" style="color: #059669;">🛡️ Insurance Discount (<?php echo $discount_percent; ?>%):</span>
                <span class="discount">- Rs. <?php echo number_format($discount_amount, 2); ?></span>
            </div>
        <?php else: ?>
            <div class="receipt-row">
                <span class="label">Insurance Discount:</span>
                <span>None (Not Insured)</span>
            </div>
        <?php endif; ?>

        <!-- Final Total -->
        <div class="receipt-row total" style="border-top: 2px solid var(--gray-900); margin-top: 0.75rem; padding-top: 0.75rem; font-size: 1.15rem; font-weight: 700; color: var(--primary-dark);">
            <span>TOTAL AMOUNT:</span>
            <span>Rs. <?php echo number_format($total_amount, 2); ?></span>
        </div>

        <!-- Payment Status Badge & Message -->
        <?php $is_paid_receipt = ($bill['payment_status'] === 'Paid'); ?>
        <div class="receipt-row" style="margin-top: 0.75rem; padding: 0.6rem 0.85rem; border-radius: var(--radius); background-color: <?php echo $is_paid_receipt ? '#f0fdf4' : '#fffbeb'; ?>; border: 1px solid <?php echo $is_paid_receipt ? '#bbf7d0' : '#fef3c7'; ?>;">
            <span class="label" style="font-weight: 700; color: <?php echo $is_paid_receipt ? '#166534' : '#92400e'; ?>;">Payment Status:</span>
            <?php if ($is_paid_receipt): ?>
                <span class="badge badge-green" style="font-size: 0.9rem; padding: 0.25rem 0.75rem;">
                    ✅ PAID
                </span>
            <?php else: ?>
                <span class="badge badge-orange" style="font-size: 0.9rem; padding: 0.25rem 0.75rem;">
                    ⚠️ UNPAID — Please settle at the clinic
                </span>
            <?php endif; ?>
        </div>

        <!-- Receipt Footer Note -->
        <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; color: var(--gray-500); font-size: 0.88rem;">
            💚 Thank you for choosing <strong>HealthCare+</strong>.<br>
            <small>Wish you good health and wellness!</small>
        </div>

    </div>

</div>

</body>
</html>
