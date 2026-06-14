-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-06-2026 a las 00:00:00
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

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `nombre`, `created_at`) VALUES
(1, 'Administrador', '2026-05-06 22:27:00'),
(2, 'Vendedor', '2026-05-06 22:27:00');

-- --------------------------------------------------------

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  KEY `rol_id` (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password_hash`, `rol_id`, `activo`, `created_at`) VALUES
(2, 'Administrador', 'admin@perfectcolor.com', '$2y$10$iieMsBMURX3rO8YB3vPHt.AETjKTK3AuD6avNwY/t0fzKA4yiXIGe', 1, 1, '2026-05-11 12:53:06'),
(5, 'Fran', 'pacheco@ejemplo.com', '$2y$10$veXHly/o4LhKg1qwKN2aEuB/4uK1XRQ.dxMFaA1DmUc2tJYprjIZS', 2, 1, '2026-05-24 22:07:07');

-- --------------------------------------------------------

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clientes` (`id`, `activo`, `cedula`, `nombres`, `apellidos`, `correo`, `direccion`, `fecha_registro`) VALUES
(6, 1, '28679228', 'Franyel David', 'Pacheco', 'pachecos@ejemplo.com', 'Av. Vargas', '2026-05-30 21:44:06');

-- --------------------------------------------------------

CREATE TABLE `telefono_cliente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `tipo` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `telefono_cliente` (`id`, `cliente_id`, `telefono`, `tipo`) VALUES
(1, 6, '04245544956', 'movil');

-- --------------------------------------------------------

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `rif` varchar(20) NOT NULL,
  `nombre_empresa` varchar(150) NOT NULL,
  `direccion` text DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rif` (`rif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `proveedores` (`id`, `activo`, `rif`, `nombre_empresa`, `direccion`, `contacto`, `correo`, `created_at`) VALUES
(3, 1, 'J-285168755', 'Polar', 'Av. polar', 'Juan', 'polar@polar.com', '2026-05-29 20:16:48');

-- --------------------------------------------------------

CREATE TABLE `telf_proveedor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` int(11) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `tipo` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `telf_proveedor` (`id`, `proveedor_id`, `telefono`, `tipo`) VALUES
(1, 3, '04245544656', 'movil');

-- --------------------------------------------------------

CREATE TABLE `rubro_proveedor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rubro_proveedor` (`id`, `proveedor_id`, `nombre`) VALUES
(1, 3, 'Cervezas');

-- --------------------------------------------------------

CREATE TABLE `insumos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `insumos` (`id`, `activo`, `codigo`, `nombre`, `marca`, `categoria`, `unidad_medida`, `stock_actual`, `stock_minimo`, `precio_venta`, `precio_compra`, `created_at`, `updated_at`) VALUES
(3, 1, '001', 'Industriacos', 'Groud', 'Tintes', 'Kilogramo', 12.00, 5.00, 2000.00, 977.00, '2026-05-30 21:45:01', '2026-05-31 15:48:46'),
(4, 1, '002', 'Breaking Bad', 'Walter', 'Quimicos', 'Litro', 17.00, 5.00, 1200.00, 750.00, '2026-05-31 15:48:15', '2026-05-31 15:48:46');

-- --------------------------------------------------------

CREATE TABLE `insumo_proveedor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `insumo_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `insumo_proveedor_unique` (`insumo_id`,`proveedor_id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `insumo_proveedor` (`id`, `insumo_id`, `proveedor_id`) VALUES
(1, 3, 3),
(2, 4, 3);

-- --------------------------------------------------------

CREATE TABLE `presupuestos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','aprobado','rechazado','convertido') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `presupuestos` (`id`, `activo`, `cliente_id`, `usuario_id`, `fecha`, `total`, `estado`, `observaciones`, `created_at`) VALUES
(4, 1, 6, 2, '2026-05-30', 6000.00, 'convertido', 'Le informamos...', '2026-05-30 21:45:25'),
(5, 1, 6, 2, '2026-05-30', 2000.00, 'convertido', '', '2026-05-30 21:46:34'),
(6, 1, 6, 2, '2026-05-31', 2000.00, 'convertido', '', '2026-05-31 15:02:42'),
(7, 1, 6, 2, '2026-05-31', 5600.00, 'convertido', '', '2026-05-31 15:48:27');

-- --------------------------------------------------------

CREATE TABLE `presupuesto_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `presupuesto_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `presupuesto_id` (`presupuesto_id`),
  KEY `insumo_id` (`insumo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `presupuesto_detalle` (`id`, `presupuesto_id`, `insumo_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(4, 4, 3, 3.00, 2000.00, 6000.00),
(5, 5, 3, 1.00, 2000.00, 2000.00),
(6, 6, 3, 1.00, 2000.00, 2000.00),
(7, 7, 4, 3.00, 1200.00, 3600.00),
(8, 7, 3, 1.00, 2000.00, 2000.00);

-- --------------------------------------------------------

CREATE TABLE `notas_entrega` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','entregado','en_espera') NOT NULL DEFAULT 'pendiente',
  `tipo_pago` enum('contado','credito') DEFAULT 'contado',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `presupuesto_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `presupuesto_id` (`presupuesto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notas_entrega` (`id`, `activo`, `cliente_id`, `usuario_id`, `fecha`, `total`, `estado`, `tipo_pago`, `metodo_pago`, `presupuesto_id`, `created_at`) VALUES
(52, 1, 6, 2, '2026-05-30', 6000.00, 'entregado', 'contado', 'Efectivo', 4, '2026-05-30 21:46:00'),
(53, 1, 6, 2, '2026-05-30', 2000.00, 'entregado', 'credito', NULL, 5, '2026-05-30 21:46:51'),
(54, 1, 6, 5, '2026-05-30', 4000.00, 'en_espera', 'contado', 'Efectivo', NULL, '2026-05-31 02:24:31'),
(55, 1, 6, 2, '2026-05-31', 2000.00, 'pendiente', 'credito', NULL, 6, '2026-05-31 15:02:56'),
(56, 1, 6, 2, '2026-05-31', 5600.00, 'entregado', 'contado', 'Transferencia', 7, '2026-05-31 15:48:46');

-- --------------------------------------------------------

CREATE TABLE `nota_entrega_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nota_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `nota_id` (`nota_id`),
  KEY `insumo_id` (`insumo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `nota_entrega_detalle` (`id`, `nota_id`, `insumo_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(52, 52, 3, 3.00, 2000.00, 6000.00),
(53, 53, 3, 1.00, 2000.00, 2000.00),
(54, 54, 3, 2.00, 2000.00, 4000.00),
(55, 55, 3, 1.00, 2000.00, 2000.00),
(56, 56, 3, 1.00, 2000.00, 2000.00),
(57, 56, 4, 3.00, 1200.00, 3600.00);

-- --------------------------------------------------------

CREATE TABLE `cuentas_cobrar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cliente_id` int(11) NOT NULL,
  `nota_entrega_id` int(11) DEFAULT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','pagado','moroso') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `nota_entrega_id` (`nota_entrega_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cuentas_cobrar` (`id`, `activo`, `cliente_id`, `nota_entrega_id`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `estado`, `created_at`) VALUES
(15, 1, 6, 53, 2000.00, 0.00, '2026-06-01', 'pagado', '2026-05-30 21:46:51'),
(16, 1, 6, 54, 4000.00, 0.00, '2026-06-06', 'pagado', '2026-05-31 02:24:31'),
(17, 1, 6, 55, 2000.00, 0.00, '2026-05-30', 'pagado', '2026-05-31 15:02:56');

-- --------------------------------------------------------

CREATE TABLE `pagos_recibidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cuenta_cobrar_id` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cuenta_cobrar_id` (`cuenta_cobrar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pagos_recibidos` (`id`, `cuenta_cobrar_id`, `monto`, `fecha`, `metodo_pago`) VALUES
(3, 15, 2000.00, '2026-05-30', 'Transferencia'),
(4, 16, 2500.00, '2026-05-31', 'Efectivo'),
(5, 16, 1500.00, '2026-05-31', 'Efectivo'),
(6, 17, 2000.00, '2026-05-31', 'Efectivo');

-- --------------------------------------------------------

CREATE TABLE `cuentas_pagar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `proveedor_id` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cuentas_pagar` (`id`, `activo`, `proveedor_id`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `estado`, `created_at`) VALUES
(5, 1, 3, 5000.00, 0.00, '2026-06-05', 'pagado', '2026-05-30 23:26:54'),
(6, 1, 3, 5000000.00, 0.00, '2026-06-05', 'pagado', '2026-05-30 23:56:46');

-- --------------------------------------------------------

CREATE TABLE `pagos_realizados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cuenta_pagar_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cuenta_pagar_id` (`cuenta_pagar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pagos_realizados` (`id`, `cuenta_pagar_id`, `monto`, `fecha`, `metodo_pago`) VALUES
(1, 6, 5000000.00, '2026-05-31', 'Transferencia'),
(2, 5, 5000.00, '2026-05-31', 'Pago Movil');

-- --------------------------------------------------------
-- Foreign Key constraints
-- --------------------------------------------------------

ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

ALTER TABLE `telefono_cliente`
  ADD CONSTRAINT `telefono_cliente_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `telf_proveedor`
  ADD CONSTRAINT `telf_proveedor_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rubro_proveedor`
  ADD CONSTRAINT `rubro_proveedor_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `insumo_proveedor`
  ADD CONSTRAINT `insumo_proveedor_ibfk_1` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `insumo_proveedor_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON UPDATE CASCADE;

ALTER TABLE `presupuestos`
  ADD CONSTRAINT `presupuestos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `presupuestos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

ALTER TABLE `presupuesto_detalle`
  ADD CONSTRAINT `presupuesto_detalle_ibfk_1` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `presupuesto_detalle_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON UPDATE CASCADE;

ALTER TABLE `notas_entrega`
  ADD CONSTRAINT `notas_entrega_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_entrega_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notas_entrega_ibfk_3` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `nota_entrega_detalle`
  ADD CONSTRAINT `nota_entrega_detalle_ibfk_1` FOREIGN KEY (`nota_id`) REFERENCES `notas_entrega` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `nota_entrega_detalle_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON UPDATE CASCADE;

ALTER TABLE `cuentas_cobrar`
  ADD CONSTRAINT `cuentas_cobrar_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `cuentas_cobrar_ibfk_3` FOREIGN KEY (`nota_entrega_id`) REFERENCES `notas_entrega` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `pagos_recibidos`
  ADD CONSTRAINT `pagos_recibidos_ibfk_1` FOREIGN KEY (`cuenta_cobrar_id`) REFERENCES `cuentas_cobrar` (`id`) ON UPDATE CASCADE;

ALTER TABLE `cuentas_pagar`
  ADD CONSTRAINT `cuentas_pagar_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON UPDATE CASCADE;

ALTER TABLE `pagos_realizados`
  ADD CONSTRAINT `pagos_realizados_ibfk_1` FOREIGN KEY (`cuenta_pagar_id`) REFERENCES `cuentas_pagar` (`id`) ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
