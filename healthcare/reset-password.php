<?php
// =====================================================
// reset-password.php — Set New Password
// =====================================================
// Step 2 of the password reset process.
// Only accessible if identity was verified in forgot-password.php
// (tracked via $_SESSION['reset_user_id']).
//
// FEATURES & SECURITY:
//   - Redirects to forgot-password.php if accessed directly
//   - Validates password length (min 6 characters) and match
//   - Updates user password with password_hash() (bcrypt)
//   - Eye icon visibility toggle on password fields
//   - Clears reset session variable and redirects to login.php?reset=1
// =====================================================

require 'db.php';

// Prevent direct access — user MUST have verified identity via forgot-password.php
if (!isset($_SESSION['reset_user_id'])) {
    header("Location: forgot-password.php");
    exit();
}

$error = '';
$user_id = $_SESSION['reset_user_id'];

// =====================================================
// HANDLE FORM SUBMISSION (POST)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($password) || empty($confirm)) {
            $error = "Please fill in both password fields.";
        } elseif (strlen($password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match. Please try again.";
        } else {

            // --- Check if new password is the same as current old password ---
            $check_stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $user_data = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($user_data && password_verify($password, $user_data['password'])) {
                $error = "New password cannot be the same as your old password. Please choose a different password.";
            } else {

                // Hash the new password with bcrypt
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Update user password in database
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);

                if ($stmt->execute()) {
                    // Clear the temporary reset user session variable
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['csrf_token']);

                    // Redirect to login page with reset success flag
                    header("Location: login.php?reset=1");
                    exit();
                } else {
                    $error = "Failed to update password. Please try again.";
                }
                $stmt->close();
            }
        }
    }
    unset($_SESSION['csrf_token']);
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <div class="auth-header">
            <span class="logo-icon">🔒</span>
            <h1>Set New Password</h1>
            <p>Enter your new password below</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="password">New Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Min. 6 characters" required autofocus>
                    <button type="button" class="toggle-eye" onclick="togglePassword('password', this)" tabindex="-1">👁️</button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                    <button type="button" class="toggle-eye" onclick="togglePassword('confirm_password', this)" tabindex="-1">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-2">
                💾 Update Password
            </button>
        </form>

        <div class="auth-footer">
            <a href="login.php">Cancel & Return to Login</a>
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
