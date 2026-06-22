<?php

declare(strict_types=1);

namespace Models;

use Core\DB;

class FormatterModel
{
    // ── Templates ────────────────────────────────────────────────────────────

    public static function allTemplates(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active=1' : '';
        return DB::query(
            "SELECT * FROM formatter_templates $where ORDER BY category, sort_order, name",
            []
        );
    }

    public static function templateById(int $id): ?array
    {
        return DB::row("SELECT * FROM formatter_templates WHERE id=? LIMIT 1", [$id]);
    }

    public static function categorised(bool $activeOnly = true): array
    {
        $all = self::allTemplates($activeOnly);
        $out = [];
        foreach ($all as $t) {
            $out[$t['category']][] = $t;
        }
        return $out;
    }

    public static function saveTemplate(array $d, int $id = 0): int
    {
        $cols = [
            'name',
            'category',
            'description',
            'body_male',
            'body_female',
            'mail_to',
            'mail_cc',
            'mail_subject',
            'is_active',
            'sort_order'
        ];
        $vals = array_map(fn($c) => $d[$c] ?? null, $cols);

        if ($id) {
            $set = implode(',', array_map(fn($c) => "$c=?", $cols));
            DB::execute(
                "UPDATE formatter_templates SET $set,updated_at=NOW() WHERE id=?",
                [...$vals, $id]
            );
            return $id;
        }

        // INSERT — בנה עמודות ו-placeholders בצורה מדויק	
        $allCols = [...$cols, 'created_by'];
        $allVals = [...$vals, $d['created_by'] ?? null];
        $placeholders = implode(',', array_fill(0, count($allCols), '?'));
        $colList      = implode(',', $allCols);

        return DB::insert(
            "INSERT INTO formatter_templates ($colList) VALUES ($placeholders)",
            $allVals
        );
    }

    public static function deleteTemplate(int $id): void
    {
        DB::execute("DELETE FROM formatter_templates WHERE id=?", [$id]);
    }

    public static function toggleTemplate(int $id): int
    {
        DB::execute("UPDATE formatter_templates SET is_active=1-is_active WHERE id=?", [$id]);
        return (int)DB::value("SELECT is_active FROM formatter_templates WHERE id=?", [$id]);
    }

    // ── Fields ───────────────────────────────────────────────────────────────

    public static function fieldsByTemplate(int $templateId): array
    {
        return DB::query(
            "SELECT * FROM formatter_fields WHERE template_id=? ORDER BY sort_order, id",
            [$templateId]
        );
    }

    public static function replaceFields(int $templateId, array $fields): void
    {
        DB::execute("DELETE FROM formatter_fields WHERE template_id=?", [$templateId]);
        if (!$fields) return;
        $stmt = DB::get()->prepare(
            "INSERT INTO formatter_fields
             (template_id, field_key, label, field_type, placeholder, options, required, sort_order)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        foreach ($fields as $i => $f) {
            $stmt->execute([
                $templateId,
                trim($f['field_key']),
                trim($f['label']),
                $f['field_type'] ?? 'text',
                $f['placeholder'] ?? null,
                isset($f['options']) && $f['options'] ? json_encode($f['options'], JSON_UNESCAPED_UNICODE) : null,
                ($f['required'] ?? 0) ? 1 : 0,
                $f['sort_order'] ?? $i,
            ]);
        }
    }

    // ── Full template with fields ─────────────────────────────────────────────

    public static function fullTemplate(int $id): ?array
    {
        $tpl = self::templateById($id);
        if (!$tpl) return null;
        $tpl['fields'] = self::fieldsByTemplate($id);
        // decode options JSON
        foreach ($tpl['fields'] as &$f) {
            if ($f['options']) {
                $f['options'] = json_decode($f['options'], true) ?? [];
            }
        }
        return $tpl;
    }

    // ── Store list (from alon_db via cross-db query) ──────────────────────────

    public static function storeList(): array
    {
        // Uses alon_db.stores via cross-db reference
        try {
            return DB::query(
                "SELECT s.store_num, s.name,
                        CONCAT(s.store_num,' ',s.name,' / ',SUBSTRING_INDEX(COALESCE(s.manager_name,''),\" \",1)) AS label,
                        COALESCE(s.phone_main,'') AS mail
                 FROM stores s
                 WHERE s.is_active=1 AND s.type='סניף באג'
                 ORDER BY s.store_num ASC",
                []
            );
        } catch (\Throwable $e) {
            return [];
        }
    }
}
