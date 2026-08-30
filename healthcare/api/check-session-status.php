<?php
// =====================================================
// check-session-status.php — Real-time Session Status Polling API
// =====================================================
// Endpoint polled every 20s by protected pages to detect real-time
// account suspensions without requiring manual page navigation.
// =====================================================

define('IS_STATUS_CHECK_API', true);

require_once 'db.php';

header('Content-Type: application/json');

// If user is not logged in via session, return unauthenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'unauthenticated']);
    exit();
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error']);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

$status = $res['status'] ?? 'unknown';

if ($status === 'suspended') {
    $user_id_to_clear = $_SESSION['user_id'];

    // Delete remember tokens for this user
    $del_tok = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($del_tok) {
        $del_tok->bind_param("i", $user_id_to_clear);
        $del_tok->execute();
        $del_tok->close();
    }

    // Clear session & cookies
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

    echo json_encode(['status' => 'suspended']);
    exit();
}

echo json_encode(['status' => 'active']);
exit();
