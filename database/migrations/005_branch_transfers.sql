-- migrations/005_branch_transfers.sql
-- Traslados de inventario entre sucursales

CREATE TABLE IF NOT EXISTS `branch_transfers` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `from_branch`   VARCHAR(255) NOT NULL,
  `to_branch`     VARCHAR(255) NOT NULL,
  `status`        ENUM('pendiente','recibido','cancelado') NOT NULL DEFAULT 'pendiente',
  `notes`         TEXT         DEFAULT NULL,
  `created_by`    INT(11)      NOT NULL,
  `received_by`   INT(11)      DEFAULT NULL,
  `cancelled_by`  INT(11)      DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `received_at`   DATETIME     DEFAULT NULL,
  `cancelled_at`  DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bt_status`      (`status`),
  KEY `idx_bt_to_branch`   (`to_branch`(50)),
  KEY `idx_bt_from_branch` (`from_branch`(50)),
  CONSTRAINT `fk_bt_created`   FOREIGN KEY (`created_by`)   REFERENCES `users` (`id`),
  CONSTRAINT `fk_bt_received`  FOREIGN KEY (`received_by`)  REFERENCES `users` (`id`),
  CONSTRAINT `fk_bt_cancelled` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `branch_transfer_items` (
  `id`                INT(11) NOT NULL AUTO_INCREMENT,
  `transfer_id`       INT(11) NOT NULL,
  `product_id`        INT(11) NOT NULL,
  `quantity_sent`     INT(11) NOT NULL,
  `quantity_received` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bti_transfer` (`transfer_id`),
  KEY `idx_bti_product`  (`product_id`),
  CONSTRAINT `fk_bti_transfer` FOREIGN KEY (`transfer_id`) REFERENCES `branch_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bti_product`  FOREIGN KEY (`product_id`)  REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
