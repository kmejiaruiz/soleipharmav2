-- 1. Add cost and profit fields to products table
-- We check if columns exist before adding them (MariaDB 10.2+)
-- Since we can't reliably use IF NOT EXISTS for columns in older MariaDB, we might use a procedure or just run the ALTER and ignore error if column exists, 
-- but a simpler way is just to run the ALTER. The user can handle "Column already exists" if re-running.

-- Adding cost, utility_percent, calculating sale_price from cost + margin + tax
ALTER TABLE products ADD COLUMN IF NOT EXISTS cost DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE products ADD COLUMN IF NOT EXISTS utility_percent DECIMAL(5,2) DEFAULT 30.00;
ALTER TABLE products ADD COLUMN IF NOT EXISTS tax_percent DECIMAL(5,2) DEFAULT 15.00;

-- 2. Create goods_entries table (merchandise entry header)
CREATE TABLE IF NOT EXISTS goods_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    received_by INT NOT NULL,
    invoice_subtotal DECIMAL(10,2) NOT NULL,
    invoice_tax DECIMAL(10,2) NOT NULL,
    system_subtotal DECIMAL(10,2) NOT NULL,
    system_tax DECIMAL(10,2) NOT NULL,
    system_total DECIMAL(10,2) NOT NULL,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (received_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 3. Create goods_entry_items table (merchandise entry details)
CREATE TABLE IF NOT EXISTS goods_entry_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goods_entry_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_received INT NOT NULL,
    justification TEXT DEFAULT NULL,
    FOREIGN KEY (goods_entry_id) REFERENCES goods_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 4. Create product_orders table (if not exists, as OrderController uses this table name)
CREATE TABLE IF NOT EXISTS product_orders (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) DEFAULT NULL,
  total DECIMAL(10,2) NOT NULL,
  status VARCHAR(50) DEFAULT 'pendiente',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  applied_by INT(11) DEFAULT NULL,
  applied_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 5. Create product_order_items table
CREATE TABLE IF NOT EXISTS product_order_items (
  id INT(11) NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,
  product_id INT(11) NOT NULL,
  quantity INT(11) DEFAULT 1,
  price DECIMAL(10,2) NOT NULL, -- This stores sale price at purchase moment? Or cost? Controller implies cost or price? Usually cost in orders to suppliers.
  PRIMARY KEY (id),
  KEY order_id (order_id),
  KEY product_id (product_id),
  FOREIGN KEY (order_id) REFERENCES product_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
