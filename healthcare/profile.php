<?php
// =====================================================
// PROFILE MANAGEMENT — Patient Profile & Picture Edit
// Allows patient to view and update profile photo & info
// =====================================================

require_once 'db.php';

// Auth check — user must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'] ?? 'patient';
$full_name = $_SESSION['full_name'] ?? 'User';

$error   = '';
$success = '';

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please refresh and try again.";
    } else {
        $phone  = trim($_POST['phone'] ?? '');
        $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : null;
        $insurance = isset($_POST['insurance_number']) ? trim($_POST['insurance_number']) : null;
        $insurance = ($insurance !== '') ? $insurance : null;

        // Basic phone validation
        if (!empty($phone) && !preg_match('/^03[0-9]{9}$/', $phone)) {
            $error = "Please enter a valid Pakistani phone number (e.g., 03001234567).";
        } elseif ($weight !== null && $weight <= 0 && $role === 'patient') {
            $error = "Weight must be greater than 0 kg.";
        }

        // Handle Profile Picture Upload if provided
        $new_profile_path = null;
        if (empty($error) && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
                $error = "Error uploading profile picture. Please choose a valid image file.";
            } else {
                $fileTmp  = $_FILES['profile_picture']['tmp_name'];
                $fileSize = $_FILES['profile_picture']['size'];
                $origName = $_FILES['profile_picture']['name'];
                $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                $allowed_exts  = ['jpg', 'jpeg', 'png', 'webp'];
                $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

                if ($fileSize > 5 * 1024 * 1024) {
                    $error = "Profile picture size must not exceed 5 MB.";
                } elseif (!in_array($ext, $allowed_exts)) {
                    $error = "Invalid file type. Only JPG, PNG, and WEBP images are accepted.";
                } else {
                    $imgInfo = @getimagesize($fileTmp);
                    if (!$imgInfo || !in_array($imgInfo['mime'], $allowed_mimes)) {
                        $error = "Uploaded file is not a valid image.";
                    } else {
                        $upload_dir = __DIR__ . '/uploads/profiles/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $filename = 'prof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $targetPath = $upload_dir . $filename;
                        if (move_uploaded_file($fileTmp, $targetPath)) {
                            $new_profile_path = 'uploads/profiles/' . $filename;
                        } else {
                            $error = "Could not save profile picture on server.";
                        }
                    }
                }
            }
        }

        if (empty($error)) {
            // Update users table
            if ($new_profile_path) {
                // Delete old picture if exists and not default
                $old_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
                $old_stmt->bind_param("i", $user_id);
                $old_stmt->execute();
                $old_pic = $old_stmt->get_result()->fetch_assoc()['profile_picture'] ?? null;
                $old_stmt->close();

                if ($old_pic && file_exists(__DIR__ . '/' . $old_pic)) {
                    @unlink(__DIR__ . '/' . $old_pic);
                }

                $upd_u = $conn->prepare("UPDATE users SET phone = ?, profile_picture = ? WHERE user_id = ?");
                $upd_u->bind_param("ssi", $phone, $new_profile_path, $user_id);
            } else {
                $upd_u = $conn->prepare("UPDATE users SET phone = ? WHERE user_id = ?");
                $upd_u->bind_param("si", $phone, $user_id);
            }
            $upd_u->execute();
            $upd_u->close();

            // Update patients table if patient
            if ($role === 'patient') {
                if ($new_profile_path) {
                    $upd_p = $conn->prepare("UPDATE patients SET weight = ?, insurance_number = ?, profile_picture = ? WHERE user_id = ?");
                    $upd_p->bind_param("dssi", $weight, $insurance, $new_profile_path, $user_id);
                } else {
                    $upd_p = $conn->prepare("UPDATE patients SET weight = ?, insurance_number = ? WHERE user_id = ?");
                    $upd_p->bind_param("dsi", $weight, $insurance, $user_id);
                }
                $upd_p->execute();
                $upd_p->close();
            }

            $success = "Profile updated successfully!";
            logActivity($user_id, 'Updated Profile', 'Profile picture / details modified');
        }
    }
    unset($_SESSION['csrf_token']);
}

