<?php
declare(strict_types=1);

namespace Models;

use Core\DB;

class InvoiceChangeNameModel
{
    private const EDITABLE_FIELDS = [
        'new_name', 'invoice_note', 'invoice_sap_number',
        'customer_name', 'customer_phone', 'customer_mail',
    ];

    public static function all(): array
    {
        return DB::query(
            "SELECT * FROM invoice_change_name
             WHERE isActive = 1
             ORDER BY
               CASE status
                 WHEN 'פתוחה'        THEN 1
                 WHEN 'בהמתנה'       THEN 2
                 WHEN 'תקלה בפרטים' THEN 3
                 WHEN 'טופלה + מייל' THEN 4
                 WHEN 'סגורה'        THEN 5
                 ELSE 6
               END,
               time_change_status DESC"
        );
    }

    public static function byId(int $id): ?array
    {
        return DB::row(
            'SELECT * FROM invoice_change_name WHERE id = ? AND isActive = 1',
            [$id]
        );
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO invoice_change_name
                (open_by_id, open_by_name, new_name, invoice_sap_number,
                 invoice_note, customer_phone, customer_mail, customer_name)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['open_by_id'],
                $data['open_by_name'],
                $data['new_name'],
                $data['invoice_sap_number'],
                $data['invoice_note'],
                $data['customer_phone'],
                $data['customer_mail'],
                $data['customer_name'],
            ]
        );
    }

    public static function updateStatus(int $id, string $status, string $careBy): bool
    {
        $rows = DB::execute(
            'UPDATE invoice_change_name
             SET status = ?, care_by = ?, time_change_status = NOW()
             WHERE id = ? AND isActive = 1',
            [$status, $careBy, $id]
        );
        return $rows > 0;
    }

    public static function editField(int $id, string $field, string $value): bool
    {
        if (!in_array($field, self::EDITABLE_FIELDS, true)) {
            return false;
        }
        $rows = DB::execute(
            "UPDATE invoice_change_name SET `{$field}` = ? WHERE id = ? AND isActive = 1",
            [$value, $id]
        );
        return $rows > 0;
    }

    public static function checkDuplicate(string $invoiceNum): bool
    {
        $count = DB::value(
            "SELECT COUNT(*) FROM invoice_change_name
             WHERE invoice_sap_number = ? AND isActive = 1
               AND status NOT IN ('סגורה','טופלה + מייל')",
            [$invoiceNum]
        );
        return (int)$count > 0;
    }
}
