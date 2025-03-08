-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 25-02-2025 a las 04:36:57
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "America/Managua";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pharmacy`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carousel`
--

CREATE TABLE `carousel` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `carousel`
--

INSERT INTO `carousel` (`id`, `image`, `title`, `description`, `created_at`) VALUES
(1, 'https://www.doctorclic.es/wp-content/uploads/2021/09/Matenimiento-preventivo.jpeg', 'En mantenimiento', 'Gracias por su interes, en este momento estamos en mantenimiento hasta el dia 25/2/25. Gracias', '2025-02-24 17:56:24'),
(3, 'https://www.mirringo.com.co/Portals/mirringo/Images/articulos-actualidad-gatuna/5-mitos-sobre-los-gatos-naranjas/caracteristicas-de-los-gatos-naranjas.png?ver=2024-10-30-115217-867', 'Nuevos productos a la venta', 'Nuevos articulos', '2025-02-24 21:05:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `cart`
--

INSERT INTO `cart` (`id`, `session_id`, `product_id`, `quantity`, `added_at`) VALUES
(1, 'dl0vq4v16b8c2dfbuvhr8jmmrr', 1, 2, '2025-02-18 19:45:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `change_type` enum('edit','stock_increase','stock_decrease','supplier_entry') NOT NULL,
  `previous_stock` int(11) DEFAULT NULL,
  `new_stock` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `inventory_log`
--

INSERT INTO `inventory_log` (`id`, `product_id`, `admin_id`, `admin_name`, `change_type`, `previous_stock`, `new_stock`, `description`, `created_at`) VALUES
(1, 1, 3, 'tforger', 'edit', 99, 99, 'Producto editado. Marcado como no disponible: Costos', '2025-02-20 21:30:37'),
(2, 7, 3, 'tforger', 'edit', 3, 3, 'Producto editado. ', '2025-02-20 21:50:15'),
(3, 2, 3, 'tforger', 'stock_increase', 1, 11, 'Se aumentÃ³ el stock en 10 unidades. Stock previo: 1, Stock nuevo: 11', '2025-02-23 19:40:43'),
(4, 4, 3, 'tforger', 'stock_increase', 1, 100, 'Se aumentÃ³ el stock en 99 unidades. Stock previo: 1, Stock nuevo: 100', '2025-02-23 19:49:41'),
(5, 7, 2, 'mmoraga', 'stock_increase', 3, 13, 'Se aumentÃ³ el stock en 10 unidades. Stock previo: 3, Stock nuevo: 13', '2025-02-23 20:19:44'),
(6, 7, 3, 'tforger', 'stock_increase', 13, 30, 'Se aumentÃ³ el stock en 17 unidades. Stock previo: 13, Stock nuevo: 30', '2025-02-23 20:23:01'),
(7, 7, 3, 'tforger', 'stock_decrease', 30, 1, 'Producto editado. ', '2025-02-23 20:26:45'),
(8, 7, 3, 'tforger', 'stock_increase', 1, 30, 'Se aumentÃ³ el stock en 29 unidades. Stock previo: 1, Stock nuevo: 30', '2025-02-23 20:27:31'),
(9, 8, 3, 'tforger', 'edit', 12, 12, 'Producto editado. ', '2025-02-23 20:55:09'),
(10, 1, 3, 'tforger', 'edit', 99, 99, 'Producto editado. Marcado como no disponible: Costo precio', '2025-02-23 21:03:30'),
(11, 11, 3, 'tforger', 'stock_increase', 0, 3, 'Se aumentÃ³ el stock en 3 unidades. Stock previo: 0, Stock nuevo: 3', '2025-02-24 17:16:36'),
(12, 11, 3, 'tforger', 'stock_increase', 3, 12, 'Se aumentÃ³ el stock en 9 unidades. Stock previo: 3, Stock nuevo: 12', '2025-02-24 17:18:14'),
(13, 8, 3, 'tforger', 'stock_increase', 0, 12, 'Se aumentÃ³ el stock en 12 unidades. Stock previo: 0, Stock nuevo: 12', '2025-02-24 18:41:07'),
(14, 13, 7, 'klmejia', 'stock_increase', 3, 9, 'Se aumentÃ³ el stock en 6 unidades. Stock previo: 3, Stock nuevo: 9', '2025-02-24 18:45:13'),
(15, 9, 3, 'tforger', 'stock_increase', 2, 9, 'Se aumentÃ³ el stock en 7 unidades. Stock previo: 2, Stock nuevo: 9', '2025-02-24 18:49:38'),
(16, 12, 7, 'klmejia', 'stock_increase', 1, 8, 'Se aumentÃ³ el stock en 7 unidades. Stock previo: 1, Stock nuevo: 8', '2025-02-24 20:55:55'),
(17, 12, 7, 'klmejia', 'stock_increase', 8, 17, 'Se aumentÃ³ el stock en 9 unidades. Stock previo: 8, Stock nuevo: 17', '2025-02-24 21:03:58'),
(18, 1, 3, 'tforger', 'edit', 99, 99, 'Producto actualizado.', '2025-02-24 21:09:10'),
(19, 1, 7, 'klmejia', 'edit', 99, 99, 'Producto actualizado.', '2025-02-24 21:09:29'),
(20, 1, 7, 'klmejia', 'edit', 1, 1, 'Producto actualizado.', '2025-02-24 21:11:07'),
(21, 1, 3, 'tforger', 'edit', 0, 0, 'Producto actualizado.', '2025-02-24 21:12:29'),
(22, 1, 7, 'klmejia', 'edit', 1, 1, 'Producto actualizado.', '2025-02-24 21:13:22'),
(23, 1, 7, 'klmejia', 'stock_increase', NULL, NULL, 'Se aumentÃ³ el stock en  unidades. Stock previo: , Stock nuevo: ', '2025-02-24 21:18:17'),
(24, 1, 7, 'klmejia', 'stock_increase', NULL, NULL, 'Se aumentÃ³ el stock en  unidades. Stock previo: , Stock nuevo: ', '2025-02-24 21:20:15'),
(25, 1, 7, 'klmejia', 'stock_increase', NULL, NULL, 'Se aumentÃ³ el stock en  unidades. Stock previo: , Stock nuevo: ', '2025-02-24 21:22:19'),
(26, 3, 7, 'klmejia', 'stock_increase', 0, 7, 'Se aumentÃ³ el stock en 7 unidades. Stock previo: 0, Stock nuevo: 7', '2025-02-24 21:24:38'),
(27, 3, 3, 'tforger', 'stock_increase', 7, 14, 'Se aumentÃ³ el stock en 7 unidades. Stock previo: 7, Stock nuevo: 14', '2025-02-24 21:25:05'),
(28, 1, 7, 'klmejia', 'stock_increase', 3, 13, 'Se aumentÃ³ el stock en 10 unidades. Stock previo: 3, Stock nuevo: 13', '2025-02-24 21:27:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pendiente',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `created_at`) VALUES
(1, 4, 119.97, 'completado', '2025-02-19 20:56:30'),
(2, 2, 239.94, 'completado', '2025-02-19 21:05:42'),
(3, 2, 599.90, 'completado', '2025-02-19 21:05:52'),
(4, 2, 4679.22, 'completado', '2025-02-19 21:06:04'),
(5, 3, 649.75, 'completado', '2025-02-19 21:19:24'),
(6, 3, 59.99, 'completado', '2025-02-19 21:32:52'),
(7, 5, 19.99, 'completado', '2025-02-19 21:40:38'),
(8, 5, 3959.01, 'completado', '2025-02-19 21:45:54'),
(9, 3, 199.90, 'completado', '2025-02-20 19:56:26'),
(10, 3, 191.99, 'completado', '2025-02-24 17:08:30'),
(11, 7, 2292.00, 'completado', '2025-02-24 18:40:10'),
(12, 7, 4739.21, 'completado', '2025-02-24 19:30:33'),
(13, 7, 49.99, 'completado', '2025-02-24 21:05:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 2, 3, 39.99),
(2, 2, 2, 6, 39.99),
(3, 3, 3, 10, 59.99),
(4, 4, 3, 78, 59.99),
(5, 5, 4, 25, 25.99),
(6, 6, 3, 1, 59.99),
(7, 7, 1, 1, 19.99),
(8, 8, 2, 99, 39.99),
(9, 9, 1, 10, 19.99),
(10, 10, 11, 1, 191.99),
(11, 11, 8, 12, 191.00),
(12, 12, 3, 79, 59.99),
(13, 13, 5, 1, 49.99);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `available` tinyint(1) DEFAULT 1,
  `reason_unavailable` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `stock`, `created_at`, `updated_at`, `available`, `reason_unavailable`) VALUES
(1, 'Camiseta Basica', 'Camiseta de algodon 100% de alta calidad, disponible en varios colores.', 24.98, 'camiseta.jpg', 13, '2025-02-18 19:15:50', '2025-02-24 21:27:59', 1, 'sin stock'),
(2, 'Jeans Clï¿½sico', 'Pantalï¿½n jeans clï¿½sico de corte recto y cï¿½modo.', 39.99, 'jeans.jpg', 11, '2025-02-18 19:15:50', '2025-02-23 19:40:43', 0, NULL),
(3, 'Zapatillas Deportivas', 'Zapatillas diseñadas para brindar comodidad y estilo en el día a día.', 59.99, 'zapatillas.jpg', 14, '2025-02-18 19:15:50', '2025-02-24 21:25:05', 1, NULL),
(4, 'Reloj de Pulsera RAKLO', 'Reloj elegante con correa de cuero y caja de acero inoxidable.', 25.99, 'reloj.jpg', 100, '2025-02-18 19:15:50', '2025-02-23 19:49:41', 1, NULL),
(5, 'Mochila Urbana', 'Mochila resistente ideal para el trabajo o la universidad.', 49.99, 'mochila.jpg', 119, '2025-02-18 19:15:50', '2025-02-24 21:05:41', 1, NULL),
(6, 'Panios multiuso', 'Multiuso', 1.89, 'none.jpg', 10, '2025-02-19 21:40:25', '2025-02-19 21:40:25', 1, NULL),
(7, 'Samsung Galaxy S24 Ultra', 'S24 Ultra Space Gray 1TB 12RAM', 1099.99, 'samsung.jpg', 30, '2025-02-20 21:49:54', '2025-02-23 20:27:31', 1, ''),
(8, 'Infinix HOT 50PRO +', '256ROM 8RAM', 191.00, 'infinix.jpg', 12, '2025-02-23 20:55:03', '2025-02-24 18:41:07', 1, ''),
(9, 'Infinix HOT 50PRO +', 'Infinix', 191.99, 'i.jpg', 9, '2025-02-23 21:33:36', '2025-02-24 18:49:38', 1, NULL),
(10, 'Infinix HOT 50PRO +', 'Infinix', 12.99, 'i.jpg', 12, '2025-02-23 21:35:09', '2025-02-23 21:35:09', 1, NULL),
(11, 'Infinix', 'Infinix', 191.99, 'i.jpg', 12, '2025-02-23 21:39:08', '2025-02-24 17:18:14', 1, ''),
(12, 'LG SOUNDBAR SQC1', 'Barra de sonido 2.1CH', 178.99, 'i.jpg', 17, '2025-02-24 16:40:55', '2025-02-24 21:03:58', 0, 'comercial autorizo su desactivacion por tiempo indefinido'),
(13, 'xScape  ', 'Michael Jackson\'s postume album vinile (Deluxe Edition)', 189.99, 'o.jpg', 9, '2025-02-24 17:16:01', '2025-02-24 18:45:13', 1, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(2, 'mmoraga', '$2y$10$KZDueWJR206yW3/EvfW6aOrZ4b3qZc6Oa4ZHWhVdQ6LmwGvqFOyZK', 'user', '2025-02-19 14:53:09'),
(3, 'tforger', '$2y$10$yfc9dmYMZxQf1KAEIPbRUeAwTeY4JnB5WjVkMZCOLWew4Dh53FZLO', 'admin', '2025-02-19 15:12:18'),
(4, 'kkira', '$2y$10$DEFJ9nGqpddVpIQ2bMN/yenEumUfEJhl742Q4/ZMlR2QlMam8/Yk2', 'user', '2025-02-19 20:53:49'),
(6, 'aaruiz', '$2y$10$ecQgAlfHnfUqxNiGg7yJdu2bgXjdMN.i3ZRk24OC6v8SN2mEZ9joO', 'user', '2025-02-20 19:57:15'),
(7, 'klmejia', '$2y$10$pcO2skxltSZYEWoJ//oDRu7Lf0hWdYDQ7CDFWC2drkM4bahURbzD.', 'admin', '2025-02-24 18:01:35');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carousel`
--
ALTER TABLE `carousel`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carousel`
--
ALTER TABLE `carousel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Filtros para la tabla `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
