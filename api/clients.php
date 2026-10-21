<?php
/**
 * DENYE KWEN - API Kliyan (CRUD)
 * Aksè: Admin + Kesye (Odite li sèlman)
 */
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {

    case 'GET':
        checkAuth();
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM clients WHERE id = ? AND aktif = 1");
            $stmt->execute([(int)$_GET['id']]);
            $row = $stmt->fetch();
            $row ? response(true, $row) : response(false, null, "Kliyan pa jwenn.", 404);
        }
        $rows = $db->query("SELECT * FROM clients WHERE aktif = 1 ORDER BY non")->fetchAll();
        response(true, $rows);
        break;

    case 'POST':
        checkRole(['Admin', 'Kesye']);
        $d = getInput();
        if (empty($d['non'])) response(false, null, "Non kliyan obligatwa.", 400);

        $stmt = $db->prepare(
            "INSERT INTO clients (non, telefon, email, adres, vil, tip) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            clean($d['non']),
            clean($d['telefon'] ?? ''),
            clean($d['email'] ?? ''),
            clean($d['adres'] ?? ''),
            clean($d['vil'] ?? ''),
            in_array($d['tip'] ?? '', ['regilye','vip','gwo']) ? $d['tip'] : 'regilye'
        ]);
        $id = $db->lastInsertId();
        logActivity('KREYE', 'Kliyan', "Nouvo kliyan: " . clean($d['non']));
        response(true, ['id' => $id], "Kliyan ajoute avèk siksè!");
        break;

    case 'PUT':
        checkRole(['Admin', 'Kesye']);
        $d = getInput();
        if (empty($d['id'])) response(false, null, "ID obligatwa.", 400);

        $stmt = $db->prepare(
            "UPDATE clients SET non=?, telefon=?, email=?, adres=?, vil=?, tip=? WHERE id=?"
        );
        $stmt->execute([
            clean($d['non']),
            clean($d['telefon'] ?? ''),
            clean($d['email'] ?? ''),
            clean($d['adres'] ?? ''),
            clean($d['vil'] ?? ''),
            in_array($d['tip'] ?? '', ['regilye','vip','gwo']) ? $d['tip'] : 'regilye',
            (int)$d['id']
        ]);
        logActivity('MODIFYE', 'Kliyan', "Kliyan #{$d['id']} modifye");
        response(true, null, "Kliyan modifye avèk siksè!");
        break;

    case 'DELETE':
        checkRole(['Admin']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) response(false, null, "ID obligatwa.", 400);
        $db->prepare("UPDATE clients SET aktif = 0 WHERE id = ?")->execute([$id]);
        logActivity('EFASE', 'Kliyan', "Kliyan #$id efase");
        response(true, null, "Kliyan efase avèk siksè!");
        break;

    default:
        response(false, null, "Metòd pa sipòte.", 405);
}
