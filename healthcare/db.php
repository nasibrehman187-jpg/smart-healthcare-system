<?php
// =====================================================
// db.php — Database Connection + DB Sessions + CSRF Helpers
// =====================================================
// Supports both Supabase (PostgreSQL via PDO) & XAMPP (MySQLi)
// =====================================================

// Set timezone to Pakistan Standard Time (UTC+5)
date_default_timezone_set('Asia/Karachi');

// Prevent browser caching of sensitive pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// =====================================================
// DATABASE CONNECTION SETTINGS (Supabase / Postgres / MySQL)
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

// Check if the connection failed
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// =====================================================
// DATABASE-BACKED SESSION HANDLER
// Solves Vercel serverless session statelessness by storing
// active session data directly in the database (php_sessions table).
// =====================================================
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $conn;
    private $is_pgsql;

    public function __construct($conn, $is_pgsql) {
        $this->conn = $conn;
        $this->is_pgsql = $is_pgsql;
    }

    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName) {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id) {
        try {
            $stmt = $this->conn->prepare("SELECT data FROM php_sessions WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && ($row = $res->fetch_assoc())) {
                    return (string)$row['data'];
                }
            }
        } catch (Exception $e) {}
        return '';
    }

    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        try {
            if ($this->is_pgsql) {
                $stmt = $this->conn->prepare(
                    "INSERT INTO php_sessions (id, data, last_activity) VALUES (?, ?, CURRENT_TIMESTAMP)
                     ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = CURRENT_TIMESTAMP"
                );
            } else {
                $stmt = $this->conn->prepare(
                    "INSERT INTO php_sessions (id, data, last_activity) VALUES (?, ?, NOW())
                     ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = NOW()"
                );
            }
            if ($stmt) {
                $stmt->bind_param("ss", $id, $data);
                return (bool)$stmt->execute();
            }
        } catch (Exception $e) {}
        return false;
    }

    #[\ReturnTypeWillChange]
    public function destroy($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM php_sessions WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("s", $id);
                return (bool)$stmt->execute();
            }
        } catch (Exception $e) {}
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        try {
            if ($this->is_pgsql) {
                $this->conn->query("DELETE FROM php_sessions WHERE last_activity < (CURRENT_TIMESTAMP - INTERVAL '30 days')");
            } else {
                $this->conn->query("DELETE FROM php_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            }
            return 1;
        } catch (Exception $e) {}
        return 0;
    }
}

// Automatically register database-backed session handler if DB is connected
if ($conn && empty($conn->connect_error)) {
    // Only register DB session handler in production / serverless environment
    if ($is_pgsql || getenv('VERCEL') || getenv('USE_DB_SESSIONS')) {
        $handler = new DatabaseSessionHandler($conn, $is_pgsql);
        session_set_save_handler($handler, true);
    }
}

// Start PHP session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
