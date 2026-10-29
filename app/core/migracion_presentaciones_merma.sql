ALTER TABLE `productos`
  MODIFY `stock_actual` decimal(14,7) NOT NULL DEFAULT 0.0000000,
  MODIFY `stock_minimo` decimal(14,7) NOT NULL DEFAULT 5.0000000;

ALTER TABLE `mezcla_producto`
  MODIFY `cantidad_galon` decimal(14,7) NOT NULL COMMENT 'Equivalente decimal descontado del stock base';

CREATE TABLE IF NOT EXISTS `presentacion` (
  `id_presentacion` tinyint(4) NOT NULL,
  `etiqueta` varchar(15) NOT NULL,
  `factor_128` smallint(6) NOT NULL COMMENT 'Equivalencia en 128avos de galon',
  PRIMARY KEY (`id_presentacion`),
  UNIQUE KEY `etiqueta` (`etiqueta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

INSERT IGNORE INTO `presentacion` (`id_presentacion`, `etiqueta`, `factor_128`) VALUES
  (1, '1 Galón', 128),
  (2, '1/2 Galón', 64),
  (3, '1/4 Galón', 32),
  (4, '1/8 Galón', 16),
  (5, '1/16 Galón', 8),
  (6, '1/32 Galón', 4),
  (7, '1/64 Galón', 2),
  (8, '1/128 Galón', 1);

CREATE TABLE IF NOT EXISTS `merma_inventario` (
  `id_merma` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_presentacion` tinyint(4) DEFAULT NULL COMMENT 'Presentacion perdida; NULL en productos que no se miden en galones',
  `cantidad` int(11) NOT NULL COMMENT 'Unidades perdidas de la presentacion indicada',
  `equivalente_128` int(11) DEFAULT NULL COMMENT 'Total perdido en 128avos de galon',
  `cantidad_descontada` decimal(14,7) NOT NULL COMMENT 'Valor restado de stock_actual',
  `motivo` varchar(250) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_merma`),
  KEY `fk_merma_producto` (`id_producto`),
  KEY `fk_merma_presentacion` (`id_presentacion`),
  KEY `fk_merma_usuario` (`id_usuario`),
  CONSTRAINT `fk_merma_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_merma_presentacion` FOREIGN KEY (`id_presentacion`) REFERENCES `presentacion` (`id_presentacion`) ON UPDATE CASCADE,
  CONSTRAINT `fk_merma_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
