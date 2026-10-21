<?php
/**
 * DENYE KWEN - API Founisè (CRUD)
 * Aksè: Admin sèlman (Odite li sèlman)
 */
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {

    case 'GET':
        checkRole(['Admin', 'Odite']);
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ? AND aktif = 1");
            $stmt->execute([(int)$_GET['id']]);
            $row = $stmt->fetch();
            $row ? response(true, $row) : response(false, null, "Founisè pa jwenn.", 404);
        }
        $rows = $db->query("SELECT * FROM suppliers WHERE aktif = 1 ORDER BY non")->fetchAll();
        response(true, $rows);
        break;

    case 'POST':
        checkRole(['Admin']);
        $d = getInput();
        if (empty($d['non'])) response(false, null, "Non founisè obligatwa.", 400);

        $stmt = $db->prepare(
            "INSERT INTO suppliers (non, konpayi, telefon, email, adres, nif) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            clean($d['non']),
            clean($d['konpayi'] ?? ''),
            clean($d['telefon'] ?? ''),
            clean($d['email'] ?? ''),
            clean($d['adres'] ?? ''),
            clean($d['nif'] ?? '')
        ]);
        $id = $db->lastInsertId();
        logActivity('KREYE', 'Founisè', "Nouvo founisè: " . clean($d['non']));
        response(true, ['id' => $id], "Founisè ajoute avèk siksè!");
        break;

    case 'PUT':
        checkRole(['Admin']);
        $d = getInput();
        if (empty($d['id'])) response(false, null, "ID obligatwa.", 400);

        $stmt = $db->prepare(
            "UPDATE suppliers SET non=?, konpayi=?, telefon=?, email=?, adres=?, nif=? WHERE id=?"
        );
        $stmt->execute([
            clean($d['non']),
            clean($d['konpayi'] ?? ''),
            clean($d['telefon'] ?? ''),
            clean($d['email'] ?? ''),
            clean($d['adres'] ?? ''),
            clean($d['nif'] ?? ''),
            (int)$d['id']
        ]);
        logActivity('MODIFYE', 'Founisè', "Founisè #{$d['id']} modifye");
        response(true, null, "Founisè modifye avèk siksè!");
        break;

    case 'DELETE':
        checkRole(['Admin']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) response(false, null, "ID obligatwa.", 400);
        $db->prepare("UPDATE suppliers SET aktif = 0 WHERE id = ?")->execute([$id]);
        logActivity('EFASE', 'Founisè', "Founisè #$id efase");
        response(true, null, "Founisè efase avèk siksè!");
        break;

    default:
        response(false, null, "Metòd pa sipòte.", 405);
}
