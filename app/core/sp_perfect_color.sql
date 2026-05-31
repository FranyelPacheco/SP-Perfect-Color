-- Simplified schema for SP Perfect Color
-- Removed unused tables: caja, compras, compra_detalle, factura_detalle
-- Removed FK to removed tables from facturas (caja_id), cuentas_pagar (compra_id FK)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

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

INSERT INTO `clientes` (`id`, `activo`, `cedula`, `nombres`, `apellidos`, `telefono`, `correo`, `direccion`, `fecha_registro`) VALUES
(3, 1, '28679228', 'wegt', 'ewg', '64657676837', 'admin@perfectcolor.com', 'ewgweg', '2026-05-11 17:56:42'),
(4, 1, '2867922', '24124', 'ggwe', '04245544955', 'gvuyv76@gmail.com', 'gweg  gweg', '2026-05-24 15:47:32');

-- --------------------------------------------------------

CREATE TABLE `cuentas_cobrar` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cliente_id` int(11) NOT NULL,
  `factura_id` int(11) DEFAULT NULL,
  `nota_entrega_id` int(11) DEFAULT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','pagado','moroso') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `cuentas_pagar` (
  `id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `proveedor_id` int(11) NOT NULL,
  `compra_id` int(11) DEFAULT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` enum('Efectivo','Punto de Venta','Pago Movil','Credito') NOT NULL,
  `estado` enum('pagado','pendiente','anulado') NOT NULL DEFAULT 'pagado',
  `nota_entrega_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `facturas` (`id`, `cliente_id`, `usuario_id`, `fecha`, `numero_factura`, `total`, `metodo_pago`, `estado`, `nota_entrega_id`, `created_at`) VALUES
(1, 3, 2, '2026-05-11', '20260511-0001', 45000.00, 'Efectivo', 'pagado', NULL, '2026-05-11 17:58:46');

-- --------------------------------------------------------

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

INSERT INTO `insumos` (`id`, `activo`, `codigo`, `nombre`, `marca`, `categoria`, `unidad_medida`, `stock_actual`, `stock_minimo`, `precio_venta`, `precio_compra`, `fecha_vencimiento`, `proveedor_id`, `created_at`, `updated_at`) VALUES
(1, 1, '001', 'Pepe', 'dwqd', 'Tintes', 'Galon', 8.00, 5.00, 15000.00, 1000.00, NULL, NULL, '2026-05-11 17:58:02', '2026-05-11 17:58:02');

-- --------------------------------------------------------

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

-- --------------------------------------------------------

CREATE TABLE `nota_entrega_detalle` (
  `id` int(11) NOT NULL,
  `nota_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `pagos_realizados` (
  `id` int(11) NOT NULL,
  `cuenta_pagar_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `metodo_pago` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `pagos_recibidos` (
  `id` int(11) NOT NULL,
  `cuenta_cobrar_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `metodo_pago` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

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

-- --------------------------------------------------------

CREATE TABLE `presupuesto_detalle` (
  `id` int(11) NOT NULL,
  `presupuesto_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

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

INSERT INTO `proveedores` (`id`, `activo`, `rif`, `nombre_empresa`, `direccion`, `contacto`, `telefono`, `correo`, `rubros`, `created_at`) VALUES
(2, 1, 'J-285168768', 'gre', 'feg', 'gfygfytf', '64657676837', 'w3qe421@gmail.com', 'fwefwef', '2026-05-11 17:57:37');

-- --------------------------------------------------------

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `nombre`, `created_at`) VALUES
(1, 'Administrador', '2026-05-06 22:27:00'),
(2, 'Vendedor', '2026-05-06 22:27:00');

-- --------------------------------------------------------

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password_hash`, `rol_id`, `activo`, `created_at`) VALUES
(2, 'Administrador', 'admin@perfectcolor.com', '$2y$10$iieMsBMURX3rO8YB3vPHt.AETjKTK3AuD6avNwY/t0fzKA4yiXIGe', 1, 1, '2026-05-11 12:53:06'),
(5, 'Pacheco', 'pacheco@ejemplo.com', '$2y$10$06YBSWASFWVqsB7Gx84.k.bi6lBHMFyjsLc0rihoIFnpoJuhPfAGK', 2, 1, '2026-05-24 22:07:07');

-- --------------------------------------------------------
-- Indexes
-- --------------------------------------------------------

ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`);

ALTER TABLE `cuentas_cobrar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `nota_entrega_id` (`nota_entrega_id`);

ALTER TABLE `cuentas_pagar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proveedor_id` (`proveedor_id`),
  ADD KEY `compra_id` (`compra_id`);

ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `nota_entrega_id` (`nota_entrega_id`);

ALTER TABLE `insumos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `proveedor_id` (`proveedor_id`);

ALTER TABLE `notas_entrega`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `presupuesto_id` (`presupuesto_id`);

ALTER TABLE `nota_entrega_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nota_id` (`nota_id`),
  ADD KEY `insumo_id` (`insumo_id`);

ALTER TABLE `pagos_realizados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuenta_pagar_id` (`cuenta_pagar_id`);

ALTER TABLE `pagos_recibidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuenta_cobrar_id` (`cuenta_cobrar_id`);

ALTER TABLE `presupuestos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

ALTER TABLE `presupuesto_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `presupuesto_id` (`presupuesto_id`),
  ADD KEY `insumo_id` (`insumo_id`);

ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rif` (`rif`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `rol_id` (`rol_id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------

ALTER TABLE `clientes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `cuentas_cobrar` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `cuentas_pagar` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `facturas` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `insumos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `notas_entrega` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `nota_entrega_detalle` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pagos_realizados` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pagos_recibidos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `presupuestos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `presupuesto_detalle` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `proveedores` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `roles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `usuarios` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- --------------------------------------------------------
-- Foreign Key Constraints
-- --------------------------------------------------------

ALTER TABLE `cuentas_cobrar`
  ADD CONSTRAINT `cuentas_cobrar_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `cuentas_cobrar_ibfk_2` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `cuentas_cobrar_ibfk_3` FOREIGN KEY (`nota_entrega_id`) REFERENCES `notas_entrega` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `cuentas_pagar`
  ADD CONSTRAINT `cuentas_pagar_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON UPDATE CASCADE;

ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `facturas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `facturas_ibfk_4` FOREIGN KEY (`nota_entrega_id`) REFERENCES `notas_entrega` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `insumos`
  ADD CONSTRAINT `insumos_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `notas_entrega`
  ADD CONSTRAINT `notas_entrega_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_entrega_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_entrega_ibfk_3` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `nota_entrega_detalle`
  ADD CONSTRAINT `nota_entrega_detalle_ibfk_1` FOREIGN KEY (`nota_id`) REFERENCES `notas_entrega` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `nota_entrega_detalle_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON UPDATE CASCADE;

ALTER TABLE `pagos_realizados`
  ADD CONSTRAINT `pagos_realizados_ibfk_1` FOREIGN KEY (`cuenta_pagar_id`) REFERENCES `cuentas_pagar` (`id`) ON UPDATE CASCADE;

ALTER TABLE `pagos_recibidos`
  ADD CONSTRAINT `pagos_recibidos_ibfk_1` FOREIGN KEY (`cuenta_cobrar_id`) REFERENCES `cuentas_cobrar` (`id`) ON UPDATE CASCADE;

ALTER TABLE `presupuestos`
  ADD CONSTRAINT `presupuestos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `presupuestos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

ALTER TABLE `presupuesto_detalle`
  ADD CONSTRAINT `presupuesto_detalle_ibfk_1` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `presupuesto_detalle_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON UPDATE CASCADE;

ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
