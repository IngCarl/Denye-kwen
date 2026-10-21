<?php
/**
 * DENYE KWEN v2.0 - Koneksyon Baz Done + Fonksyon Helper
 * Konfigirasyon PDO MySQL
 */

// ---------- CORS + Sesyon ----------
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Sesyon 24 èdtan
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
if (session_status() === PHP_SESSION_NONE) session_start();

// ---------- Konfigirasyon Baz Done ----------
define('DB_HOST', 'localhost');
define('DB_NAME', 'denye-kwen');
define('DB_USER', 'root');
define('DB_PASS', '');           // Chanje si MySQL ou gen modpas

// ---------- Koneksyon PDO ----------
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            response(false, null, "Erè koneksyon baz done: " . $e->getMessage(), 500);
        }
    }
    return $pdo;
}

// ---------- Repons JSON estanda ----------
function response($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- Li kò JSON rekèt la ----------
function getInput() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

// ---------- Verifye otantifikasyon ----------
function checkAuth() {
    if (empty($_SESSION['user_id'])) {
        response(false, null, "Ou pa konekte. Tanpri konekte anvan.", 401);
    }
    // Timeout 24h
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 86400)) {
        session_unset(); session_destroy();
        response(false, null, "Sesyon ou ekspire. Konekte ankò.", 401);
    }
    $_SESSION['last_activity'] = time();
    return [
        'id' => $_SESSION['user_id'],
        'non_konple' => $_SESSION['non_konple'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ];
}

// ---------- Verifye wòl ----------
function checkRole($roles) {
    $user = checkAuth();
    if (!in_array($user['role'], (array)$roles)) {
        response(false, null, "Aksè refize. Wòl ou (" . $user['role'] . ") pa gen dwa fè aksyon sa a.", 403);
    }
    return $user;
}

// ---------- Anrejistre aktivite nan jounal ----------
function logActivity($aksyon, $modil, $deskripsyon = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO activity_logs (user_id, user_non, aksyon, modil, deskripsyon, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $_SESSION['non_konple'] ?? 'Sistèm',
            $aksyon,
            $modil,
            $deskripsyon,
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) { /* pa bloke rekèt la si log echwe */ }
}

// ---------- Netwaye done antre ----------
function clean($val) {
    return is_string($val) ? htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8') : $val;
}
