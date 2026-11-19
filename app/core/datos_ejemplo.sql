-- ARCHIVO: datos_ejemplo.sql
-- OBJETIVO: Reemplazar datos de prueba por datos de ejemplo profesionales y realistas para SP Perfect Color (Barquisimeto, Venezuela)
-- NOTA: NO modifica usuarios, roles, modulos, rol_modulo, tipo_producto ni estado_venta.

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

-- 2. Bancos
INSERT INTO `banco` (`id_banco`, `nombre`, `created_at`, `estado`) VALUES
(1, 'Banesco Banco Universal', '2026-11-14 08:00:00', 1),
(2, 'Banco Mercantil', '2026-11-14 08:00:00', 1),
(3, 'BBVA Provincial', '2026-11-14 08:00:00', 1),
(4, 'Banco de Venezuela', '2026-11-14 08:00:00', 1),
(5, 'Bancamiga Banco Universal', '2026-11-14 08:00:00', 1);

-- 3. Tipos de Pago
INSERT INTO `tipo_pago` (`id_tipo_pago`, `nombre`, `created_at`, `estado`) VALUES
(1, 'Efectivo ($ USD)', '2026-11-14 08:00:00', 1),
(2, 'Transferencia Bancaria', '2026-11-14 08:00:00', 1),
(3, 'Pago Móvil', '2026-11-14 08:00:00', 1),
(4, 'Efectivo (Bs.)', '2026-11-14 08:00:00', 1);

-- 4. Tasas de Cambio Recientes
INSERT INTO `tasa_cambio` (`id_tasa_cambio`, `tasa`, `fecha`, `created_at`, `estado`) VALUES
(1, 45.8000, '2026-11-14', '2026-11-14 08:00:00', 1),
(2, 45.9500, '2026-11-15', '2026-11-15 08:00:00', 1),
(3, 46.2000, '2026-11-16', '2026-11-16 08:00:00', 1),
(4, 46.5000, '2026-11-17', '2026-11-17 08:00:00', 1),
(5, 46.8500, '2026-11-18', '2026-11-18 08:00:00', 1),
(6, 47.1500, '2026-11-19', '2026-11-19 08:00:00', 1);

-- 5. Rubros
INSERT INTO `rubro` (`id_rubro`, `nombre`, `created_at`, `estado`) VALUES
(1, 'Pinturas y Colorimetría Automotriz', '2026-11-14 08:30:00', 1),
(2, 'Solventes y Diluyentes Industriales', '2026-11-14 08:30:00', 1),
(3, 'Abrasivos, Masillas y Accesorios', '2026-11-14 08:30:00', 1);

-- 6. Proveedores
INSERT INTO `proveedores` (`id_proveedor`, `rif`, `nombre_empresa`, `direccion`, `correo`, `created_at`, `estado`) VALUES
(1, 'J-301258941', 'Pinturas Montana C.A.', 'Zona Industrial II, Av. Circunvalación, Barquisimeto, Edo. Lara', 'contacto@montana.com.ve', '2026-11-14 09:00:00', 1),
(2, 'J-314896253', 'Químicos y Solventes del Centro S.A.', 'Av. Florencio Jiménez, Km 4, Barquisimeto, Edo. Lara', 'ventas@quimicoscentro.com.ve', '2026-11-14 09:15:00', 1),
(3, 'J-408962314', 'Distribuidora de Abrasivos 3M Lara C.A.', 'Carrera 19 entre Calles 22 y 23, Barquisimeto, Edo. Lara', 'pedidos@abrasivoslara.com.ve', '2026-11-14 09:30:00', 1);

INSERT INTO `telefono_proveedor` (`id_proveedor`, `id_telefono_proveedor`, `telefono`, `created_at`, `estado`) VALUES
(1, 1, '04145123456', '2026-11-14 09:00:00', 1),
(2, 2, '04127896541', '2026-11-14 09:15:00', 1),
(3, 3, '04245412389', '2026-11-14 09:30:00', 1);

