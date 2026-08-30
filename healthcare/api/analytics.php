<?php
// =====================================================
// analytics.php — Admin Analytics Dashboard
// =====================================================
// Displays database-driven analytical charts using Chart.js:
//   1. Most Common Symptoms (Bar Chart — Top 8)
//   2. Disease Distribution (Pie Chart)
//   3. Appointment Status Breakdown (Doughnut Chart)
//
// Access restricted to role = 'admin' only.
// Data is dynamically queried from MySQL database.
// Empty state displayed gracefully if no data exists.
// =====================================================

require 'db.php';
requireLogin();
requireRole('admin'); // Admin access only

// Friendly symptom name map
$symptom_names = [
    'fever'                => 'Fever',
    'cough'                => 'Cough',
    'body_ache'            => 'Body Ache',
    'shortness_of_breath'  => 'Shortness of Breath',
    'headache'             => 'Headache',
    'nausea'               => 'Nausea',
    'sensitivity_to_light' => 'Light Sensitivity',
    'stomach_pain'         => 'Stomach Pain',
    'vomiting'             => 'Vomiting',
    'diarrhea'             => 'Diarrhea',
    'chest_pain'           => 'Chest Pain',
    'sweating'             => 'Sweating',
    'rash'                 => 'Rash',
    'joint_pain'           => 'Joint Pain',
    'sore_throat'          => 'Sore Throat',
    'swollen_glands'       => 'Swollen Glands'
];

// Check total appointments
$total_appts_res = $conn->query("SELECT COUNT(*) as c FROM appointments");
$total_appts = $total_appts_res ? $total_appts_res->fetch_assoc()['c'] : 0;

// =====================================================
// QUERY 1: MOST COMMON SYMPTOMS (Bar Chart)
// Explode comma-separated symptoms_selected and count frequency
// =====================================================
$symptom_tally = [];
$sym_res = $conn->query(
    "SELECT symptoms_selected FROM appointments WHERE symptoms_selected IS NOT NULL AND symptoms_selected != ''"
);
if ($sym_res) {
    while ($row = $sym_res->fetch_assoc()) {
        $symptoms = explode(',', $row['symptoms_selected']);
        foreach ($symptoms as $sym) {
            $sym = trim($sym);
            if ($sym !== '') {
                $label = $symptom_names[$sym] ?? ucwords(str_replace('_', ' ', $sym));
                $symptom_tally[$label] = ($symptom_tally[$label] ?? 0) + 1;
            }
        }
    }
}
arsort($symptom_tally);
$top_symptoms = array_slice($symptom_tally, 0, 8, true);
$symptom_labels = array_keys($top_symptoms);
$symptom_counts = array_values($top_symptoms);

// =====================================================
// QUERY 2: DISEASE DISTRIBUTION (Pie Chart)
// Group appointments by diagnosed_disease
// =====================================================
$disease_labels = [];
$disease_counts = [];
$dis_res = $conn->query(
    "SELECT diagnosed_disease, COUNT(*) as count 
     FROM appointments 
     WHERE diagnosed_disease IS NOT NULL AND diagnosed_disease != '' 
     GROUP BY diagnosed_disease 
     ORDER BY count DESC"
);
if ($dis_res) {
    while ($row = $dis_res->fetch_assoc()) {
        $disease_labels[] = $row['diagnosed_disease'];
        $disease_counts[] = (int)$row['count'];
    }
}

// =====================================================
// QUERY 3: APPOINTMENT STATUS BREAKDOWN (Doughnut Chart)
// Group appointments by status
// =====================================================
$status_labels = [];
$status_counts = [];
$status_colors = [];

$status_color_map = [
    'Pending'   => '#d97706', // Amber
    'Confirmed' => '#0284c7', // Sky Blue
    'Completed' => '#059669', // Green
    'Cancelled' => '#dc2626'  // Red
];

$st_res = $conn->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
if ($st_res) {
    while ($row = $st_res->fetch_assoc()) {
        $st = $row['status'];
        $status_labels[] = $st;
        $status_counts[] = (int)$row['count'];
        $status_colors[] = $status_color_map[$st] ?? '#64748b';
    }
}

