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
        if ($del_stmt) {
            $del_stmt->bind_param("s", $selector);
            $del_stmt->execute();
            $del_stmt->close();
        }
    }
}

// Invalidate all tokens for this user if session exists
if (!empty($_SESSION['user_id'])) {
    $del_all = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($del_all) {
        $del_all->bind_param("i", $_SESSION['user_id']);
        $del_all->execute();
        $del_all->close();
    }
}

// Clear remember_me cookie on browser
setcookie('remember_me', '', time() - 3600, '/', '', false, true);
unset($_COOKIE['remember_me']);

// Remove all session variables
$_SESSION = array();
session_unset();

// Delete the PHP session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session file on the server
session_destroy();

// Prevent browser from caching protected pages after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Send the user back to the login page
header("Location: login.php");

// Stop script execution
exit();
?>