INSERT INTO `rubro_proveedor` (`id_rubro_proveedor`, `id_proveedor`, `id_rubro`, `created_at`, `estado`) VALUES
(1, 1, 1, '2026-11-14 09:00:00', 1),
(2, 2, 2, '2026-11-14 09:15:00', 1),
(3, 3, 3, '2026-11-14 09:30:00', 1);

-- 7. Clientes
INSERT INTO `clientes` (`id_cliente`, `cedula`, `nombres`, `apellidos`, `correo`, `direccion`, `created_at`, `estado`) VALUES
(1, 'V-15487962', 'Carlos Alberto', 'Mendoza Rodríguez', 'carlos.mendoza@gmail.com', 'Urb. Las Trinitarias, Manzana E, Barquisimeto', '2026-11-14 10:00:00', 1),
(2, 'V-18965234', 'María Elena', 'Guédez Salcedo', 'maria.guedez@hotmail.com', 'Av. Los Leones con Av. Lara, Torre Bell, Barquisimeto', '2026-11-14 10:15:00', 1),
(3, 'V-12874561', 'José Gregorio', 'Hernández Lucena', 'taller.hernandez@gmail.com', 'Barrio Unión, Calle 15 con Carrera 2, Taller Chapa y Pintura, Barquisimeto', '2026-11-14 10:30:00', 1),
(4, 'J-401589632', 'Auto Sport Tuning', 'C.A.', 'administracion@autosporttuning.com', 'Zona Industrial I, Galpón 4-B, Barquisimeto', '2026-11-14 10:45:00', 1),
(5, 'V-21456987', 'Alejandro José', 'Pérez Alvarado', 'alejandro.perez@gmail.com', 'Cabudare Centro, Calle San Rafael, Edo. Lara', '2026-11-14 11:00:00', 1);

INSERT INTO `telefono_cliente` (`id_cliente`, `id_telefono_cliente`, `telefono`, `created_at`, `estado`) VALUES
(1, 1, '04123456789', '2026-11-14 10:00:00', 1),
(2, 2, '04149876543', '2026-11-14 10:15:00', 1),
(3, 3, '04245128963', '2026-11-14 10:30:00', 1),
(4, 4, '02512378945', '2026-11-14 10:45:00', 1),
(5, 5, '04166541238', '2026-11-14 11:00:00', 1);

