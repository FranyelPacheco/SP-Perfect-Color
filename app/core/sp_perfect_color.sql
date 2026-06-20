-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-06-2026 a las 01:28:26
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
-- Estructura de tabla para la tabla `banco`
--

CREATE TABLE `banco` (
  `id_banco` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `banco`
--

INSERT INTO `banco` (`id_banco`, `nombre`, `activo`, `created_at`) VALUES
(1, 'Banco de Venezuela', 1, '2026-06-16 01:14:35'),
(2, 'Banesco', 1, '2026-06-16 01:14:35'),
(3, 'Mercantil', 1, '2026-06-16 01:14:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `activo`, `cedula`, `nombres`, `apellidos`, `correo`, `direccion`, `fecha_registro`) VALUES
(6, 1, '28679228', 'Franyel David', 'Pacheco', 'pachecos@ejemplo.com', 'Av. Vargas', '2026-05-30 21:44:06'),
(13, 1, '29679229', 'Pepe', 'Aguilar', 'gvuyv76@gmail.com', 'Aqui vivo', '2026-06-16 21:43:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_cobrar`
--

CREATE TABLE `cuentas_cobrar` (
  `id_cuenta_cobrar` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `id_cliente` int(11) NOT NULL,
  `id_nota_entrega` int(11) DEFAULT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` datetime DEFAULT NULL,
  `estado` enum('pendiente','pagado','moroso') NOT NULL DEFAULT 'pendiente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `cuentas_cobrar`
--

INSERT INTO `cuentas_cobrar` (`id_cuenta_cobrar`, `activo`, `id_cliente`, `id_nota_entrega`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `estado`, `created_at`) VALUES
(1, 1, 13, 2, 99.00, 0.00, '2026-06-27 00:00:00', 'pagado', '2026-06-16 21:51:01'),
(2, 1, 6, 4, 33.00, 0.00, '2026-06-27 00:00:00', 'pagado', '2026-06-16 22:09:44'),
(3, 1, 6, 8, 15.00, 0.00, '2026-06-27 00:00:00', 'pagado', '2026-06-16 23:13:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_pagar`
--

CREATE TABLE `cuentas_pagar` (
  `id_cuenta_pagar` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `id_proveedor` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` datetime DEFAULT NULL,
  `estado` enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `cuentas_pagar`
--

INSERT INTO `cuentas_pagar` (`id_cuenta_pagar`, `activo`, `id_proveedor`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `estado`, `created_at`) VALUES
(1, 1, 4, 15.00, 0.00, '2026-06-30 00:00:00', 'pagado', '2026-06-16 20:53:26'),
(2, 1, 4, 10.00, 0.00, '2026-06-18 00:00:00', 'pagado', '2026-06-16 20:54:11'),
(3, 1, 4, 15.00, 0.00, '2026-06-30 00:00:00', 'pagado', '2026-06-16 20:54:39'),
(4, 1, 5, 44214.00, 0.00, '2026-06-30 00:00:00', 'pagado', '2026-06-16 23:10:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos`
--

CREATE TABLE `insumos` (
  `id_insumo` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(30) DEFAULT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT 5.00,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_compra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `insumos`
--

INSERT INTO `insumos` (`id_insumo`, `activo`, `codigo`, `nombre`, `marca`, `unidad_medida`, `stock_actual`, `stock_minimo`, `precio_venta`, `precio_compra`, `created_at`, `updated_at`) VALUES
(6, 1, '001', 'Fondos esteticos', 'Dall', 'Unidad', 2.00, 5.00, 15.00, 10.00, '2026-06-16 20:49:42', '2026-06-17 12:48:56'),
(7, 1, '002', 'Colors', 'Jbalvin', 'Unidad', 6.00, 5.00, 33.00, 15.90, '2026-06-16 21:46:58', '2026-06-16 23:19:44'),
(8, 1, '003', 'Fondos esteticos 3', 'Dall', 'Unidad', 10.00, 5.00, 12.00, 10.00, '2026-06-17 16:34:17', '2026-06-17 16:34:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumo_proveedor`
--

CREATE TABLE `insumo_proveedor` (
  `id_insumo_proveedor` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `insumo_proveedor`
--

INSERT INTO `insumo_proveedor` (`id_insumo_proveedor`, `id_insumo`, `id_proveedor`) VALUES
(8, 6, 4),
(7, 7, 5),
(9, 8, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas_entrega`
--

CREATE TABLE `notas_entrega` (
  `id_nota_entrega` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_presupuesto` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','entregado','en_espera') NOT NULL DEFAULT 'pendiente',
  `condicion_pago` enum('contado','credito') NOT NULL DEFAULT 'contado',
  `id_tipo_pago` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `notas_entrega`
--

INSERT INTO `notas_entrega` (`id_nota_entrega`, `activo`, `id_cliente`, `id_usuario`, `id_presupuesto`, `fecha`, `total`, `estado`, `condicion_pago`, `id_tipo_pago`, `created_at`) VALUES
(1, 1, 6, 2, 1, '2026-06-16 20:50:23', 45.00, 'entregado', 'contado', NULL, '2026-06-16 20:50:23'),
(2, 1, 13, 2, 2, '2026-06-16 21:51:01', 99.00, 'pendiente', 'credito', 3, '2026-06-16 21:51:01'),
(3, 1, 13, 2, 5, '2026-06-16 21:56:30', 297.00, 'pendiente', 'contado', 3, '2026-06-16 21:56:30'),
(4, 1, 6, 2, 6, '2026-06-16 22:09:44', 33.00, 'pendiente', 'credito', 3, '2026-06-16 22:09:44'),
(5, 1, 6, 2, 7, '2026-06-16 23:08:21', 33.00, 'pendiente', 'contado', NULL, '2026-06-16 23:08:21'),
(6, 1, 13, 2, 8, '2026-06-16 23:09:07', 33.00, 'entregado', 'contado', 3, '2026-06-16 23:09:07'),
(7, 1, 6, 2, 9, '2026-06-16 23:10:15', 15.00, 'pendiente', 'contado', NULL, '2026-06-16 23:10:15'),
(8, 1, 6, 2, 10, '2026-06-16 23:13:04', 15.00, 'pendiente', 'credito', NULL, '2026-06-16 23:13:04'),
(9, 1, 6, 2, 11, '2026-06-16 23:19:44', 33.00, 'pendiente', 'contado', 1, '2026-06-16 23:19:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nota_entrega_detalle`
--

CREATE TABLE `nota_entrega_detalle` (
  `id_nota_entrega_detalle` int(11) NOT NULL,
  `id_nota_entrega` int(11) NOT NULL,
  `id_presupuesto_detalle` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `nota_entrega_detalle`
--

INSERT INTO `nota_entrega_detalle` (`id_nota_entrega_detalle`, `id_nota_entrega`, `id_presupuesto_detalle`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, 3.00, 15.00, 45.00),
(2, 2, 2, 3.00, 33.00, 99.00),
(3, 3, 5, 9.00, 33.00, 297.00),
(4, 4, 6, 1.00, 33.00, 33.00),
(5, 5, 7, 1.00, 33.00, 33.00),
(6, 6, 8, 1.00, 33.00, 33.00),
(7, 7, 9, 1.00, 15.00, 15.00),
(8, 8, 10, 1.00, 15.00, 15.00),
(9, 9, 11, 1.00, 33.00, 33.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_realizados`
--

CREATE TABLE `pagos_realizados` (
  `id_pago_realizado` int(11) NOT NULL,
  `id_cuenta_pagar` int(11) NOT NULL,
  `id_tipo_pago` int(11) NOT NULL,
  `id_banco` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime NOT NULL,
  `referencia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `pagos_realizados`
--

INSERT INTO `pagos_realizados` (`id_pago_realizado`, `id_cuenta_pagar`, `id_tipo_pago`, `id_banco`, `monto`, `fecha`, `referencia`) VALUES
(1, 1, 1, 1, 10.00, '2026-06-17 00:00:00', NULL),
(2, 1, 2, 2, 5.00, '2026-06-17 00:00:00', '55421'),
(3, 2, 3, 1, 10.00, '2026-06-17 00:00:00', '55421'),
(4, 3, 3, NULL, 15.00, '2026-06-17 00:00:00', NULL),
(5, 4, 1, NULL, 44214.00, '2026-06-17 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_recibidos`
--

CREATE TABLE `pagos_recibidos` (
  `id_pago_recibido` int(11) NOT NULL,
  `id_cuenta_cobrar` int(11) DEFAULT NULL,
  `id_tipo_pago` int(11) NOT NULL,
  `id_banco` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime NOT NULL,
  `referencia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `pagos_recibidos`
--

INSERT INTO `pagos_recibidos` (`id_pago_recibido`, `id_cuenta_cobrar`, `id_tipo_pago`, `id_banco`, `monto`, `fecha`, `referencia`) VALUES
(1, NULL, 1, NULL, 45.00, '2026-06-16 20:50:23', NULL),
(2, 1, 1, NULL, 99.00, '2026-06-17 00:00:00', NULL),
(3, NULL, 3, 3, 297.00, '2026-06-16 21:56:30', '5545'),
(4, 2, 3, NULL, 33.00, '2026-06-17 00:00:00', NULL),
(5, NULL, 1, NULL, 33.00, '2026-06-16 23:08:21', NULL),
(6, NULL, 3, 1, 33.00, '2026-06-16 23:09:07', '4434'),
(7, NULL, 1, NULL, 15.00, '2026-06-16 23:10:15', NULL),
(8, 3, 1, NULL, 15.00, '2026-06-17 00:00:00', NULL),
(9, NULL, 1, NULL, 33.00, '2026-06-16 23:19:44', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presupuestos`
--

CREATE TABLE `presupuestos` (
  `id_presupuesto` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','aprobado','rechazado','convertido') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `presupuestos`
--

INSERT INTO `presupuestos` (`id_presupuesto`, `activo`, `id_cliente`, `id_usuario`, `fecha`, `total`, `estado`, `observaciones`, `created_at`) VALUES
(1, 0, 6, 2, '2026-06-16 20:50:01', 45.00, 'convertido', 'Compra', '2026-06-16 20:50:01'),
(2, 1, 13, 2, '2026-06-16 21:47:26', 99.00, 'convertido', '', '2026-06-16 21:47:26'),
(3, 0, 6, 2, '2026-06-16 21:52:01', 15.00, 'rechazado', '', '2026-06-16 21:52:01'),
(4, 0, 6, 2, '2026-06-16 21:52:19', 297.00, 'rechazado', '', '2026-06-16 21:52:19'),
(5, 1, 13, 2, '2026-06-16 21:52:33', 297.00, 'convertido', '', '2026-06-16 21:52:33'),
(6, 1, 6, 2, '2026-06-16 22:09:08', 33.00, 'convertido', '', '2026-06-16 22:09:08'),
(7, 1, 6, 2, '2026-06-16 23:08:09', 33.00, 'convertido', '', '2026-06-16 23:08:09'),
(8, 1, 13, 2, '2026-06-16 23:08:42', 33.00, 'convertido', '', '2026-06-16 23:08:42'),
(9, 0, 6, 2, '2026-06-16 23:09:58', 15.00, 'convertido', '', '2026-06-16 23:09:58'),
(10, 1, 6, 2, '2026-06-16 23:12:47', 15.00, 'convertido', '', '2026-06-16 23:12:47'),
(11, 1, 6, 2, '2026-06-16 23:19:34', 33.00, 'convertido', '', '2026-06-16 23:19:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `presupuesto_detalle`
--

CREATE TABLE `presupuesto_detalle` (
  `id_presupuesto_detalle` int(11) NOT NULL,
  `id_presupuesto` int(11) NOT NULL,
  `id_insumo` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `presupuesto_detalle`
--

INSERT INTO `presupuesto_detalle` (`id_presupuesto_detalle`, `id_presupuesto`, `id_insumo`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 6, 3.00, 15.00, 45.00),
(2, 2, 7, 3.00, 33.00, 99.00),
(3, 3, 6, 1.00, 15.00, 15.00),
(4, 4, 7, 9.00, 33.00, 297.00),
(5, 5, 7, 9.00, 33.00, 297.00),
(6, 6, 7, 1.00, 33.00, 33.00),
(7, 7, 7, 1.00, 33.00, 33.00),
(8, 8, 7, 1.00, 33.00, 33.00),
(9, 9, 6, 1.00, 15.00, 15.00),
(10, 10, 6, 1.00, 15.00, 15.00),
(11, 11, 7, 1.00, 33.00, 33.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `rif` varchar(20) NOT NULL,
  `nombre_empresa` varchar(150) NOT NULL,
  `direccion` text DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `activo`, `rif`, `nombre_empresa`, `direccion`, `contacto`, `correo`, `created_at`) VALUES
(4, 1, 'J-285168768', 'Promton', 'Cerca', 'Edgar', 'gvuyv76@gmail.com', '2026-06-16 20:49:08'),
(5, 1, 'J-285168666', 'Polar', 'Av siempre viva', 'Edaga', 'edaga@ejemplo.com', '2026-06-16 21:45:56'),
(6, 1, 'J-285168769', 'Dundel', 'Aqui', 'pepe', 'pepe@ejemplo.com', '2026-06-17 12:47:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre`, `created_at`) VALUES
(1, 'Administrador', '2026-05-06 22:27:00'),
(2, 'Vendedor', '2026-05-06 22:27:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rubro`
--

CREATE TABLE `rubro` (
  `id_rubro` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `rubro`
--

INSERT INTO `rubro` (`id_rubro`, `nombre`) VALUES
(3, 'Cervezas'),
(2, 'Quimicos'),
(1, 'Tintes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rubro_proveedor`
--

CREATE TABLE `rubro_proveedor` (
  `id_rubro_proveedor` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_rubro` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `rubro_proveedor`
--

INSERT INTO `rubro_proveedor` (`id_rubro_proveedor`, `id_proveedor`, `id_rubro`) VALUES
(2, 4, 2),
(3, 5, 1),
(5, 6, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono_cliente`
--

CREATE TABLE `telefono_cliente` (
  `id_telefono_cliente` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `tipo` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `telefono_cliente`
--

INSERT INTO `telefono_cliente` (`id_telefono_cliente`, `id_cliente`, `telefono`, `tipo`) VALUES
(1, 6, '04245544956', 'movil'),
(3, 13, '04245544955', 'movil');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telf_proveedor`
--

CREATE TABLE `telf_proveedor` (
  `id_telf_proveedor` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `tipo` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `telf_proveedor`
--

INSERT INTO `telf_proveedor` (`id_telf_proveedor`, `id_proveedor`, `telefono`, `tipo`) VALUES
(2, 4, '04245544659', 'movil'),
(3, 5, '04245544666', 'movil'),
(5, 6, '04245544955', 'movil');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_pago`
--

CREATE TABLE `tipo_pago` (
  `id_tipo_pago` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `tipo_pago`
--

INSERT INTO `tipo_pago` (`id_tipo_pago`, `nombre`, `activo`) VALUES
(1, 'Efectivo', 1),
(2, 'Transferencia', 1),
(3, 'Pago Movil', 1),
(4, 'Tarjeta Debito', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `password_hash`, `id_rol`, `activo`, `created_at`) VALUES
(2, 'Administrador', 'admin@perfectcolor.com', '$2y$10$jna5O2..M06Bfn07dl2cxeHPKfh6xG8Vd6f.JDmNjvlFHlEz.EA8C', 1, 1, '2026-05-11 12:53:06'),
(5, 'Fran', 'pacheco@ejemplo.com', '$2y$10$iRQ/JFgFfrVnh1pTk/v5/.z5HdYEmIwruI/P8EYSnepJbBT1Jb9f2', 2, 1, '2026-05-24 22:07:07');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `banco`
--
ALTER TABLE `banco`
  ADD PRIMARY KEY (`id_banco`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  ADD PRIMARY KEY (`id_cuenta_cobrar`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_nota_entrega` (`id_nota_entrega`);

--
-- Indices de la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  ADD PRIMARY KEY (`id_cuenta_pagar`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`id_insumo`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `insumo_proveedor`
--
ALTER TABLE `insumo_proveedor`
  ADD PRIMARY KEY (`id_insumo_proveedor`),
  ADD UNIQUE KEY `insumo_proveedor_unique` (`id_insumo`,`id_proveedor`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `notas_entrega`
--
ALTER TABLE `notas_entrega`
  ADD PRIMARY KEY (`id_nota_entrega`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_presupuesto` (`id_presupuesto`),
  ADD KEY `id_tipo_pago` (`id_tipo_pago`);

--
-- Indices de la tabla `nota_entrega_detalle`
--
ALTER TABLE `nota_entrega_detalle`
  ADD PRIMARY KEY (`id_nota_entrega_detalle`),
  ADD KEY `id_nota_entrega` (`id_nota_entrega`),
  ADD KEY `id_presupuesto_detalle` (`id_presupuesto_detalle`);

--
-- Indices de la tabla `pagos_realizados`
--
ALTER TABLE `pagos_realizados`
  ADD PRIMARY KEY (`id_pago_realizado`),
  ADD KEY `id_cuenta_pagar` (`id_cuenta_pagar`),
  ADD KEY `id_tipo_pago` (`id_tipo_pago`),
  ADD KEY `id_banco` (`id_banco`);

--
-- Indices de la tabla `pagos_recibidos`
--
ALTER TABLE `pagos_recibidos`
  ADD PRIMARY KEY (`id_pago_recibido`),
  ADD KEY `id_cuenta_cobrar` (`id_cuenta_cobrar`),
  ADD KEY `id_tipo_pago` (`id_tipo_pago`),
  ADD KEY `id_banco` (`id_banco`);

--
-- Indices de la tabla `presupuestos`
--
ALTER TABLE `presupuestos`
  ADD PRIMARY KEY (`id_presupuesto`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `presupuesto_detalle`
--
ALTER TABLE `presupuesto_detalle`
  ADD PRIMARY KEY (`id_presupuesto_detalle`),
  ADD KEY `id_presupuesto` (`id_presupuesto`),
  ADD KEY `id_insumo` (`id_insumo`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `rif` (`rif`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `rubro`
--
ALTER TABLE `rubro`
  ADD PRIMARY KEY (`id_rubro`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `rubro_proveedor`
--
ALTER TABLE `rubro_proveedor`
  ADD PRIMARY KEY (`id_rubro_proveedor`),
  ADD UNIQUE KEY `proveedor_rubro_unique` (`id_proveedor`,`id_rubro`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `id_rubro` (`id_rubro`);

--
-- Indices de la tabla `telefono_cliente`
--
ALTER TABLE `telefono_cliente`
  ADD PRIMARY KEY (`id_telefono_cliente`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `telf_proveedor`
--
ALTER TABLE `telf_proveedor`
  ADD PRIMARY KEY (`id_telf_proveedor`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `tipo_pago`
--
ALTER TABLE `tipo_pago`
  ADD PRIMARY KEY (`id_tipo_pago`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `banco`
--
ALTER TABLE `banco`
  MODIFY `id_banco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  MODIFY `id_cuenta_cobrar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  MODIFY `id_cuenta_pagar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id_insumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `insumo_proveedor`
--
ALTER TABLE `insumo_proveedor`
  MODIFY `id_insumo_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `notas_entrega`
--
ALTER TABLE `notas_entrega`
  MODIFY `id_nota_entrega` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `nota_entrega_detalle`
--
ALTER TABLE `nota_entrega_detalle`
  MODIFY `id_nota_entrega_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `pagos_realizados`
--
ALTER TABLE `pagos_realizados`
  MODIFY `id_pago_realizado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pagos_recibidos`
--
ALTER TABLE `pagos_recibidos`
  MODIFY `id_pago_recibido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `presupuestos`
--
ALTER TABLE `presupuestos`
  MODIFY `id_presupuesto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `presupuesto_detalle`
--
ALTER TABLE `presupuesto_detalle`
  MODIFY `id_presupuesto_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `rubro`
--
ALTER TABLE `rubro`
  MODIFY `id_rubro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rubro_proveedor`
--
ALTER TABLE `rubro_proveedor`
  MODIFY `id_rubro_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `telefono_cliente`
--
ALTER TABLE `telefono_cliente`
  MODIFY `id_telefono_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `telf_proveedor`
--
ALTER TABLE `telf_proveedor`
  MODIFY `id_telf_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipo_pago`
--
ALTER TABLE `tipo_pago`
  MODIFY `id_tipo_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  ADD CONSTRAINT `fk_cc_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_nota_entrega` FOREIGN KEY (`id_nota_entrega`) REFERENCES `notas_entrega` (`id_nota_entrega`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `cuentas_pagar`
--
ALTER TABLE `cuentas_pagar`
  ADD CONSTRAINT `fk_cp_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `insumo_proveedor`
--
ALTER TABLE `insumo_proveedor`
  ADD CONSTRAINT `fk_insumo_proveedor_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_insumo_proveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `notas_entrega`
--
ALTER TABLE `notas_entrega`
  ADD CONSTRAINT `fk_nota_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nota_presupuesto` FOREIGN KEY (`id_presupuesto`) REFERENCES `presupuestos` (`id_presupuesto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nota_tipo_pago` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago` (`id_tipo_pago`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nota_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `nota_entrega_detalle`
--
ALTER TABLE `nota_entrega_detalle`
  ADD CONSTRAINT `fk_ned_nota` FOREIGN KEY (`id_nota_entrega`) REFERENCES `notas_entrega` (`id_nota_entrega`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ned_pres_detalle` FOREIGN KEY (`id_presupuesto_detalle`) REFERENCES `presupuesto_detalle` (`id_presupuesto_detalle`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_realizados`
--
ALTER TABLE `pagos_realizados`
  ADD CONSTRAINT `fk_preal_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_preal_cuenta_pagar` FOREIGN KEY (`id_cuenta_pagar`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_preal_tipo_pago` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago` (`id_tipo_pago`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_recibidos`
--
ALTER TABLE `pagos_recibidos`
  ADD CONSTRAINT `fk_pr_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pr_cuenta_cobrar` FOREIGN KEY (`id_cuenta_cobrar`) REFERENCES `cuentas_cobrar` (`id_cuenta_cobrar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pr_tipo_pago` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago` (`id_tipo_pago`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `presupuestos`
--
ALTER TABLE `presupuestos`
  ADD CONSTRAINT `fk_presupuesto_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presupuesto_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `presupuesto_detalle`
--
ALTER TABLE `presupuesto_detalle`
  ADD CONSTRAINT `fk_pres_detalle_insumo` FOREIGN KEY (`id_insumo`) REFERENCES `insumos` (`id_insumo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pres_detalle_presupuesto` FOREIGN KEY (`id_presupuesto`) REFERENCES `presupuestos` (`id_presupuesto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rubro_proveedor`
--
ALTER TABLE `rubro_proveedor`
  ADD CONSTRAINT `fk_rubro_proveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rubro_proveedor_rubro` FOREIGN KEY (`id_rubro`) REFERENCES `rubro` (`id_rubro`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `telefono_cliente`
--
ALTER TABLE `telefono_cliente`
  ADD CONSTRAINT `fk_telf_cliente_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `telf_proveedor`
--
ALTER TABLE `telf_proveedor`
  ADD CONSTRAINT `fk_telf_proveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