// Fetch current user details
$stmt = $conn->prepare("SELECT full_name, email, phone, role, profile_picture FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$patient_info = null;
if ($role === 'patient') {
    $stmt = $conn->prepare("SELECT patient_id, age, weight, cnic, insurance_number, profile_picture FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $patient_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — Smart Healthcare</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            max-width: 680px;
            margin: 0 auto;
        }
        .avatar-wrap {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 1.5rem;
        }
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 10px rgba(37,99,235,0.15);
            background: #f8fafc;
        }
        .avatar-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.75rem;
            border: 3px dashed #93c5fd;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <span class="icon">🏥</span> Smart Healthcare
    </div>
    <ul class="navbar-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <?php if ($role === 'patient'): ?>
            <li><a href="book-appointment.php">📋 Book Appointment</a></li>
            <li><a href="my-appointments.php">📅 My Appointments</a></li>
            <li><a href="my-bills.php">💰 My Bills</a></li>
            <li><a href="profile.php" class="active">👤 My Profile</a></li>
        <?php elseif ($role === 'doctor'): ?>
            <li><a href="doctor-appointments.php">📅 Today's Appointments</a></li>
            <li><a href="doctor-schedule.php">⏰ My Schedule</a></li>
            <li><a href="profile.php" class="active">👤 My Profile</a></li>
        <?php elseif ($role === 'admin'): ?>
            <li><a href="admin-panel.php">⚙️ Admin Panel</a></li>
            <li><a href="profile.php" class="active">👤 My Profile</a></li>
        <?php endif; ?>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container" style="max-width: 800px; margin-top: 2rem;">

    <div class="flex-between mb-3">
        <h1 class="page-title" style="margin-bottom: 0;">👤 Profile Settings</h1>
        <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>

    <!-- Flash Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- Avatar Preview & Upload -->
            <div class="avatar-wrap">
                <?php if (!empty($user_info['profile_picture']) && file_exists(__DIR__ . '/' . $user_info['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user_info['profile_picture']); ?>" alt="Profile Photo" class="avatar-preview" id="previewImg">
                <?php else: ?>
                    <div class="avatar-placeholder" id="placeholderIcon">🧑‍⚕️</div>
                    <img src="" alt="Profile Photo" class="avatar-preview" id="previewImg" style="display: none;">
                <?php endif; ?>

                <div style="flex: 1;">
                    <h3 style="margin: 0 0 0.25rem 0; color: #1e293b;"><?php echo htmlspecialchars($user_info['full_name']); ?></h3>
                    <p style="margin: 0 0 0.75rem 0; color: #64748b; font-size: 0.9rem;">
                        Role: <strong><?php echo ucfirst($user_info['role']); ?></strong> &bull; <?php echo htmlspecialchars($user_info['email']); ?>
                    </p>
                    <label for="profile_picture" style="font-size: 0.85rem; font-weight: 700; color: var(--primary); cursor: pointer;">
                        📷 Change Profile Picture:
                    </label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp" 
                           onchange="previewImage(this)" style="margin-top: 0.35rem; font-size: 0.85rem;">
                    <small style="display: block; color: #94a3b8; font-size: 0.78rem; margin-top: 0.25rem;">
                        JPG, PNG, or WEBP &bull; Max 5MB
                    </small>
                </div>
            </div>

            <!-- Profile Details -->
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" disabled style="background-color: #f1f5f9;">
                <small style="color: #64748b;">Full name cannot be changed directly</small>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" disabled style="background-color: #f1f5f9;">
                <small style="color: #64748b;">Email is linked to your account credentials</small>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" required
                       value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>">
                <small style="color: #64748b;">Format: 03XXXXXXXXX (11 digits)</small>
            </div>

            <?php if ($role === 'patient' && $patient_info): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" value="<?php echo htmlspecialchars($patient_info['age']); ?>" disabled style="background-color: #f1f5f9;">
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (kg) <span class="required">*</span></label>
                        <input type="number" id="weight" name="weight" step="0.1" min="1" required
                               value="<?php echo htmlspecialchars($patient_info['weight'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>CNIC Number</label>
                    <input type="text" value="<?php echo htmlspecialchars($patient_info['cnic']); ?>" disabled style="background-color: #f1f5f9;">
                </div>

                <div class="form-group">
                    <label for="insurance_number">Insurance Card / Policy Number</label>
                    <input type="text" id="insurance_number" name="insurance_number" 
                           placeholder="Leave empty if not insured"
                           value="<?php echo htmlspecialchars($patient_info['insurance_number'] ?? ''); ?>">
                    <small style="color: #059669;">💡 Insured patients enjoy a 20% discount on hospital charges</small>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem; padding: 0.75rem;">
                💾 Save Changes
            </button>
        </form>
    </div>

</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('previewImg');
            var icon = document.getElementById('placeholderIcon');
            img.src = e.target.result;
            img.style.display = 'block';
            if (icon) icon.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
