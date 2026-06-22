<?php
declare(strict_types=1);

namespace Controllers;

use Core\Auth;
use Core\Controller;
use Core\DB;

class LabController extends Controller
{
    // ── helpers ─────────────────────────────────────────────────────────────

    private function permGroup(): int
    {
        return (int)($_SESSION['perm_group'] ?? 0);
    }

    private function isAdmin(): bool
    {
        return in_array($this->permGroup(), [3, 20], true);
    }

    private function isTech(): bool
    {
        return $this->permGroup() === 16;
    }

    // ── pages ────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->requirePermission('canEditLabInv');
        $technicians = $this->isAdmin() ? $this->getTechnicians() : [];
        $this->view('pages/lab/index', [
            'technicians' => $technicians,
            'isAdmin'     => $this->isAdmin(),
            'isTech'      => $this->isTech(),
            'permGroup'   => $this->permGroup(),
        ]);
    }

    // ── API: inventory list ──────────────────────────────────────────────────

    public function apiInventory(): void
    {
        $this->requirePermission('canEditLabInv');
        $rows = DB::query('SELECT * FROM lab_inventory_items ORDER BY updated_at DESC');
        $this->json($rows);
    }

    // ── API: movement history ────────────────────────────────────────────────

    public function apiHistory(): void
    {
        $this->requirePermission('canEditLabInv');
        $rows = DB::query("
            SELECT
                m.*,
                m.movement_date        AS date,
                i.product_name_en,
                i.part_number,
                CONCAT(u.first_name,' ',u.last_name) AS username,
                CONCAT(t.first_name,' ',t.last_name) AS technician
            FROM lab_inventory_movements m
            LEFT JOIN lab_inventory_items i ON i.id = m.item_id
            LEFT JOIN users u               ON u.id = m.user_id
            LEFT JOIN users t               ON t.id = m.technician_id
            ORDER BY m.movement_date DESC
        ");
        $this->json($rows);
    }

    // ── API: history chart (pivot) ───────────────────────────────────────────

    public function apiHistoryChart(): void
    {
        $this->requirePermission('canEditLabInv');
        $rows = DB::query("
            SELECT
                m.qty              AS כמות_תנועה,
                m.direction        AS כיוון,
                i.qty              AS מלאי_נוכחי,
                m.service_call_id  AS קריאת_שירות,
                m.notes            AS הערות,
                DATE(m.movement_date) AS תאריך,
                i.part_number      AS מספר_חלק,
                i.model            AS דגם,
                i.manufacturer     AS יצרן,
                i.barcode          AS ברקוד,
                i.updated_at       AS עדכון_אחרון,
                i.product_name_en  AS שם_מוצר_באנגלית,
                CONCAT(u.first_name,' ',u.last_name) AS שם_משתמש,
                CONCAT(t.first_name,' ',t.last_name) AS טכנאי
            FROM lab_inventory_movements m
            JOIN lab_inventory_items i ON i.id = m.item_id
            LEFT JOIN users u          ON u.id = m.user_id
            LEFT JOIN users t          ON t.id = m.technician_id
            ORDER BY m.movement_date DESC
        ");
        $this->json($rows);
    }

    // ── API: single movement (OUT) ───────────────────────────────────────────

    public function apiMovement(): void
    {
        $this->requirePermission('canEditLabInv');
        $this->verifyCsrf();

        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $permGroup = $this->permGroup();

        $itemIds       = array_map('intval', (array)($_POST['item_id']   ?? []));
        $itemQtys      = array_map('intval', (array)($_POST['item_qty']  ?? []));
        $technicianId  = (int)($_POST['technician_id']   ?? $userId);
        $serviceCallId = trim($_POST['service_call_id'] ?? '');
        $notes         = trim($_POST['notes']           ?? '');
        $serialNumber  = trim($_POST['sNum']            ?? '');

        if (empty($itemIds) || $technicianId <= 0) {
            $this->json(['success' => false, 'message' => 'שדות חסרים'], 400);
        }

        $status = ($permGroup === 16) ? 'pending' : 'approved';

        $pdo = DB::get();
        $pdo->beginTransaction();

        try {
            foreach ($itemIds as $idx => $itemId) {
                $qty = $itemQtys[$idx] ?? 0;
                if ($itemId <= 0 || $qty <= 0) {
                    throw new \Exception("פריט או כמות לא תקינים (ID: $itemId)");
                }

                $row = DB::row('SELECT qty FROM lab_inventory_items WHERE id = ? FOR UPDATE', [$itemId]);
                if (!$row) throw new \Exception("פריט $itemId לא נמצא");

                $scId = $serviceCallId !== '' ? $serviceCallId : null;

                DB::execute("
                    INSERT INTO lab_inventory_movements
                        (user_id, item_id, direction, qty, technician_id, service_call_id, notes, serial_number, status, movement_date)
                    VALUES (?, ?, 'OUT', ?, ?, ?, ?, ?, ?, NOW())
                ", [$userId, $itemId, $qty, $technicianId, $scId, $notes, $serialNumber, $status]);

                if ($status === 'approved') {
                    DB::execute('UPDATE lab_inventory_items SET qty = qty - ?, updated_at = NOW() WHERE id = ?', [$qty, $itemId]);
                }
            }

            $pdo->commit();
            $msg = $status === 'pending' ? 'הבקשות נשלחו לאישור מנהל' : 'המלאי עודכן בהצלחה';
            $this->json(['success' => true, 'message' => $msg, 'is_pending' => $status === 'pending']);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── API: approve pending movement ────────────────────────────────────────

    public function apiApproveMovement(): void
    {
        $this->requirePermission('canEditLabInv');
        $this->verifyCsrf();

        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => 'רק מנהל יכול לאשר תנועות'], 403);
        }

        $moveId = (int)($_POST['move_id'] ?? 0);
        $move   = DB::row('SELECT item_id, qty, direction, status FROM lab_inventory_movements WHERE id = ?', [$moveId]);

        if (!$move || $move['status'] !== 'pending') {
            $this->json(['success' => false, 'message' => 'תנועה לא נמצאה או כבר אושרה'], 404);
        }

        $op = $move['direction'] === 'IN' ? '+' : '-';
        DB::execute('UPDATE lab_inventory_movements SET status = ? WHERE id = ?', ['approved', $moveId]);
        DB::execute("UPDATE lab_inventory_items SET qty = qty $op ?, updated_at = NOW() WHERE id = ?", [(int)$move['qty'], (int)$move['item_id']]);

        $this->json(['success' => true, 'message' => 'התנועה אושרה והמלאי עודכן']);
    }

    // ── API: update item ─────────────────────────────────────────────────────

    public function apiUpdateItem(): void
    {
        $this->requirePermission('canEditLabInv');
        $this->verifyCsrf();

        if (!$this->isAdmin()) {
            $this->json(['success' => false, 'message' => 'אין הרשאה לעדכון פריט'], 403);
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $id     = (int)($_POST['id']      ?? 0);
        $qty    = (int)($_POST['qty']     ?? 0);

        $old = DB::row('SELECT qty FROM lab_inventory_items WHERE id = ?', [$id]);
        if (!$old) $this->json(['success' => false, 'message' => 'פריט לא נמצא'], 404);

        DB::execute("
            UPDATE lab_inventory_items
            SET product_name_en = ?, model = ?, manufacturer = ?, qty = ?,
                min_qty = ?, location = ?, compatibility = ?, updated_at = NOW()
            WHERE id = ?
        ", [
            $_POST['product_name_en'] ?? '',
            $_POST['model']           ?? '',
            $_POST['manufacturer']    ?? '',
            $qty,
            (int)($_POST['min_qty']  ?? 0),
            $_POST['location']        ?? '',
            $_POST['compatibility']   ?? '',
            $id,
        ]);

        $prevQty = (int)$old['qty'];
        if ($qty !== $prevQty) {
            $diff = $qty - $prevQty;
            $dir  = $diff > 0 ? 'IN' : 'OUT';
            DB::execute("
                INSERT INTO lab_inventory_movements
                    (item_id, direction, qty, user_id, movement_date, notes, status)
                VALUES (?, ?, ?, ?, NOW(), 'עדכון ידני', 'approved')
            ", [$id, $dir, abs($diff), $userId]);
        }

        $this->json(['success' => true]);
    }

    // ── API: add item manually ───────────────────────────────────────────────

    public function apiAddItem(): void
    {
        $this->requirePermission('canEditLabInv');
        $this->verifyCsrf();

        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $partNum   = trim($_POST['part_number']     ?? '');
        $barcode   = trim($_POST['barcode']         ?? '');
        $qtyToAdd  = (int)($_POST['qty_to_add']    ?? 0);

        if ($partNum === '' && $barcode === '') {
            $this->json(['success' => false, 'message' => 'חסר מק"ט או ברקוד'], 400);
        }
        if ($qtyToAdd <= 0) {
            $this->json(['success' => false, 'message' => 'יש להזין כמות חיובית'], 400);
        }

        $conditions = [];
        $params     = [];
        if ($partNum !== '') { $conditions[] = 'part_number = ?'; $params[] = $partNum; }
        if ($barcode  !== '') { $conditions[] = 'barcode = ?';     $params[] = $barcode; }

        $existing = DB::row(
            'SELECT id, qty FROM lab_inventory_items WHERE ' . implode(' OR ', $conditions) . ' LIMIT 1',
            $params
        );

        $pdo = DB::get();
        $pdo->beginTransaction();

        try {
            if ($existing) {
                $itemId  = (int)$existing['id'];
                $newQty  = (int)$existing['qty'] + $qtyToAdd;

                $updatable = ['product_name_en', 'tags', 'compatibility', 'model', 'min_qty', 'location', 'manufacturer', 'price_store'];
                $sets = []; $vals = [];
                foreach ($updatable as $f) {
                    if (isset($_POST[$f]) && $_POST[$f] !== '') {
                        $sets[] = "`$f` = ?";
                        $vals[] = in_array($f, ['min_qty'], true) ? (int)$_POST[$f] : trim($_POST[$f]);
                    }
                }
                if ($sets) {
                    DB::execute('UPDATE lab_inventory_items SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ?', [...$vals, $itemId]);
                }
                DB::execute('UPDATE lab_inventory_items SET qty = ?, updated_at = NOW() WHERE id = ?', [$newQty, $itemId]);
                DB::execute("INSERT INTO lab_inventory_movements (item_id, direction, qty, user_id, movement_date, notes) VALUES (?, 'IN', ?, ?, NOW(), 'הוספה ידנית')", [$itemId, $qtyToAdd, $userId]);

                $pdo->commit();
                $this->json(['success' => true, 'action' => 'updated', 'item_id' => $itemId]);
            } else {
                if (empty($_POST['product_name_en'])) {
                    throw new \Exception('שם פריט הוא שדה חובה לפריט חדש');
                }
                $itemId = DB::insert("
                    INSERT INTO lab_inventory_items
                        (part_number, barcode, product_name_en, tags, compatibility, model, min_qty, location, manufacturer, price_store, qty, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ", [
                    $partNum,
                    $barcode,
                    trim($_POST['product_name_en'] ?? ''),
                    trim($_POST['tags']            ?? ''),
                    trim($_POST['compatibility']   ?? ''),
                    trim($_POST['model']           ?? ''),
                    (int)($_POST['min_qty']        ?? 0),
                    trim($_POST['location']        ?? ''),
                    trim($_POST['manufacturer']    ?? ''),
                    is_numeric($_POST['price_store'] ?? '') ? (float)$_POST['price_store'] : null,
                    $qtyToAdd,
                ]);

                DB::execute("INSERT INTO lab_inventory_movements (item_id, direction, qty, user_id, movement_date, notes) VALUES (?, 'IN', ?, ?, NOW(), 'הוספה ידנית - יצירה')", [$itemId, $qtyToAdd, $userId]);

                $pdo->commit();
                $this->json(['success' => true, 'action' => 'inserted', 'item_id' => $itemId]);
            }
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // ── API: import from Excel ───────────────────────────────────────────────

    public function apiImport(): void
    {
        $this->requirePermission('canEditLabInv');
        $this->verifyCsrf();

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (empty($data['items']) || !is_array($data['items'])) {
            $this->json(['success' => false, 'message' => 'לא התקבלו נתונים תקינים'], 400);
        }

        $userId   = (int)($_SESSION['user_id'] ?? 0);
        $imported = $inserted = $updated = $failed = 0;

        $pdo = DB::get();
        $pdo->beginTransaction();

        try {
            foreach ($data['items'] as $item) {
                $part         = trim($item['part_number']  ?? '');
                $barcode      = trim($item['barcode']      ?? '');
                $name         = trim($item['name']         ?? '');
                $manufacturer = strtoupper(trim($item['manufacturer'] ?? ''));
                $qtyToAdd     = is_numeric($item['qty']          ?? null) ? (int)$item['qty']          : 0;
                $incomingQty  = is_numeric($item['incoming_qty'] ?? null) ? (int)$item['incoming_qty'] : 0;
                $compatibility = trim($item['compatibility'] ?? '');

                if ($part === '' && $barcode === '') { $failed++; continue; }

                $existing = null;
                if ($part !== '')    $existing = DB::row('SELECT id FROM lab_inventory_items WHERE part_number = ? LIMIT 1', [$part]);
                if (!$existing && $barcode !== '') $existing = DB::row('SELECT id FROM lab_inventory_items WHERE barcode = ? LIMIT 1', [$barcode]);

                if ($existing) {
                    $itemId = (int)$existing['id'];
                    DB::execute("
                        UPDATE lab_inventory_items
                        SET qty = qty + ?,
                            incoming_qty = ?,
                            compatibility = IF(? != '', ?, compatibility),
                            updated_at = NOW()
                        WHERE id = ?
                    ", [$qtyToAdd, $incomingQty, $compatibility, $compatibility, $itemId]);

                    if ($qtyToAdd > 0) {
                        DB::execute("INSERT INTO lab_inventory_movements (item_id, direction, qty, user_id, movement_date, notes) VALUES (?, 'IN', ?, ?, NOW(), 'ייבוא מאקסל')", [$itemId, $qtyToAdd, $userId]);
                    }
                    $updated++;
                } else {
                    $itemId = DB::insert("
                        INSERT INTO lab_inventory_items (part_number, barcode, product_name_en, manufacturer, compatibility, qty, incoming_qty, min_qty, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
                    ", [$part, $barcode, $name, $manufacturer, $compatibility, $qtyToAdd, $incomingQty]);

                    if ($itemId > 0 && $qtyToAdd > 0) {
                        DB::execute("INSERT INTO lab_inventory_movements (item_id, direction, qty, user_id, movement_date, notes) VALUES (?, 'IN', ?, ?, NOW(), 'ייבוא מאקסל')", [$itemId, $qtyToAdd, $userId]);
                    }
                    $inserted++;
                }
                $imported++;
            }

            $pdo->commit();
            $this->json(['success' => true, 'imported' => $imported, 'inserted' => $inserted, 'updated' => $updated, 'failed' => $failed]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->json(['success' => false, 'message' => 'שגיאה בייבוא: ' . $e->getMessage()], 500);
        }
    }

    // ── API: lab users (admin only) ──────────────────────────────────────────

    public function apiUsersList(): void
    {
        $this->requirePermission('canEditLabInv');
        if (!$this->isAdmin()) $this->json(['error' => 'אין הרשאה'], 403);

        $deptId = (int)($_SESSION['department_id'] ?? 0);
        $rows   = DB::query(
            'SELECT id, first_name, last_name, email, is_active, last_login FROM users WHERE department_id = ? ORDER BY first_name',
            [$deptId]
        );

        // DataTables expects {data: [...]}
        $this->json(['data' => $rows]);
    }

    public function apiAddUser(): void
    {
        $this->requirePermission('canEditLabInv');
        $this->verifyCsrf();
        if (!$this->isAdmin()) $this->json(['success' => false, 'message' => 'אין הרשאה'], 403);

        $deptId    = (int)($_SESSION['department_id'] ?? 0);
        $firstName = trim($_POST['fName']    ?? '');
        $lastName  = trim($_POST['lName']    ?? '');
        $email     = trim($_POST['email']    ?? '');
        $password  = $_POST['password']      ?? '';

        if (!$firstName || !$lastName || !$email || !$password) {
            $this->json(['success' => false, 'message' => 'כל השדות חובה'], 400);
        }

        $exists = DB::value('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
        if ($exists) $this->json(['success' => false, 'message' => 'האימייל כבר קיים'], 409);

        $techGroupId = DB::value("SELECT id FROM permission_groups WHERE name_heb LIKE '%טכנאי%' LIMIT 1") ?? 16;

        DB::insert("
            INSERT INTO users (first_name, last_name, email, password_hash, department_id, permission_group_id, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ", [$firstName, $lastName, $email, password_hash($password, PASSWORD_BCRYPT), $deptId, $techGroupId]);

        $this->json(['success' => true]);
    }

    public function apiToggleUser(): void
    {
        $this->requirePermission('canEditLabInv');
        $this->verifyCsrf();
        if (!$this->isAdmin()) $this->json(['success' => false, 'message' => 'אין הרשאה'], 403);

        $deptId = (int)($_SESSION['department_id'] ?? 0);
        $userId = (int)($_POST['id'] ?? 0);

        DB::execute('UPDATE users SET is_active = 1 - is_active WHERE id = ? AND department_id = ?', [$userId, $deptId]);
        $this->json(['success' => true]);
    }

    // ── private helpers ──────────────────────────────────────────────────────

    private function getTechnicians(): array
    {
        $deptId = (int)($_SESSION['department_id'] ?? 0);
        return DB::query(
            'SELECT id, first_name, last_name FROM users WHERE department_id = ? AND is_active = 1 ORDER BY first_name',
            [$deptId]
        );
    }
}
