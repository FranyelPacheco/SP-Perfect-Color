-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sp_perfect_color
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `banco`
--

DROP TABLE IF EXISTS `banco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banco` (
  `id_banco` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_banco`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banco`
--

LOCK TABLES `banco` WRITE;
/*!40000 ALTER TABLE `banco` DISABLE KEYS */;
INSERT INTO `banco` VALUES (1,'Banesco Banco Universal','2026-11-14 08:00:00',1),(2,'Banco Mercantil','2026-11-14 08:00:00',1),(3,'BBVA Provincial','2026-11-14 08:00:00',1),(4,'Banco de Venezuela','2026-11-14 08:00:00',1),(5,'Bancamiga Banco Universal','2026-11-14 08:00:00',1);
/*!40000 ALTER TABLE `banco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'V-15487962','Carlos Alberto','Mendoza Rodríguez','carlos.mendoza@gmail.com','Urb. Las Trinitarias, Manzana E, Barquisimeto','2026-11-14 10:00:00',1),(2,'V-18965234','María Elena','Guédez Salcedo','maria.guedez@hotmail.com','Av. Los Leones con Av. Lara, Torre Bell, Barquisimeto','2026-11-14 10:15:00',1),(3,'V-12874561','José Gregorio','Hernández Lucena','taller.hernandez@gmail.com','Barrio Unión, Calle 15 con Carrera 2, Taller Chapa y Pintura, Barquisimeto','2026-11-14 10:30:00',1),(4,'J-401589632','Auto Sport Tuning','C.A.','administracion@autosporttuning.com','Zona Industrial I, Galpón 4-B, Barquisimeto','2026-11-14 10:45:00',1),(5,'V-21456987','Alejandro José','Pérez Alvarado','alejandro.perez@gmail.com','Cabudare Centro, Calle San Rafael, Edo. Lara','2026-11-14 11:00:00',1);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compra`
--

DROP TABLE IF EXISTS `compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_compra`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `idx_compra_fecha` (`fecha`),
  CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON UPDATE CASCADE,
  CONSTRAINT `chk_compra_total` CHECK (`total` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra`
--

LOCK TABLES `compra` WRITE;
/*!40000 ALTER TABLE `compra` DISABLE KEYS */;
INSERT INTO `compra` VALUES (1,1,'2026-11-15 09:30:00',270.00,'Recepción de tintes base para formulación de colores automotrices','2026-11-15 09:30:00',1),(2,2,'2026-11-16 11:15:00',360.00,'Despacho de thinners acrílicos y barnices poliuretano (Crédito a 15 días)','2026-11-16 11:15:00',1),(3,3,'2026-11-17 14:00:00',185.00,'Reposición de abrasivos 3M, tirros automotrices y masilla','2026-11-17 14:00:00',1);
/*!40000 ALTER TABLE `compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compra_detalle`
--

DROP TABLE IF EXISTS `compra_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compra_detalle` (
  `id_compra_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (round(`cantidad` * `precio_compra`,2)) STORED,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_compra_detalle`),
  KEY `id_producto` (`id_producto`),
  KEY `id_compra` (`id_compra`),
  CONSTRAINT `fk_cd_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cd_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `chk_compra_detalle` CHECK (`cantidad` > 0 and `precio_compra` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra_detalle`
--

LOCK TABLES `compra_detalle` WRITE;
/*!40000 ALTER TABLE `compra_detalle` DISABLE KEYS */;
INSERT INTO `compra_detalle` VALUES (1,1,1,5.0000,25.00,125.00,'2026-11-15 09:30:00',1),(2,1,2,5.0000,25.00,125.00,'2026-11-15 09:30:00',1),(3,1,3,1.0000,20.00,20.00,'2026-11-15 09:30:00',1),(4,2,7,20.0000,12.00,240.00,'2026-11-16 11:15:00',1),(5,2,13,8.0000,15.00,120.00,'2026-11-16 11:15:00',1),(6,3,9,10.0000,8.50,85.00,'2026-11-17 14:00:00',1),(7,3,10,100.0000,0.60,60.00,'2026-11-17 14:00:00',1),(8,3,12,36.0000,1.11,39.96,'2026-11-17 14:00:00',1);
/*!40000 ALTER TABLE `compra_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_cobrar`
--

DROP TABLE IF EXISTS `cuentas_cobrar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_cobrar` (
  `id_cuenta_cobrar` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` datetime DEFAULT NULL,
  `situacion` enum('pendiente','pagado') GENERATED ALWAYS AS (if(`saldo_pendiente` <= 0,'pagado','pendiente')) STORED,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cuenta_cobrar`),
  UNIQUE KEY `uk_cxc_venta` (`id_venta`),
  CONSTRAINT `fk_cc_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON UPDATE CASCADE,
  CONSTRAINT `chk_cxc_monto` CHECK (`monto_total` >= 0),
  CONSTRAINT `chk_cxc_saldo` CHECK (`saldo_pendiente` >= 0 and `saldo_pendiente` <= `monto_total`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_cobrar`
--

LOCK TABLES `cuentas_cobrar` WRITE;
/*!40000 ALTER TABLE `cuentas_cobrar` DISABLE KEYS */;
INSERT INTO `cuentas_cobrar` VALUES (1,2,83.90,0.00,'2026-11-17 23:59:59','pagado','2026-11-17 09:15:00',1),(2,3,210.00,110.00,'2026-12-03 23:59:59','pendiente','2026-11-18 14:10:00',1),(3,4,43.20,0.00,'2026-11-19 23:59:59','pagado','2026-11-19 11:35:00',1);
/*!40000 ALTER TABLE `cuentas_cobrar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_pagar`
--

DROP TABLE IF EXISTS `cuentas_pagar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_pagar` (
  `id_cuenta_pagar` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` datetime DEFAULT NULL,
  `situacion` enum('pendiente','pagado') GENERATED ALWAYS AS (if(`saldo_pendiente` <= 0,'pagado','pendiente')) STORED,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cuenta_pagar`),
  UNIQUE KEY `uk_cxp_compra` (`id_compra`),
  CONSTRAINT `fk_cp_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON UPDATE CASCADE,
  CONSTRAINT `chk_cxp_monto` CHECK (`monto_total` >= 0),
  CONSTRAINT `chk_cxp_saldo` CHECK (`saldo_pendiente` >= 0 and `saldo_pendiente` <= `monto_total`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_pagar`
--

LOCK TABLES `cuentas_pagar` WRITE;
/*!40000 ALTER TABLE `cuentas_pagar` DISABLE KEYS */;
INSERT INTO `cuentas_pagar` VALUES (1,1,270.00,0.00,'2026-11-15 23:59:59','pagado','2026-11-15 09:30:00',1),(2,2,360.00,160.00,'2026-11-30 23:59:59','pendiente','2026-11-16 11:15:00',1),(3,3,185.00,0.00,'2026-11-17 23:59:59','pagado','2026-11-17 14:00:00',1);
/*!40000 ALTER TABLE `cuentas_pagar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado_venta`
--

DROP TABLE IF EXISTS `estado_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estado_venta` (
  `id_estado_venta` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_estado_venta`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado_venta`
--

LOCK TABLES `estado_venta` WRITE;
/*!40000 ALTER TABLE `estado_venta` DISABLE KEYS */;
INSERT INTO `estado_venta` VALUES (1,'presupuesto','2026-10-29 22:10:29',1),(2,'aprobado','2026-10-29 22:10:29',1),(3,'rechazado','2026-10-29 22:10:29',1),(4,'anulado','2026-10-29 22:10:29',1);
/*!40000 ALTER TABLE `estado_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merma_inventario`
--

DROP TABLE IF EXISTS `merma_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merma_inventario` (
  `id_merma` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_presentacion` tinyint(4) DEFAULT NULL COMMENT 'Presentacion perdida; NULL en productos que no se miden en galones',
  `cantidad` int(11) NOT NULL COMMENT 'Unidades perdidas de la presentacion indicada',
  `equivalente_128` int(11) GENERATED ALWAYS AS (if(`id_presentacion` is null,NULL,`cantidad` << 8 - `id_presentacion`)) STORED COMMENT 'Total perdido en 128avos de galon',
  `cantidad_descontada` decimal(14,7) GENERATED ALWAYS AS (if(`id_presentacion` is null,`cantidad`,`cantidad` * (1 << 8 - `id_presentacion`) / 128)) STORED COMMENT 'Valor restado de stock_actual',
  `motivo` varchar(250) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_merma`),
  KEY `fk_merma_producto` (`id_producto`),
  CONSTRAINT `fk_merma_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `chk_merma_cantidad` CHECK (`cantidad` > 0),
  CONSTRAINT `chk_merma_presentacion` CHECK (`id_presentacion` is null or `id_presentacion` between 1 and 8),
  CONSTRAINT `chk_merma_tope` CHECK (`cantidad` <= 1000000)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merma_inventario`
--

LOCK TABLES `merma_inventario` WRITE;
/*!40000 ALTER TABLE `merma_inventario` DISABLE KEYS */;
INSERT INTO `merma_inventario` VALUES (1,7,NULL,1,NULL,1.0000000,'Envase perforado accidentalmente durante descarga y estibado en almacén','2026-11-18 09:00:00');
/*!40000 ALTER TABLE `merma_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mezcla_producto`
--

DROP TABLE IF EXISTS `mezcla_producto`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;

CREATE TABLE `mezcla_producto` (
  `id_mezcla_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto_mezclado` int(11) NOT NULL COMMENT 'Producto nuevo resultante de la mezcla',
  `id_producto_base` int(11) NOT NULL COMMENT 'Pintura base o fraccion utilizada en la formula',
  `cantidad_galon` decimal(14,7) NOT NULL COMMENT 'Equivalente decimal descontado del stock base',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_mezcla_producto`),
  KEY `fk_mezcla_prod_mezclado` (`id_producto_mezclado`),
  KEY `fk_mezcla_prod_base` (`id_producto_base`),
  CONSTRAINT `fk_mezcla_prod_base` FOREIGN KEY (`id_producto_base`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mezcla_prod_mezclado` FOREIGN KEY (`id_producto_mezclado`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DELIMITER //

CREATE TRIGGER `validar_mezcla_producto_insert`
BEFORE INSERT ON `mezcla_producto`
FOR EACH ROW
BEGIN
  IF NEW.`cantidad_galon` <= 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La cantidad de galones debe ser mayor que cero';
  END IF;

  IF NEW.`id_producto_base` = NEW.`id_producto_mezclado` THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El producto base y el mezclado deben ser diferentes';
  END IF;
END//

CREATE TRIGGER `validar_mezcla_producto_update`
BEFORE UPDATE ON `mezcla_producto`
FOR EACH ROW
BEGIN
  IF NEW.`cantidad_galon` <= 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La cantidad de galones debe ser mayor que cero';
  END IF;

  IF NEW.`id_producto_base` = NEW.`id_producto_mezclado` THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El producto base y el mezclado deben ser diferentes';
  END IF;
END//

DELIMITER ;

--
-- Dumping data for table `mezcla_producto`
--

LOCK TABLES `mezcla_producto` WRITE;
/*!40000 ALTER TABLE `mezcla_producto` DISABLE KEYS */;
INSERT INTO `mezcla_producto` VALUES (1,14,3,0.1250000,'2026-11-14 12:00:00',1),(2,14,6,0.0937500,'2026-11-14 12:00:00',1),(3,14,1,0.0312500,'2026-11-14 12:00:00',1),(4,15,6,0.3750000,'2026-11-14 12:00:00',1),(5,15,1,0.0625000,'2026-11-14 12:00:00',1),(6,15,3,0.0625000,'2026-11-14 12:00:00',1);
/*!40000 ALTER TABLE `mezcla_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulos`
--

DROP TABLE IF EXISTS `modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulos` (
  `id_modulo` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_modulo`),
  UNIQUE KEY `uk_modulo_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulos`
--

LOCK TABLES `modulos` WRITE;
/*!40000 ALTER TABLE `modulos` DISABLE KEYS */;
INSERT INTO `modulos` VALUES (1,'dashboard','Dashboard','Panel principal y estadisticas','2026-10-29 22:10:29',1),(2,'cliente','Clientes','Gestion de clientes y contactos','2026-10-29 22:10:29',1),(3,'proveedor','Proveedores','Gestion de proveedores y rubros','2026-10-29 22:10:29',1),(4,'inventario','Inventario','Catalogo de productos y stock','2026-10-29 22:10:29',1),(5,'presupuesto','Presupuestos','Cotizaciones y presupuestos','2026-10-29 22:10:29',1),(6,'notaEntrega','Notas de Venta','Ventas y entregas a clientes','2026-10-29 22:10:29',1),(7,'cuentaCobrar','Cuentas por Cobrar','Cobranzas y pagos de clientes','2026-10-29 22:10:29',1),(8,'cuentaPagar','Cuentas por Pagar','Pagos a proveedores','2026-10-29 22:10:29',1),(9,'banco','Bancos','Catalogo de entidades bancarias','2026-10-29 22:10:29',1),(10,'tipoPago','Tipos de Pago','Formas y metodos de pago','2026-10-29 22:10:29',1),(11,'reporte','Reportes','Reportes y exportaciones PDF/Excel','2026-10-29 22:10:29',1),(12,'usuario','Usuarios y Roles','Administracion de usuarios, roles y permisos','2026-10-29 22:10:29',1),(13,'tasaCambio','Tasa de Cambio','Gestion de la tasa de cambio USD/Bs','2026-10-29 22:10:29',1),(14,'notaEntregaCompra','Notas de Entrega (Compras)','Recepcion de insumos de proveedores','2026-10-29 22:10:29',1);
/*!40000 ALTER TABLE `modulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos_realizados`
--

DROP TABLE IF EXISTS `pagos_realizados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos_realizados` (
  `id_pago_realizado` int(11) NOT NULL AUTO_INCREMENT,
  `id_cuenta_pagar` int(11) NOT NULL,
  `id_tipo_pago` int(11) NOT NULL,
  `id_banco` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tasa_bcv` decimal(12,4) DEFAULT NULL COMMENT 'Tasa Bs/$ vigente el dia del pago, fija',
  `fecha` datetime NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_pago_realizado`),
  KEY `id_cuenta_pagar` (`id_cuenta_pagar`),
  KEY `id_tipo_pago` (`id_tipo_pago`),
  KEY `id_banco` (`id_banco`),
  KEY `idx_pago_realizado_fecha` (`fecha`),
  CONSTRAINT `fk_preal_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON UPDATE CASCADE,
  CONSTRAINT `fk_preal_cuenta_pagar` FOREIGN KEY (`id_cuenta_pagar`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`) ON UPDATE CASCADE,
  CONSTRAINT `fk_preal_tipo_pago` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago` (`id_tipo_pago`) ON UPDATE CASCADE,
  CONSTRAINT `chk_pago_realizado_monto` CHECK (`monto` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_realizados`
--

LOCK TABLES `pagos_realizados` WRITE;
/*!40000 ALTER TABLE `pagos_realizados` DISABLE KEYS */;
INSERT INTO `pagos_realizados` VALUES (1,1,2,1,270.00,45.9500,'2026-11-15 10:00:00','78412','2026-11-15 10:00:00',1),(2,2,3,2,200.00,46.2000,'2026-11-16 11:30:00','35961','2026-11-16 11:30:00',1),(3,3,1,NULL,185.00,46.5000,'2026-11-17 14:15:00','Efectivo','2026-11-17 14:15:00',1);
/*!40000 ALTER TABLE `pagos_realizados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos_recibidos`
--

DROP TABLE IF EXISTS `pagos_recibidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos_recibidos` (
  `id_pago_recibido` int(11) NOT NULL AUTO_INCREMENT,
  `id_cuenta_cobrar` int(11) NOT NULL,
  `id_tipo_pago` int(11) NOT NULL,
  `id_banco` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tasa_bcv` decimal(12,4) DEFAULT NULL COMMENT 'Tasa Bs/$ vigente el dia del pago, fija',
  `fecha` datetime NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_pago_recibido`),
  KEY `id_cuenta_cobrar` (`id_cuenta_cobrar`),
  KEY `id_tipo_pago` (`id_tipo_pago`),
  KEY `id_banco` (`id_banco`),
  KEY `idx_pago_recibido_fecha` (`fecha`),
  CONSTRAINT `fk_pr_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pr_cuenta_cobrar` FOREIGN KEY (`id_cuenta_cobrar`) REFERENCES `cuentas_cobrar` (`id_cuenta_cobrar`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pr_tipo_pago` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago` (`id_tipo_pago`) ON UPDATE CASCADE,
  CONSTRAINT `chk_pago_recibido_monto` CHECK (`monto` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_recibidos`
--

LOCK TABLES `pagos_recibidos` WRITE;
/*!40000 ALTER TABLE `pagos_recibidos` DISABLE KEYS */;
INSERT INTO `pagos_recibidos` VALUES (1,1,2,2,83.90,46.5000,'2026-11-17 09:30:00','45812','2026-11-17 09:30:00',1),(2,2,3,1,100.00,46.8500,'2026-11-18 14:15:00','96325','2026-11-18 14:15:00',1),(3,3,1,NULL,43.20,47.1500,'2026-11-19 11:40:00','Efectivo','2026-11-19 11:40:00',1);
/*!40000 ALTER TABLE `pagos_recibidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_proveedor`
--

DROP TABLE IF EXISTS `producto_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto_proveedor` (
  `id_producto_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_producto_proveedor`),
  UNIQUE KEY `insumo_proveedor_unique` (`id_producto`,`id_proveedor`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `fk_insumo_proveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_proveedor_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_proveedor`
--

LOCK TABLES `producto_proveedor` WRITE;
/*!40000 ALTER TABLE `producto_proveedor` DISABLE KEYS */;
INSERT INTO `producto_proveedor` VALUES (1,1,1,'2026-11-14 12:10:00',1),(2,2,1,'2026-11-14 12:10:00',1),(3,3,1,'2026-11-14 12:10:00',1),(4,4,1,'2026-11-14 12:10:00',1),(5,5,1,'2026-11-14 12:10:00',1),(6,6,1,'2026-11-14 12:10:00',1),(7,7,2,'2026-11-14 12:10:00',1),(8,8,2,'2026-11-14 12:10:00',1),(9,13,2,'2026-11-14 12:10:00',1),(10,9,3,'2026-11-14 12:10:00',1),(11,10,3,'2026-11-14 12:10:00',1),(12,11,3,'2026-11-14 12:10:00',1),(13,12,3,'2026-11-14 12:10:00',1);
/*!40000 ALTER TABLE `producto_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo_producto` int(11) NOT NULL DEFAULT 2,
  `id_producto_padre` int(11) DEFAULT NULL COMMENT 'Si es fraccion (1/2, 1/4... 1/128), apunta al producto principal de 1 Galon',
  `fraccion` varchar(15) NOT NULL DEFAULT '1' COMMENT 'Presentacion: 1, 1/2, 1/4, 1/8, 1/16, 1/32, 1/64, 1/128',
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `unidad_medida` enum('Galon','1/2 Galon','1/4 Galon','1/8 Galon','1/16 Galon','1/32 Galon','1/64 Galon','1/128 Galon','Unidad','KG') NOT NULL DEFAULT 'Unidad',
  `stock_actual` decimal(14,7) NOT NULL DEFAULT 0.0000000,
  `stock_minimo` decimal(14,7) NOT NULL DEFAULT 5.0000000,
  `alerta_activa` tinyint(1) NOT NULL DEFAULT 1,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_productos_tipo` (`id_tipo_producto`),
  KEY `fk_productos_padre` (`id_producto_padre`),
  CONSTRAINT `fk_productos_padre` FOREIGN KEY (`id_producto_padre`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_productos_tipo` FOREIGN KEY (`id_tipo_producto`) REFERENCES `tipo_producto` (`id_tipo_producto`) ON UPDATE CASCADE,
  CONSTRAINT `chk_producto_stock` CHECK (`stock_actual` >= 0 and `stock_minimo` >= 0),
  CONSTRAINT `chk_producto_precio` CHECK (`precio_venta` >= 0),
  CONSTRAINT `chk_producto_fraccion` CHECK (`fraccion` in ('1','1/2','1/4','1/8','1/16','1/32','1/64','1/128'))
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,1,NULL,'1','TINT-001','Tinte Base Negro Concentrado','Galon',18.5000000,5.0000000,1,35.00,'2026-11-14 11:30:00',1),(2,1,NULL,'1','TINT-002','Tinte Base Blanco Puro','Galon',24.0000000,5.0000000,1,35.00,'2026-11-14 11:30:00',1),(3,1,NULL,'1','TINT-003','Tinte Base Azul Perlado','Galon',14.2500000,4.0000000,1,42.00,'2026-11-14 11:30:00',1),(4,1,NULL,'1','TINT-004','Tinte Base Rojo Escarlata','Galon',12.0000000,4.0000000,1,45.00,'2026-11-14 11:30:00',1),(5,1,NULL,'1','TINT-005','Tinte Base Amarillo Óxido','Galon',10.0000000,3.0000000,1,38.00,'2026-11-14 11:30:00',1),(6,1,NULL,'1','TINT-006','Tinte Base Plata Aluminio Fino','Galon',16.7500000,5.0000000,1,48.00,'2026-11-14 11:30:00',1),(7,2,NULL,'1','THIN-001','Thinner Acrílico Limpiador 1 Galón','Unidad',45.0000000,10.0000000,1,18.00,'2026-11-14 11:30:00',1),(8,2,NULL,'1','FON-001','Fondo 2K Gris Primer Rellenador','Unidad',28.0000000,8.0000000,1,26.50,'2026-11-14 11:30:00',1),(9,2,NULL,'1','MAS-001','Masilla Plástica Poliéster 1KG','KG',34.0000000,10.0000000,1,13.00,'2026-11-14 11:30:00',1),(10,2,NULL,'1','LIJ-001','Lija de Agua Grano 400 3M','Unidad',180.0000000,50.0000000,1,1.20,'2026-11-14 11:30:00',1),(11,2,NULL,'1','LIJ-002','Lija de Agua Grano 1200 3M','Unidad',140.0000000,40.0000000,1,1.40,'2026-11-14 11:30:00',1),(12,2,NULL,'1','TIR-001','Tirro de Papel Crepe 3/4\" Automotriz','Unidad',95.0000000,25.0000000,1,2.20,'2026-11-14 11:30:00',1),(13,2,NULL,'1','BAR-001','Barniz Poliuretano Transparente Kit 1L','Unidad',22.0000000,6.0000000,1,22.00,'2026-11-14 11:30:00',1),(14,3,NULL,'1/4','MEZC-001','Azul Real Metalizado Toyota (1/4 Galón)','Galon',2.0000000,0.0000000,1,28.00,'2026-11-14 12:00:00',1),(15,3,NULL,'1/2','MEZC-002','Gris Plata Lunar Chevrolet (1/2 Galón)','Galon',1.0000000,0.0000000,1,48.00,'2026-11-14 12:00:00',1);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `rif` varchar(20) NOT NULL,
  `nombre_empresa` varchar(150) NOT NULL,
  `direccion` text DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `rif` (`rif`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'J-301258941','Pinturas Montana C.A.','Zona Industrial II, Av. Circunvalación, Barquisimeto, Edo. Lara','contacto@montana.com.ve','2026-11-14 09:00:00',1),(2,'J-314896253','Químicos y Solventes del Centro S.A.','Av. Florencio Jiménez, Km 4, Barquisimeto, Edo. Lara','ventas@quimicoscentro.com.ve','2026-11-14 09:15:00',1),(3,'J-408962314','Distribuidora de Abrasivos 3M Lara C.A.','Carrera 19 entre Calles 22 y 23, Barquisimeto, Edo. Lara','pedidos@abrasivoslara.com.ve','2026-11-14 09:30:00',1);
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol_modulo`
--

DROP TABLE IF EXISTS `rol_modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_modulo` (
  `id_rol_modulo` int(11) NOT NULL AUTO_INCREMENT,
  `id_rol` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_rol_modulo`),
  UNIQUE KEY `uk_rol_modulo` (`id_rol`,`id_modulo`),
  KEY `fk_rol_modulo_modulo` (`id_modulo`),
  CONSTRAINT `fk_rol_modulo_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rol_modulo_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_modulo`
--

LOCK TABLES `rol_modulo` WRITE;
/*!40000 ALTER TABLE `rol_modulo` DISABLE KEYS */;
INSERT INTO `rol_modulo` VALUES (1,1,1,'2026-10-29 22:10:29',1),(2,1,2,'2026-10-29 22:10:29',1),(3,1,3,'2026-10-29 22:10:29',1),(4,1,4,'2026-10-29 22:10:29',1),(5,1,5,'2026-10-29 22:10:29',1),(6,1,6,'2026-10-29 22:10:29',1),(7,1,7,'2026-10-29 22:10:29',1),(8,1,8,'2026-10-29 22:10:29',1),(9,1,9,'2026-10-29 22:10:29',1),(10,1,10,'2026-10-29 22:10:29',1),(11,1,11,'2026-10-29 22:10:29',1),(12,1,12,'2026-10-29 22:10:29',1),(13,1,13,'2026-10-29 22:10:29',1),(14,1,14,'2026-10-29 22:10:29',1),(15,2,1,'2026-10-29 22:10:29',1),(16,2,2,'2026-10-29 22:10:29',1),(17,2,4,'2026-10-29 22:10:29',1),(18,2,5,'2026-10-29 22:10:29',1),(19,2,6,'2026-10-29 22:10:29',1),(20,2,7,'2026-10-29 22:10:29',1),(21,2,11,'2026-10-29 22:10:29',1);
/*!40000 ALTER TABLE `rol_modulo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador','2026-10-11 23:10:59',1),(2,'Vendedor','2026-10-11 23:10:59',1);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rubro`
--

DROP TABLE IF EXISTS `rubro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rubro` (
  `id_rubro` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_rubro`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rubro`
--

LOCK TABLES `rubro` WRITE;
/*!40000 ALTER TABLE `rubro` DISABLE KEYS */;
INSERT INTO `rubro` VALUES (1,'Pinturas y Colorimetría Automotriz','2026-11-14 08:30:00',1),(2,'Solventes y Diluyentes Industriales','2026-11-14 08:30:00',1),(3,'Abrasivos, Masillas y Accesorios','2026-11-14 08:30:00',1);
/*!40000 ALTER TABLE `rubro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rubro_proveedor`
--

DROP TABLE IF EXISTS `rubro_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rubro_proveedor` (
  `id_rubro_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `id_rubro` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_rubro_proveedor`),
  UNIQUE KEY `proveedor_rubro_unique` (`id_proveedor`,`id_rubro`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_rubro` (`id_rubro`),
  CONSTRAINT `fk_rubro_proveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rubro_proveedor_rubro` FOREIGN KEY (`id_rubro`) REFERENCES `rubro` (`id_rubro`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rubro_proveedor`
--

LOCK TABLES `rubro_proveedor` WRITE;
/*!40000 ALTER TABLE `rubro_proveedor` DISABLE KEYS */;
INSERT INTO `rubro_proveedor` VALUES (1,1,1,'2026-11-14 09:00:00',1),(2,2,2,'2026-11-14 09:15:00',1),(3,3,3,'2026-11-14 09:30:00',1);
/*!40000 ALTER TABLE `rubro_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasa_cambio`
--

DROP TABLE IF EXISTS `tasa_cambio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasa_cambio` (
  `id_tasa_cambio` int(11) NOT NULL AUTO_INCREMENT,
  `tasa` decimal(12,4) NOT NULL COMMENT 'Valor de 1 USD en Bolivares (Bs.)',
  `fecha` date NOT NULL COMMENT 'Fecha de vigencia de la tasa',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tasa_cambio`),
  UNIQUE KEY `uk_tasa_fecha` (`fecha`),
  CONSTRAINT `chk_tasa_valor` CHECK (`tasa` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasa_cambio`
--

LOCK TABLES `tasa_cambio` WRITE;
/*!40000 ALTER TABLE `tasa_cambio` DISABLE KEYS */;
INSERT INTO `tasa_cambio` VALUES (1,45.8000,'2026-11-14','2026-11-14 08:00:00',1),(2,45.9500,'2026-11-15','2026-11-15 08:00:00',1),(3,46.2000,'2026-11-16','2026-11-16 08:00:00',1),(4,46.5000,'2026-11-17','2026-11-17 08:00:00',1),(5,46.8500,'2026-11-18','2026-11-18 08:00:00',1),(6,47.1500,'2026-11-19','2026-11-19 08:00:00',1);
/*!40000 ALTER TABLE `tasa_cambio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telefono_cliente`
--

DROP TABLE IF EXISTS `telefono_cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telefono_cliente` (
  `id_cliente` int(11) NOT NULL,
  `id_telefono_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `telefono` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cliente`,`id_telefono_cliente`),
  UNIQUE KEY `uk_id_telefono_cliente` (`id_telefono_cliente`),
  CONSTRAINT `fk_telf_cliente_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telefono_cliente`
--

LOCK TABLES `telefono_cliente` WRITE;
/*!40000 ALTER TABLE `telefono_cliente` DISABLE KEYS */;
INSERT INTO `telefono_cliente` VALUES (1,1,'04123456789','2026-11-14 10:00:00',1),(2,2,'04149876543','2026-11-14 10:15:00',1),(3,3,'04245128963','2026-11-14 10:30:00',1),(4,4,'02512378945','2026-11-14 10:45:00',1),(5,5,'04166541238','2026-11-14 11:00:00',1);
/*!40000 ALTER TABLE `telefono_cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telefono_proveedor`
--

DROP TABLE IF EXISTS `telefono_proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telefono_proveedor` (
  `id_proveedor` int(11) NOT NULL,
  `id_telefono_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `telefono` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_proveedor`,`id_telefono_proveedor`),
  UNIQUE KEY `uk_id_telefono_proveedor` (`id_telefono_proveedor`),
  CONSTRAINT `fk_telefono_proveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telefono_proveedor`
--

LOCK TABLES `telefono_proveedor` WRITE;
/*!40000 ALTER TABLE `telefono_proveedor` DISABLE KEYS */;
INSERT INTO `telefono_proveedor` VALUES (1,1,'04145123456','2026-11-14 09:00:00',1),(2,2,'04127896541','2026-11-14 09:15:00',1),(3,3,'04245412389','2026-11-14 09:30:00',1);
/*!40000 ALTER TABLE `telefono_proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_pago`
--

DROP TABLE IF EXISTS `tipo_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_pago` (
  `id_tipo_pago` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tipo_pago`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_pago`
--

LOCK TABLES `tipo_pago` WRITE;
/*!40000 ALTER TABLE `tipo_pago` DISABLE KEYS */;
INSERT INTO `tipo_pago` VALUES (1,'Efectivo ($ USD)','2026-11-14 08:00:00',1),(2,'Transferencia Bancaria','2026-11-14 08:00:00',1),(3,'Pago Móvil','2026-11-14 08:00:00',1),(4,'Efectivo (Bs.)','2026-11-14 08:00:00',1);
/*!40000 ALTER TABLE `tipo_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_producto`
--

DROP TABLE IF EXISTS `tipo_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_producto` (
  `id_tipo_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tipo_producto`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_producto`
--

LOCK TABLES `tipo_producto` WRITE;
/*!40000 ALTER TABLE `tipo_producto` DISABLE KEYS */;
INSERT INTO `tipo_producto` VALUES (1,'Base','Insumo / tinte primario o quimico base para mezclas de colorimetria','2026-10-29 22:10:29',1),(2,'Simple','Producto de venta directa terminada (tirros, lijas, brochas, pulituras, etc.)','2026-10-29 22:10:29',1),(3,'Preparado','Pintura preparada creada mediante mezcla de varios productos base','2026-10-29 22:10:29',1);
/*!40000 ALTER TABLE `tipo_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `contrato` varchar(255) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (2,'Administrador','admin@perfectcolor.com','$2y$10$ZqUk5Awoey5CVkqx3Tgg2.A9YTTQwRHLehxDLtLN/fYLkyGrMi5HC',NULL,NULL,1,'2026-10-11 23:10:59',1),(5,'Fran','pacheco@ejemplo.com','$2y$10$ZqUk5Awoey5CVkqx3Tgg2.A9YTTQwRHLehxDLtLN/fYLkyGrMi5HC',NULL,NULL,2,'2026-10-11 23:10:59',1),(6,'Fran','franyelpacheco@gmail.com','$2y$12$33a4YZouMdDsKZtigXdiU.IFWop6Kc6rduftxFFimhJfNBrDVKh/K',NULL,NULL,1,'2026-11-17 19:45:44',1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_detalle`
--

DROP TABLE IF EXISTS `venta_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venta_detalle` (
  `id_venta_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `descripcion` varchar(200) NOT NULL DEFAULT '',
  `cantidad` decimal(12,4) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (round(`cantidad` * `precio_unitario`,2)) STORED,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_venta_detalle`),
  KEY `id_venta` (`id_venta`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `fk_vdetalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vdetalle_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_venta_detalle` CHECK (`cantidad` > 0 and `precio_unitario` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_detalle`
--

LOCK TABLES `venta_detalle` WRITE;
/*!40000 ALTER TABLE `venta_detalle` DISABLE KEYS */;
INSERT INTO `venta_detalle` VALUES (1,1,9,'Masilla Plástica Poliéster 1KG',1.0000,13.00,13.00,'2026-11-18 10:20:00'),(2,1,8,'Fondo 2K Gris Primer Rellenador',1.0000,26.50,26.50,'2026-11-18 10:20:00'),(3,1,10,'Lija de Agua Grano 400 3M',10.0000,1.20,12.00,'2026-11-18 10:20:00'),(4,1,12,'Tirro de Papel Crepe 3/4\" Automotriz',3.0000,2.40,7.20,'2026-11-18 10:20:00'),(5,2,14,'Azul Real Metalizado Toyota (1/4 Galón)',1.0000,28.00,28.00,'2026-11-17 09:10:00'),(6,2,8,'Fondo 2K Gris Primer Rellenador',1.0000,26.50,26.50,'2026-11-17 09:10:00'),(7,2,7,'Thinner Acrílico Limpiador 1 Galón',1.0000,18.00,18.00,'2026-11-17 09:10:00'),(8,2,11,'Lija de Agua Grano 1200 3M',8.0000,1.43,11.44,'2026-11-17 09:10:00'),(9,3,15,'Gris Plata Lunar Chevrolet (1/2 Galón)',2.0000,48.00,96.00,'2026-11-18 14:00:00'),(10,3,13,'Barniz Poliuretano Transparente Kit 1L',3.0000,22.00,66.00,'2026-11-18 14:00:00'),(11,3,7,'Thinner Acrílico Limpiador 1 Galón',2.0000,18.00,36.00,'2026-11-18 14:00:00'),(12,3,12,'Tirro de Papel Crepe 3/4\" Automotriz',5.0000,2.40,12.00,'2026-11-18 14:00:00'),(13,4,14,'Azul Real Metalizado Toyota (1/4 Galón)',1.0000,28.00,28.00,'2026-11-19 11:30:00'),(14,4,10,'Lija de Agua Grano 400 3M',5.0000,1.20,6.00,'2026-11-19 11:30:00'),(15,4,11,'Lija de Agua Grano 1200 3M',5.0000,1.40,7.00,'2026-11-19 11:30:00'),(16,4,12,'Tirro de Papel Crepe 3/4\" Automotriz',1.0000,2.20,2.20,'2026-11-19 11:30:00');
/*!40000 ALTER TABLE `venta_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_estado_venta` int(11) NOT NULL DEFAULT 1,
  `fecha` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creacion / cotizacion',
  `fecha_aprobacion` datetime DEFAULT NULL COMMENT 'Fecha de aprobacion de la venta',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `condicion_pago` enum('contado','credito') NOT NULL DEFAULT 'contado',
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_venta`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_estado_venta` (`id_estado_venta`),
  KEY `idx_ventas_fecha` (`fecha`),
  CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_estado` FOREIGN KEY (`id_estado_venta`) REFERENCES `estado_venta` (`id_estado_venta`) ON UPDATE CASCADE,
  CONSTRAINT `chk_venta_total` CHECK (`total` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,1,1,'2026-11-18 10:20:00',NULL,58.70,'contado','Presupuesto cotizado para pintar capot y guardafango de Optra','2026-11-18 10:20:00',1),(2,3,2,'2026-11-17 09:10:00','2026-11-17 09:15:00',83.90,'contado','Materiales despachados a Taller Hernández para Toyota Corolla','2026-11-17 09:10:00',1),(3,4,2,'2026-11-18 14:00:00','2026-11-18 14:10:00',210.00,'credito','Lote de pinturas preparadas y barnices a crédito comercial (15 días)','2026-11-18 14:00:00',1),(4,2,2,'2026-11-19 11:30:00','2026-11-19 11:35:00',43.20,'contado','Pintura preparada Azul Toyota y consumibles de lijado en mostrador','2026-11-19 11:30:00',1);
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-19 19:25:50
