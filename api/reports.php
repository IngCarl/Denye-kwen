<?php
/**
 * DENYE KWEN - API Rapò ak Estatistik
 * Aksè: Admin + Odite (dashboard stats)
 */
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$type = $_GET['type'] ?? 'dashboard';

switch ($type) {

    // ---------- DASHBOARD ADMIN / ODITE ----------
    case 'dashboard':
        checkRole(['Admin', 'Odite']);

        // Lavant jodi a
        $jodi = $db->query(
            "SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS tranzaksyon
             FROM sales WHERE DATE(dat) = CURDATE() AND estati = 'konplete'"
        )->fetch();

        // Pwofi jodi a (pri vant - pri dacha)
        $pwofi = $db->query(
            "SELECT COALESCE(SUM((si.pri_inite - COALESCE(p.pri_dacha,0)) * si.kantite),0) AS pwofi
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             LEFT JOIN products p ON si.product_id = p.id
             WHERE DATE(s.dat) = CURDATE() AND s.estati = 'konplete'"
        )->fetch()['pwofi'];

        // Stòk ba
        $stok_ba = $db->query(
            "SELECT COUNT(*) c FROM products WHERE aktif = 1 AND stok <= min_stok"
        )->fetch()['c'];

        // Lavant 7 dènye jou
        $sevenDays = $db->query(
            "SELECT DATE(dat) AS jou, COALESCE(SUM(total),0) AS total
             FROM sales WHERE dat >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estati = 'konplete'
             GROUP BY DATE(dat) ORDER BY jou"
        )->fetchAll();

        // Pwodwi pa kategori
        $parKategori = $db->query(
            "SELECT k.non, k.icon, COUNT(p.id) AS kantite
             FROM kategori k LEFT JOIN products p ON p.kategori_id = k.id AND p.aktif = 1
             WHERE k.aktif = 1 GROUP BY k.id ORDER BY k.id"
        )->fetchAll();

        // Dènye 5 tranzaksyon
        $denye = $db->query("SELECT * FROM sales ORDER BY dat DESC LIMIT 5")->fetchAll();

        // Alèt stòk
        $alet = $db->query(
            "SELECT id, non, icon, stok, min_stok FROM products
             WHERE aktif = 1 AND stok <= min_stok ORDER BY stok ASC LIMIT 10"
        )->fetchAll();

        response(true, [
            'lavant_jodi' => $jodi['total'],
            'tranzaksyon_jodi' => $jodi['tranzaksyon'],
            'pwofi_jodi' => $pwofi,
            'stok_ba' => $stok_ba,
            'lavant_7jou' => $sevenDays,
            'pa_kategori' => $parKategori,
            'denye_tranzaksyon' => $denye,
            'alet_stok' => $alet
        ]);
        break;

    // ---------- RAPÒ FINANSYE ----------
    case 'finans':
        checkRole(['Admin', 'Odite']);
        $debi = $_GET['debi'] ?? date('Y-m-01');
        $fen  = $_GET['fen'] ?? date('Y-m-d');

        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(total),0) t FROM sales
             WHERE DATE(dat) BETWEEN ? AND ? AND estati = 'konplete'"
        );
        $stmt->execute([$debi, $fen]);
        $revni = $stmt->fetch()['t'];

        $stmt = $db->prepare("SELECT COALESCE(SUM(montan),0) t FROM expenses WHERE dat BETWEEN ? AND ?");
        $stmt->execute([$debi, $fen]);
        $depans = $stmt->fetch()['t'];

        // Lavant pa jou (bar chart)
        $stmt = $db->prepare(
            "SELECT DATE(dat) AS jou, COALESCE(SUM(total),0) AS total FROM sales
             WHERE DATE(dat) BETWEEN ? AND ? AND estati = 'konplete'
             GROUP BY DATE(dat) ORDER BY jou"
        );
        $stmt->execute([$debi, $fen]);
        $paJou = $stmt->fetchAll();

        // Mwayen peman (doughnut)
        $stmt = $db->prepare(
            "SELECT mwayen_peman, COALESCE(SUM(total),0) AS total, COUNT(*) AS kantite FROM sales
             WHERE DATE(dat) BETWEEN ? AND ? AND estati = 'konplete'
             GROUP BY mwayen_peman"
        );
        $stmt->execute([$debi, $fen]);
        $mwayen = $stmt->fetchAll();

        // Tout tranzaksyon
        $stmt = $db->prepare(
            "SELECT * FROM sales WHERE DATE(dat) BETWEEN ? AND ? ORDER BY dat DESC LIMIT 300"
        );
        $stmt->execute([$debi, $fen]);
        $tranzaksyon = $stmt->fetchAll();

        response(true, [
            'revni' => $revni,
            'depans' => $depans,
            'pwofi' => $revni - $depans,
            'pa_jou' => $paJou,
            'mwayen_peman' => $mwayen,
            'tranzaksyon' => $tranzaksyon
        ]);
        break;

    // ---------- JOUNAL AKTIVITE ----------
    case 'logs':
        checkRole(['Admin', 'Odite']);
        $limit = min((int)($_GET['limit'] ?? 50), 300);
        $rows = $db->query("SELECT * FROM activity_logs ORDER BY dat DESC LIMIT $limit")->fetchAll();
        response(true, $rows);
        break;

    // ---------- STATS PWODWI ----------
    case 'products':
        checkRole(['Admin', 'Odite']);
        $stats = $db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN stok <= min_stok AND stok > 0 THEN 1 ELSE 0 END) AS stok_ba,
                    SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) AS epwize,
                    COALESCE(SUM(stok * pri),0) AS vale_stok
             FROM products WHERE aktif = 1"
        )->fetch();
        response(true, $stats);
        break;

    // ---------- BACKUP ----------
    case 'backup':
        checkRole(['Admin']);
        $tables = ['users','kategori','products','clients','suppliers','expenses','sales','sale_items','activity_logs','settings'];
        $backup = ['version' => '2.0', 'dat' => date('Y-m-d H:i:s'), 'tables' => []];
        foreach ($tables as $t) {
            $backup['tables'][$t] = $db->query("SELECT * FROM `$t`")->fetchAll();
        }
        logActivity('BACKUP', 'Sistèm', 'Backup done kreye');
        response(true, $backup, "Backup kreye avèk siksè!");
        break;

    default:
        response(false, null, "Tip rapò pa rekonèt.", 400);
}
