<?php
// =====================================================
// db.php — Database Connection + Session + CSRF Helpers
// =====================================================
// This file is included at the top of EVERY PHP page.
// It handles:
//   1. Starting the session (for login tracking)
//   2. Connecting to MySQL database
//   3. CSRF token generation and verification (security)
// =====================================================

// Start the session — this lets us track logged-in users across pages
// Must be called before any HTML output
session_start();

// Set timezone to Pakistan Standard Time (UTC+5) — ensures PHP's time()
// and strtotime() match MySQL's NOW() and the user's actual local clock.
// Without this, php.ini's default (Europe/Berlin, UTC+2) causes a 3-hour
// mismatch that breaks appointment time comparisons.
date_default_timezone_set('Asia/Karachi');

// Prevent browser caching of sensitive pages (forces browser to fetch fresh page on BACK button / logout)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// =====================================================
// DATABASE CONNECTION SETTINGS (XAMPP defaults)
// =====================================================
$host     = "localhost";        // XAMPP MySQL runs on localhost
$username = "root";             // Default XAMPP username
$password = "";                 // Default XAMPP has no password
$database = "healthcare_system"; // Our database name from schema.sql

// Create the MySQLi connection object
$conn = new mysqli($host, $username, $password, $database);

// Check if the connection failed — stop the page if it did
if ($conn->connect_error) {
    // die() stops the script and shows the error message
    die("Database Connection Failed: " . $conn->connect_error);
}

// =====================================================
// REMEMBER ME COOKIE AUTO-LOGIN
// If user is NOT logged in via session, but has a valid 30-day remember_me cookie:
// Validate selector + validator against remember_tokens table and auto-login
// =====================================================
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
    $cookie_parts = explode(':', $_COOKIE['remember_me']);
    if (count($cookie_parts) === 2) {
        $selector  = $cookie_parts[0];
        $validator = $cookie_parts[1];

        // Look up unexpired token by selector
        $token_stmt = $conn->prepare(
            "SELECT token_id, user_id, hashed_validator FROM remember_tokens 
             WHERE selector = ? AND expires_at > NOW()"
        );
        $token_stmt->bind_param("s", $selector);
        $token_stmt->execute();
        $token_row = $token_stmt->get_result()->fetch_assoc();
        $token_stmt->close();

        if ($token_row && password_verify($validator, $token_row['hashed_validator'])) {
            // Token is valid! Fetch user and set session variables
            $user_stmt = $conn->prepare("SELECT user_id, full_name, email, role, status FROM users WHERE user_id = ?");
            $user_stmt->bind_param("i", $token_row['user_id']);
            $user_stmt->execute();
            $user = $user_stmt->get_result()->fetch_assoc();
            $user_stmt->close();

            if ($user && isset($user['status']) && $user['status'] === 'active') {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];
            } else {
                // Suspended or invalid user account — clear remember_me cookie
                setcookie('remember_me', '', time() - 3600, '/');
            }
        } else {
            // Bad/expired token — clear cookie
            setcookie('remember_me', '', time() - 3600, '/');
        }
    }
}

// =====================================================
// ACTIVE SESSION SUSPENSION CHECK
// Runs on every page load where a user session exists.
// If an admin suspends a user while they are logged in,
// immediately destroy their session, clear cookies, and
// redirect them to login.php?suspended=1.
// =====================================================
if (isset($_SESSION['user_id']) && !defined('IS_STATUS_CHECK_API')) {
    $sus_stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
    if ($sus_stmt) {
        $sus_stmt->bind_param("i", $_SESSION['user_id']);
        $sus_stmt->execute();
        $sus_res = $sus_stmt->get_result()->fetch_assoc();
        $sus_stmt->close();

        if ($sus_res && isset($sus_res['status']) && $sus_res['status'] === 'suspended') {
            $user_id_to_clear = $_SESSION['user_id'];
            
            // Delete remember tokens for this user
            $del_tok = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            if ($del_tok) {
                $del_tok->bind_param("i", $user_id_to_clear);
                $del_tok->execute();
                $del_tok->close();
            }

            // Clear session data & destroy session
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_unset();
            session_destroy();

            // Clear remember_me cookie
            setcookie('remember_me', '', time() - 3600, '/');

            // Redirect immediately to login.php?suspended=1
            header("Location: login.php?suspended=1");
            exit();
        }
    }
}

// =====================================================
// CSRF TOKEN FUNCTIONS
// =====================================================
// CSRF (Cross-Site Request Forgery) tokens prevent attackers
// from tricking users into submitting forms on other websites.
// How it works:
//   1. We generate a random token and store it in the session
//   2. We put the token in a hidden input field in every form
//   3. When the form is submitted, we check the token matches
//   4. If it doesn't match, we reject the request
// =====================================================

/**
 * Generate a CSRF token and store it in the session
 * If a token already exists, return the existing one
 * 
 * @return string The CSRF token (64-character hex string)
 */
function generateCsrfToken() {
    // Only generate a new token if one doesn't exist yet
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes(32) generates 32 random bytes
        // bin2hex() converts those bytes to a 64-character hex string
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify that a submitted CSRF token matches the one in the session
 * Uses hash_equals() for timing-safe comparison (prevents timing attacks)
 * 
 * @param string $token The token submitted from the form
 * @return bool True if valid, false if invalid
 */
function verifyCsrfToken($token) {
    // Check that both the session token exists AND it matches the submitted one
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect user to login page if they're not logged in
 * Call this at the top of any page that requires authentication
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit(); // Stop script execution after redirect
    }
}

/**
 * Check if the logged-in user has a specific role
 * Redirects to dashboard if they don't have permission
 * 
 * @param string|array $allowed_roles Single role string or array of allowed roles
 */
function requireRole($allowed_roles) {
    // Convert single role string to array for consistent handling
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    // If user's role is not in the allowed list, redirect them
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: dashboard.php");
        exit();
    }
}

/**
 * Log user action into activity_log table
 * 
 * @param int $user_id
 * @param string $action
 * @param string|null $details
 */
function logActivity($user_id, $action, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}
?>
