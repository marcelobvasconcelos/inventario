
ALTER TABLE `almoxarifado_materiais`
ADD COLUMN `local_id` INT(11) NULL AFTER `status`,
ADD COLUMN `responsavel_id` INT(11) NULL AFTER `local_id`,
ADD COLUMN `estado` VARCHAR(50) NULL DEFAULT 'Novo' AFTER `responsavel_id`,
ADD COLUMN `status_confirmacao` VARCHAR(50) NOT NULL DEFAULT 'Pendente' AFTER `estado`;

ALTER TABLE `almoxarifado_materiais`
ADD CONSTRAINT `fk_almoxarifado_local`
  FOREIGN KEY (`local_id`)
  REFERENCES `locais` (`id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE,
ADD CONSTRAINT `fk_almoxarifado_responsavel`
  FOREIGN KEY (`responsavel_id`)
  REFERENCES `usuarios` (`id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;
  
ALTER TABLE `almoxarifado_materiais`
   3 ADD COLUMN `quantidade_maxima_requisicao` INT(11) DEFAULT NULL;
