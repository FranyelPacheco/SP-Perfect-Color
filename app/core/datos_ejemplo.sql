-- ARCHIVO: datos_ejemplo.sql
-- OBJETIVO: Datos de prueba genéricos y estandarizados (datodeprueba1, etc.)
-- NOTA: No altera usuarios, roles, modulos ni tablas estructurales maestras.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Limpieza de tablas operativas
TRUNCATE TABLE `merma_inventario`;
TRUNCATE TABLE `pagos_recibidos`;
TRUNCATE TABLE `cuentas_cobrar`;
TRUNCATE TABLE `venta_detalle`;
TRUNCATE TABLE `ventas`;
TRUNCATE TABLE `pagos_realizados`;
TRUNCATE TABLE `cuentas_pagar`;
TRUNCATE TABLE `compra_detalle`;
TRUNCATE TABLE `compra`;
TRUNCATE TABLE `mezcla_producto`;
TRUNCATE TABLE `producto_proveedor`;
TRUNCATE TABLE `productos`;
TRUNCATE TABLE `rubro_proveedor`;
TRUNCATE TABLE `rubro`;
TRUNCATE TABLE `telefono_proveedor`;
TRUNCATE TABLE `proveedores`;
TRUNCATE TABLE `telefono_cliente`;
TRUNCATE TABLE `clientes`;
TRUNCATE TABLE `tasa_cambio`;
TRUNCATE TABLE `banco`;
TRUNCATE TABLE `tipo_pago`;

-- 2. Bancos de Prueba
INSERT INTO `banco` (`id_banco`, `nombre`, `created_at`, `estado`) VALUES
(1, 'Banco de Prueba 1', '2026-11-14 08:00:00', 1),
(2, 'Banco de Prueba 2', '2026-11-14 08:00:00', 1),
(3, 'Banco de Prueba 3', '2026-11-14 08:00:00', 1);

-- 3. Tipos de Pago de Prueba
INSERT INTO `tipo_pago` (`id_tipo_pago`, `nombre`, `created_at`, `estado`) VALUES
(1, 'Efectivo USD (Prueba)', '2026-11-14 08:00:00', 1),
(2, 'Transferencia (Prueba)', '2026-11-14 08:00:00', 1),
(3, 'Pago Movil (Prueba)', '2026-11-14 08:00:00', 1),
(4, 'Efectivo Bs (Prueba)', '2026-11-14 08:00:00', 1);

-- 4. Tasas de Cambio de Prueba
INSERT INTO `tasa_cambio` (`id_tasa_cambio`, `tasa`, `fecha`, `created_at`, `estado`) VALUES
(1, 50.0000, '2026-11-15', '2026-11-15 08:00:00', 1),
(2, 50.0000, '2026-11-16', '2026-11-16 08:00:00', 1),
(3, 50.0000, '2026-11-17', '2026-11-17 08:00:00', 1),
(4, 50.0000, '2026-11-18', '2026-11-18 08:00:00', 1),
(5, 50.0000, '2026-11-19', '2026-11-19 08:00:00', 1);

-- 5. Rubros de Prueba
INSERT INTO `rubro` (`id_rubro`, `nombre`, `created_at`, `estado`) VALUES
(1, 'Rubro de Prueba 1', '2026-11-14 08:30:00', 1),
(2, 'Rubro de Prueba 2', '2026-11-14 08:30:00', 1),
(3, 'Rubro de Prueba 3', '2026-11-14 08:30:00', 1);

-- 6. Proveedores de Prueba
INSERT INTO `proveedores` (`id_proveedor`, `rif`, `nombre_empresa`, `direccion`, `correo`, `created_at`, `estado`) VALUES
(1, 'J-123456780', 'Proveedor Prueba 1', 'Zona de Prueba 1', 'proveedor1@prueba.com', '2026-11-14 09:00:00', 1),
(2, 'J-234567890', 'Proveedor Prueba 2', 'Zona de Prueba 2', 'proveedor2@prueba.com', '2026-11-14 09:00:00', 1);

INSERT INTO `telefono_proveedor` (`id_proveedor`, `id_telefono_proveedor`, `telefono`, `estado`) VALUES
(1, 1, '02511234567', 1),
(2, 2, '02512345678', 1);

INSERT INTO `rubro_proveedor` (`id_proveedor`, `id_rubro`) VALUES
(1, 1),
(2, 2);

