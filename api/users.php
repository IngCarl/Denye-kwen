<?php
/**
 * DENYE KWEN - API Itilizatè (CRUD)
 * Aksè: Admin sèlman (Odite li sèlman)
 */
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {

    case 'GET':
        checkRole(['Admin', 'Odite']);
        $rows = $db->query(
            "SELECT id, non_konple, username, email, telefon, role, aktif, dernye_koneksyon, dat_kreye
             FROM users ORDER BY id"
        )->fetchAll();
        response(true, $rows);
        break;

    case 'POST':
        checkRole(['Admin']);
        $d = getInput();
        if (empty($d['non_konple']) || empty($d['username']) || empty($d['password']) || empty($d['role'])) {
            response(false, null, "Non konplè, username, modpas ak wòl obligatwa.", 400);
        }
        if (!in_array($d['role'], ['Admin','Kesye','Odite'])) response(false, null, "Wòl pa valid.", 400);
        if (strlen($d['password']) < 6) response(false, null, "Modpas dwe gen omwen 6 karaktè.", 400);

        try {
            $stmt = $db->prepare(
                "INSERT INTO users (non_konple, username, email, telefon, password, role)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                clean($d['non_konple']),
                clean($d['username']),
                clean($d['email'] ?? ''),
                clean($d['telefon'] ?? ''),
                password_hash($d['password'], PASSWORD_BCRYPT),
                $d['role']
            ]);
            $id = $db->lastInsertId();
            logActivity('KREYE', 'Itilizatè', "Nouvo itilizatè: " . clean($d['username']) . " ({$d['role']})");
            response(true, ['id' => $id], "Itilizatè kreye avèk siksè!");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) response(false, null, "Username sa a egziste deja.", 409);
            response(false, null, "Erè: " . $e->getMessage(), 500);
        }
        break;

    case 'PUT':
        $me = checkRole(['Admin']);
        $d = getInput();
        if (empty($d['id'])) response(false, null, "ID obligatwa.", 400);

        try {
            if (!empty($d['password'])) {
                if (strlen($d['password']) < 6) response(false, null, "Modpas dwe gen omwen 6 karaktè.", 400);
                $stmt = $db->prepare(
                    "UPDATE users SET non_konple=?, username=?, email=?, telefon=?, role=?, aktif=?, password=? WHERE id=?"
                );
                $stmt->execute([
                    clean($d['non_konple']), clean($d['username']), clean($d['email'] ?? ''),
                    clean($d['telefon'] ?? ''), $d['role'], (int)($d['aktif'] ?? 1),
                    password_hash($d['password'], PASSWORD_BCRYPT), (int)$d['id']
                ]);
            } else {
                $stmt = $db->prepare(
                    "UPDATE users SET non_konple=?, username=?, email=?, telefon=?, role=?, aktif=? WHERE id=?"
                );
                $stmt->execute([
                    clean($d['non_konple']), clean($d['username']), clean($d['email'] ?? ''),
                    clean($d['telefon'] ?? ''), $d['role'], (int)($d['aktif'] ?? 1), (int)$d['id']
                ]);
            }
            logActivity('MODIFYE', 'Itilizatè', "Itilizatè #{$d['id']} modifye");
            response(true, null, "Itilizatè modifye avèk siksè!");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) response(false, null, "Username sa a egziste deja.", 409);
            response(false, null, "Erè: " . $e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        $me = checkRole(['Admin']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) response(false, null, "ID obligatwa.", 400);
        if ($id === (int)$me['id']) response(false, null, "Ou pa ka efase pwòp kont ou.", 400);
        $db->prepare("UPDATE users SET aktif = 0 WHERE id = ?")->execute([$id]);
        logActivity('EFASE', 'Itilizatè', "Itilizatè #$id dezaktive");
        response(true, null, "Itilizatè dezaktive avèk siksè!");
        break;

    default:
        response(false, null, "Metòd pa sipòte.", 405);
}
