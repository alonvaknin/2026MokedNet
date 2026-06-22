<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $instance    = null;
    private static ?PDO $instanceV1 = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $c = CFG['db'];
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
            try {
                self::$instance = new PDO($dsn, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('שגיאת חיבור למסד נתונים');
            }
        }
        return self::$instance;
    }

    /**
     * חיבור ל-DB של V1 (alon_db) — לטבלאות משותפות כמו CronJob, callStatus
     */
    public static function v1(): PDO
    {
        if (self::$instanceV1 === null) {
            $c = CFG['db_v1'];
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
            try {
                self::$instanceV1 = new PDO($dsn, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB v1 connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('שגיאת חיבור ל-DB של V1');
            }
        }
        return self::$instanceV1;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function row(string $sql, array $params = []): ?array
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::execute($sql, $params);
        return (int) self::get()->lastInsertId();
    }

    // ── V1 variants (alon_db) ────────────────────────────────────────────────

    public static function v1Query(string $sql, array $params = []): array
    {
        $stmt = self::v1()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function v1Row(string $sql, array $params = []): ?array
    {
        $stmt = self::v1()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function v1Value(string $sql, array $params = []): mixed
    {
        $stmt = self::v1()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function v1Execute(string $sql, array $params = []): int
    {
        $stmt = self::v1()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function v1Insert(string $sql, array $params = []): int
    {
        self::v1Execute($sql, $params);
        return (int) self::v1()->lastInsertId();
    }
}
