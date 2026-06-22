<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\DB;
use Models\NavManagerModel;

class NavController extends Controller
{
    public function getNav(): void
    {
        // API endpoint — אם לא מחובר מחזיר JSON ריק במקום redirect
        if (!\Core\Auth::check()) {
            $this->json([]);
            return;
        }
        $groupId = (int)($_SESSION['perm_group'] ?? 0);

        // נסה alon_db2 קודם
        try {
            $items = NavManagerModel::getNavForGroup($groupId);
            if (!empty($items)) {
                $this->json($this->formatV2Nav($items));
                return;
            }
        } catch (\Throwable) {}

        // Fallback → navBar של v1
        $this->json($this->getV1Nav($groupId));
    }

    private function formatV2Nav(array $items): array
    {
        return array_map(fn($i) => [
            'id'              => $i['id'],
            'navNameHEB'      => $i['name_heb'] ?? $i['label_he'] ?? '',
            'navLink'         => $i['link'] ?? '',
            'navLinkType'     => $i['link_type'] ?? 'url',
            'icon'            => $i['icon'] ?? '',
            // parent = אין link ואין parent_id (כלומר container)
            'isParent'        => (empty($i['link']) && empty($i['parent_id'])) ? 1 : 0,
            'isSubMenu'       => !empty($i['parent_id']) ? 1 : (empty($i['link']) ? 0 : 1),
            'mainNavContainer'=> empty($i['parent_id']) ? 1 : 0,
            'parentID'        => $i['parent_id'],
            'ordering'        => $i['ordering'],
            'toBlank'         => (int)($i['open_blank'] ?? $i['open_in_blank'] ?? 0),
            'navPermmission'  => $i['permission'] ?? 'all',
        ], $items);
    }

    private function getV1Nav(int $groupId): array
    {
        $items = DB::query(
            'SELECT id, navNameHEB, navLink, navLinkType, icon,
                    isParent, isSubMenu, mainNavContainer, parentID,
                    ordering, toBlank, navPermmission
             FROM navBar WHERE isActive = 1 ORDER BY ordering ASC'
        );

        return array_values(array_filter($items, function ($item) use ($groupId) {
            $perm = $item['navPermmission'] ?? 'all';
            if ($perm === 'all' || $perm === '') return true;
            return in_array((string)$groupId, array_map('trim', explode(',', $perm)), true);
        }));
    }
}
