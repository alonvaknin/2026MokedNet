<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\FormatterModel;

class FormatterController extends Controller
{
    // ── Management page ───────────────────────────────────────────────────────

    public function index(): void
    {
        $this->requirePermission('canFormatter');
        $categorised = FormatterModel::categorised(false);
        $this->view('pages/formatter/index', compact('categorised'));
    }

    // ── Editor ────────────────────────────────────────────────────────────────

    public function editor(): void
    {
        $this->requirePermission('canFormatter');
        $id          = (int)$this->get('id', 0);
        $tplData     = $id ? FormatterModel::fullTemplate($id) : null;

        $fieldTypes = [
            'text'           => 'טקסט חופשי',
            'textarea'       => 'אזור טקסט',
            'tel'            => 'טלפון',
            'email'          => 'מייל',
            'number'         => 'מספר',
            'select'         => 'רשימה נפתחת',
            'radio'          => 'בחירה (radio)',
            'checkbox'       => 'תיבת סימון',
            'date'           => 'תאריך',
            'product_search' => 'חיפוש מוצר (API)',
            'store_select'   => 'בחירת חנות',
        ];

        $categories = array_keys(FormatterModel::categorised(false));
        sort($categories);

        $tpl = $tplData;
        $this->view('pages/formatter/editor', compact('tpl','fieldTypes','categories','id'));
    }

    // ── Save template ─────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->requirePermission('canFormatter');
        $this->verifyCsrf();

        $id = (int)$this->post('id', 0);
        $data = [
            'name'         => trim($this->post('name','')),
            'category'     => trim($this->post('category','')),
            'description'  => trim($this->post('description','')),
            'body_male'    => $this->post('body_male',''),
            'body_female'  => $this->post('body_female','') ?: null,
            'mail_to'      => trim($this->post('mail_to','')),
            'mail_cc'      => trim($this->post('mail_cc','')),
            'mail_subject' => trim($this->post('mail_subject','')),
            'is_active'    => (bool)$this->post('is_active',1),
            'sort_order'   => (int)$this->post('sort_order',0),
            'created_by'   => Auth::user()['id'] ?? null,
        ];

        if (!$data['name']) $this->json(['error' => 'שם תבנית חובה'], 400);

        $newId = FormatterModel::saveTemplate($data, $id);

        $fieldsRaw = $this->post('fields', []);
        if (is_array($fieldsRaw)) {
            $fields = [];
            foreach ($fieldsRaw as $f) {
                if (empty($f['field_key']) || empty($f['label'])) continue;
                $opts = null;
                if (!empty($f['options_raw'])) {
                    $lines = array_filter(array_map('trim', explode("\n", $f['options_raw'])));
                    $opts  = array_values(array_map(function ($line) {
                        $parts = explode('|', $line, 2);
                        return ['value' => trim($parts[0]), 'label' => trim($parts[1] ?? $parts[0])];
                    }, $lines));
                }
                $fields[] = [
                    'field_key'   => preg_replace('/[^a-z0-9_]/', '_', strtolower($f['field_key'])),
                    'label'       => $f['label'],
                    'field_type'  => $f['field_type'] ?? 'text',
                    'placeholder' => $f['placeholder'] ?? null,
                    'options'     => $opts,
                    'required'    => ($f['required'] ?? 0) ? 1 : 0,
                    'sort_order'  => $f['sort_order'] ?? 0,
                ];
            }
            FormatterModel::replaceFields($newId, $fields);
        }

