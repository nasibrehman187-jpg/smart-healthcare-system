<?php
// =====================================================
// register.php — User Registration Page
// =====================================================
// This page allows new users to register as either a Patient or Doctor.
// Features:
//   - Role selection (Patient or Doctor) with dynamic form fields
//   - CSRF token protection on the form
//   - Password hashing using password_hash() — NEVER stores plain text
//   - Prepared statements (bind_param) for SQL injection prevention
//   - Duplicate email check before inserting
//   - Client-side validation (JS) for CNIC, phone, age
//   - Inserts into users table + patients/doctors table based on role
// =====================================================

// Include database connection and session/CSRF helpers
require 'db.php';

// If user is already logged in, redirect them to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables for error/success messages
$error = '';
$success = '';

// =====================================================
// HANDLE FORM SUBMISSION (POST request)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Step 1: Verify CSRF token ---
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {

        // --- Step 2: Collect and sanitize form data ---
        // trim() removes leading/trailing whitespace
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $phone     = trim($_POST['phone'] ?? '');
        $role      = $_POST['role'] ?? '';

        // --- Step 3: Server-side validation ---
        // We validate on both client (JS) and server (PHP) side
        // Server-side validation is the real security — JS can be bypassed

        if (empty($full_name) || empty($email) || empty($password) || empty($phone) || empty($role)) {
            $error = "All required fields must be filled.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Check if email format is valid (e.g., user@example.com)
            $error = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (!in_array($role, ['patient', 'doctor'])) {
            // Only patient and doctor can self-register (admin is created manually)
            $error = "Invalid role selected.";
        } else {

            // --- Step 4: Check for duplicate email ---
            // Use prepared statement to safely check if email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email); // "s" = string parameter
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "An account with this email already exists. Please use a different email or login.";
            } else {

                // --- Step 5: Pre-validate Role-Specific Fields & Profile Picture BEFORE User Insertion ---
                $profile_picture_path = null;

                if ($role === 'patient') {
                    $age     = intval($_POST['age'] ?? 0);
                    $weight  = floatval($_POST['weight'] ?? 0);
                    $cnic    = trim($_POST['cnic'] ?? '');
                    $insurance = trim($_POST['insurance_number'] ?? '');
                    $insurance = ($insurance !== '') ? $insurance : null;

                    // 1. Mandatory Profile Picture Validation (Change 2)
                    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
                        $error = "Profile picture is mandatory for patient registration. Please upload your photo.";
                    } elseif ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
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
                            $error = "Invalid file type. Only JPG, PNG, and WEBP images are accepted as profile pictures.";
                        } else {
                            $imgInfo = @getimagesize($fileTmp);
                            if (!$imgInfo || !in_array($imgInfo['mime'], $allowed_mimes)) {
                                $error = "Uploaded file is not a valid image.";
                            }
                        }
                    }

                    // 2. Patient Demographics Validation
                    if (empty($error)) {
                        if ($age < 1 || $age > 120) {
                            $error = "Age must be between 1 and 120.";
                        } elseif ($weight <= 0) {
                            $error = "Weight must be greater than 0 kg.";
                        } elseif (empty($cnic)) {
                            $error = "CNIC number is required.";
                        }
                    }
                } elseif ($role === 'doctor') {
                    $specialization   = trim($_POST['specialization'] ?? '');
                    $clinic_address   = trim($_POST['clinic_address'] ?? '');
                    $city             = trim($_POST['city'] ?? '');
                    $consultation_fee = floatval($_POST['consultation_fee'] ?? 500);
                    $available_from   = $_POST['available_from'] ?? '09:00';
                    $available_to     = $_POST['available_to'] ?? '17:00';

                    if (empty($specialization)) {
                        $error = "Please select a specialization.";
                    }
                }

                // If pre-validation passed, handle file upload and insert user + role
                if (empty($error)) {
                    if ($role === 'patient') {
                        $upload_dir = __DIR__ . '/uploads/profiles/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $filename = 'prof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $targetPath = $upload_dir . $filename;
                        if (move_uploaded_file($fileTmp, $targetPath)) {
                            $profile_picture_path = 'uploads/profiles/' . $filename;
                        } else {
                            $error = "Could not save profile picture on server. Please try again.";
                        }
                    }
                }

                if (empty($error)) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert into users table
                    $insert_user = $conn->prepare(
                        "INSERT INTO users (full_name, email, password, role, phone, profile_picture) VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $insert_user->bind_param("ssssss", $full_name, $email, $hashed_password, $role, $phone, $profile_picture_path);

                    if ($insert_user->execute()) {
                        $new_user_id = $conn->insert_id;
                        $insert_user->close();

                        if ($role === 'patient') {
                            $insert_patient = $conn->prepare(
                                "INSERT INTO patients (user_id, age, weight, cnic, insurance_number, profile_picture) VALUES (?, ?, ?, ?, ?, ?)"
                            );
                            $insert_patient->bind_param("iidsss", $new_user_id, $age, $weight, $cnic, $insurance, $profile_picture_path);
                            $insert_patient->execute();
                            $insert_patient->close();
                        } else {
                            $insert_doctor = $conn->prepare(
                                "INSERT INTO doctors (user_id, specialization, clinic_address, city, available_from, available_to, consultation_fee) VALUES (?, ?, ?, ?, ?, ?, ?)"
                            );
                            $insert_doctor->bind_param("isssssd", $new_user_id, $specialization, $clinic_address, $city, $available_from, $available_to, $consultation_fee);
                            $insert_doctor->execute();
                            $insert_doctor->close();
                        }

                        logActivity($new_user_id, 'Registered', $role);

                        unset($_SESSION['csrf_token']);
                        header("Location: login.php?registered=1");
                        exit();
                    } else {
                        $error = "Failed to create account. Please try again.";
                        $insert_user->close();
                        if ($profile_picture_path && file_exists(__DIR__ . '/' . $profile_picture_path)) {
                            @unlink(__DIR__ . '/' . $profile_picture_path);
                        }
                    }
                }
            }
            $check_stmt->close();
        }
    }

    // After processing, regenerate CSRF token so the old one can't be reused
    unset($_SESSION['csrf_token']);
}

