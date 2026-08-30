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
// DATABASE CONNECTION SETTINGS (Supabase / Postgres / MySQL)
// Supports Cloud Environment Variables (Vercel / Supabase) with XAMPP defaults
// =====================================================

$database_url = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL');
$is_pgsql = false;

if (!empty($database_url)) {
    // Parse standard PostgreSQL Connection URI: postgresql://user:pass@host:port/dbname
    $db_parts = parse_url($database_url);
    $host     = $db_parts['host'] ?? "localhost";
    $port     = $db_parts['port'] ?? 5432;
    $username = $db_parts['user'] ?? "postgres";
    $password = isset($db_parts['pass']) ? urldecode($db_parts['pass']) : "";
    $database = isset($db_parts['path']) ? ltrim($db_parts['path'], '/') : "postgres";
    $is_pgsql = true;
} else {
    $db_type  = getenv('DB_TYPE') ?: 'mysql';
    $host     = getenv('DB_HOST') ?: "localhost";
    $username = getenv('DB_USER') ?: "root";
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
    $database = getenv('DB_NAME') ?: "healthcare_system";
    $port     = getenv('DB_PORT') ? intval(getenv('DB_PORT')) : ($db_type === 'pgsql' ? 5432 : 3306);
    $is_pgsql = ($db_type === 'pgsql' || strpos($host, 'supabase') !== false || $port == 5432 || $port == 6543);
}

// =====================================================
// POSTGRESQL PDO COMPATIBILITY ADAPTER
// Provides a transparent mysqli-compatible interface over PDO pgsql
// =====================================================
if ($is_pgsql) {
    class PgSqlResult {
        private $rows;
        private $currentIndex = 0;
        public $num_rows = 0;

        public function __construct(array $rows) {
            $this->rows = $rows;
            $this->num_rows = count($rows);
        }

        public function fetch_assoc() {
            if ($this->currentIndex < $this->num_rows) {
                return $this->rows[$this->currentIndex++];
            }
            return null;
        }

        public function fetch_all($mode = MYSQLI_NUM) {
            return $this->rows;
        }
    }

    class PgSqlStmt {
        private $pdo;
        private $sql;
        private $stmt;
        private $params = [];
        public $affected_rows = 0;
        public $error = '';

        public function __construct($pdo, $sql) {
            $this->pdo = $pdo;
            $this->sql = $sql;
            $this->stmt = $pdo->prepare($sql);
        }

        public function bind_param($types, &...$vars) {
            $this->params = [];
            foreach ($vars as $key => &$val) {
                $typeChar = $types[$key] ?? 's';
                $pdoType = PDO::PARAM_STR;
                if ($typeChar === 'i') $pdoType = PDO::PARAM_INT;
                elseif ($typeChar === 'b') $pdoType = PDO::PARAM_BOOL;
                $this->params[] = [
                    'value' => &$val,
                    'type'  => $pdoType
                ];
            }
            return true;
        }

        public function execute($params = null) {
            try {
                if ($params !== null) {
                    $res = $this->stmt->execute($params);
                } elseif (!empty($this->params)) {
                    $flatParams = [];
                    foreach ($this->params as $idx => $p) {
                        $this->stmt->bindValue($idx + 1, $p['value'], $p['type']);
                    }
                    $res = $this->stmt->execute();
                } else {
                    $res = $this->stmt->execute();
                }
                $this->affected_rows = $this->stmt->rowCount();
                return $res;
            } catch (Exception $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function get_result() {
            $rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            return new PgSqlResult($rows);
        }

        public function close() {
            $this->stmt = null;
            return true;
        }
    }

    class PgSqlDbAdapter {
        public $pdo;
        public $insert_id = 0;
        public $affected_rows = 0;
        public $connect_error = null;
        public $error = '';

        public function __construct($host, $username, $password, $database, $port = 5432) {
            try {
                $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode=require";
                $this->pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false
                ]);
            } catch (PDOException $e) {
                // Fallback without sslmode if connecting to local postgres
                try {
                    $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
                    $this->pdo = new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                } catch (PDOException $e2) {
                    $this->connect_error = $e2->getMessage();
                }
            }
        }

        public function query($sql) {
            try {
                $stmt = $this->pdo->query($sql);
                if (stripos(trim($sql), 'SELECT') === 0 || stripos(trim($sql), 'SHOW') === 0) {
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    return new PgSqlResult($rows);
                }
                $this->affected_rows = $stmt->rowCount();
                return true;
            } catch (Exception $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function prepare($sql) {
            try {
                // Automatically handle last insert ID for Postgres when doing INSERT
                return new PgSqlStmt($this->pdo, $sql);
            } catch (Exception $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function __get($name) {
            if ($name === 'insert_id') {
                try {
                    return (int)$this->pdo->lastInsertId();
                } catch (Exception $e) {
                    return 0;
                }
            }
            return null;
        }

        public function close() {
            $this->pdo = null;
            return true;
        }
    }

    $conn = new PgSqlDbAdapter($host, $username, $password, $database, $port);
} else {
    // Default XAMPP MySQLi Connection
    $conn = new mysqli($host, $username, $password, $database, $port);
}

// Check if the connection failed — stop the page if it did
if ($conn->connect_error) {
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
