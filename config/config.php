<?php
declare(strict_types=1);

$base = [

    'app' => [
        'name'     => 'מוקדנט',
        'debug'    => false,
        'url'      => 'https://alon.alexisdeveloping.com',
        'timezone' => 'Asia/Jerusalem',
        'version'  => '2.0.0',
    ],

    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'alon_db2',
        'user'    => 'alonbaboon',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // DB של V1 — לטבלאות משותפות (CronJob, callStatus וכו')
    // ה-credentials האמיתיים מוגדרים ב-local.php
    'db_v1' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'alon_db',
        'user'    => 'alonbaboon',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'lifetime' => 0,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => true,
    ],

    'tables' => [
        'users'           => 'users',
        'stores'          => 'stores',
        'tasks'           => 'tasks',
        'departments'     => 'departments',
        'perm_groups'     => 'permission_groups',
        'perm_grants'     => 'permission_group_grants',
        'nav_items'       => 'nav_items',
        'nav_permissions' => 'nav_permissions',
        'lab_items'       => 'lab_inventory_items',
        'lab_movements'   => 'lab_inventory_movements',
        'lab_logs'        => 'lab_inventory_logs',
    ],

];

$local = file_exists(__DIR__ . '/local.php')
    ? (require __DIR__ . '/local.php')
    : [];

return array_replace_recursive($base, $local);
