CREATE TABLE `notificacoes_almoxarifado_detalhes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `status_item` varchar(50) NOT NULL DEFAULT 'Pendente',
  PRIMARY KEY (`id`),
  KEY `notificacao_id` (`notificacao_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `fk_notificacoes_almoxarifado_detalhes_notificacao` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacoes_almoxarifado_detalhes_item` FOREIGN KEY (`item_id`) REFERENCES `almoxarifado_materiais` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
