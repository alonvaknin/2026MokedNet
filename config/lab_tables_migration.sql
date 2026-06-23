-- ============================================================
-- Lab Inventory Tables Migration — הרץ ב-alon_db2
-- ============================================================

USE `alon_db2`;

-- ⚠️  DROP מושבת בכוונה — הרצה חוזרת מחקה את כל הנתונים בעבר.
-- אם אתה יוצר את הטבלאות בפעם הראשונה, הסר את ה-comment מה-DROP בעצמך.
-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE IF EXISTS `lab_inventory_logs`;
-- DROP TABLE IF EXISTS `lab_inventory_movements`;
-- DROP TABLE IF EXISTS `lab_inventory_items`;
-- SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS `lab_inventory_items` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `part_number`      VARCHAR(100)  NULL,
    `barcode`          VARCHAR(100)  NULL,
    `product_name_en`  VARCHAR(255)  NOT NULL DEFAULT '',
    `tags`             VARCHAR(500)  NULL,
    `compatibility`    TEXT          NULL,
    `model`            VARCHAR(150)  NULL,
    `manufacturer`     VARCHAR(100)  NULL,
    `location`         VARCHAR(150)  NULL,
    `price_store`      DECIMAL(10,2) NULL,
    `qty`              INT           NOT NULL DEFAULT 0,
    `incoming_qty`     INT           NOT NULL DEFAULT 0,
    `min_qty`          INT           NOT NULL DEFAULT 0,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_part`   (`part_number`),
    INDEX `idx_barcode`(`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lab_inventory_movements` (
    `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_id`         INT UNSIGNED    NOT NULL,
    `direction`       ENUM('IN','OUT') NOT NULL DEFAULT 'OUT',
    `qty`             INT             NOT NULL DEFAULT 0,
    `user_id`         INT             NULL,
    `technician_id`   INT             NULL,
    `service_call_id` VARCHAR(50)     NULL,
    `notes`           VARCHAR(500)    NULL,
    `serial_number`   VARCHAR(150)    NULL,
    `status`          ENUM('pending','approved') NOT NULL DEFAULT 'approved',
    `movement_date`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_item`  (`item_id`),
    INDEX `idx_date`  (`movement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Done — lab_inventory_items and lab_inventory_movements created in alon_db2' AS status;
