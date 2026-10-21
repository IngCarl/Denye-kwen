<?php
/**
 * DENYE KWEN - API Vant
 * POST: kreye vant (Kesye sèlman) ak tranzaksyon MySQL
 * GET: istorik (Admin/Odite: tout | Kesye: pwòp vant li)
 */
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {

    // ---------- LI ISTORIK ----------
    case 'GET':
        $user = checkAuth();

        // Detay yon vant ak atik li yo
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM sales WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $sale = $stmt->fetch();
            if (!$sale) response(false, null, "Vant pa jwenn.", 404);
            if ($user['role'] === 'Kesye' && $sale['user_id'] != $user['id']) {
                response(false, null, "Ou ka wè sèlman pwòp vant ou.", 403);
            }
            $stmt = $db->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $sale['items'] = $stmt->fetchAll();
            response(true, $sale);
        }

        // Estatistik kesye pou jounen an
        if (isset($_GET['kesye_stats'])) {
            if ($user['role'] !== 'Kesye') response(false, null, "Pou Kesye sèlman.", 403);
            $stmt = $db->prepare(
                "SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS tranzaksyon,
                        COUNT(DISTINCT client_id) AS kliyan
                 FROM sales WHERE user_id = ? AND DATE(dat) = CURDATE() AND estati = 'konplete'"
            );
            $stmt->execute([$user['id']]);
            response(true, $stmt->fetch());
        }

        // Lis vant
        $limit = min((int)($_GET['limit'] ?? 100), 500);
        if ($user['role'] === 'Kesye') {
            $stmt = $db->prepare("SELECT * FROM sales WHERE user_id = ? ORDER BY dat DESC LIMIT $limit");
            $stmt->execute([$user['id']]);
        } else {
            $stmt = $db->query("SELECT * FROM sales ORDER BY dat DESC LIMIT $limit");
        }
        response(true, $stmt->fetchAll());
        break;

    // ---------- KREYE VANT ----------
    case 'POST':
        $user = checkRole(['Kesye']); // SÈLMAN Kesye ka vann
        $d = getInput();

        if (empty($d['items']) || !is_array($d['items'])) {
            response(false, null, "Chariot la vid. Ajoute pwodwi anvan.", 400);
        }

        try {
            $db->beginTransaction();

            // Nimewo fakti inik: FKT-YYYYMMDD-XXXX
            $jou = date('Ymd');
            $stmt = $db->prepare("SELECT COUNT(*) c FROM sales WHERE nimewo_fakti LIKE ?");
            $stmt->execute(["FKT-$jou-%"]);
            $seq = str_pad($stmt->fetch()['c'] + 1, 4, '0', STR_PAD_LEFT);
            $nimewo = "FKT-$jou-$seq";

            // Kalkile total sou sèvè a (sekirite)
            $sou_total = 0;
            $items_ok = [];
            $checkStmt = $db->prepare("SELECT id, non, pri, stok FROM products WHERE id = ? AND aktif = 1 FOR UPDATE");

            foreach ($d['items'] as $it) {
                $pid = (int)($it['product_id'] ?? 0);
                $qty = max(1, (int)($it['kantite'] ?? 1));
                $checkStmt->execute([$pid]);
                $p = $checkStmt->fetch();
                if (!$p) throw new Exception("Pwodwi #$pid pa jwenn.");
                if ($p['stok'] < $qty) throw new Exception("Stòk pa ase pou '{$p['non']}' (rete {$p['stok']}).");
                $ltotal = $p['pri'] * $qty;
                $sou_total += $ltotal;
                $items_ok[] = ['id' => $p['id'], 'non' => $p['non'], 'qty' => $qty, 'pri' => $p['pri'], 'total' => $ltotal];
            }

            $rabe = max(0, (float)($d['rabe'] ?? 0));
            $taks = max(0, (float)($d['taks'] ?? 0));
            $total = max(0, $sou_total - $rabe + $taks);
            $kob_resevwa = (float)($d['kob_resevwa'] ?? $total);
            $monen = max(0, $kob_resevwa - $total);
            $mwayen = in_array($d['mwayen_peman'] ?? '', ['kach','moncash','natcash','kat','kredi']) ? $d['mwayen_peman'] : 'kach';

            // Kliyan
            $client_id = !empty($d['client_id']) ? (int)$d['client_id'] : null;
            $client_non = 'Kliyan Pasaj';
            if ($client_id) {
                $stmt = $db->prepare("SELECT non FROM clients WHERE id = ?");
                $stmt->execute([$client_id]);
                $c = $stmt->fetch();
                if ($c) $client_non = $c['non']; else $client_id = null;
            }

            // Anrejistre vant lan
            $stmt = $db->prepare(
                "INSERT INTO sales (nimewo_fakti, client_id, client_non, user_id, user_non,
                 sou_total, rabe, taks, total, kob_resevwa, monen_rann, mwayen_peman, estati)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'konplete')"
            );
            $stmt->execute([
                $nimewo, $client_id, $client_non, $user['id'], $user['non_konple'],
                $sou_total, $rabe, $taks, $total, $kob_resevwa, $monen, $mwayen
            ]);
            $sale_id = $db->lastInsertId();

            // Atik yo + dediksyon stòk
            $itemStmt = $db->prepare(
                "INSERT INTO sale_items (sale_id, product_id, product_non, kantite, pri_inite, total)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stockStmt = $db->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
            foreach ($items_ok as $it) {
                $itemStmt->execute([$sale_id, $it['id'], $it['non'], $it['qty'], $it['pri'], $it['total']]);
                $stockStmt->execute([$it['qty'], $it['id']]);
            }

            // Pwen fidelite (1 pwen pou chak 100 HTG)
            if ($client_id) {
                $db->prepare("UPDATE clients SET pwen_fidelite = pwen_fidelite + ? WHERE id = ?")
                   ->execute([floor($total / 100), $client_id]);
            }

            $db->commit();
            logActivity('VANT', 'POS', "Vant $nimewo - Total: $total HTG ($mwayen)");

            // Retounen vant konplè pou resi a
            $stmt = $db->prepare("SELECT * FROM sales WHERE id = ?");
            $stmt->execute([$sale_id]);
            $sale = $stmt->fetch();
            $stmt = $db->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
            $stmt->execute([$sale_id]);
            $sale['items'] = $stmt->fetchAll();

            response(true, $sale, "Vant $nimewo anrejistre avèk siksè!");

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            response(false, null, $e->getMessage(), 400);
        }
        break;

    // ---------- ANILE VANT ----------
    case 'PUT':
        checkRole(['Admin']);
        $d = getInput();
        if (empty($d['id']) || empty($d['estati'])) response(false, null, "ID ak estati obligatwa.", 400);
        if (!in_array($d['estati'], ['anile','retounen'])) response(false, null, "Estati pa valid.", 400);

        try {
            $db->beginTransaction();
            // Remete stòk la
            $stmt = $db->prepare("SELECT product_id, kantite FROM sale_items WHERE sale_id = ?");
            $stmt->execute([(int)$d['id']]);
            $back = $db->prepare("UPDATE products SET stok = stok + ? WHERE id = ?");
            foreach ($stmt->fetchAll() as $it) {
                if ($it['product_id']) $back->execute([$it['kantite'], $it['product_id']]);
            }
            $db->prepare("UPDATE sales SET estati = ? WHERE id = ?")->execute([$d['estati'], (int)$d['id']]);
            $db->commit();
            logActivity('MODIFYE', 'Vant', "Vant #{$d['id']} make kòm {$d['estati']}, stòk remete");
            response(true, null, "Vant lan make kòm {$d['estati']}.");
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            response(false, null, $e->getMessage(), 500);
        }
        break;

    default:
        response(false, null, "Metòd pa sipòte.", 405);
}
