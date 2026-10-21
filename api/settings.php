<?php
/**
 * DENYE KWEN - API Paramèt
 * GET: tout wòl (POS bezwen li pou resi) | PUT: Admin sèlman
 * DELETE ?reset=1 : Zòn danje - efase tout done tranzaksyonèl
 */
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {

    case 'GET':
        checkAuth();
        $row = $db->query("SELECT * FROM settings ORDER BY id LIMIT 1")->fetch();
        response(true, $row);
        break;

    case 'PUT':
        checkRole(['Admin']);
        $d = getInput();

        $stmt = $db->prepare(
            "UPDATE settings SET business_name=?, phone=?, email=?, address=?, nif=?,
             currency=?, tax_rate=?, receipt_footer=? WHERE id = (SELECT id FROM (SELECT MIN(id) id FROM settings) x)"
        );
        $stmt->execute([
            clean($d['business_name'] ?? 'DENYE KWEN'),
            clean($d['phone'] ?? ''),
            clean($d['email'] ?? ''),
            clean($d['address'] ?? ''),
            clean($d['nif'] ?? ''),
            clean($d['currency'] ?? 'HTG'),
            (float)($d['tax_rate'] ?? 0),
            clean($d['receipt_footer'] ?? 'Mèsi pou acha ou!')
        ]);
        logActivity('MODIFYE', 'Paramèt', 'Paramèt biznis modifye');
        response(true, null, "Paramèt anrejistre avèk siksè!");
        break;

    // ---------- ZÒN DANJE: EFASE TOUT DONE ----------
    case 'DELETE':
        checkRole(['Admin']);
        if (($_GET['reset'] ?? '') !== '1') response(false, null, "Paramèt reset manke.", 400);

        try {
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            foreach (['sale_items','sales','expenses','activity_logs'] as $t) {
                $db->exec("TRUNCATE TABLE `$t`");
            }
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            logActivity('EFASE', 'Sistèm', 'TOUT done tranzaksyonèl efase (vant, depans, jounal)');
            response(true, null, "Tout done tranzaksyonèl efase. Pwodwi, kliyan ak itilizatè rete.");
        } catch (Exception $e) {
            response(false, null, "Erè: " . $e->getMessage(), 500);
        }
        break;

    default:
        response(false, null, "Metòd pa sipòte.", 405);
}
