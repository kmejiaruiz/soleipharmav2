-- migrations/005_branch_stock.sql (CORREGIDA)
-- Stock independiente por sucursal

-- ── Tabla principal: stock por producto + sucursal ────────────────────────────
CREATE TABLE IF NOT EXISTS `branch_product_stock` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `branch`     VARCHAR(100) NOT NULL,
  `stock`      INT NOT NULL DEFAULT 0,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_prod_branch` (`product_id`, `branch`),
  CONSTRAINT `fk_bps_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Agregar columna branch a bodega_stock ─────────────────────────────────────
ALTER TABLE `bodega_stock`
  ADD COLUMN IF NOT EXISTS `branch` VARCHAR(100) NOT NULL DEFAULT '' AFTER `bodega`;

-- Primero eliminar la FK que depende del índice, luego el índice, luego recrear ambos
ALTER TABLE `bodega_stock`
  DROP FOREIGN KEY IF EXISTS `fk_bs_product`;

ALTER TABLE `bodega_stock`
  DROP INDEX IF EXISTS `uq_product_bodega`;

ALTER TABLE `bodega_stock`
  ADD UNIQUE KEY `uq_product_bodega_branch` (`product_id`, `bodega`, `branch`),
  ADD CONSTRAINT `fk_bs_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE;

-- ── Agregar columna branch a bodega_movements ─────────────────────────────────
ALTER TABLE `bodega_movements`
  ADD COLUMN IF NOT EXISTS `branch` VARCHAR(100) NOT NULL DEFAULT '' AFTER `to_bodega`;
