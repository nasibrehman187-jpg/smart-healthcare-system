<?php
// =====================================================
// my-bills.php — Patient Billing History Page
// =====================================================
// Accessible ONLY by users with role = 'patient'.
// Lists all generated bills for this patient with options
// to view, print, or download individual receipts.
// =====================================================

require 'db.php';
requireLogin();
requireRole('patient');

$user_id = $_SESSION['user_id'];

// Get patient_id for current user
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient_row = $stmt->get_result()->fetch_assoc();
$patient_id = $patient_row['patient_id'] ?? 0;
$stmt->close();

// Fetch all bills for this patient
$stmt = $conn->prepare(
    "SELECT b.bill_id, b.consultation_fee, b.test_charges, 
            b.insurance_discount_percent, b.total_amount, b.payment_status, b.created_at,
            a.appointment_time, u.full_name AS doctor_name, d.specialization
     FROM billing b
     JOIN appointments a ON b.appointment_id = a.appointment_id
     JOIN doctors d ON a.doctor_id = d.doctor_id
     JOIN users u ON d.user_id = u.user_id
     WHERE a.patient_id = ?
     ORDER BY b.bill_id DESC"
);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$bills_result = $stmt->get_result();
$stmt->close();

$total_bills_count = $bills_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bills — Smart Healthcare System</title>
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
        <li><a href="book-appointment.php">📋 Book Appointment</a></li>
        <li><a href="my-appointments.php">📅 My Appointments</a></li>
        <li><a href="my-bills.php">💰 My Bills</a></li>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <div class="flex-between mb-3">
        <h1 class="page-title" style="margin-bottom: 0;">💰 My Bills & Receipts</h1>
        <a href="book-appointment.php" class="btn btn-primary">📋 Book New Appointment</a>
    </div>

    <div class="card">
        <div class="flex-between" style="margin-bottom: 1rem; align-items: center;">
            <div class="card-header" style="margin-bottom: 0;">Billing History (<?php echo $total_bills_count; ?>)</div>
            <?php if ($total_bills_count > 0): ?>
                <input type="text" placeholder="🔍 Search bills..." 
                       onkeyup="filterTable(this, 'billsTable')" 
                       style="max-width: 250px; padding: 0.45rem 0.85rem; font-size: 0.88rem; border-radius: var(--radius); border: 1.5px solid var(--gray-300);">
            <?php endif; ?>
        </div>

        <?php if ($total_bills_count > 0): ?>
            <div class="table-responsive">
                <table id="billsTable">
                    <thead>
                        <tr>
                            <th>Bill #</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Appointment Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Bill Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bill = $bills_result->fetch_assoc()): 
                            $is_paid = ($bill['payment_status'] === 'Paid');
                        ?>
                            <tr>
                                <td><strong>#<?php echo $bill['bill_id']; ?></strong></td>
                                <td>Dr. <?php echo htmlspecialchars($bill['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($bill['specialization']); ?></td>
                                <td><?php echo date('M j, Y h:i A', strtotime($bill['appointment_time'])); ?></td>
                                <td>
                                    <strong style="color: var(--primary-dark);">
                                        Rs. <?php echo number_format($bill['total_amount'], 2); ?>
                                    </strong>
                                    <?php if ($bill['insurance_discount_percent'] > 0): ?>
                                        <br><small style="color: #059669;">🛡️ 20% Insured Discount</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $is_paid ? 'badge-green' : 'badge-orange'; ?>">
                                        <?php echo $is_paid ? 'Paid' : 'Unpaid'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($bill['created_at'])); ?></td>
                                <td>
                                    <a href="view-receipt.php?bill_id=<?php echo $bill['bill_id']; ?>" 
                                       class="btn btn-secondary" 
                                       style="padding: 0.35rem 0.75rem; font-size: 0.85rem; text-decoration: none;">
                                        📄 View / Download Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-500);">
                <div style="font-size: 3rem; margin-bottom: 0.75rem;">💰</div>
                <h3 style="color: var(--gray-700); margin-bottom: 0.5rem;">No Bills Yet</h3>
                <p style="margin-bottom: 1.5rem;">You do not have any generated bills for your appointments at this time.</p>
                <a href="my-appointments.php" class="btn btn-primary">📅 View My Appointments</a>
            </div>
        <?php endif; ?>
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
</script>

</body>
</html>
