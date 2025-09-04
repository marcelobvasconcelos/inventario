-- Tabela para notificações de requisições de almoxarifado
CREATE TABLE `almoxarifado_requisicoes_notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisicao_id` int(11) NOT NULL,
  `usuario_origem_id` int(11) NOT NULL, -- Quem criou/enviou a notificação
  `usuario_destino_id` int(11) NOT NULL, -- Para quem é a notificação
  `tipo` enum('nova_requisicao','resposta_admin','resposta_usuario','aprovada','rejeitada','agendamento') NOT NULL,
  `mensagem` text NOT NULL,
  `status` enum('pendente','lida','respondida','concluida') NOT NULL DEFAULT 'pendente',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_leitura` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requisicao_id` (`requisicao_id`),
  KEY `usuario_origem_id` (`usuario_origem_id`),
  KEY `usuario_destino_id` (`usuario_destino_id`),
  CONSTRAINT `fk_notif_req_requisicao` FOREIGN KEY (`requisicao_id`) REFERENCES `almoxarifado_requisicoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_req_usuario_origem` FOREIGN KEY (`usuario_origem_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notif_req_usuario_destino` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela para histórico de conversas nas requisições
CREATE TABLE `almoxarifado_requisicoes_conversas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo_usuario` enum('requisitante','administrador') NOT NULL,
  `data_mensagem` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notificacao_id` (`notificacao_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_conv_notif_req` FOREIGN KEY (`notificacao_id`) REFERENCES `almoxarifado_requisicoes_notificacoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_conv_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela para agendamentos de entrega
CREATE TABLE `almoxarifado_agendamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisicao_id` int(11) NOT NULL,
  `data_agendamento` datetime NOT NULL,
  `observacoes` text,
  `status` enum('agendado','concluido','cancelado') NOT NULL DEFAULT 'agendado',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requisicao_id` (`requisicao_id`),
  CONSTRAINT `fk_agendamento_requisicao` FOREIGN KEY (`requisicao_id`) REFERENCES `almoxarifado_requisicoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adicionar coluna de status de notificação à tabela de requisições
ALTER TABLE `almoxarifado_requisicoes` 
ADD COLUMN `status_notificacao` enum('pendente','em_discussao','aprovada','rejeitada','agendada','concluida') NOT NULL DEFAULT 'pendente';