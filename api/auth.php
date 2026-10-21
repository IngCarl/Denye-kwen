<?php
/**
 * DENYE KWEN - API Otantifikasyon
 * Aksyon: login, logout, check
 */
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ---------- KONEKTE ----------
    case 'login':
        if ($method !== 'POST') response(false, null, "Metòd pa valid", 405);
        $input = getInput();
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if ($username === '' || $password === '') {
            response(false, null, "Tanpri antre non itilizatè ak modpas.", 400);
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND aktif = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            response(false, null, "Non itilizatè oswa modpas pa kòrèk.", 401);
        }

        // Kreye sesyon
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['non_konple'] = $user['non_konple'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        // Mete ajou dènye koneksyon
        $db->prepare("UPDATE users SET dernye_koneksyon = NOW() WHERE id = ?")->execute([$user['id']]);

        logActivity('KONEKSYON', 'Otantifikasyon', "Itilizatè {$user['username']} konekte");

        response(true, [
            'id' => $user['id'],
            'non_konple' => $user['non_konple'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ], "Byenveni, {$user['non_konple']}!");
        break;

    // ---------- DEKONEKTE ----------
    case 'logout':
        if (!empty($_SESSION['user_id'])) {
            logActivity('DEKONEKSYON', 'Otantifikasyon', "Itilizatè {$_SESSION['username']} dekonekte");
        }
        session_unset();
        session_destroy();
        response(true, null, "Ou dekonekte avèk siksè.");
        break;

    // ---------- VERIFYE SESYON ----------
    case 'check':
        $user = checkAuth();
        response(true, $user, "Sesyon aktif.");
        break;

    default:
        response(false, null, "Aksyon pa rekonèt.", 400);
}
