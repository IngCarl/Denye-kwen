<?php
/**
 * DENYE KWEN - API Sèvis
 * POST: Kesye anrejistre yon sèvis (ak rabè opsyonèl + dediksyon otomatik)
 * GET: Kesye wè pwòp sèvis li | Admin/Odite wè tout
 * DELETE: Admin sèlman
 */
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {

    // ---------- LI ----------
    case 'GET':
        $user = checkAuth();

        // Estatistik jounen kesye a
        if (isset($_GET['kesye_stats'])) {
            if ($user['role'] !== 'Kesye') response(false, null, "Pou Kesye sèlman.", 403);
            $stmt = $db->prepare(
                "SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS kantite
                 FROM services WHERE user_id = ? AND dat = CURDATE()"
            );
            $stmt->execute([$user['id']]);
            response(true, $stmt->fetch());
        }

        // Yon sèl sèvis (pou resi)
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $row = $stmt->fetch();
            if (!$row) response(false, null, "Sèvis pa jwenn.", 404);
            if ($user['role'] === 'Kesye' && $row['user_id'] != $user['id']) {
                response(false, null, "Ou ka wè sèlman pwòp sèvis ou.", 403);
            }
            response(true, $row);
        }

        // Lis
        $limit = min((int)($_GET['limit'] ?? 100), 500);
        if ($user['role'] === 'Kesye') {
            $stmt = $db->prepare("SELECT * FROM services WHERE user_id = ? ORDER BY dat_kreye DESC LIMIT $limit");
            $stmt->execute([$user['id']]);
        } else {
            $stmt = $db->query("SELECT * FROM services ORDER BY dat_kreye DESC LIMIT $limit");
        }
        response(true, $stmt->fetchAll());
        break;

    // ---------- KREYE ----------
    case 'POST':
        $user = checkRole(['Kesye']);
        $d = getInput();

        if (empty($d['deskripsyon']) || !isset($d['pri']) || empty($d['moun_bay_sevis']) || empty($d['dat'])) {
            response(false, null, "Deskripsyon, pri, moun kap bay sèvis la ak dat obligatwa.", 400);
        }

        $pri = (float)$d['pri'];
        if ($pri <= 0) response(false, null, "Pri sèvis la dwe pi gran pase 0.", 400);

        // Rabè opsyonèl — men si li ranpli, dediksyon fèt otomatikman
        $rabe = max(0, (float)($d['rabe'] ?? 0));
        if ($rabe > $pri) response(false, null, "Rabè a pa ka pi gran pase pri sèvis la.", 400);
        $total = $pri - $rabe;

        $mwayen = in_array($d['mwayen_peman'] ?? '', ['kach','moncash','natcash','kat']) ? $d['mwayen_peman'] : 'kach';

        // Jou a: si li pa voye, kalkile li otomatikman apati dat la (an Kreyòl)
        $jouKreyol = ['Dimanch','Lendi','Madi','Mèkredi','Jedi','Vandredi','Samdi'];
        $jou = !empty($d['jou']) ? clean($d['jou']) : $jouKreyol[(int)date('w', strtotime($d['dat']))];

        try {
            $db->beginTransaction();

            // Nimewo resi inik: SVC-YYYYMMDD-XXXX
            $jodi = date('Ymd');
            $stmt = $db->prepare("SELECT COUNT(*) c FROM services WHERE nimewo_resi LIKE ?");
            $stmt->execute(["SVC-$jodi-%"]);
            $seq = str_pad($stmt->fetch()['c'] + 1, 4, '0', STR_PAD_LEFT);
            $nimewo = "SVC-$jodi-$seq";

            $stmt = $db->prepare(
                "INSERT INTO services (nimewo_resi, deskripsyon, pri, rabe, total, moun_bay_sevis, jou, dat, mwayen_peman, user_id, user_non)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $nimewo,
                clean($d['deskripsyon']),
                $pri, $rabe, $total,
                clean($d['moun_bay_sevis']),
                $jou,
                $d['dat'],
                $mwayen,
                $user['id'],
                $user['non_konple']
            ]);
            $id = $db->lastInsertId();
            $db->commit();

            logActivity('SEVIS', 'Sèvis', "Sèvis $nimewo - " . clean($d['deskripsyon']) . " - Total: $total HTG");

            $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([$id]);
            response(true, $stmt->fetch(), "Sèvis $nimewo anrejistre avèk siksè!");

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            response(false, null, "Erè: " . $e->getMessage(), 500);
        }
        break;

    // ---------- EFASE ----------
    case 'DELETE':
        checkRole(['Admin']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) response(false, null, "ID obligatwa.", 400);
        $db->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
        logActivity('EFASE', 'Sèvis', "Sèvis #$id efase");
        response(true, null, "Sèvis efase avèk siksè!");
        break;

    default:
        response(false, null, "Metòd pa sipòte.", 405);
}
