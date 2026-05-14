-- migrations/004_bodega_system.sql
-- Sistema de bodegas independientes: stock por bodega + historial de traslados

-- ── Tabla: bodega_stock ──────────────────────────────────────────────────────
-- Guarda el stock de cada producto en las bodegas secundarias (débito y merma).
-- La Sucursal sigue usando products.stock para compatibilidad con el sistema existente.
CREATE TABLE IF NOT EXISTS `bodega_stock` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `product_id` INT(11)      NOT NULL,
  `bodega`     ENUM('debito','merma') NOT NULL,
  `stock`      INT(11)      NOT NULL DEFAULT 0,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_bodega` (`product_id`, `bodega`),
  KEY `idx_bodega` (`bodega`),
  CONSTRAINT `fk_bs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla: bodega_movements ──────────────────────────────────────────────────
-- Registra cada traslado de unidades entre bodegas.
CREATE TABLE IF NOT EXISTS `bodega_movements` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `product_id`  INT(11)      NOT NULL,
  `from_bodega` ENUM('sucursal','debito','merma') NOT NULL,
  `to_bodega`   ENUM('sucursal','debito','merma') NOT NULL,
  `quantity`    INT(11)      NOT NULL,
  `reason`      TEXT         DEFAULT NULL,
  `user_id`     INT(11)      NOT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bm_product`  (`product_id`),
  KEY `idx_bm_user`     (`user_id`),
  KEY `idx_bm_created`  (`created_at`),
  CONSTRAINT `fk_bm_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_bm_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
