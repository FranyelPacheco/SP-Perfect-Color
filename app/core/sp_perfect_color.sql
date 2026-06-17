-- ============================================================
-- sp_perfect_color — Versión 2.0 LIMPIA
-- Base de datos lista para producción.
-- Datos de ejemplo eliminados en: presupuestos, presupuesto_detalle,
-- notas_entrega, nota_entrega_detalle, cuentas_cobrar,
-- pagos_recibidos, cuentas_pagar, pagos_realizados.
-- Se conservan: roles, rubro, banco, tipo_pago, usuarios,
-- clientes, telefono_cliente, proveedores, telf_proveedor,
-- rubro_proveedor, insumos, insumo_proveedor.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- CATÁLOGOS BASE
-- ============================================================

CREATE TABLE `roles` (
  `id_rol`     int(11)     NOT NULL AUTO_INCREMENT,
  `nombre`     varchar(50) NOT NULL,
  `created_at` timestamp   NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `roles` (`id_rol`, `nombre`, `created_at`) VALUES
(1, 'Administrador', '2026-05-06 22:27:00'),
(2, 'Vendedor',      '2026-05-06 22:27:00');

-- ------------------------------------------------------------
-- rubro: catálogo único compartido entre insumos y proveedores
-- (antes existía como categoria varchar en insumos y nombre
--  varchar en rubro_proveedor — ahora es una sola tabla)
-- ------------------------------------------------------------
CREATE TABLE `rubro` (
  `id_rubro` int(11)      NOT NULL AUTO_INCREMENT,
  `nombre`   varchar(100) NOT NULL,
  PRIMARY KEY (`id_rubro`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `rubro` (`id_rubro`, `nombre`) VALUES
(1, 'Tintes'),
(2, 'Quimicos'),
(3, 'Cervezas');

-- ------------------------------------------------------------
-- banco: bancos de la empresa (nuevo en v2)
-- ------------------------------------------------------------
CREATE TABLE `banco` (
  `id_banco`   int(11)      NOT NULL AUTO_INCREMENT,
  `nombre`     varchar(150) NOT NULL,
  `activo`     tinyint(1)   NOT NULL DEFAULT 1,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_banco`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `banco` (`id_banco`, `nombre`, `activo`) VALUES
(1, 'Banco de Venezuela', 1),
(2, 'Banesco',            1),
(3, 'Mercantil',          1);

-- ------------------------------------------------------------
-- tipo_pago: catálogo de métodos de pago (nuevo en v2)
-- Reemplaza el campo metodo_pago varchar(50) libre que existía
-- en notas_entrega, pagos_recibidos y pagos_realizados
-- ------------------------------------------------------------
CREATE TABLE `tipo_pago` (
  `id_tipo_pago` int(11)     NOT NULL AUTO_INCREMENT,
  `nombre`       varchar(80) NOT NULL,
  `activo`       tinyint(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tipo_pago`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `tipo_pago` (`id_tipo_pago`, `nombre`, `activo`) VALUES
(1, 'Efectivo',        1),
(2, 'Transferencia',   1),
(3, 'Pago Movil',      1),
(4, 'Tarjeta Debito',  1);

-- ============================================================
-- ENTIDADES PRINCIPALES
-- ============================================================

CREATE TABLE `usuarios` (
  `id_usuario`    int(11)      NOT NULL AUTO_INCREMENT,
  `nombre`        varchar(100) NOT NULL,
  `correo`        varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id`        int(11)      NOT NULL,
  `activo`        tinyint(1)   NOT NULL DEFAULT 1,
  `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `rol_id` (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `password_hash`, `rol_id`, `activo`, `created_at`) VALUES
(2, 'Administrador', 'admin@perfectcolor.com', '$2y$10$iieMsBMURX3rO8YB3vPHt.AETjKTK3AuD6avNwY/t0fzKA4yiXIGe', 1, 1, '2026-05-11 12:53:06'),
(5, 'Fran',          'pacheco@ejemplo.com',    '$2y$10$veXHly/o4LhKg1qwKN2aEuB/4uK1XRQ.dxMFaA1DmUc2tJYprjIZS', 2, 1, '2026-05-24 22:07:07');

CREATE TABLE `clientes` (
  `id_cliente`     int(11)      NOT NULL AUTO_INCREMENT,
  `activo`         tinyint(1)   NOT NULL DEFAULT 1,
  `cedula`         varchar(20)  NOT NULL,
  `nombres`        varchar(100) NOT NULL,
  `apellidos`      varchar(100) NOT NULL,
  `correo`         varchar(100) DEFAULT NULL,
  `direccion`      text         DEFAULT NULL,
  `fecha_registro` datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `clientes` (`id_cliente`, `activo`, `cedula`, `nombres`, `apellidos`, `correo`, `direccion`, `fecha_registro`) VALUES
(6, 1, '28679228', 'Franyel David', 'Pacheco', 'pachecos@ejemplo.com', 'Av. Vargas', '2026-05-30 21:44:06');

CREATE TABLE `telefono_cliente` (
  `id_telefono_cliente` int(11)     NOT NULL AUTO_INCREMENT,
  `cliente_id`          int(11)     NOT NULL,
  `telefono`            varchar(20) NOT NULL,
  `tipo`                varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id_telefono_cliente`),
  KEY `cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `telefono_cliente` (`id_telefono_cliente`, `cliente_id`, `telefono`, `tipo`) VALUES
(1, 6, '04245544956', 'movil');

CREATE TABLE `proveedores` (
  `id_proveedor`   int(11)      NOT NULL AUTO_INCREMENT,
  `activo`         tinyint(1)   NOT NULL DEFAULT 1,
  `rif`            varchar(20)  NOT NULL,
  `nombre_empresa` varchar(150) NOT NULL,
  `direccion`      text         DEFAULT NULL,
  `contacto`       varchar(100) DEFAULT NULL,
  `correo`         varchar(100) DEFAULT NULL,
  `created_at`     datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `rif` (`rif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `proveedores` (`id_proveedor`, `activo`, `rif`, `nombre_empresa`, `direccion`, `contacto`, `correo`, `created_at`) VALUES
(3, 1, 'J-285168755', 'Polar', 'Av. polar', 'Juan', 'polar@polar.com', '2026-05-29 20:16:48');

CREATE TABLE `telf_proveedor` (
  `id_telf_proveedor` int(11)     NOT NULL AUTO_INCREMENT,
  `proveedor_id`      int(11)     NOT NULL,
  `telefono`          varchar(20) NOT NULL,
  `tipo`              varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id_telf_proveedor`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `telf_proveedor` (`id_telf_proveedor`, `proveedor_id`, `telefono`, `tipo`) VALUES
(1, 3, '04245544656', 'movil');

-- ------------------------------------------------------------
-- rubro_proveedor: pivote proveedor ↔ rubro
-- Antes tenía campo nombre varchar propio — ahora usa FK a rubro
-- ------------------------------------------------------------
CREATE TABLE `rubro_proveedor` (
  `id_rubro_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id`       int(11) NOT NULL,
  `rubro_id`           int(11) NOT NULL,
  PRIMARY KEY (`id_rubro_proveedor`),
  UNIQUE KEY `proveedor_rubro_unique` (`proveedor_id`, `rubro_id`),
  KEY `proveedor_id` (`proveedor_id`),
  KEY `rubro_id`     (`rubro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `rubro_proveedor` (`id_rubro_proveedor`, `proveedor_id`, `rubro_id`) VALUES
(1, 3, 3);

-- ------------------------------------------------------------
-- insumos: categoria varchar → rubro_id FK
-- ------------------------------------------------------------
CREATE TABLE `insumos` (
  `id_insumo`     int(11)       NOT NULL AUTO_INCREMENT,
  `activo`        tinyint(1)    NOT NULL DEFAULT 1,
  `codigo`        varchar(50)   NOT NULL,
  `nombre`        varchar(150)  NOT NULL,
  `marca`         varchar(100)  DEFAULT NULL,
  `rubro_id`      int(11)       DEFAULT NULL,
  `unidad_medida` varchar(30)   DEFAULT NULL,
  `stock_actual`  decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_minimo`  decimal(10,2) NOT NULL DEFAULT 5.00,
  `precio_venta`  decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_compra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at`    datetime      NOT NULL DEFAULT current_timestamp(),
  `updated_at`    datetime      NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_insumo`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `rubro_id` (`rubro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `insumos` (`id_insumo`, `activo`, `codigo`, `nombre`, `marca`, `rubro_id`, `unidad_medida`, `stock_actual`, `stock_minimo`, `precio_venta`, `precio_compra`, `created_at`, `updated_at`) VALUES
(3, 1, '001', 'Industriacos', 'Groud',  1, 'Kilogramo', 12.00, 5.00, 2000.00, 977.00,  '2026-05-30 21:45:01', '2026-05-31 15:48:46'),
(4, 1, '002', 'Breaking Bad',  'Walter', 2, 'Litro',     17.00, 5.00, 1200.00, 750.00,  '2026-05-31 15:48:15', '2026-05-31 15:48:46');

CREATE TABLE `insumo_proveedor` (
  `id_insumo_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `insumo_id`           int(11) NOT NULL,
  `proveedor_id`        int(11) NOT NULL,
  PRIMARY KEY (`id_insumo_proveedor`),
  UNIQUE KEY `insumo_proveedor_unique` (`insumo_id`, `proveedor_id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT INTO `insumo_proveedor` (`id_insumo_proveedor`, `insumo_id`, `proveedor_id`) VALUES
(1, 3, 3),
(2, 4, 3);

-- ============================================================
-- FLUJO COMERCIAL
-- Tablas sin datos de ejemplo — listas para producción
-- ============================================================

CREATE TABLE `presupuestos` (
  `id_presupuesto` int(11)       NOT NULL AUTO_INCREMENT,
  `activo`         tinyint(1)    NOT NULL DEFAULT 1,
  `cliente_id`     int(11)       NOT NULL,
  `usuario_id`     int(11)       NOT NULL,
  `fecha`          datetime      NOT NULL,
  `total`          decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado`         enum('pendiente','aprobado','rechazado','convertido') NOT NULL DEFAULT 'pendiente',
  `observaciones`  text          DEFAULT NULL,
  `created_at`     datetime      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_presupuesto`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE `presupuesto_detalle` (
  `id_presupuesto_detalle` int(11)       NOT NULL AUTO_INCREMENT,
  `presupuesto_id`         int(11)       NOT NULL,
  `insumo_id`              int(11)       NOT NULL,
  `cantidad`               decimal(10,2) NOT NULL,
  `precio_unitario`        decimal(10,2) NOT NULL,
  `subtotal`               decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_presupuesto_detalle`),
  KEY `presupuesto_id` (`presupuesto_id`),
  KEY `insumo_id`      (`insumo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- ------------------------------------------------------------
-- notas_entrega: presupuesto_id es NOT NULL (obligatorio)
-- condicion_pago reemplaza tipo_pago (contado/credito)
-- tipo_pago_id FK reemplaza metodo_pago varchar
-- ------------------------------------------------------------
CREATE TABLE `notas_entrega` (
  `id_nota_entrega` int(11)       NOT NULL AUTO_INCREMENT,
  `activo`          tinyint(1)    NOT NULL DEFAULT 1,
  `cliente_id`      int(11)       NOT NULL,
  `usuario_id`      int(11)       NOT NULL,
  `presupuesto_id`  int(11)       NOT NULL,
  `fecha`           datetime      NOT NULL,
  `total`           decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado`          enum('pendiente','entregado','en_espera') NOT NULL DEFAULT 'pendiente',
  `condicion_pago`  enum('contado','credito') NOT NULL DEFAULT 'contado',
  `tipo_pago_id`    int(11)       DEFAULT NULL,
  `created_at`      datetime      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_nota_entrega`),
  KEY `cliente_id`     (`cliente_id`),
  KEY `usuario_id`     (`usuario_id`),
  KEY `presupuesto_id` (`presupuesto_id`),
  KEY `tipo_pago_id`   (`tipo_pago_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- ------------------------------------------------------------
-- nota_entrega_detalle: ya NO referencia insumos directamente.
-- Apunta a presupuesto_detalle, garantizando que toda nota
-- de entrega provenga de un presupuesto aprobado.
-- ------------------------------------------------------------
CREATE TABLE `nota_entrega_detalle` (
  `id_nota_entrega_detalle` int(11)       NOT NULL AUTO_INCREMENT,
  `nota_id`                 int(11)       NOT NULL,
  `presupuesto_detalle_id`  int(11)       NOT NULL,
  `cantidad`                decimal(10,2) NOT NULL,
  `precio_unitario`         decimal(10,2) NOT NULL,
  `subtotal`                decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_nota_entrega_detalle`),
  KEY `nota_id`                (`nota_id`),
  KEY `presupuesto_detalle_id` (`presupuesto_detalle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- ============================================================
-- CUENTAS Y PAGOS
-- Tablas sin datos de ejemplo — listas para producción
-- ============================================================

CREATE TABLE `cuentas_cobrar` (
  `id_cuenta_cobrar`  int(11)       NOT NULL AUTO_INCREMENT,
  `activo`            tinyint(1)    NOT NULL DEFAULT 1,
  `cliente_id`        int(11)       NOT NULL,
  `nota_entrega_id`   int(11)       DEFAULT NULL,
  `monto_total`       decimal(10,2) NOT NULL,
  `saldo_pendiente`   decimal(10,2) NOT NULL,
  `fecha_vencimiento` datetime      DEFAULT NULL,
  `estado`            enum('pendiente','pagado','moroso') NOT NULL DEFAULT 'pendiente',
  `created_at`        datetime      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cuenta_cobrar`),
  KEY `cliente_id`      (`cliente_id`),
  KEY `nota_entrega_id` (`nota_entrega_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE `pagos_recibidos` (
  `id_pago_recibido` int(11)       NOT NULL AUTO_INCREMENT,
  `cuenta_cobrar_id` int(11)       DEFAULT NULL,
  `tipo_pago_id`     int(11)       NOT NULL,
  `banco_id`         int(11)       DEFAULT NULL,
  `monto`            decimal(10,2) NOT NULL,
  `fecha`            datetime      NOT NULL,
  `referencia`       varchar(100)  DEFAULT NULL,
  PRIMARY KEY (`id_pago_recibido`),
  KEY `cuenta_cobrar_id` (`cuenta_cobrar_id`),
  KEY `tipo_pago_id`     (`tipo_pago_id`),
  KEY `banco_id`         (`banco_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE `cuentas_pagar` (
  `id_cuenta_pagar`   int(11)       NOT NULL AUTO_INCREMENT,
  `activo`            tinyint(1)    NOT NULL DEFAULT 1,
  `proveedor_id`      int(11)       NOT NULL,
  `monto_total`       decimal(10,2) NOT NULL,
  `saldo_pendiente`   decimal(10,2) NOT NULL,
  `fecha_vencimiento` datetime      DEFAULT NULL,
  `estado`            enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `created_at`        datetime      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cuenta_pagar`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

CREATE TABLE `pagos_realizados` (
  `id_pago_realizado` int(11)       NOT NULL AUTO_INCREMENT,
  `cuenta_pagar_id`   int(11)       NOT NULL,
  `tipo_pago_id`      int(11)       NOT NULL,
  `banco_id`          int(11)       DEFAULT NULL,
  `monto`             decimal(10,2) NOT NULL,
  `fecha`             datetime      NOT NULL,
  `referencia`        varchar(100)  DEFAULT NULL,
  PRIMARY KEY (`id_pago_realizado`),
  KEY `cuenta_pagar_id` (`cuenta_pagar_id`),
  KEY `tipo_pago_id`    (`tipo_pago_id`),
  KEY `banco_id`        (`banco_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- ============================================================
-- FOREIGN KEYS
-- ============================================================

ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol`
    FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id_rol`)
    ON UPDATE CASCADE;

ALTER TABLE `telefono_cliente`
  ADD CONSTRAINT `fk_telf_cliente_cliente`
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `telf_proveedor`
  ADD CONSTRAINT `fk_telf_proveedor_proveedor`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id_proveedor`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rubro_proveedor`
  ADD CONSTRAINT `fk_rubro_proveedor_proveedor`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id_proveedor`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rubro_proveedor_rubro`
    FOREIGN KEY (`rubro_id`) REFERENCES `rubro` (`id_rubro`)
    ON UPDATE CASCADE;

ALTER TABLE `insumos`
  ADD CONSTRAINT `fk_insumos_rubro`
    FOREIGN KEY (`rubro_id`) REFERENCES `rubro` (`id_rubro`)
    ON UPDATE CASCADE;

ALTER TABLE `insumo_proveedor`
  ADD CONSTRAINT `fk_insumo_proveedor_insumo`
    FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id_insumo`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_insumo_proveedor_proveedor`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id_proveedor`)
    ON UPDATE CASCADE;

ALTER TABLE `presupuestos`
  ADD CONSTRAINT `fk_presupuesto_cliente`
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presupuesto_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`)
    ON UPDATE CASCADE;

ALTER TABLE `presupuesto_detalle`
  ADD CONSTRAINT `fk_pres_detalle_presupuesto`
    FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id_presupuesto`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pres_detalle_insumo`
    FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id_insumo`)
    ON UPDATE CASCADE;

ALTER TABLE `notas_entrega`
  ADD CONSTRAINT `fk_nota_cliente`
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nota_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nota_presupuesto`
    FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id_presupuesto`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nota_tipo_pago`
    FOREIGN KEY (`tipo_pago_id`) REFERENCES `tipo_pago` (`id_tipo_pago`)
    ON UPDATE CASCADE;

ALTER TABLE `nota_entrega_detalle`
  ADD CONSTRAINT `fk_ned_nota`
    FOREIGN KEY (`nota_id`) REFERENCES `notas_entrega` (`id_nota_entrega`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ned_pres_detalle`
    FOREIGN KEY (`presupuesto_detalle_id`) REFERENCES `presupuesto_detalle` (`id_presupuesto_detalle`)
    ON UPDATE CASCADE;

ALTER TABLE `cuentas_cobrar`
  ADD CONSTRAINT `fk_cc_cliente`
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cc_nota_entrega`
    FOREIGN KEY (`nota_entrega_id`) REFERENCES `notas_entrega` (`id_nota_entrega`)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `pagos_recibidos`
  ADD CONSTRAINT `fk_pr_cuenta_cobrar`
    FOREIGN KEY (`cuenta_cobrar_id`) REFERENCES `cuentas_cobrar` (`id_cuenta_cobrar`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pr_tipo_pago`
    FOREIGN KEY (`tipo_pago_id`) REFERENCES `tipo_pago` (`id_tipo_pago`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pr_banco`
    FOREIGN KEY (`banco_id`) REFERENCES `banco` (`id_banco`)
    ON UPDATE CASCADE;

ALTER TABLE `cuentas_pagar`
  ADD CONSTRAINT `fk_cp_proveedor`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id_proveedor`)
    ON UPDATE CASCADE;

ALTER TABLE `pagos_realizados`
  ADD CONSTRAINT `fk_preal_cuenta_pagar`
    FOREIGN KEY (`cuenta_pagar_id`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_preal_tipo_pago`
    FOREIGN KEY (`tipo_pago_id`) REFERENCES `tipo_pago` (`id_tipo_pago`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_preal_banco`
    FOREIGN KEY (`banco_id`) REFERENCES `banco` (`id_banco`)
    ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
