<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\ActivityLog;
use Models\StoreModel;
use Models\AreaManagerModel;

class StoreController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $types  = StoreModel::types();
        $cities = StoreModel::allCities();
        $type   = $this->get('type', '');
        $city   = $this->get('city', '');
        $stores = ($type||$city) ? StoreModel::search('', $type, $city) : [];
        $this->view('pages/stores/index', compact('stores','types','cities','type','city'));
    }

    public function search(): void
    {
        $this->requireAuth();
        $q      = trim($this->get('q',''));
        $type   = $this->get('type','');
        $city   = $this->get('city','');
        $stores = StoreModel::search($q, $type, $city);
        $types  = StoreModel::types();
        $cities = StoreModel::allCities();
        $this->view('pages/stores/index', compact('stores','types','cities','type','city','q'));
    }

    public function show(string $sNum): void
    {
        $this->requireAuth();
        $store   = StoreModel::byNum($sNum);
        $canEdit = Auth::can('canEditStore');
        if (!$store) { http_response_code(404); echo '404'; return; }
        $areaManagers = AreaManagerModel::managersForStore((int)$store['id']);
        $this->view('pages/stores/show', compact('store','canEdit','areaManagers'));
    }

    public function showById(string $id): void
    {
        $this->requireAuth();
        $store   = StoreModel::byId((int)$id);
        $canEdit = Auth::can('canEditStore');
        if (!$store) { http_response_code(404); echo '404'; return; }
        $areaManagers = AreaManagerModel::managersForStore((int)$store['id']);
        $this->view('pages/stores/show', compact('store','canEdit','areaManagers'));
    }

    public function apiSearch(): void
    {
        $this->requireAuth();
        $q = trim($this->get('q',''));
        if (!$q || mb_strlen($q) < 2) $this->json([]);
        $this->json(StoreModel::search($q));
    }

    public function apiGet(string $sNum): void
    {
        $this->requireAuth();
        $store = is_numeric($sNum)
            ? StoreModel::byId((int)$sNum)
            : StoreModel::byNum($sNum);
        $this->json($store ?: (object)[]);
    }

    public function save(): void
    {
        $this->requirePermission('canEditStore');
        $this->verifyCsrf();

        $id     = (int)$this->post('id', 0);
        $before = $id ? StoreModel::logSnapshot($id) : [];

        $data = [
            'store_num'          => trim($this->post('store_num','')),
            'name'               => trim($this->post('name','')),
            'type'               => trim($this->post('type','סניף באג')),
            'city'               => trim($this->post('city','')),
            'address'            => trim($this->post('address','')),
            'phone_main'         => trim($this->post('phone_main','')),
            'phone_cell'         => trim($this->post('phone_cell','')),
            'email'              => trim($this->post('email','')),
            'manager_name'       => trim($this->post('manager_name','')),
            'manager_cell'       => trim($this->post('manager_cell','')),
            'mvoice_queue'       => trim($this->post('mvoice_queue','')),
            'telephone_line_num' => trim($this->post('telephone_line_num','')),
            'alert_note'         => trim($this->post('alert_note','')),
            'note'               => trim($this->post('note','')),
            'tags'               => trim($this->post('tags','')),
            'is_active'          => (bool)$this->post('is_active', 1),
            'is_display'         => (bool)$this->post('is_display', 1),
        ];

        if (!$data['name']) $this->json(['שגיאה' => 'שם חובה'], 400);

        $label = trim($data['name'] . ($data['store_num'] ? ' #'.$data['store_num'] : ''));

        if ($id) {
            StoreModel::update($id, $data);
            $after = StoreModel::logSnapshot($id);
            ActivityLog::update('store', $id, $label, $before, $after);
            $this->json(['ok' => true, 'id' => $id]);
        } else {
            $newId = StoreModel::create($data);
            ActivityLog::create('store', $newId, $label);
            $this->json(['ok' => true, 'id' => $newId]);
        }
    }

    public function syncWorkHours(): void
    {
        $this->requirePermission('canEditStore');
        $this->verifyCsrf();

        $url = 'https://www.bug.co.il/Elements/branchJArray.js?v=638462300582311602';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
        ]);
        $js = curl_exec($ch);
        curl_close($ch);
        if (!$js) {
            $this->json(['ok' => false, 'error' => 'לא ניתן להגיע לאתר באג'], 502);
            return;
        }

        // strip BOM if present
        $js = ltrim($js, "\xEF\xBB\xBF");

        // extract the array: everything from the first '[' to the end
        $start = strpos($js, '[');
        if ($start === false) {
            $this->json(['ok' => false, 'error' => 'מבנה לא מזוהה'], 502);
            return;
        }
        $branches = json_decode(substr($js, $start), true);
        if (!is_array($branches)) {
            $this->json(['ok' => false, 'error' => 'JSON לא תקין'], 502);
            return;
        }

        $updated = 0;
        foreach ($branches as $branch) {
            $hiddenId = $branch['Id2'] ?? null;
            if (!$hiddenId) continue;
            $storeNum = (string)(((int)substr((string)$hiddenId, 3) - 1986) / 548);

            $wh1 = explode('-', $branch['WorkHours1'] ?? '-');
            $wh2 = explode('-', $branch['WorkHours2'] ?? '-');
            $wh3 = explode('-', $branch['WorkHours3'] ?? '-');

            $o1 = trim($wh1[0] ?? '');
            $c1 = trim($wh1[1] ?? '');
            $o2 = trim($wh2[0] ?? '');
            $c2 = trim($wh2[1] ?? '');
            $o3 = trim($wh3[0] ?? '');
            $c3 = trim($wh3[1] ?? '');

            $workHours = json_encode([
                'א' => $o1 && $c1 ? "{$o1}-{$c1}" : null,
                'ו' => $o2 && $c2 ? "{$o2}-{$c2}" : null,
                'ש' => $o3 && $c3 ? "{$o3}-{$c3}" : null,
            ], JSON_UNESCAPED_UNICODE);
            $rows = StoreModel::updateWorkHours($storeNum, $workHours);
            $updated += $rows;
        }

        ActivityLog::log('store.sync_hours', 'store', null, 'סנכרון שעות מאתר באג', "עודכנו {$updated} חנויות");
        $this->json(['ok' => true, 'updated' => $updated]);
    }

    public function toggleActive(string $id): void
    {
        $this->requirePermission('canEditStore');
        $this->verifyCsrf();
        $store  = StoreModel::byId((int)$id);
        $label  = ($store['name'] ?? '') . ($store['store_num'] ? ' #'.$store['store_num'] : '');
        $active = StoreModel::toggleActive((int)$id);
        ActivityLog::toggle('store', (int)$id, $label, (bool)$active);
        $this->json(['ok' => true, 'is_active' => $active]);
    }
}
