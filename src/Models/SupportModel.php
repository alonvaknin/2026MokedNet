<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class SupportModel
{
    /** כל הקטגוריות */
    public static function categories(): array
    {
        return DB::query(
            'SELECT id, Cname AS name, background_color AS color
             FROM SupportProductsCategory
             ORDER BY Cname'
        );
    }

    /** מוצרים לפי קטגוריה */
    public static function productsByCategory(int $catId): array
    {
        return DB::query(
            'SELECT p.id, p.pModel AS model, p.pBarcodeBug AS barcode,
                    m.manfName AS manufacturer, p.tags
             FROM SupportProducts p
             LEFT JOIN SupportProductsManufactures m ON m.id = p.pManufacture
             WHERE p.pCategory = ? AND p.ACTIVE = 1
             ORDER BY p.pModel',
            [$catId]
        );
    }

    /** חיפוש מוצר לפי ברקוד / שם */
    public static function searchProduct(string $q): array
    {
        $like = '%' . trim($q) . '%';
        return DB::query(
            'SELECT p.id, p.pModel AS model, p.pBarcodeBug AS barcode,
                    c.Cname AS category, m.manfName AS manufacturer
             FROM SupportProducts p
             LEFT JOIN SupportProductsCategory c ON c.id = p.pCategory
             LEFT JOIN SupportProductsManufactures m ON m.id = p.pManufacture
             WHERE p.ACTIVE = 1
               AND (p.pModel LIKE ? OR p.pBarcodeBug LIKE ? OR p.tags LIKE ?)
             ORDER BY p.pModel LIMIT 30',
            [$like, $like, $like]
        );
    }

    /** בעיות ופתרונות לפי ברקוד או קטגוריה */
    public static function issuesByProduct(?string $barcode, ?int $catId): array
    {
        if (!$barcode && !$catId) return [];
        return DB::query(
            'SELECT id, issue_sort AS title, issue_solution AS solution
             FROM supportIssues
             WHERE (item_codes = ? OR item_categories = ?)
               AND active = 1
             ORDER BY id DESC',
            [$barcode, (string)$catId]
        );
    }

    /** הוספת בעיה/פתרון */
    public static function addIssue(array $data): int
    {
        return DB::insert(
            'INSERT INTO supportIssues
                (issue_sort, issue_solution, item_categories, item_codes, added_by_user, active)
             VALUES (?, ?, ?, ?, ?, 1)',
            [
                $data['title'],
                $data['solution'],
                $data['cat_id']   ?? null,
                $data['barcode']  ?? null,
                $data['user_id'],
            ]
        );
    }

    /** כל הבעיות לניהול */
    public static function allIssues(): array
    {
        return DB::query(
            'SELECT i.id, i.issue_sort AS title, i.issue_solution AS solution,
                    i.item_codes AS barcode, i.item_categories AS cat_id,
                    i.active,
                    c.Cname AS cat_name,
                    CONCAT(a.fName," ",a.lName) AS added_by
             FROM supportIssues i
             LEFT JOIN SupportProductsCategory c ON c.id = i.item_categories
             LEFT JOIN accounts a ON a.id = i.added_by_user
             ORDER BY i.id DESC'
        );
    }
}