-- 7. Clientes de Prueba
INSERT INTO `clientes` (`id_cliente`, `cedula`, `nombres`, `apellidos`, `correo`, `direccion`, `created_at`, `estado`) VALUES
(1, 'V-12345678', 'Cliente', 'Prueba 1', 'cliente1@prueba.com', 'Direccion de Prueba 1', '2026-11-14 10:00:00', 1),
(2, 'V-23456789', 'Cliente', 'Prueba 2', 'cliente2@prueba.com', 'Direccion de Prueba 2', '2026-11-14 10:00:00', 1),
(3, 'J-123456789', 'Empresa', 'Prueba 3', 'cliente3@prueba.com', 'Direccion de Prueba 3', '2026-11-14 10:00:00', 1);

INSERT INTO `telefono_cliente` (`id_cliente`, `id_telefono_cliente`, `telefono`, `estado`) VALUES
(1, 1, '04121234567', 1),
(2, 2, '04142345678', 1),
(3, 3, '04243456789', 1);

-- 8. Productos de Prueba (Simples, Bases y Mezclas)
INSERT INTO `productos` (`id_producto`, `id_tipo_producto`, `id_producto_padre`, `fraccion`, `codigo`, `nombre`, `unidad_medida`, `stock_actual`, `stock_minimo`, `alerta_activa`, `precio_venta`, `created_at`, `estado`) VALUES
(1, 1, NULL, '1', 'BASE-001', 'Producto Base Prueba 1', 'Galon', 50.0000000, 5.0000000, 1, 10.00, '2026-11-14 11:00:00', 1),
(2, 1, NULL, '1', 'BASE-002', 'Producto Base Prueba 2', 'Galon', 40.0000000, 5.0000000, 1, 12.00, '2026-11-14 11:00:00', 1),
(3, 1, NULL, '1', 'BASE-003', 'Producto Base Prueba 3', 'Galon', 30.0000000, 5.0000000, 1, 15.00, '2026-11-14 11:00:00', 1),
(4, 2, NULL, '1', 'SIMP-001', 'Producto Simple Prueba 1', 'Unidad', 100.0000000, 10.0000000, 1, 5.00, '2026-11-14 11:00:00', 1),
(5, 2, NULL, '1', 'SIMP-002', 'Producto Simple Prueba 2', 'Unidad', 80.0000000, 10.0000000, 1, 8.00, '2026-11-14 11:00:00', 1),
(6, 3, NULL, '1/4', 'MEZ-001', 'Mezcla Preparada Prueba 1', '1/4 Galon', 10.0000000, 2.0000000, 1, 35.00, '2026-11-14 11:30:00', 1),
(7, 3, NULL, '1/2', 'MEZ-002', 'Mezcla Preparada Prueba 2', '1/2 Galon', 5.0000000, 2.0000000, 1, 50.00, '2026-11-14 11:30:00', 1);

INSERT INTO `producto_proveedor` (`id_producto`, `id_proveedor`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 2),
(5, 2);

-- Recetas de Mezclas
INSERT INTO `mezcla_producto` (`id_mezcla_producto`, `id_producto_mezclado`, `id_producto_base`, `cantidad_galon`, `created_at`, `estado`) VALUES
(1, 6, 1, 0.1250000, '2026-11-14 12:00:00', 1),
(2, 6, 2, 0.1250000, '2026-11-14 12:00:00', 1),
(3, 7, 2, 0.2500000, '2026-11-14 12:00:00', 1),
(4, 7, 3, 0.2500000, '2026-11-14 12:00:00', 1);

-- 9. Recepción de Compras de Prueba
INSERT INTO `compra` (`id_compra`, `id_proveedor`, `fecha`, `total`, `observaciones`, `created_at`, `estado`) VALUES
(1, 1, '2026-11-15 09:30:00', 200.00, 'Compra de prueba 1', '2026-11-15 09:30:00', 1);

INSERT INTO `compra_detalle` (`id_compra`, `id_producto`, `cantidad`, `precio_compra`) VALUES
(1, 1, 10.0000000, 10.00),
(1, 2, 8.3333333, 12.00);

INSERT INTO `cuentas_pagar` (`id_cuenta_pagar`, `id_compra`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `created_at`, `estado`) VALUES
(1, 1, 200.00, 0.00, '2026-11-15 09:30:00', '2026-11-15 09:30:00', 1);

