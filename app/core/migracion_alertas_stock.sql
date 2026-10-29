ALTER TABLE `productos` ADD COLUMN `alerta_activa` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Solo las presentaciones hijas pueden silenciar la alerta de stock minimo' AFTER `stock_minimo`;
UPDATE `productos` SET `stock_minimo` = 0 WHERE `id_tipo_producto` = 3;
