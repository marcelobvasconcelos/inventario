-- Estrutura para tabela `almoxarifado_materiais`
CREATE TABLE `almoxarifado_materiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `unidade_medida` varchar(20) NOT NULL,
  `estoque_minimo` decimal(10,2) DEFAULT 0.00,
  `estoque_atual` decimal(10,2) DEFAULT 0.00,
  `valor_unitario` decimal(10,2) DEFAULT 0.00,
  `categoria` varchar(100) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura para tabela `almoxarifado_entradas`
CREATE TABLE `almoxarifado_entradas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `fornecedor` varchar(150) DEFAULT NULL,
  `nota_fiscal` varchar(50) DEFAULT NULL,
  `data_entrada` date NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `almoxarifado_entradas_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`),
  CONSTRAINT `almoxarifado_entradas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura para tabela `almoxarifado_saidas`
CREATE TABLE `almoxarifado_saidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `setor_destino` varchar(100) DEFAULT NULL,
  `responsavel_saida` varchar(150) DEFAULT NULL,
  `data_saida` date NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `almoxarifado_saidas_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`),
  CONSTRAINT `almoxarifado_saidas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura para tabela `almoxarifado_movimentacoes`
CREATE TABLE `almoxarifado_movimentacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `saldo_anterior` decimal(10,2) NOT NULL,
  `saldo_atual` decimal(10,2) NOT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `almoxarifado_movimentacoes_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`),
  CONSTRAINT `almoxarifado_movimentacoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura para tabela `almoxarifado_produtos`
CREATE TABLE `almoxarifado_produtos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(255) NOT NULL,
    `descricao` text,
    `unidade_medida` varchar(50),
    `estoque_atual` int(11) DEFAULT 0,
    `estoque_minimo` int(11) DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura para tabela `almoxarifado_requisicoes`
CREATE TABLE `almoxarifado_requisicoes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) NOT NULL,
    `local_id` int(11),
    `data_requisicao` datetime NOT NULL,
    `status` enum('pendente','aprovada','rejeitada','concluida') DEFAULT 'pendente',
    `justificativa` text,
    PRIMARY KEY (`id`),
    KEY `usuario_id` (`usuario_id`),
    KEY `local_id` (`local_id`),
    CONSTRAINT `almoxarifado_requisicoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `almoxarifado_requisicoes_ibfk_2` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura para tabela `almoxarifado_requisicoes_itens`
CREATE TABLE `almoxarifado_requisicoes_itens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `requisicao_id` int(11) NOT NULL,
    `produto_id` int(11) NOT NULL,
    `quantidade_solicitada` int(11) NOT NULL,
    `quantidade_entregue` int(11) DEFAULT 0,
    `observacao` text,
    PRIMARY KEY (`id`),
    KEY `requisicao_id` (`requisicao_id`),
    KEY `produto_id` (`produto_id`),
    CONSTRAINT `almoxarifado_requisicoes_itens_ibfk_1` FOREIGN KEY (`requisicao_id`) REFERENCES `almoxarifado_requisicoes` (`id`),
    CONSTRAINT `almoxarifado_requisicoes_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `almoxarifado_produtos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estrutura para tabela `almoxarifado_requisicoes_notificacoes`
CREATE TABLE `almoxarifado_requisicoes_notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisicao_id` int(11) NOT NULL,
  `usuario_origem_id` int(11) NOT NULL,
  `usuario_destino_id` int(11) NOT NULL,
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

-- Estrutura para tabela `almoxarifado_requisicoes_conversas`
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

-- Estrutura para tabela `almoxarifado_agendamentos`
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