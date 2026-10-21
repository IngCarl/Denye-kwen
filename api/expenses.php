<?php
/**
 * DENYE KWEN - API Depans (CRUD)
 * Aksè: Admin (Odite li sèlman)
 */
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {

    case 'GET':
        checkRole(['Admin', 'Odite']);

        // Estatistik rapid
        if (isset($_GET['stats'])) {
            $jodi = $db->query("SELECT COALESCE(SUM(montan),0) t FROM expenses WHERE dat = CURDATE()")->fetch()['t'];
            $mwa  = $db->query("SELECT COALESCE(SUM(montan),0) t FROM expenses WHERE MONTH(dat)=MONTH(CURDATE()) AND YEAR(dat)=YEAR(CURDATE())")->fetch()['t'];
            $tout = $db->query("SELECT COALESCE(SUM(montan),0) t FROM expenses")->fetch()['t'];
            response(true, ['jodi' => $jodi, 'mwa' => $mwa, 'total' => $tout]);
        }

        $rows = $db->query("SELECT e.*, u.non_konple AS user_non FROM expenses e
                            LEFT JOIN users u ON e.user_id = u.id
                            ORDER BY e.dat DESC, e.id DESC")->fetchAll();
        response(true, $rows);
        break;

    case 'POST':
        $user = checkRole(['Admin']);
        $d = getInput();
        if (empty($d['kategori']) || !isset($d['montan']) || empty($d['dat'])) {
            response(false, null, "Kategori, montan ak dat obligatwa.", 400);
        }

        $stmt = $db->prepare(
            "INSERT INTO expenses (kategori, deskripsyon, montan, mwayen_peman, dat, user_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            clean($d['kategori']),
            clean($d['deskripsyon'] ?? ''),
            (float)$d['montan'],
            clean($d['mwayen_peman'] ?? 'kach'),
            $d['dat'],
            $user['id']
        ]);
        $id = $db->lastInsertId();
        logActivity('KREYE', 'Depans', "Nouvo depans: " . clean($d['kategori']) . " - " . $d['montan'] . " HTG");
        response(true, ['id' => $id], "Depans anrejistre avèk siksè!");
        break;

    case 'PUT':
        checkRole(['Admin']);
        $d = getInput();
        if (empty($d['id'])) response(false, null, "ID obligatwa.", 400);

        $stmt = $db->prepare(
            "UPDATE expenses SET kategori=?, deskripsyon=?, montan=?, mwayen_peman=?, dat=? WHERE id=?"
        );
        $stmt->execute([
            clean($d['kategori']),
            clean($d['deskripsyon'] ?? ''),
            (float)$d['montan'],
            clean($d['mwayen_peman'] ?? 'kach'),
            $d['dat'],
            (int)$d['id']
        ]);
        logActivity('MODIFYE', 'Depans', "Depans #{$d['id']} modifye");
        response(true, null, "Depans modifye avèk siksè!");
        break;

    case 'DELETE':
        checkRole(['Admin']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) response(false, null, "ID obligatwa.", 400);
        $db->prepare("DELETE FROM expenses WHERE id = ?")->execute([$id]);
        logActivity('EFASE', 'Depans', "Depans #$id efase");
        response(true, null, "Depans efase avèk siksè!");
        break;

    default:
        response(false, null, "Metòd pa sipòte.", 405);
}