// Generate a fresh CSRF token for the form
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for Smart Healthcare System - Create your patient or doctor account">
    <title>Register — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card" style="max-width: 600px;">

        <!-- Page Header -->
        <div class="auth-header">
            <span class="logo-icon">🏥</span>
            <h1>Create Your Account</h1>
            <p>Join the Smart Healthcare System</p>
        </div>

        <!-- Show error message if validation failed -->
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Show success message after registration -->
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- =====================================================
             REGISTRATION FORM
             - action="" means it submits to itself (this page)
             - method="POST" sends data securely in the request body
             - enctype="multipart/form-data" enables profile photo upload
             - onsubmit calls our JS validation function
             ===================================================== -->
        <form method="POST" action="" id="registerForm" enctype="multipart/form-data" onsubmit="return validateForm()">

            <!-- CSRF Token — hidden field for security -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- =====================================================
                 ROLE SELECTION — Patient or Doctor
                 JavaScript toggles the role-specific fields below
                 ===================================================== -->
            <label style="font-weight: 600; color: #334155; margin-bottom: 0.5rem; display: block;">
                I am registering as: <span class="required">*</span>
            </label>
            <div class="role-selector">
                <div class="role-option">
                    <input type="radio" id="role_patient" name="role" value="patient" 
                           onchange="toggleRoleFields()" <?php echo (($_POST['role'] ?? 'patient') === 'patient') ? 'checked' : ''; ?>>
                    <label for="role_patient">
                        <span class="role-icon">🧑‍🦱</span>
                        <span class="role-name">Patient</span>
                    </label>
                </div>
                <div class="role-option">
                    <input type="radio" id="role_doctor" name="role" value="doctor" 
                           onchange="toggleRoleFields()" <?php echo (($_POST['role'] ?? '') === 'doctor') ? 'checked' : ''; ?>>
                    <label for="role_doctor">
                        <span class="role-icon">👨‍⚕️</span>
                        <span class="role-name">Doctor</span>
                    </label>
                </div>
            </div>

            <!-- =====================================================
                 COMMON FIELDS — Same for all roles
                 ===================================================== -->
            <div class="form-group">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" placeholder="Enter your full name"
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" placeholder="you@example.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" placeholder="03XXXXXXXXX"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Min. 6 characters" required>
                        <button type="button" class="toggle-eye" onclick="togglePassword('password', this)" tabindex="-1">👁️</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                        <button type="button" class="toggle-eye" onclick="togglePassword('confirm_password', this)" tabindex="-1">👁️</button>
                    </div>
                </div>
            </div>

            <!-- =====================================================
                 PATIENT-SPECIFIC FIELDS — Shown when "Patient" is selected
                 ===================================================== -->
            <div id="patient_fields" class="role-fields active">
                <h3 style="margin-bottom: 1rem; color: #2563eb;">📋 Patient Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="age">Age <span class="required">*</span></label>
                        <input type="number" id="age" name="age" placeholder="e.g., 25" min="1" max="120"
                               value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (kg) <span class="required">*</span></label>
                        <input type="number" id="weight" name="weight" placeholder="e.g., 70" step="0.1" min="1"
                               value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="cnic">CNIC Number <span class="required">*</span></label>
                    <input type="text" id="cnic" name="cnic" placeholder="XXXXX-XXXXXXX-X"
                           value="<?php echo htmlspecialchars($_POST['cnic'] ?? ''); ?>">
                    <small style="color: #64748b;">Format: 12345-1234567-1</small>
                </div>

                <div class="form-group">
                    <label for="insurance_number">Insurance Number <span style="color: #64748b;">(Optional)</span></label>
                    <input type="text" id="insurance_number" name="insurance_number" 
                           placeholder="Leave empty if not insured"
                           value="<?php echo htmlspecialchars($_POST['insurance_number'] ?? ''); ?>">
                    <small style="color: #059669;">💡 Patients with insurance get 20% billing discount</small>
                </div>

                <!-- CHANGE 2: Mandatory Patient Profile Picture -->
                <div class="form-group">
                    <label for="profile_picture">Profile Picture <span class="required">*</span></label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp">
                    <small style="color: #64748b;">Upload a passport/face photo (JPG, PNG, or WEBP &mdash; Max 5MB)</small>
                </div>
            </div>

            <!-- =====================================================
                 DOCTOR-SPECIFIC FIELDS — Shown when "Doctor" is selected
                 ===================================================== -->
            <div id="doctor_fields" class="role-fields">
                <h3 style="margin-bottom: 1rem; color: #2563eb;">🩺 Doctor Information</h3>

                <div class="form-group">
                    <label for="specialization">Specialization <span class="required">*</span></label>
                    <?php $sel_spec = $_POST['specialization'] ?? ''; ?>
                    <select id="specialization" name="specialization">
                        <option value="">Select Specialization</option>
                        <option value="General Physician" <?php echo ($sel_spec === 'General Physician') ? 'selected' : ''; ?>>General Physician</option>
                        <option value="Cardiologist" <?php echo ($sel_spec === 'Cardiologist') ? 'selected' : ''; ?>>Cardiologist</option>
                        <option value="Dermatologist" <?php echo ($sel_spec === 'Dermatologist') ? 'selected' : ''; ?>>Dermatologist</option>
                        <option value="Neurologist" <?php echo ($sel_spec === 'Neurologist') ? 'selected' : ''; ?>>Neurologist</option>
                        <option value="Orthopedic" <?php echo ($sel_spec === 'Orthopedic') ? 'selected' : ''; ?>>Orthopedic</option>
                        <option value="ENT Specialist" <?php echo ($sel_spec === 'ENT Specialist') ? 'selected' : ''; ?>>ENT Specialist</option>
                        <option value="Gastroenterologist" <?php echo ($sel_spec === 'Gastroenterologist') ? 'selected' : ''; ?>>Gastroenterologist</option>
                        <option value="Pulmonologist" <?php echo ($sel_spec === 'Pulmonologist') ? 'selected' : ''; ?>>Pulmonologist</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="clinic_address">Clinic Address</label>
                        <input type="text" id="clinic_address" name="clinic_address" 
                               placeholder="e.g., Suite 402, Medical Complex" 
                               value="<?php echo htmlspecialchars($_POST['clinic_address'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               placeholder="e.g., Khairpur" 
                               value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="consultation_fee">Consultation Fee (PKR)</label>
                    <input type="number" id="consultation_fee" name="consultation_fee" 
                           placeholder="Default: 500" value="<?php echo htmlspecialchars($_POST['consultation_fee'] ?? '500'); ?>" min="0" step="50">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="available_from">Available From</label>
                        <input type="time" id="available_from" name="available_from" value="<?php echo htmlspecialchars($_POST['available_from'] ?? '09:00'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="available_to">Available To</label>
                        <input type="time" id="available_to" name="available_to" value="<?php echo htmlspecialchars($_POST['available_to'] ?? '17:00'); ?>">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-block mt-2">
                🚀 Create Account
            </button>
        </form>

        <!-- Link to login page -->
        <div class="auth-footer">
            Already have an account? <a href="login.php"><strong>Login here</strong></a>
        </div>

    </div>