-- 8. Productos
INSERT INTO `productos` (`id_producto`, `id_tipo_producto`, `id_producto_padre`, `fraccion`, `codigo`, `nombre`, `unidad_medida`, `stock_actual`, `stock_minimo`, `alerta_activa`, `precio_venta`, `created_at`, `estado`) VALUES
(1, 1, NULL, '1', 'TINT-001', 'Tinte Base Negro Concentrado', 'Galon', 18.5000000, 5.0000000, 1, 35.00, '2026-11-14 11:30:00', 1),
(2, 1, NULL, '1', 'TINT-002', 'Tinte Base Blanco Puro', 'Galon', 24.0000000, 5.0000000, 1, 35.00, '2026-11-14 11:30:00', 1),
(3, 1, NULL, '1', 'TINT-003', 'Tinte Base Azul Perlado', 'Galon', 14.2500000, 4.0000000, 1, 42.00, '2026-11-14 11:30:00', 1),
(4, 1, NULL, '1', 'TINT-004', 'Tinte Base Rojo Escarlata', 'Galon', 12.0000000, 4.0000000, 1, 45.00, '2026-11-14 11:30:00', 1),
(5, 1, NULL, '1', 'TINT-005', 'Tinte Base Amarillo Óxido', 'Galon', 10.0000000, 3.0000000, 1, 38.00, '2026-11-14 11:30:00', 1),
(6, 1, NULL, '1', 'TINT-006', 'Tinte Base Plata Aluminio Fino', 'Galon', 16.7500000, 5.0000000, 1, 48.00, '2026-11-14 11:30:00', 1),
(7, 2, NULL, '1', 'THIN-001', 'Thinner Acrílico Limpiador 1 Galón', 'Unidad', 45.0000000, 10.0000000, 1, 18.00, '2026-11-14 11:30:00', 1),
(8, 2, NULL, '1', 'FON-001', 'Fondo 2K Gris Primer Rellenador', 'Unidad', 28.0000000, 8.0000000, 1, 26.50, '2026-11-14 11:30:00', 1),
(9, 2, NULL, '1', 'MAS-001', 'Masilla Plástica Poliéster 1KG', 'KG', 34.0000000, 10.0000000, 1, 13.00, '2026-11-14 11:30:00', 1),
(10, 2, NULL, '1', 'LIJ-001', 'Lija de Agua Grano 400 3M', 'Unidad', 180.0000000, 50.0000000, 1, 1.20, '2026-11-14 11:30:00', 1),
(11, 2, NULL, '1', 'LIJ-002', 'Lija de Agua Grano 1200 3M', 'Unidad', 140.0000000, 40.0000000, 1, 1.40, '2026-11-14 11:30:00', 1),
(12, 2, NULL, '1', 'TIR-001', 'Tirro de Papel Crepe 3/4" Automotriz', 'Unidad', 95.0000000, 25.0000000, 1, 2.20, '2026-11-14 11:30:00', 1),
(13, 2, NULL, '1', 'BAR-001', 'Barniz Poliuretano Transparente Kit 1L', 'Unidad', 22.0000000, 6.0000000, 1, 22.00, '2026-11-14 11:30:00', 1),
(14, 3, NULL, '1/4', 'MEZC-001', 'Azul Real Metalizado Toyota (1/4 Galón)', 'Galon', 2.0000000, 0.0000000, 1, 28.00, '2026-11-14 12:00:00', 1),
(15, 3, NULL, '1/2', 'MEZC-002', 'Gris Plata Lunar Chevrolet (1/2 Galón)', 'Galon', 1.0000000, 0.0000000, 1, 48.00, '2026-11-14 12:00:00', 1);

INSERT INTO `mezcla_producto` (`id_mezcla_producto`, `id_producto_mezclado`, `id_producto_base`, `cantidad_galon`, `created_at`, `estado`) VALUES
(1, 14, 3, 0.1250000, '2026-11-14 12:00:00', 1),
(2, 14, 6, 0.0937500, '2026-11-14 12:00:00', 1),
(3, 14, 1, 0.0312500, '2026-11-14 12:00:00', 1),
(4, 15, 6, 0.3750000, '2026-11-14 12:00:00', 1),
(5, 15, 1, 0.0625000, '2026-11-14 12:00:00', 1),
(6, 15, 3, 0.0625000, '2026-11-14 12:00:00', 1);

INSERT INTO `producto_proveedor` (`id_producto_proveedor`, `id_producto`, `id_proveedor`, `created_at`, `estado`) VALUES
(1, 1, 1, '2026-11-14 12:10:00', 1),
(2, 2, 1, '2026-11-14 12:10:00', 1),
(3, 3, 1, '2026-11-14 12:10:00', 1),
(4, 4, 1, '2026-11-14 12:10:00', 1),
(5, 5, 1, '2026-11-14 12:10:00', 1),
(6, 6, 1, '2026-11-14 12:10:00', 1),
(7, 7, 2, '2026-11-14 12:10:00', 1),
(8, 8, 2, '2026-11-14 12:10:00', 1),
(9, 13, 2, '2026-11-14 12:10:00', 1),
(10, 9, 3, '2026-11-14 12:10:00', 1),
(11, 10, 3, '2026-11-14 12:10:00', 1),
(12, 11, 3, '2026-11-14 12:10:00', 1),
(13, 12, 3, '2026-11-14 12:10:00', 1);

