<?php
// =====================================================
// login.php — User Login Page
// =====================================================
// This page allows users (Patient, Doctor, Admin) to log in.
// Features:
//   - CSRF token protection
//   - Password verification using password_verify() — compares against bcrypt hash
//   - Sets session variables on successful login (user_id, full_name, role)
//   - Redirects to dashboard.php based on role
//   - Prepared statements for SQL injection prevention
//   - htmlspecialchars() on all output for XSS prevention
// =====================================================

// Include database connection and session/CSRF helpers
require 'db.php';

// If user is already logged in and requesting via GET, redirect them to dashboard.
// If submitting credentials via POST, allow the login process to execute and override any stale session.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize error and success message variables
$error = '';
$success = '';

// Determine welcome heading and subtext based on how the user arrived
if (isset($_GET['suspended']) && $_GET['suspended'] === '1') {
    $error = "Your account was suspended. Please contact admin for assistance.";
    $welcomeHeading = "Account Suspended";
    $welcomeSubtext = "Please contact administrator for help.";
} elseif (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success = "Registration successful! Please log in with your new account.";
    $welcomeHeading = "Welcome!";
    $welcomeSubtext = "Your account has been created. Please log in below.";
} elseif (isset($_GET['reset']) && $_GET['reset'] === '1') {
    $success = "Password reset successful! Please log in with your new password.";
    $welcomeHeading = "Welcome!";
    $welcomeSubtext = "Please log in with your new password.";
} else {
    $welcomeHeading = "Welcome Back";
    $welcomeSubtext = "Please log in to continue.";
}

// =====================================================
// HANDLE FORM SUBMISSION (POST request)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Step 1: Verify CSRF token ---
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please refresh and try again.";
    } else {

        // --- Step 2: Get form data ---
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // --- Step 3: Validate inputs aren't empty ---
        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {

            // --- Step 4: Look up user by email ---
            // We use a prepared statement to prevent SQL injection
            // We SELECT the hashed password to verify it with password_verify()
            $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role, status FROM users WHERE email = ?");
            $stmt->bind_param("s", $email); // "s" means string parameter
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // User found — get their data
                $user = $result->fetch_assoc();

                // --- Step 5: Verify password ---
                // password_verify() compares the plain-text password against the bcrypt hash
                // It returns true if they match, false otherwise
                if (password_verify($password, $user['password'])) {

                    // --- Check if account is suspended ---
                    if (isset($user['status']) && $user['status'] === 'suspended') {
                        $error = "Your account has been suspended. Contact admin for assistance.";
                    } else {
                        // --- Step 6: Regenerate session & set fresh session variables ---
                        // Prevent session fixation & wipe out any old account's session data
                        session_regenerate_id(true);
                        $_SESSION = array();

                        $_SESSION['user_id']   = (int)$user['user_id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email']     = $user['email'];
                        $_SESSION['role']      = $user['role'];

                        // Log activity
                        logActivity($user['user_id'], 'Logged In');

                        // --- Remember Me Cookie Token Generator ---
                        if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
                            $selector  = bin2hex(random_bytes(8));
                            $validator = bin2hex(random_bytes(32));
                            $hashed_validator = password_hash($validator, PASSWORD_DEFAULT);
                            $expires_at = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30 days

                            $rem_stmt = $conn->prepare(
                                "INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at)
                                 VALUES (?, ?, ?, ?)"
                            );
                            $rem_stmt->bind_param("isss", $user['user_id'], $selector, $hashed_validator, $expires_at);
                            $rem_stmt->execute();
                            $rem_stmt->close();

                            // Set HttpOnly 30-day cookie
                            setcookie('remember_me', $selector . ':' . $validator, time() + 30 * 24 * 60 * 60, '/', '', false, true);
                        } else {
                            // If Remember Me was not checked, invalidate any existing remember_me cookie
                            // so a previously remembered account (e.g. patient) does not linger in browser
                            if (!empty($_COOKIE['remember_me'])) {
                                $cookie_parts = explode(':', $_COOKIE['remember_me']);
                                if (count($cookie_parts) === 2) {
                                    $del_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
                                    $del_stmt->bind_param("s", $cookie_parts[0]);
                                    $del_stmt->execute();
                                    $del_stmt->close();
                                }
                            }
                            setcookie('remember_me', '', time() - 3600, '/', '', false, true);
                            unset($_COOKIE['remember_me']);
                        }

                        // Clear the CSRF token so a fresh one is generated next time
                        unset($_SESSION['csrf_token']);

                        // --- Step 7: Redirect to dashboard ---
                        // All roles go to dashboard.php which shows role-specific content
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    // Password doesn't match the hash in database
                    $error = "Invalid email or password.";
                }
            } else {
                // No user found with this email
                // We show the same generic error to prevent email enumeration
                // (i.e., attackers can't tell if an email exists or not)
                $error = "Invalid email or password.";
            }

            $stmt->close();
        }
    }

    // Regenerate CSRF token after failed attempt
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
    <meta name="description" content="Login to Smart Healthcare System - Manage appointments, diagnosis, and billing">
    <title>Login — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <!-- Page Header with Logo -->
        <div class="auth-header">
            <span class="logo-icon">🏥</span>
            <h1><?php echo htmlspecialchars($welcomeHeading); ?></h1>
            <p><?php echo htmlspecialchars($welcomeSubtext); ?></p>
        </div>

        <!-- Show success message if redirected from registration -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Show error message if login failed -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- =====================================================
             LOGIN FORM
             - CSRF token in hidden field
             - Email and password fields
             - Submits to itself (POST)
             ===================================================== -->
        <form method="POST" action="" autocomplete="off">

            <!-- CSRF Token — hidden input for security -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <!-- Email Field -->
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                       value="<?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? htmlspecialchars($_POST['email'] ?? '') : ''; ?>" required autofocus>
            </div>

            <!-- Password Field with Forgot Password Link -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label for="password" style="margin-bottom: 0;">Password <span class="required">*</span></label>
                    <a href="forgot-password.php" style="font-size: 0.85rem;">Forgot Password?</a>
                </div>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="toggle-eye" onclick="togglePassword('password', this)" tabindex="-1">👁️</button>
                </div>
            </div>

            <!-- Remember Me Checkbox -->
            <div class="form-group" style="margin-top: 0.75rem; margin-bottom: 1.25rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer; color: var(--gray-700); font-size: 0.9rem;">
                    <input type="checkbox" id="remember_me" name="remember_me" value="1" style="width: auto; margin: 0;">
                    <span>Remember Me (Keep me logged in for 30 days)</span>
                </label>
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn btn-primary btn-block mt-2">
                🔐 Login
            </button>
        </form>

        <!-- Link to registration page -->
        <div class="auth-footer">
            Don't have an account? <a href="register.php"><strong>Register here</strong></a>
        </div>

    </div>
</div>

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
</script>

</body>
</html>