</div>

<!-- =====================================================
     JAVASCRIPT — Client-Side Validation & Role Toggle
     ===================================================== -->
<script>
/**
 * Toggle password field visibility (show/hide text)
 */
function togglePassword(fieldId, iconElement) {
    var field = document.getElementById(fieldId);
    if (field) {
        if (field.type === "password") {
            field.type = "text";
            iconElement.textContent = "🙈";
        } else {
            field.type = "password";
            iconElement.textContent = "👁️";
        }
    }
}

/**
 * Toggle visibility of role-specific form fields
 * Called when user clicks "Patient" or "Doctor" radio button
 */
function toggleRoleFields() {
    // Get references to the field sections
    var patientFields = document.getElementById('patient_fields');
    var doctorFields  = document.getElementById('doctor_fields');

    // Check which radio button is selected
    var isPatient = document.getElementById('role_patient').checked;

    if (isPatient) {
        // Show patient fields, hide doctor fields
        patientFields.classList.add('active');
        doctorFields.classList.remove('active');
    } else {
        // Show doctor fields, hide patient fields
        patientFields.classList.remove('active');
        doctorFields.classList.add('active');
    }
}

/**
 * Validate form before submission
 * Returns true if valid (form submits), false if invalid (form blocked)
 */
function validateForm() {
    var role = document.querySelector('input[name="role"]:checked').value;
    var phone = document.getElementById('phone').value.trim();
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;

    // --- Validate phone number ---
    // Pakistani phone format: 03XX-XXXXXXX (11 digits starting with 03)
    var phoneRegex = /^03[0-9]{9}$/;
    if (!phoneRegex.test(phone)) {
        alert('Please enter a valid Pakistani phone number (11 digits starting with 03).\nExample: 03001234567');
        return false;  // Block form submission
    }

    // --- Validate password match ---
    if (password !== confirm) {
        alert('Passwords do not match. Please re-enter.');
        return false;
    }

    // --- Validate password length ---
    if (password.length < 6) {
        alert('Password must be at least 6 characters long.');
        return false;
    }

    // --- Patient-specific validation ---
    if (role === 'patient') {
        var picInput = document.getElementById('profile_picture');
        if (!picInput || !picInput.files || picInput.files.length === 0) {
            alert('Profile picture is mandatory for patient registration. Please select a photo.');
            return false;
        }

        var age = parseInt(document.getElementById('age').value);
        var cnic = document.getElementById('cnic').value.trim();

        // Age validation: must be between 1 and 120
        if (isNaN(age) || age < 1 || age > 120) {
            alert('Please enter a valid age between 1 and 120.');
            return false;
        }

        // CNIC validation: format XXXXX-XXXXXXX-X (15 chars with dashes)
        var cnicRegex = /^[0-9]{5}-[0-9]{7}-[0-9]{1}$/;
        if (!cnicRegex.test(cnic)) {
            alert('Please enter CNIC in correct format: XXXXX-XXXXXXX-X\nExample: 35201-1234567-1');
            return false;
        }
    }

    // --- Doctor-specific validation ---
    if (role === 'doctor') {
        var specialization = document.getElementById('specialization').value;
        if (specialization === '') {
            alert('Please select a specialization.');
            return false;
        }
    }

    // All validations passed — allow form submission
    return true;
}

// Initialize the role fields display when page loads
// (in case browser auto-fills the form on refresh)
document.addEventListener('DOMContentLoaded', function() {
    toggleRoleFields();
});
</script>

</body>
</html>
