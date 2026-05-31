-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-05-2026 a las 15:53:19
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sp_perfect_color`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `activo`, `cedula`, `nombres`, `apellidos`, `telefono`, `correo`, `direccion`, `fecha_registro`) VALUES
(6, 1, '28679228', 'Franyel David', 'Pacheco', '04245544956', 'pachecos@ejemplo.com', 'Av. Vargas', '2026-05-30 21:44:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_cobrar`
--

CREATE TABLE `cuentas_cobrar` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cliente_id` int(11) NOT NULL,
  `nota_entrega_id` int(11) DEFAULT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','pagado','moroso') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cuentas_cobrar`
--

INSERT INTO `cuentas_cobrar` (`id`, `activo`, `cliente_id`, `nota_entrega_id`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `estado`, `created_at`) VALUES
(15, 1, 6, 53, 2000.00, 0.00, '2026-06-01', 'pagado', '2026-05-30 21:46:51'),
(16, 1, 6, 54, 4000.00, 0.00, '2026-06-06', 'pagado', '2026-05-31 02:24:31'),
(17, 1, 6, 55, 2000.00, 0.00, '2026-05-30', 'pagado', '2026-05-31 15:02:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_pagar`
--

CREATE TABLE `cuentas_pagar` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `proveedor_id` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cuentas_pagar`
--

INSERT INTO `cuentas_pagar` (`id`, `activo`, `proveedor_id`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `estado`, `created_at`) VALUES
(5, 1, 3, 5000.00, 0.00, '2026-06-05', 'pagado', '2026-05-30 23:26:54'),
(6, 1, 3, 5000000.00, 0.00, '2026-06-05', 'pagado', '2026-05-30 23:56:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(30) DEFAULT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT 5.00,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_compra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_vencimiento` date DEFAULT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `insumos`
--

INSERT INTO `insumos` (`id`, `activo`, `codigo`, `nombre`, `marca`, `categoria`, `unidad_medida`, `stock_actual`, `stock_minimo`, `precio_venta`, `precio_compra`, `fecha_vencimiento`, `proveedor_id`, `created_at`, `updated_at`) VALUES
(3, 1, '001', 'Industriacos', 'Groud', 'Tintes', 'Kilogramo', 12.00, 5.00, 2000.00, 977.00, '2026-08-30', 3, '2026-05-30 21:45:01', '2026-05-31 15:48:46'),
(4, 1, '002', 'Breaking Bad', 'Walter', 'Quimicos', 'Litro', 17.00, 5.00, 1200.00, 750.00, '2026-06-09', 3, '2026-05-31 15:48:15', '2026-05-31 15:48:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas_entrega`
--

CREATE TABLE `notas_entrega` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','entregado') NOT NULL DEFAULT 'pendiente',
  `presupuesto_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notas_entrega`
--

INSERT INTO `notas_entrega` (`id`, `activo`, `cliente_id`, `usuario_id`, `fecha`, `total`, `estado`, `presupuesto_id`, `created_at`) VALUES
(52, 1, 6, 2, '2026-05-30', 6000.00, 'entregado', 4, '2026-05-30 21:46:00'),
(53, 1, 6, 2, '2026-05-30', 2000.00, 'entregado', 5, '2026-05-30 21:46:51'),
(54, 1, 6, 5, '2026-05-30', 4000.00, 'entregado', NULL, '2026-05-31 02:24:31'),
(55, 1, 6, 2, '2026-05-31', 2000.00, 'entregado', 6, '2026-05-31 15:02:56'),
(56, 1, 6, 2, '2026-05-31', 5600.00, 'entregado', 7, '2026-05-31 15:48:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nota_entrega_detalle`
--

CREATE TABLE `nota_entrega_detalle` (
  `id` int(11) NOT NULL,
  `nota_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nota_entrega_detalle`
--

INSERT INTO `nota_entrega_detalle` (`id`, `nota_id`, `insumo_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(52, 52, 3, 3.00, 2000.00, 6000.00),
(53, 53, 3, 1.00, 2000.00, 2000.00),
(54, 54, 3, 2.00, 2000.00, 4000.00),
(55, 55, 3, 1.00, 2000.00, 2000.00),
(56, 56, 3, 1.00, 2000.00, 2000.00),
(57, 56, 4, 3.00, 1200.00, 3600.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_realizados`
--

CREATE TABLE `pagos_realizados` (
  `id` int(11) NOT NULL,
  `cuenta_pagar_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `metodo_pago` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos_realizados`
--

INSERT INTO `pagos_realizados` (`id`, `cuenta_pagar_id`, `monto`, `fecha`, `metodo_pago`) VALUES
(1, 6, 5000000.00, '2026-05-31', 'Transferencia'),
(2, 5, 5000.00, '2026-05-31', 'Pago Movil');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_recibidos`
--

CREATE TABLE `pagos_recibidos` (
  `id` int(11) NOT NULL,
  `cuenta_cobrar_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `metodo_pago` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos_recibidos`
--

INSERT INTO `pagos_recibidos` (`id`, `cuenta_cobrar_id`, `monto`, `fecha`, `metodo_pago`) VALUES
(3, 15, 2000.00, '2026-05-30', 'Transferencia'),
(4, 16, 2500.00, '2026-05-31', 'Efectivo'),
(5, 16, 1500.00, '2026-05-31', 'Efectivo'),
(6, 17, 2000.00, '2026-05-31', 'Efectivo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presupuestos`
--

CREATE TABLE `presupuestos` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','aprobado','rechazado','convertido') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `presupuestos`
--

INSERT INTO `presupuestos` (`id`, `activo`, `cliente_id`, `usuario_id`, `fecha`, `total`, `estado`, `observaciones`, `created_at`) VALUES
(4, 1, 6, 2, '2026-05-30', 6000.00, 'convertido', 'Le informamos...', '2026-05-30 21:45:25'),
(5, 1, 6, 2, '2026-05-30', 2000.00, 'convertido', '', '2026-05-30 21:46:34'),
(6, 1, 6, 2, '2026-05-31', 2000.00, 'convertido', '', '2026-05-31 15:02:42'),
(7, 1, 6, 2, '2026-05-31', 5600.00, 'convertido', '', '2026-05-31 15:48:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presupuesto_detalle`
--

CREATE TABLE `presupuesto_detalle` (
  `id` int(11) NOT NULL,
  `presupuesto_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `presupuesto_detalle`
--

INSERT INTO `presupuesto_detalle` (`id`, `presupuesto_id`, `insumo_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(4, 4, 3, 3.00, 2000.00, 6000.00),
(5, 5, 3, 1.00, 2000.00, 2000.00),
(6, 6, 3, 1.00, 2000.00, 2000.00),
(7, 7, 4, 3.00, 1200.00, 3600.00),
(8, 7, 3, 1.00, 2000.00, 2000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `rif` varchar(20) NOT NULL,
  `nombre_empresa` varchar(150) NOT NULL,
  `direccion` text DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `rubros` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `activo`, `rif`, `nombre_empresa`, `direccion`, `contacto`, `telefono`, `correo`, `rubros`, `created_at`) VALUES
(3, 1, 'J-285168755', 'Polar', 'Av. polar', 'Juan', '04245544656', 'polar@polar.com', 'Cervezas', '2026-05-29 20:16:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `created_at`) VALUES
(1, 'Administrador', '2026-05-06 22:27:00'),
(2, 'Vendedor', '2026-05-06 22:27:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password_hash`, `rol_id`, `activo`, `created_at`) VALUES
(2, 'Administrador', 'admin@perfectcolor.com', '$2y$10$iieMsBMURX3rO8YB3vPHt.AETjKTK3AuD6avNwY/t0fzKA4yiXIGe', 1, 1, '2026-05-11 12:53:06'),
(5, 'Fran', 'pacheco@ejemplo.com', '$2y$10$veXHly/o4LhKg1qwKN2aEuB/4uK1XRQ.dxMFaA1DmUc2tJYprjIZS', 2, 1, '2026-05-24 22:07:07');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `nota_entrega_id` (`nota_entrega_id`);

--
-- Indices de la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `notas_entrega`
--
ALTER TABLE `notas_entrega`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `presupuesto_id` (`presupuesto_id`);

--
-- Indices de la tabla `nota_entrega_detalle`
--
ALTER TABLE `nota_entrega_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nota_id` (`nota_id`),
  ADD KEY `insumo_id` (`insumo_id`);

--
-- Indices de la tabla `pagos_realizados`
--
ALTER TABLE `pagos_realizados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuenta_pagar_id` (`cuenta_pagar_id`);

--
-- Indices de la tabla `pagos_recibidos`
--
ALTER TABLE `pagos_recibidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuenta_cobrar_id` (`cuenta_cobrar_id`);

--
-- Indices de la tabla `presupuestos`
--
ALTER TABLE `presupuestos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `presupuesto_detalle`
--
ALTER TABLE `presupuesto_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `presupuesto_id` (`presupuesto_id`),
  ADD KEY `insumo_id` (`insumo_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rif` (`rif`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `notas_entrega`
--
ALTER TABLE `notas_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de la tabla `nota_entrega_detalle`
--
ALTER TABLE `nota_entrega_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `pagos_realizados`
--
ALTER TABLE `pagos_realizados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pagos_recibidos`
--
ALTER TABLE `pagos_recibidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `presupuestos`
--
ALTER TABLE `presupuestos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `presupuesto_detalle`
--
ALTER TABLE `presupuesto_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  ADD CONSTRAINT `cuentas_cobrar_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `cuentas_cobrar_ibfk_3` FOREIGN KEY (`nota_entrega_id`) REFERENCES `notas_entrega` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  ADD CONSTRAINT `cuentas_pagar_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD CONSTRAINT `insumos_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `notas_entrega`
--
ALTER TABLE `notas_entrega`
  ADD CONSTRAINT `notas_entrega_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_entrega_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_entrega_ibfk_3` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `nota_entrega_detalle`
--
ALTER TABLE `nota_entrega_detalle`
  ADD CONSTRAINT `nota_entrega_detalle_ibfk_1` FOREIGN KEY (`nota_id`) REFERENCES `notas_entrega` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `nota_entrega_detalle_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_realizados`
--
ALTER TABLE `pagos_realizados`
  ADD CONSTRAINT `pagos_realizados_ibfk_1` FOREIGN KEY (`cuenta_pagar_id`) REFERENCES `cuentas_pagar` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_recibidos`
--
ALTER TABLE `pagos_recibidos`
  ADD CONSTRAINT `pagos_recibidos_ibfk_1` FOREIGN KEY (`cuenta_cobrar_id`) REFERENCES `cuentas_cobrar` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `presupuestos`
--
ALTER TABLE `presupuestos`
  ADD CONSTRAINT `presupuestos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `presupuestos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `presupuesto_detalle`
--
ALTER TABLE `presupuesto_detalle`
  ADD CONSTRAINT `presupuesto_detalle_ibfk_1` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `presupuesto_detalle_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
