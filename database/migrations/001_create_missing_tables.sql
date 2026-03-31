-- Migración para crear tablas faltantes
-- discard_requests, product_orders, product_order_items, goods_entries, goods_entry_items

-- --------------------------------------------------------
-- Tabla: discard_requests
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `discard_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `decision_by` int(11) DEFAULT NULL,
  `decision_reason` text DEFAULT NULL,
  `decision_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: product_orders (Pedidos de resurtido)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `admin_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','applied','received') DEFAULT 'pending',
  `order_date` datetime DEFAULT current_timestamp(),
  `applied_by` int(11) DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: product_order_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: goods_entries (Entrada de mercancía)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `goods_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `invoice_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `invoice_tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `system_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `system_tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `system_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `received_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla: goods_entry_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `goods_entry_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_entry_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `justification` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_entry_id` (`goods_entry_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Foreign Keys (opcional, pero recomendado)
-- ALTER TABLE `product_order_items` ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `product_orders` (`id`) ON DELETE CASCADE;
-- ... se pueden agregar más si se desea estricta integridad referencial
