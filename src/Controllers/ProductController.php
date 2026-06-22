<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;

/**
 * ProductController
 * ─────────────────────────────────────────────────────────────────────────────
 * חיפוש מוצרים ובדיקת מלאי — זמין לכל המערכת
 * משמש גם את ה-Formatter (product_search field) וגם את החיפוש הגלובלי
 *
 * Routes:
 *   GET /api/products?query=...   → apiSearch()
 *   GET /api/inventory?itemid=... → apiInventory()
 */
class ProductController extends Controller
{
    private const BUG_API   = 'https://priceportal.bugnet.dev/BUG_API/warrantyapi.php';
    private const BUG_TOKEN = 'tCwZKGnq!L2exkN';

    // ── GET /api/products?query=... ───────────────────────────────────────────
    // מחזיר: [{barcode, description, user, bugid, manufacturer, warranty, link}]

    public function apiSearch(): void
    {
        $this->requireAuth();

        $query = trim($this->get('query', ''));
        if (!$query || mb_strlen($query) < 2) {
            $this->json([]);
            return;
        }

        $url = $this->_buildSearchUrl($query);
        $raw = $this->_fetch($url);

        if ($raw === null) {
            $this->json([]);
            return;
        }

        $data = json_decode($raw, true);
        if (($data['ERR'] ?? 1) !== 0 || !isset($data['results'])) {
            $this->json([]);
            return;
        }

        $userMap = $this->_userMap();
        $results = $data['results'];

        foreach ($results as &$r) {
            $uid = (int)($r['user'] ?? 0);
            if ($uid <= 1) {
                $r['user'] = null;
            } elseif (isset($userMap[$uid])) {
                $r['user'] = $userMap[$uid]['name'] ?? null;
            }
        }
        unset($r);

        $this->json($results);
    }

    // ── GET /api/inventory?itemid=... ─────────────────────────────────────────
    // מחזיר: {table: "<html>..."} — HTML של טבלת מלאי מ-bug.co.il

    public function apiInventory(): void
    {
        $this->requireAuth();

        $itemId = (int)$this->get('itemid', 0);
        if (!$itemId) {
            $this->json(['error' => 'itemid חסר'], 400);
            return;
        }

        $url = 'https://www.bug.co.il/product/check-inventory?productId=' . $itemId;
        $raw = $this->_fetch($url, 10);

        if ($raw === null) {
            $this->json(['error' => 'שגיאה בטעינת מלאי']);
            return;
        }

        // הסר onclick handlers (כמו V1)
        $clean = preg_replace("/onclick='openBranchModal\(\d+\);'/", '', $raw);
        $this->json(['table' => $clean]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function _buildSearchUrl(string $q): string
    {
        $base  = self::BUG_API;
        $token = self::BUG_TOKEN;

        if (is_numeric($q)) {
            if (strlen($q) === 3) {
                return "$base?apicall=$token&barcode=" . urlencode($q);
            }
            return "$base?apicall=$token&iswildcard=1&barcode=" . urlencode($q);
        }

        return "$base?apicall=$token&desc=" . urlencode($q);
    }

    private function _fetch(string $url, int $timeout = 8): ?string
    {
        $ctx = stream_context_create([
            'http' => ['timeout' => $timeout, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => false],           // dev compat
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        return ($raw === false || $raw === '') ? null : $raw;
    }

    private function _userMap(): array
    {
        // cache לאורך הsession — קריאה אחת בלבד
        if (!empty($_SESSION['_prod_users'])) {
            return $_SESSION['_prod_users'];
        }

        $raw = $this->_fetch(self::BUG_API . '?apicall=' . self::BUG_TOKEN . '&getusers=1', 5);
        if ($raw === null) return [];

        $arr = json_decode($raw, true);
        $map = [];
        if (is_array($arr)) {
            foreach ($arr as $u) {
                if (isset($u['id'])) $map[(int)$u['id']] = $u;
            }
        }

        $_SESSION['_prod_users'] = $map;
        return $map;
    }
}
