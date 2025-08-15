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