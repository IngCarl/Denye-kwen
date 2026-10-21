<?php
/**
 * DENYE KWEN - API Pwodwi (CRUD)
 * GET: lis / yon sèl | POST: kreye | PUT: modifye | DELETE: soft delete
 * Kategori: GET ?kategori=1 pou lis kategori
 */
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {

    // ---------- LI ----------
    case 'GET':
        checkAuth(); // tout wòl ka li (POS bezwen li)

        // Lis kategori
        if (isset($_GET['kategori'])) {
            $rows = $db->query("SELECT * FROM kategori WHERE aktif = 1 ORDER BY id")->fetchAll();
            response(true, $rows);
        }

        // Yon sèl pwodwi
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT p.*, k.non AS kategori_non FROM products p
                                  LEFT JOIN kategori k ON p.kategori_id = k.id
                                  WHERE p.id = ? AND p.aktif = 1");
            $stmt->execute([(int)$_GET['id']]);
            $row = $stmt->fetch();
            $row ? response(true, $row) : response(false, null, "Pwodwi pa jwenn.", 404);
        }

        // Chèche pa barcode
        if (isset($_GET['barcode'])) {
            $stmt = $db->prepare("SELECT p.*, k.non AS kategori_non FROM products p
                                  LEFT JOIN kategori k ON p.kategori_id = k.id
                                  WHERE p.barcode = ? AND p.aktif = 1");
            $stmt->execute([$_GET['barcode']]);
            $row = $stmt->fetch();
            $row ? response(true, $row) : response(false, null, "Okenn pwodwi ak barcode sa a.", 404);
        }

        // Lis konplè
        $rows = $db->query("SELECT p.*, k.non AS kategori_non, k.icon AS kategori_icon
                            FROM products p
                            LEFT JOIN kategori k ON p.kategori_id = k.id
                            WHERE p.aktif = 1 ORDER BY p.non")->fetchAll();
        response(true, $rows);
        break;

    // ---------- KREYE ----------
    case 'POST':
        checkRole(['Admin']);
        $d = getInput();

        if (empty($d['kod']) || empty($d['non']) || !isset($d['pri'])) {
            response(false, null, "Kòd, non ak pri obligatwa.", 400);
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO products (kod, barcode, non, deskripsyon, kategori_id, icon, pri_dacha, pri, stok, min_stok)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                clean($d['kod']),
                !empty($d['barcode']) ? clean($d['barcode']) : null,
                clean($d['non']),
                clean($d['deskripsyon'] ?? ''),
                !empty($d['kategori_id']) ? (int)$d['kategori_id'] : null,
                clean($d['icon'] ?? '📦'),
                (float)($d['pri_dacha'] ?? 0),
                (float)$d['pri'],
                (int)($d['stok'] ?? 0),
                (int)($d['min_stok'] ?? 5)
            ]);
            $id = $db->lastInsertId();
            logActivity('KREYE', 'Pwodwi', "Nouvo pwodwi: " . clean($d['non']));
            response(true, ['id' => $id], "Pwodwi ajoute avèk siksè!");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) response(false, null, "Kòd oswa barcode sa a egziste deja.", 409);
            response(false, null, "Erè: " . $e->getMessage(), 500);
        }
        break;

    // ---------- MODIFYE ----------
    case 'PUT':
        checkRole(['Admin']);
        $d = getInput();
        if (empty($d['id'])) response(false, null, "ID obligatwa.", 400);

        try {
            $stmt = $db->prepare(
                "UPDATE products SET kod=?, barcode=?, non=?, deskripsyon=?, kategori_id=?, icon=?,
                 pri_dacha=?, pri=?, stok=?, min_stok=? WHERE id=?"
            );
            $stmt->execute([
                clean($d['kod']),
                !empty($d['barcode']) ? clean($d['barcode']) : null,
                clean($d['non']),
                clean($d['deskripsyon'] ?? ''),
                !empty($d['kategori_id']) ? (int)$d['kategori_id'] : null,
                clean($d['icon'] ?? '📦'),
                (float)($d['pri_dacha'] ?? 0),
                (float)$d['pri'],
                (int)($d['stok'] ?? 0),
                (int)($d['min_stok'] ?? 5),
                (int)$d['id']
            ]);
            logActivity('MODIFYE', 'Pwodwi', "Pwodwi #{$d['id']} modifye: " . clean($d['non']));
            response(true, null, "Pwodwi modifye avèk siksè!");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) response(false, null, "Kòd oswa barcode sa a egziste deja.", 409);
            response(false, null, "Erè: " . $e->getMessage(), 500);
        }
        break;

    // ---------- EFASE (soft delete) ----------
    case 'DELETE':
        checkRole(['Admin']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) response(false, null, "ID obligatwa.", 400);

        $db->prepare("UPDATE products SET aktif = 0 WHERE id = ?")->execute([$id]);
        logActivity('EFASE', 'Pwodwi', "Pwodwi #$id efase (soft delete)");
        response(true, null, "Pwodwi efase avèk siksè!");
        break;

    default:
        response(false, null, "Metòd pa sipòte.", 405);
}