-- 9. Compras e Insumos Recibidos
INSERT INTO `compra` (`id_compra`, `id_proveedor`, `fecha`, `total`, `observaciones`, `created_at`, `estado`) VALUES
(1, 1, '2026-11-15 09:30:00', 270.00, 'Recepción de tintes base para formulación de colores automotrices', '2026-11-15 09:30:00', 1),
(2, 2, '2026-11-16 11:15:00', 360.00, 'Despacho de thinners acrílicos y barnices poliuretano (Crédito a 15 días)', '2026-11-16 11:15:00', 1),
(3, 3, '2026-11-17 14:00:00', 185.00, 'Reposición de abrasivos 3M, tirros automotrices y masilla', '2026-11-17 14:00:00', 1);

INSERT INTO `compra_detalle` (`id_compra_detalle`, `id_compra`, `id_producto`, `cantidad`, `precio_compra`, `created_at`, `estado`) VALUES
(1, 1, 1, 5.0000, 25.00, '2026-11-15 09:30:00', 1),
(2, 1, 2, 5.0000, 25.00, '2026-11-15 09:30:00', 1),
(3, 1, 3, 1.0000, 20.00, '2026-11-15 09:30:00', 1),
(4, 2, 7, 20.0000, 12.00, '2026-11-16 11:15:00', 1),
(5, 2, 13, 8.0000, 15.00, '2026-11-16 11:15:00', 1),
(6, 3, 9, 10.0000, 8.50, '2026-11-17 14:00:00', 1),
(7, 3, 10, 100.0000, 0.60, '2026-11-17 14:00:00', 1),
(8, 3, 12, 36.0000, 1.11, '2026-11-17 14:00:00', 1);

INSERT INTO `cuentas_pagar` (`id_cuenta_pagar`, `id_compra`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `created_at`, `estado`) VALUES
(1, 1, 270.00, 0.00, '2026-11-15 23:59:59', '2026-11-15 09:30:00', 1),
(2, 2, 360.00, 160.00, '2026-11-30 23:59:59', '2026-11-16 11:15:00', 1),
(3, 3, 185.00, 0.00, '2026-11-17 23:59:59', '2026-11-17 14:00:00', 1);

INSERT INTO `pagos_realizados` (`id_pago_realizado`, `id_cuenta_pagar`, `id_tipo_pago`, `id_banco`, `monto`, `tasa_bcv`, `fecha`, `referencia`, `created_at`, `estado`) VALUES
(1, 1, 2, 1, 270.00, 45.9500, '2026-11-15 10:00:00', '78412', '2026-11-15 10:00:00', 1),
(2, 2, 3, 2, 200.00, 46.2000, '2026-11-16 11:30:00', '35961', '2026-11-16 11:30:00', 1),
(3, 3, 1, NULL, 185.00, 46.5000, '2026-11-17 14:15:00', 'Efectivo', '2026-11-17 14:15:00', 1);

-- 10. Ventas y Presupuestos
INSERT INTO `ventas` (`id_venta`, `id_cliente`, `id_estado_venta`, `fecha`, `fecha_aprobacion`, `total`, `condicion_pago`, `observaciones`, `created_at`, `estado`) VALUES
(1, 1, 1, '2026-11-18 10:20:00', NULL, 58.70, 'contado', 'Presupuesto cotizado para pintar capot y guardafango de Optra', '2026-11-18 10:20:00', 1),
(2, 3, 2, '2026-11-17 09:10:00', '2026-11-17 09:15:00', 83.90, 'contado', 'Materiales despachados a Taller Hernández para Toyota Corolla', '2026-11-17 09:10:00', 1),
(3, 4, 2, '2026-11-18 14:00:00', '2026-11-18 14:10:00', 210.00, 'credito', 'Lote de pinturas preparadas y barnices a crédito comercial (15 días)', '2026-11-18 14:00:00', 1),
(4, 2, 2, '2026-11-19 11:30:00', '2026-11-19 11:35:00', 43.20, 'contado', 'Pintura preparada Azul Toyota y consumibles de lijado en mostrador', '2026-11-19 11:30:00', 1);

