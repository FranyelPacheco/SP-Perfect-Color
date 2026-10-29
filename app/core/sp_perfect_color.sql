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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_banco`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banco`
--

LOCK TABLES `banco` WRITE;
/*!40000 ALTER TABLE `banco` DISABLE KEYS */;
INSERT INTO `banco` VALUES (1,'Banco de Venezuela','2026-10-12 03:10:59',1),(2,'Banesco','2026-10-12 03:10:59',1),(3,'Mercantil','2026-10-12 03:10:59',1),(4,'Banco Provincial','2026-10-12 03:10:59',1);
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'V-12345678','Juan Carlos','Perez Silva','juan.perez@email.com','Av. Bolivar, Edf. Los Andes, Barquisimeto','2026-10-17 15:58:45',1),(2,'V-19876543','Maria Alejandra','Gonzalez Rivas','maria.gonzalez@email.com','Calle 30 con Carrera 19, Barquisimeto','2026-10-17 15:58:45',1),(3,'J-30123456','Taller Automotriz','Los Andes C.A.','contacto@tallerlosandes.com','Zona Industrial 1, Galpon 4B, Barquisimeto','2026-10-17 15:58:45',1);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
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
  `situacion` enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cuenta_cobrar`),
  KEY `id_venta` (`id_venta`),
  CONSTRAINT `fk_cc_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_cobrar`
--

LOCK TABLES `cuentas_cobrar` WRITE;
/*!40000 ALTER TABLE `cuentas_cobrar` DISABLE KEYS */;
INSERT INTO `cuentas_cobrar` VALUES (1,2,37.50,0.00,'2026-10-15 11:25:00','pagado','2026-10-15 11:25:00',1),(2,3,150.50,100.50,'2026-10-31 00:00:00','pendiente','2026-10-16 16:10:00',1);
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
  `id_nota_entrega` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `saldo_pendiente` decimal(10,2) NOT NULL,
  `fecha_vencimiento` datetime DEFAULT NULL,
  `situacion` enum('pendiente','pagado') NOT NULL DEFAULT 'pendiente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cuenta_pagar`),
  KEY `id_nota_entrega` (`id_nota_entrega`),
  CONSTRAINT `fk_cp_nota_entrega` FOREIGN KEY (`id_nota_entrega`) REFERENCES `nota_entrega` (`id_nota_entrega`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_pagar`
--

LOCK TABLES `cuentas_pagar` WRITE;
/*!40000 ALTER TABLE `cuentas_pagar` DISABLE KEYS */;
INSERT INTO `cuentas_pagar` VALUES (1,1,480.00,0.00,'2026-10-14 10:30:00','pagado','2026-10-14 10:30:00',1),(2,2,250.00,150.00,'2026-10-31 00:00:00','pendiente','2026-10-16 14:15:00',1);
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
INSERT INTO `estado_venta` VALUES (1,'presupuesto',1),(2,'aprobado',1),(3,'rechazado',1),(4,'anulado',1);
/*!40000 ALTER TABLE `estado_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mezcla_producto`
--

DROP TABLE IF EXISTS `mezcla_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mezcla_producto` (
  `id_mezcla_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto_mezclado` int(11) NOT NULL COMMENT 'Producto nuevo resultante de la mezcla',
  `id_producto_base` int(11) NOT NULL COMMENT 'Pintura base o fraccion utilizada en la formula',
  `fraccion` varchar(15) NOT NULL DEFAULT '1/4' COMMENT 'Medida usada: 1/2, 1/4, ..., 1/128',
  `cantidad_galon` decimal(14,7) NOT NULL COMMENT 'Equivalente decimal descontado del stock base',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_mezcla_producto`),
  KEY `fk_mezcla_prod_mezclado` (`id_producto_mezclado`),
  KEY `fk_mezcla_prod_base` (`id_producto_base`),
  CONSTRAINT `fk_mezcla_prod_base` FOREIGN KEY (`id_producto_base`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mezcla_prod_mezclado` FOREIGN KEY (`id_producto_mezclado`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mezcla_producto`
--

LOCK TABLES `mezcla_producto` WRITE;
/*!40000 ALTER TABLE `mezcla_producto` DISABLE KEYS */;
INSERT INTO `mezcla_producto` VALUES (1,17,1,'1/8',0.1250,1),(2,17,2,'1/8',0.1250,1);
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
INSERT INTO `modulos` VALUES (1,'dashboard','Dashboard','Panel principal y estadisticas',1),(2,'cliente','Clientes','Gestion de clientes y contactos',1),(3,'proveedor','Proveedores','Gestion de proveedores y rubros',1),(4,'inventario','Inventario','Catalogo de productos y stock',1),(5,'presupuesto','Presupuestos','Cotizaciones y presupuestos',1),(6,'notaEntrega','Notas de Venta','Ventas y entregas a clientes',1),(7,'cuentaCobrar','Cuentas por Cobrar','Cobranzas y pagos de clientes',1),(8,'cuentaPagar','Cuentas por Pagar','Pagos a proveedores',1),(9,'banco','Bancos','Catalogo de entidades bancarias',1),(10,'tipoPago','Tipos de Pago','Formas y metodos de pago',1),(11,'reporte','Reportes','Reportes y exportaciones PDF/Excel',1),(12,'usuario','Usuarios y Roles','Administracion de usuarios, roles y permisos',1),(13,'tasaCambio','Tasa de Cambio','Gestion de la tasa de cambio USD/Bs',1),(14,'notaEntregaCompra','Notas de Entrega (Compras)','Recepcion de insumos de proveedores',1);
/*!40000 ALTER TABLE `modulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nota_entrega`
--

DROP TABLE IF EXISTS `nota_entrega`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nota_entrega` (
  `id_nota_entrega` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_nota_entrega`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `fk_ne_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nota_entrega`
--

LOCK TABLES `nota_entrega` WRITE;
/*!40000 ALTER TABLE `nota_entrega` DISABLE KEYS */;
INSERT INTO `nota_entrega` VALUES (1,1,'2026-10-14 10:30:00',480.00,'Recepcion de tintes base montana lote 104','2026-10-14 10:30:00',1),(2,2,'2026-10-16 14:15:00',250.00,'Insumos de lijado y enmascarar','2026-10-16 14:15:00',1);
/*!40000 ALTER TABLE `nota_entrega` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nota_entrega_producto`
--

DROP TABLE IF EXISTS `nota_entrega_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nota_entrega_producto` (
  `id_nota_entrega_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_nota_entrega` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(12,4) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_nota_entrega_producto`),
  KEY `id_nota_entrega` (`id_nota_entrega`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `fk_nep_nota_entrega` FOREIGN KEY (`id_nota_entrega`) REFERENCES `nota_entrega` (`id_nota_entrega`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_nep_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nota_entrega_producto`
--

LOCK TABLES `nota_entrega_producto` WRITE;
/*!40000 ALTER TABLE `nota_entrega_producto` DISABLE KEYS */;
INSERT INTO `nota_entrega_producto` VALUES (1,1,1,10.0000,35.00,350.00,1),(2,1,15,15.0000,6.00,90.00,1),(3,1,12,10.0000,4.00,40.00,1),(4,2,12,20.0000,2.50,50.00,1),(5,2,13,100.0000,0.80,80.00,1),(6,2,16,10.0000,12.00,120.00,1);
/*!40000 ALTER TABLE `nota_entrega_producto` ENABLE KEYS */;
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
  `fecha` datetime NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_pago_realizado`),
  KEY `id_cuenta_pagar` (`id_cuenta_pagar`),
  KEY `id_tipo_pago` (`id_tipo_pago`),
  KEY `id_banco` (`id_banco`),
  CONSTRAINT `fk_preal_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON UPDATE CASCADE,
  CONSTRAINT `fk_preal_cuenta_pagar` FOREIGN KEY (`id_cuenta_pagar`) REFERENCES `cuentas_pagar` (`id_cuenta_pagar`) ON UPDATE CASCADE,
  CONSTRAINT `fk_preal_tipo_pago` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago` (`id_tipo_pago`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_realizados`
--

LOCK TABLES `pagos_realizados` WRITE;
/*!40000 ALTER TABLE `pagos_realizados` DISABLE KEYS */;
INSERT INTO `pagos_realizados` VALUES (1,1,2,2,480.00,'2026-10-14 10:35:00','TRANSF-774411',1),(2,2,1,NULL,100.00,'2026-10-16 14:15:00','Efectivo',1);
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
  `fecha` datetime NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_pago_recibido`),
  KEY `id_cuenta_cobrar` (`id_cuenta_cobrar`),
  KEY `id_tipo_pago` (`id_tipo_pago`),
  KEY `id_banco` (`id_banco`),
  CONSTRAINT `fk_pr_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pr_cuenta_cobrar` FOREIGN KEY (`id_cuenta_cobrar`) REFERENCES `cuentas_cobrar` (`id_cuenta_cobrar`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pr_tipo_pago` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago` (`id_tipo_pago`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_recibidos`
--

LOCK TABLES `pagos_recibidos` WRITE;
/*!40000 ALTER TABLE `pagos_recibidos` DISABLE KEYS */;
INSERT INTO `pagos_recibidos` VALUES (1,1,3,1,37.50,'2026-10-15 11:25:00','PAGOMOVIL-998822',1),(2,2,1,NULL,50.00,'2026-10-16 16:10:00','Abono Inicial',1);
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
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_producto_proveedor`),
  UNIQUE KEY `insumo_proveedor_unique` (`id_producto`,`id_proveedor`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `fk_insumo_proveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_proveedor_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_proveedor`
--

LOCK TABLES `producto_proveedor` WRITE;
/*!40000 ALTER TABLE `producto_proveedor` DISABLE KEYS */;
INSERT INTO `producto_proveedor` VALUES (1,1,1,1),(2,2,1,1),(3,3,1,1),(4,4,1,1),(5,5,1,1),(6,6,1,1),(7,7,1,1),(8,8,1,1),(9,9,1,1),(10,10,1,1),(11,11,1,1),(12,12,2,1),(13,13,2,1),(14,14,2,1),(15,15,3,1),(16,16,2,1);
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
  `unidad_medida` varchar(30) DEFAULT NULL,
  `stock_actual` decimal(14,7) NOT NULL DEFAULT 0.0000000,
  `stock_minimo` decimal(14,7) NOT NULL DEFAULT 5.0000000,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_productos_tipo` (`id_tipo_producto`),
  KEY `fk_productos_padre` (`id_producto_padre`),
  CONSTRAINT `fk_productos_padre` FOREIGN KEY (`id_producto_padre`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_productos_tipo` FOREIGN KEY (`id_tipo_producto`) REFERENCES `tipo_producto` (`id_tipo_producto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,1,NULL,'1','BAS-001','Tinte Concentrado Blanco Puro (Galon)','Galon',15.0000,2.0000,48.00,'2026-10-17 11:58:45',1),(2,1,NULL,'1','BAS-002','Tinte Concentrado Negro Profundo (Galon)','Galon',12.0000,2.0000,45.00,'2026-10-17 11:58:45',1),(3,1,NULL,'1','BAS-003','Tinte Concentrado Rojo Oxido (Galon)','Galon',10.0000,2.0000,52.00,'2026-10-17 11:58:45',1),(4,1,NULL,'1','BAS-004','Tinte Concentrado Azul Rey Perlado (Galon)','Galon',8.0000,2.0000,55.00,'2026-10-17 11:58:45',1),(5,1,NULL,'1','BAS-005','Tinte Concentrado Amarillo Cromo (Galon)','Galon',6.0000,2.0000,50.00,'2026-10-17 11:58:45',1),(6,1,1,'1/2','BAS-001-1/2','Tinte Concentrado Blanco Puro 1/2','1/2 Galon',8.0000,2.0000,26.00,'2026-10-17 11:58:45',1),(7,1,1,'1/4','BAS-001-1/4','Tinte Concentrado Blanco Puro 1/4','1/4 Galon',14.0000,3.0000,14.00,'2026-10-17 11:58:45',1),(8,1,2,'1/4','BAS-002-1/4','Tinte Concentrado Negro Profundo 1/4','1/4 Galon',10.0000,2.0000,13.00,'2026-10-17 11:58:45',1),(9,1,3,'1/8','BAS-003-1/8','Tinte Concentrado Rojo Oxido 1/8','1/8 Galon',12.0000,2.0000,8.00,'2026-10-17 11:58:45',1),(10,1,4,'1/8','BAS-004-1/8','Tinte Concentrado Azul Rey Perlado 1/8','1/8 Galon',9.0000,2.0000,8.50,'2026-10-17 11:58:45',1),(11,1,5,'1/16','BAS-005-1/16','Tinte Concentrado Amarillo Cromo 1/16','1/16 Galon',16.0000,4.0000,4.50,'2026-10-17 11:58:45',1),(12,2,NULL,'1','ART-001','Tirro de Enmascarar 3M 3/4 pulgada','Unidad',45.0000,10.0000,3.50,'2026-10-17 11:58:45',1),(13,2,NULL,'1','ART-002','Lija al Agua Grano 1500 Automotriz','Pliego',120.0000,20.0000,1.20,'2026-10-17 11:58:45',1),(14,2,NULL,'1','ART-003','Brocha de Cerdas Finas 2 pulgadas','Unidad',30.0000,5.0000,4.50,'2026-10-17 11:58:45',1),(15,2,NULL,'1','ART-004','Solvente Thinner Acrilico 1L','Litro',50.0000,10.0000,8.50,'2026-10-17 11:58:45',1),(16,2,NULL,'1','ART-005','Pulitura Pasta Fina 500g','Pote',20.0000,5.0000,12.00,'2026-10-17 11:58:45',1),(17,3,NULL,'1','MEZ-001','Gris Grafito Perlado Met 1/4','1/4 Galon',4.0000,1.0000,28.00,'2026-10-17 11:58:45',1);
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
INSERT INTO `proveedores` VALUES (1,'J-00012345','Pinturas Montana C.A.','Zona Industrial 2, Valencia, Edo. Carabobo','ventas@montana.com.ve','2026-10-17 11:58:45',1),(2,'J-12345678','Distribuidora Automotriz Occidental C.A.','Av. Venezuela con Calle 33, Barquisimeto','contacto@distoccidental.com','2026-10-17 11:58:45',1),(3,'J-98765432','Quimicos y Solventes Lara S.A.','Zona Industrial 3, Galpon 12, Barquisimeto','pedidos@quimicoslara.com','2026-10-17 11:58:45',1);
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
INSERT INTO `rol_modulo` VALUES (1,1,1,1),(2,1,2,1),(3,1,3,1),(4,1,4,1),(5,1,5,1),(6,1,6,1),(7,1,7,1),(8,1,8,1),(9,1,9,1),(10,1,10,1),(11,1,11,1),(12,1,12,1),(13,1,13,1),(14,1,14,1),(15,2,1,1),(16,2,2,1),(17,2,4,1),(18,2,5,1),(19,2,6,1),(20,2,7,1),(21,2,11,1);
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
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
INSERT INTO `roles` VALUES (1,'Administrador','2026-10-12 03:10:59',1),(2,'Vendedor','2026-10-12 03:10:59',1);
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
INSERT INTO `rubro` VALUES (1,'Tintes y Pigmentos',1),(2,'Quimicos y Solventes',1),(3,'Materiales y Herramientas',1);
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
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_rubro_proveedor`),
  UNIQUE KEY `proveedor_rubro_unique` (`id_proveedor`,`id_rubro`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_rubro` (`id_rubro`),
  CONSTRAINT `fk_rubro_proveedor_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rubro_proveedor_rubro` FOREIGN KEY (`id_rubro`) REFERENCES `rubro` (`id_rubro`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rubro_proveedor`
--

LOCK TABLES `rubro_proveedor` WRITE;
/*!40000 ALTER TABLE `rubro_proveedor` DISABLE KEYS */;
INSERT INTO `rubro_proveedor` VALUES (1,1,1,1),(2,1,2,1),(3,2,3,1),(4,3,2,1);
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
  UNIQUE KEY `uk_tasa_fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasa_cambio`
--

LOCK TABLES `tasa_cambio` WRITE;
/*!40000 ALTER TABLE `tasa_cambio` DISABLE KEYS */;
INSERT INTO `tasa_cambio` VALUES (1,65.5000,'2026-10-16','2026-10-16 08:00:00',1),(2,66.2000,'2026-10-17','2026-10-17 11:58:45',1);
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
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cliente`,`id_telefono_cliente`),
  UNIQUE KEY `uk_id_telefono_cliente` (`id_telefono_cliente`),
  CONSTRAINT `fk_telf_cliente_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telefono_cliente`
--

LOCK TABLES `telefono_cliente` WRITE;
/*!40000 ALTER TABLE `telefono_cliente` DISABLE KEYS */;
INSERT INTO `telefono_cliente` VALUES (1,1,'0414-5551234',1),(1,2,'0251-2334455',1),(2,3,'0424-5123456',1),(3,4,'0251-2691122',1);
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
INSERT INTO `telefono_proveedor` VALUES (1,1,'0241-8765432',1),(2,2,'0251-2319988',1),(3,3,'0251-2684455',1);
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
INSERT INTO `tipo_pago` VALUES (1,'Efectivo',1),(2,'Transferencia',1),(3,'Pago Movil',1),(4,'Tarjeta Debito',1);
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
INSERT INTO `tipo_producto` VALUES (1,'Base','Insumo / tinte primario o quimico base para mezclas de colorimetria',1),(2,'Simple','Producto de venta directa terminada (tirros, lijas, brochas, pulituras, etc.)',1),(3,'Preparado','Pintura preparada creada mediante mezcla de varios productos base',1);
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
  `id_rol` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (2,'Administrador','admin@perfectcolor.com','$2y$10$ZqUk5Awoey5CVkqx3Tgg2.A9YTTQwRHLehxDLtLN/fYLkyGrMi5HC',1,'2026-10-12 03:10:59',1),(5,'Fran','pacheco@ejemplo.com','$2y$10$ZqUk5Awoey5CVkqx3Tgg2.A9YTTQwRHLehxDLtLN/fYLkyGrMi5HC',2,'2026-10-12 03:10:59',1);
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
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_venta_detalle`),
  KEY `id_venta` (`id_venta`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `fk_vdetalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vdetalle_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_detalle`
--

LOCK TABLES `venta_detalle` WRITE;
/*!40000 ALTER TABLE `venta_detalle` DISABLE KEYS */;
INSERT INTO `venta_detalle` VALUES (1,1,17,'Gris Grafito Perlado Met 1/4',1.0000,28.00,28.00),(2,1,13,'Lija al Agua Grano 1500 Automotriz',5.0000,1.20,6.00),(3,1,17,'Gris Grafito Perlado Met 1/4',1.0000,28.00,28.00),(4,2,6,'Tinte Concentrado Blanco Puro 1/2',1.0000,26.00,26.00),(5,2,12,'Tirro de Enmascarar 3M 3/4 pulgada',2.0000,3.50,7.00),(6,2,14,'Brocha de Cerdas Finas 2 pulgadas',1.0000,4.50,4.50),(7,3,1,'Tinte Concentrado Blanco Puro (Galon)',2.0000,48.00,96.00),(8,3,15,'Solvente Thinner Acrilico 1L',5.0000,8.50,42.50),(9,3,13,'Lija al Agua Grano 1500 Automotriz',10.0000,1.20,12.00);
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
  `id_tipo_pago` int(11) DEFAULT NULL,
  `id_banco` int(11) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
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
  KEY `id_tipo_pago` (`id_tipo_pago`),
  KEY `fk_ventas_banco` (`id_banco`),
  CONSTRAINT `fk_ventas_banco` FOREIGN KEY (`id_banco`) REFERENCES `banco` (`id_banco`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_estado` FOREIGN KEY (`id_estado_venta`) REFERENCES `estado_venta` (`id_estado_venta`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_tipo_pago` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago` (`id_tipo_pago`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,1,1,NULL,NULL,NULL,'2026-10-17 09:00:00',NULL,62.00,'contado','Cotizacion para repintado de guardafango','2026-10-17 09:00:00',1),(2,2,2,3,1,'PAGOMOVIL-998822','2026-10-15 11:20:00','2026-10-15 11:25:00',37.50,'contado','Despacho cliente particular','2026-10-15 11:20:00',1),(3,3,2,1,NULL,'Abono Inicial Efectivo','2026-10-16 16:00:00','2026-10-16 16:10:00',150.50,'credito','Material para taller mecanico','2026-10-16 16:00:00',1);
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

-- Dump completed on 2026-10-17 11:59:00

-- Historial de mermas (las presentaciones de galon viven en InventarioModel::PRESENTACIONES)
CREATE TABLE IF NOT EXISTS `merma_inventario` (
  `id_merma` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_presentacion` tinyint(4) DEFAULT NULL COMMENT 'Codigo 1-8 de la presentacion perdida (1 galon ... 1/128); NULL si no se mide en galones',
  `cantidad` int(11) NOT NULL COMMENT 'Unidades perdidas de la presentacion indicada',
  `equivalente_128` int(11) DEFAULT NULL COMMENT 'Total perdido en 128avos de galon',
  `cantidad_descontada` decimal(14,7) NOT NULL COMMENT 'Valor restado de stock_actual',
  `motivo` varchar(250) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_merma`),
  KEY `fk_merma_producto` (`id_producto`),
  KEY `fk_merma_usuario` (`id_usuario`),
  CONSTRAINT `fk_merma_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_merma_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- Tasa fija por pago (se guarda al registrar el pago)
ALTER TABLE `pagos_recibidos` ADD COLUMN `tasa_bcv` decimal(12,4) DEFAULT NULL COMMENT 'Tasa Bs/$ vigente el dia del pago, fija' AFTER `monto`;
ALTER TABLE `pagos_realizados` ADD COLUMN `tasa_bcv` decimal(12,4) DEFAULT NULL COMMENT 'Tasa Bs/$ vigente el dia del pago, fija' AFTER `monto`;

-- Alerta de stock minimo silenciable en presentaciones hijas
ALTER TABLE `productos` ADD COLUMN `alerta_activa` tinyint(1) NOT NULL DEFAULT 1 AFTER `stock_minimo`;
UPDATE `productos` SET `stock_minimo` = 0 WHERE `id_tipo_producto` = 3;