INSERT INTO `pagos_realizados` (`id_pago_realizado`, `id_cuenta_pagar`, `id_tipo_pago`, `id_banco`, `monto`, `tasa_bcv`, `fecha`, `referencia`, `created_at`, `estado`) VALUES
(1, 1, 2, 1, 200.00, 50.0000, '2026-11-15 09:35:00', '11111', '2026-11-15 09:35:00', 1);

-- 10. Ventas y Presupuestos de Prueba
-- Venta 1: Presupuesto Pendiente ($50.00)
INSERT INTO `ventas` (`id_venta`, `id_cliente`, `id_estado_venta`, `fecha`, `fecha_aprobacion`, `total`, `condicion_pago`, `observaciones`, `created_at`, `estado`) VALUES
(1, 1, 1, '2026-11-18 10:00:00', NULL, 50.00, 'contado', 'Presupuesto de prueba 1', '2026-11-18 10:00:00', 1);

INSERT INTO `venta_detalle` (`id_venta`, `id_producto`, `descripcion`, `cantidad`, `precio_unitario`) VALUES
(1, 4, 'Producto Simple Prueba 1', 10.0000, 5.00);

-- Venta 2: Aprobada / Contado / Pagada ($100.00)
INSERT INTO `ventas` (`id_venta`, `id_cliente`, `id_estado_venta`, `fecha`, `fecha_aprobacion`, `total`, `condicion_pago`, `observaciones`, `created_at`, `estado`) VALUES
(2, 2, 2, '2026-11-18 11:30:00', '2026-11-18 11:35:00', 100.00, 'contado', 'Venta aprobada de prueba 2', '2026-11-18 11:30:00', 1);

INSERT INTO `venta_detalle` (`id_venta`, `id_producto`, `descripcion`, `cantidad`, `precio_unitario`) VALUES
(2, 6, 'Mezcla Preparada Prueba 1', 2.0000, 35.00),
(2, 5, 'Producto Simple Prueba 2', 3.7500, 8.00);

INSERT INTO `cuentas_cobrar` (`id_cuenta_cobrar`, `id_venta`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `created_at`, `estado`) VALUES
(1, 2, 100.00, 0.00, '2026-11-18 11:35:00', '2026-11-18 11:35:00', 1);

INSERT INTO `pagos_recibidos` (`id_pago_recibido`, `id_cuenta_cobrar`, `id_tipo_pago`, `id_banco`, `monto`, `tasa_bcv`, `fecha`, `referencia`, `created_at`, `estado`) VALUES
(1, 1, 3, 1, 100.00, 50.0000, '2026-11-18 11:40:00', '22222', '2026-11-18 11:40:00', 1);

-- Venta 3: Aprobada / Credito / Con Saldo ($150.00, pagado $100.00, resta $50.00)
INSERT INTO `ventas` (`id_venta`, `id_cliente`, `id_estado_venta`, `fecha`, `fecha_aprobacion`, `total`, `condicion_pago`, `observaciones`, `created_at`, `estado`) VALUES
(3, 3, 2, '2026-11-19 09:00:00', '2026-11-19 09:15:00', 150.00, 'credito', 'Venta a credito de prueba 3', '2026-11-19 09:00:00', 1);

INSERT INTO `venta_detalle` (`id_venta`, `id_producto`, `descripcion`, `cantidad`, `precio_unitario`) VALUES
(3, 7, 'Mezcla Preparada Prueba 2', 3.0000, 50.00);

INSERT INTO `cuentas_cobrar` (`id_cuenta_cobrar`, `id_venta`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `created_at`, `estado`) VALUES
(2, 3, 150.00, 50.00, '2026-12-05 09:15:00', '2026-11-19 09:15:00', 1);

INSERT INTO `pagos_recibidos` (`id_pago_recibido`, `id_cuenta_cobrar`, `id_tipo_pago`, `id_banco`, `monto`, `tasa_bcv`, `fecha`, `referencia`, `created_at`, `estado`) VALUES
(2, 2, 2, 2, 100.00, 50.0000, '2026-11-19 09:30:00', '33333', '2026-11-19 09:30:00', 1);

SET FOREIGN_KEY_CHECKS = 1;