        $this->json(['ok' => true, 'id' => $newId]);
    }

    // ── Toggle / Delete ───────────────────────────────────────────────────────

    public function toggle(): void
    {
        $this->requirePermission('canFormatter');
        $this->verifyCsrf();
        $id = (int)$this->post('id', 0);
        if (!$id) $this->json(['error' => 'id חסר'], 400);
        $active = FormatterModel::toggleTemplate($id);
        $this->json(['ok' => true, 'is_active' => $active]);
    }

    public function delete(): void
    {
        $this->requirePermission('canFormatter');
        $this->verifyCsrf();
        $id = (int)$this->post('id', 0);
        if (!$id) $this->json(['error' => 'id חסר'], 400);
        FormatterModel::deleteTemplate($id);
        $this->json(['ok' => true]);
    }

    // ── API: get template JSON ────────────────────────────────────────────────

    public function getTemplate(): void
    {
        $this->requireAuth();
        $id = (int)$this->get('id', 0);
        if (!$id) $this->json(['error' => 'id חסר'], 400);
        $tpl = FormatterModel::fullTemplate($id);
        if (!$tpl) $this->json(['error' => 'תבנית לא נמצאה'], 404);
        $this->json($tpl);
    }

    // ── API: list templates ───────────────────────────────────────────────────

    public function apiList(): void
    {
        $this->requireAuth();
        $this->json(FormatterModel::categorised());
    }

    // ── API: store list ───────────────────────────────────────────────────────

    public function apiStores(): void
    {
        $this->requireAuth();
        $this->json(FormatterModel::storeList());
    }

    // ── GET /api/products?query=... ─────────────────────────────────────────────
    public function apiProducts(): void
    {
        $this->requireAuth();
        $q = trim($this->get('query', ''));
        if (!$q || mb_strlen($q) < 2) { $this->json([]); return; }

        $base  = 'https://priceportal.bugnet.dev/BUG_API/warrantyapi.php';
        $token = 'tCwZKGnq!L2exkN';

        if (is_numeric($q)) {
            $url = strlen($q) === 3
                ? "$base?apicall=$token&barcode=" . urlencode($q)
                : "$base?apicall=$token&iswildcard=1&barcode=" . urlencode($q);
        } else {
            $url = "$base?apicall=$token&desc=" . urlencode($q);
        }

        $raw = $this->_bugFetch($url);
        if (!$raw) { $this->json([]); return; }

        $data = json_decode($raw, true);
        if (($data['ERR'] ?? 1) !== 0 || !isset($data['results'])) {
            $this->json([]); return;
        }

        $userMap = $this->_bugUsers($base, $token);
        $results = $data['results'];
        foreach ($results as &$r) {
            $uid = (int)($r['user'] ?? 0);
            $r['user'] = $uid <= 1 ? null : ($userMap[$uid]['name'] ?? null);
        }
        unset($r);
        $this->json($results);
    }

    // ── GET /api/inventory?itemid=... ────────────────────────────────────────
    public function apiInventory(): void
    {
        $this->requireAuth();
        $id = (int)$this->get('itemid', 0);
        if (!$id) { $this->json(['error' => 'itemid חסר'], 400); return; }

        $raw = $this->_bugFetch('https://www.bug.co.il/product/check-inventory?productId=' . $id, 10);
        if (!$raw) { $this->json(['error' => 'שגיאה בטעינת מלאי']); return; }

        $clean = preg_replace("/onclick='openBranchModal\\(\\d+\\);'/", '', $raw);
        $this->json(['table' => $clean]);
    }

    private function _bugFetch(string $url, int $t = 8): ?string
    {
        // cURL (עדיף על file_get_contents עבור HTTPS חיצוני)
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $t,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 MokedNet/2',
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $out = curl_exec($ch);
            curl_close($ch);
            return ($out && $out !== '') ? $out : null;
        }
        // fallback
        $ctx = stream_context_create(['http' => ['timeout' => $t], 'ssl' => ['verify_peer' => false]]);
        $out = @file_get_contents($url, false, $ctx);
        return ($out && $out !== '') ? $out : null;
    }

    private function _bugUsers(string $base, string $token): array
    {
        if (!empty($_SESSION['_prod_users'])) return $_SESSION['_prod_users'];
        $raw = $this->_bugFetch("$base?apicall=$token&getusers=1", 5);
        if (!$raw) return [];
        $arr = json_decode($raw, true);
        $map = [];
        if (is_array($arr)) foreach ($arr as $u) { if (isset($u['id'])) $map[(int)$u['id']] = $u; }
        return $_SESSION['_prod_users'] = $map;
    }
}
