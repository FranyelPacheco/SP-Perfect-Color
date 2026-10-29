ALTER TABLE `pagos_recibidos` ADD COLUMN `tasa_bcv` decimal(12,4) DEFAULT NULL COMMENT 'Tasa Bs/$ vigente el dia del pago, fija' AFTER `monto`;
ALTER TABLE `pagos_realizados` ADD COLUMN `tasa_bcv` decimal(12,4) DEFAULT NULL COMMENT 'Tasa Bs/$ vigente el dia del pago, fija' AFTER `monto`;

UPDATE `pagos_recibidos` p SET p.tasa_bcv = (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(p.fecha) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1) WHERE p.tasa_bcv IS NULL;
UPDATE `pagos_realizados` p SET p.tasa_bcv = (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(p.fecha) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1) WHERE p.tasa_bcv IS NULL;