$has_analytics_data = ($total_appts > 0) && (!empty($symptom_counts) || !empty($disease_counts) || !empty($status_counts));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .chart-card {
            background-color: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .chart-card h3 {
            font-size: 1.1rem;
            color: var(--gray-900);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .chart-container {
            position: relative;
            flex: 1;
            min-height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
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
        <li><a href="admin-panel.php">⚙️ Admin Panel</a></li>
        <li><a href="analytics.php">📊 Analytics</a></li>
        <li><a href="billing.php">💰 Billing</a></li>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <div class="flex-between mb-3">
        <h1 class="page-title" style="margin-bottom: 0;">📊 System Analytics & Insights</h1>
        <a href="admin-panel.php" class="btn btn-secondary">⚙️ Back to Admin Panel</a>
    </div>

    <?php if (!$has_analytics_data): ?>
        <!-- EMPTY STATE CARD -->
        <div class="card" style="text-align: center; padding: 4rem 2rem; color: var(--gray-500);">
            <p style="font-size: 3.5rem; margin-bottom: 1rem;">📊</p>
            <h3 style="color: var(--gray-700); margin-bottom: 0.5rem;">No Analytics Data Available Yet</h3>
            <p>Book some appointments with symptoms to generate real-time database analytics and charts.</p>
        </div>
    <?php else: ?>

        <!-- CHARTS GRID -->
        <div class="analytics-grid">

            <!-- CHART 1: MOST COMMON SYMPTOMS (Bar Chart) -->
            <div class="chart-card">
                <h3>🩺 Most Common Symptoms (Top 8)</h3>
                <div class="chart-container">
                    <?php if (!empty($symptom_counts)): ?>
                        <canvas id="symptomsChart"></canvas>
                    <?php else: ?>
                        <p style="color: var(--gray-500);">No symptom data recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CHART 2: DISEASE DISTRIBUTION (Pie Chart) -->
            <div class="chart-card">
                <h3>🦠 Disease Distribution (Assessed)</h3>
                <div class="chart-container">
                    <?php if (!empty($disease_counts)): ?>
                        <canvas id="diseaseChart"></canvas>
                    <?php else: ?>
                        <p style="color: var(--gray-500);">No disease assessment data recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CHART 3: APPOINTMENT STATUS BREAKDOWN (Doughnut Chart) -->
            <div class="chart-card">
                <h3>📅 Appointment Status Breakdown</h3>
                <div class="chart-container">
                    <?php if (!empty($status_counts)): ?>
                        <canvas id="statusChart"></canvas>
                    <?php else: ?>
                        <p style="color: var(--gray-500);">No appointment status data recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    <?php endif; ?>

</div>

<!-- =====================================================
     JAVASCRIPT — Render Chart.js Visualizations
     ===================================================== -->
<?php if ($has_analytics_data): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Global Chart.js defaults
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.color = '#334155';

    // -----------------------------------------------------
    // 1. BAR CHART: Most Common Symptoms
    // -----------------------------------------------------
    var symptomsCtx = document.getElementById('symptomsChart');
    if (symptomsCtx) {
        new Chart(symptomsCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($symptom_labels); ?>,
                datasets: [{
                    label: 'Symptom Frequency',
                    data: <?php echo json_encode($symptom_counts); ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.75)', // Primary Blue
                    borderColor: '#1d4ed8',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // -----------------------------------------------------
    // 2. PIE CHART: Disease Distribution
    // -----------------------------------------------------
    var diseaseCtx = document.getElementById('diseaseChart');
    if (diseaseCtx) {
        new Chart(diseaseCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($disease_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($disease_counts); ?>,
                    backgroundColor: [
                        '#2563eb', // Blue
                        '#059669', // Green
                        '#d97706', // Amber
                        '#dc2626', // Red
                        '#7c3aed', // Purple
                        '#0284c7', // Sky Blue
                        '#db2777'  // Pink
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // -----------------------------------------------------
    // 3. DOUGHNUT CHART: Appointment Status
    // -----------------------------------------------------
    var statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: <?php echo json_encode($status_colors); ?>
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

});
</script>
<?php endif; ?>

</body>
</html>
