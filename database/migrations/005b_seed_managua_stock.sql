-- migrations/005b_seed_managua_stock.sql
-- Siembra el stock existente de products.stock en branch_product_stock
-- para la sucursal registrada en config/app.json en el momento de la primera instalación.
-- NOTA: ejecutar solo una vez. León arranca en 0 automáticamente.

-- Este script usa una variable SQL para insertar el nombre de sucursal dinámicamente.
-- Si tu sucursal se llama diferente, ajusta el valor de @branch_name.

SET @branch_name = (
    SELECT DISTINCT branch FROM users WHERE branch IS NOT NULL AND branch != '' LIMIT 1
);

INSERT INTO branch_product_stock (product_id, branch, stock)
SELECT id, @branch_name, GREATEST(0, stock)
FROM products
WHERE @branch_name IS NOT NULL AND @branch_name != ''
ON DUPLICATE KEY UPDATE stock = VALUES(stock);
