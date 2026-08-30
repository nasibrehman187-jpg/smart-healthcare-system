<?php
// =====================================================
// forgot-password.php — Identity Verification for Password Reset
// =====================================================
// Step 1 of the simplified password reset process.
// Asks for Email + Phone Number to verify account ownership.
//
// FEATURES & SECURITY:
//   - Works for both Patients and Doctors (both have phone in users table)
//   - Generic error message prevents email/phone enumeration attacks
//   - Prepared statements + CSRF token protection
//   - On success, stores user_id in session and redirects to reset-password.php
// =====================================================

require 'db.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// =====================================================
// HANDLE FORM SUBMISSION (POST)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {

        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($email) || empty($phone)) {
            $error = "Please enter both your email address and registered phone number.";
        } else {

            // Look up user matching BOTH email and phone number
            // Prepared statement prevents SQL injection
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND phone = ?");
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Store user_id temporarily in session to allow access to reset-password.php
                $_SESSION['reset_user_id'] = $user['user_id'];
                unset($_SESSION['csrf_token']);

                // Redirect to Step 2
                header("Location: reset-password.php");
                exit();
            } else {
                // Show generic error to avoid email/phone enumeration
                $error = "No account found matching these details. Please check your email and phone number.";
            }
            $stmt->close();
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
    <title>Forgot Password — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <div class="auth-header">
            <span class="logo-icon">🔑</span>
            <h1>Forgot Password?</h1>
            <p>Verify your identity to reset your password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="email">Registered Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="phone">Registered Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" placeholder="03XXXXXXXXX"
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                <small style="color: var(--gray-500);">Enter the 11-digit phone number associated with your account.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-2">
                🔍 Verify & Proceed
            </button>
        </form>

        <div class="auth-footer">
            Remembered your password? <a href="login.php"><strong>Back to Login</strong></a>
        </div>

    </div>
</div>

</body>
</html>