INSERT INTO `venta_detalle` (`id_venta_detalle`, `id_venta`, `id_producto`, `descripcion`, `cantidad`, `precio_unitario`, `created_at`) VALUES
(1, 1, 9, 'Masilla Plástica Poliéster 1KG', 1.0000, 13.00, '2026-11-18 10:20:00'),
(2, 1, 8, 'Fondo 2K Gris Primer Rellenador', 1.0000, 26.50, '2026-11-18 10:20:00'),
(3, 1, 10, 'Lija de Agua Grano 400 3M', 10.0000, 1.20, '2026-11-18 10:20:00'),
(4, 1, 12, 'Tirro de Papel Crepe 3/4" Automotriz', 3.0000, 2.40, '2026-11-18 10:20:00'),
(5, 2, 14, 'Azul Real Metalizado Toyota (1/4 Galón)', 1.0000, 28.00, '2026-11-17 09:10:00'),
(6, 2, 8, 'Fondo 2K Gris Primer Rellenador', 1.0000, 26.50, '2026-11-17 09:10:00'),
(7, 2, 7, 'Thinner Acrílico Limpiador 1 Galón', 1.0000, 18.00, '2026-11-17 09:10:00'),
(8, 2, 11, 'Lija de Agua Grano 1200 3M', 8.0000, 1.4250, '2026-11-17 09:10:00'),
(9, 3, 15, 'Gris Plata Lunar Chevrolet (1/2 Galón)', 2.0000, 48.00, '2026-11-18 14:00:00'),
(10, 3, 13, 'Barniz Poliuretano Transparente Kit 1L', 3.0000, 22.00, '2026-11-18 14:00:00'),
(11, 3, 7, 'Thinner Acrílico Limpiador 1 Galón', 2.0000, 18.00, '2026-11-18 14:00:00'),
(12, 3, 12, 'Tirro de Papel Crepe 3/4" Automotriz', 5.0000, 2.40, '2026-11-18 14:00:00'),
(13, 4, 14, 'Azul Real Metalizado Toyota (1/4 Galón)', 1.0000, 28.00, '2026-11-19 11:30:00'),
(14, 4, 10, 'Lija de Agua Grano 400 3M', 5.0000, 1.20, '2026-11-19 11:30:00'),
(15, 4, 11, 'Lija de Agua Grano 1200 3M', 5.0000, 1.40, '2026-11-19 11:30:00'),
(16, 4, 12, 'Tirro de Papel Crepe 3/4" Automotriz', 1.0000, 2.20, '2026-11-19 11:30:00');

INSERT INTO `cuentas_cobrar` (`id_cuenta_cobrar`, `id_venta`, `monto_total`, `saldo_pendiente`, `fecha_vencimiento`, `created_at`, `estado`) VALUES
(1, 2, 83.90, 0.00, '2026-11-17 23:59:59', '2026-11-17 09:15:00', 1),
(2, 3, 210.00, 110.00, '2026-12-03 23:59:59', '2026-11-18 14:10:00', 1),
(3, 4, 43.20, 0.00, '2026-11-19 23:59:59', '2026-11-19 11:35:00', 1);

INSERT INTO `pagos_recibidos` (`id_pago_recibido`, `id_cuenta_cobrar`, `id_tipo_pago`, `id_banco`, `monto`, `tasa_bcv`, `fecha`, `referencia`, `created_at`, `estado`) VALUES
(1, 1, 2, 2, 83.90, 46.5000, '2026-11-17 09:30:00', '45812', '2026-11-17 09:30:00', 1),
(2, 2, 3, 1, 100.00, 46.8500, '2026-11-18 14:15:00', '96325', '2026-11-18 14:15:00', 1),
(3, 3, 1, NULL, 43.20, 47.1500, '2026-11-19 11:40:00', 'Efectivo', '2026-11-19 11:40:00', 1);

-- 11. Mermas de Inventario
INSERT INTO `merma_inventario` (`id_merma`, `id_producto`, `id_presentacion`, `cantidad`, `motivo`, `created_at`) VALUES
(1, 7, NULL, 1, 'Envase perforado accidentalmente durante descarga y estibado en almacén', '2026-11-18 09:00:00');

SET FOREIGN_KEY_CHECKS = 1;
