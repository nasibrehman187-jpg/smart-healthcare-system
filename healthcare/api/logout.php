<?php
// =====================================================
// logout.php — Log Out the Current User
// =====================================================
// This script:
//   1. Invalidates and deletes the 30-day Remember Me token
//   2. Clears the remember_me cookie
//   3. Clears all session variables
//   4. Destroys the session completely
//   5. Redirects to the login page with no-cache headers
// =====================================================

require 'db.php';

// Invalidate & delete Remember Me cookie token from database if present
if (!empty($_COOKIE['remember_me'])) {
    $cookie_parts = explode(':', $_COOKIE['remember_me']);
    if (count($cookie_parts) === 2) {
        $selector = $cookie_parts[0];
        $del_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
        $del_stmt->bind_param("s", $selector);
        $del_stmt->execute();
        $del_stmt->close();
    }
    // Clear cookie on browser
    setcookie('remember_me', '', time() - 3600, '/');
}

// Remove all session variables (user_id, role, etc.)
session_unset();

// Destroy the session file on the server
session_destroy();

// Send the user back to the login page
header("Location: login.php");

// Stop script execution — important after header redirect
exit();
?>
