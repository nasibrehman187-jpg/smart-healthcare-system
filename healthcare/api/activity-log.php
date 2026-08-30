<?php
// =====================================================
// activity-log.php — System Activity Audit Log (Admin Only)
// =====================================================

require 'db.php';
requireLogin();
requireRole('admin'); // Admin access only

// Fetch latest 100 activity log entries with user details
$logs = $conn->query(
    "SELECT l.log_id, l.user_id, l.action, l.details, l.created_at,
            u.full_name, u.email, u.role
     FROM activity_log l
     JOIN users u ON l.user_id = u.user_id
     ORDER BY l.log_id DESC
     LIMIT 100"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log — Smart Healthcare System</title>
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

    <div class="flex-between" style="margin-bottom: 1.5rem;">
        <h1 class="page-title" style="margin-bottom: 0;">📜 System Activity Log</h1>
        <a href="admin-panel.php" class="btn btn-secondary">⬅️ Back to Admin Panel</a>
    </div>

    <div class="card">
        <div class="flex-between" style="margin-bottom: 1rem; align-items: center;">
            <div class="card-header" style="margin-bottom: 0;">Recent Actions (Latest 100)</div>
            <?php if ($logs && $logs->num_rows > 0): ?>
                <input type="text" placeholder="🔍 Search logs..." 
                       onkeyup="filterTable(this, 'logTable')" 
                       style="max-width: 250px; padding: 0.45rem 0.85rem; font-size: 0.88rem; border-radius: var(--radius); border: 1.5px solid var(--gray-300);">
            <?php endif; ?>
        </div>

        <?php if ($logs && $logs->num_rows > 0): ?>
            <div class="table-responsive">
                <table id="logTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($row = $logs->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo ($row['role'] === 'admin') ? 'emergency' : (($row['role'] === 'doctor') ? 'normal' : 'followup'); ?>">
                                        <?php echo htmlspecialchars(ucfirst($row['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-blue">
                                        <?php echo htmlspecialchars($row['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['details'] ?? '—'); ?></td>
                                <td><?php echo date('M j, Y h:i A', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 2.5rem;">No activity log records found yet.</p>
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
