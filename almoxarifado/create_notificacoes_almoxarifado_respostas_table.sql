CREATE TABLE `notificacoes_almoxarifado_respostas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacao_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `justificativa` text NOT NULL,
  `data_resposta` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notificacao_id` (`notificacao_id`),
  KEY `item_id` (`item_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_respostas_almoxarifado_notificacao` FOREIGN KEY (`notificacao_id`) REFERENCES `notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_respostas_almoxarifado_item` FOREIGN KEY (`item_id`) REFERENCES `almoxarifado_materiais` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_respostas_almoxarifado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
