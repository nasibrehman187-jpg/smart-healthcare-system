<?php
// =====================================================
// db.php — Database Connection + Session + CSRF Helpers
// =====================================================
// Standard MySQLi database connection for InfinityFree & XAMPP
// =====================================================

// Start native PHP session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Pakistan Standard Time (UTC+5)
date_default_timezone_set('Asia/Karachi');

// Prevent browser caching of sensitive pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// =====================================================
// DATABASE CONNECTION SETTINGS
// Configure with your InfinityFree MySQL details (or XAMPP defaults)
// =====================================================
$host     = getenv('DB_HOST') ?: "localhost";          // e.g. sqlXXX.infinityfree.com
$username = getenv('DB_USER') ?: "root";               // e.g. if0_XXXXXXXX
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ""; // Hosting account password
$database = getenv('DB_NAME') ?: "healthcare_system";  // e.g. if0_XXXXXXXX_healthcare
$port     = getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306;

// Create the MySQLi connection object
$conn = @new mysqli($host, $username, $password, $database, $port);

// Check if the connection failed
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set UTF-8 character encoding
$conn->set_charset("utf8mb4");

// =====================================================
// REMEMBER ME COOKIE AUTO-LOGIN
// =====================================================
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
    $cookie_parts = explode(':', $_COOKIE['remember_me']);
    if (count($cookie_parts) === 2) {
        $selector  = $cookie_parts[0];
        $validator = $cookie_parts[1];

        $token_stmt = $conn->prepare(
            "SELECT token_id, user_id, hashed_validator FROM remember_tokens 
             WHERE selector = ? AND expires_at > NOW()"
        );
        if ($token_stmt) {
            $token_stmt->bind_param("s", $selector);
            $token_stmt->execute();
            $token_row = $token_stmt->get_result()->fetch_assoc();
            $token_stmt->close();

            if ($token_row && password_verify($validator, $token_row['hashed_validator'])) {
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
                    setcookie('remember_me', '', time() - 3600, '/');
                }
            } else {
                setcookie('remember_me', '', time() - 3600, '/');
            }
        }
    }
}

// =====================================================
// ACTIVE SESSION SUSPENSION CHECK
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
            
            $del_tok = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            if ($del_tok) {
                $del_tok->bind_param("i", $user_id_to_clear);
                $del_tok->execute();
                $del_tok->close();
            }

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

            setcookie('remember_me', '', time() - 3600, '/');

            header("Location: login.php?suspended=1");
            exit();
        }
    }
}

// =====================================================
// CSRF & ACCESS CONTROL HELPER FUNCTIONS
// =====================================================

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header("Location: dashboard.php");
        exit();
    }
}

function logActivity($user_id, $action, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}
