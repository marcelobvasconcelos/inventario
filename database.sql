-- Base de dados: `inventario_db`
--

CREATE DATABASE IF NOT EXISTS `inventario_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `inventario_db`;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `permissao` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Inserindo um usuário administrador padrão
--

INSERT INTO `usuarios` (`nome`, `email`, `senha`, `permissao`) VALUES
('Admin', 'admin@example.com', '$2y$10$g.g.j.Z.p.Z.p.Z.p.Z.p.Z.p.Z.p.Z.p.Z.p.Z.p.Z.p.Z.p.Z.p.Z', 'admin'); -- Senha padrão: admin123

-- --------------------------------------------------------

--
-- Estrutura da tabela `locais`
--

CREATE TABLE `locais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `itens`
--

CREATE TABLE `itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `patrimonio_novo` varchar(50) NOT NULL,
  `patrimonio_secundario` varchar(50) DEFAULT NULL,
  `local_id` int(11) NOT NULL,
  `responsavel_id` int(11) NOT NULL,
  `estado` enum('Bom','Razoável','Inservível') NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_anterior_id` int(11) DEFAULT NULL,
  `empenho` varchar(100) DEFAULT NULL,
  `data_emissao_empenho` date DEFAULT NULL,
  `fornecedor` varchar(150) DEFAULT NULL,
  `cnpj_fornecedor` varchar(20) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `valor_nf` decimal(10,2) DEFAULT NULL,
  `nd_nota_despesa` varchar(100) DEFAULT NULL,
  `unidade_medida` varchar(50) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patrimonio_novo` (`patrimonio_novo`),
  KEY `local_id` (`local_id`),
  KEY `responsavel_id` (`responsavel_id`),
  KEY `usuario_anterior_id` (`usuario_anterior_id`),
  CONSTRAINT `itens_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`),
  CONSTRAINT `itens_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `itens_ibfk_3` FOREIGN KEY (`usuario_anterior_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `movimentacoes`
--

CREATE TABLE `movimentacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `local_origem_id` int(11) NOT NULL,
  `local_destino_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `local_origem_id` (`local_origem_id`),
  KEY `local_destino_id` (`local_destino_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`),
  CONSTRAINT `movimentacoes_ibfk_2` FOREIGN KEY (`local_origem_id`) REFERENCES `locais` (`id`),
  CONSTRAINT `movimentacoes_ibfk_3` FOREIGN KEY (`local_destino_id`) REFERENCES `locais` (`id`),
  CONSTRAINT `movimentacoes_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `notificacoes_movimentacao`
--

CREATE TABLE `notificacoes_movimentacao` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `movimentacao_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `usuario_notificado_id` INT(11) NOT NULL, -- O usuário que precisa confirmar/rejeitar
  `status_confirmacao` ENUM('pendente', 'confirmado', 'rejeitado', 'replicado') NOT NULL DEFAULT 'pendente',
  `justificativa_usuario` TEXT DEFAULT NULL, -- Justificativa do usuário para rejeição
  `resposta_admin` TEXT DEFAULT NULL, -- Resposta do administrador à justificativa do usuário
  `data_notificacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `data_atualizacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `movimentacao_id` (`movimentacao_id`),
  KEY `item_id` (`item_id`),
  KEY `usuario_notificado_id` (`usuario_notificado_id`),
  CONSTRAINT `notificacoes_movimentacao_ibfk_1` FOREIGN KEY (`movimentacao_id`) REFERENCES `movimentacoes` (`id`),
  CONSTRAINT `notificacoes_movimentacao_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`),
  CONSTRAINT `notificacoes_movimentacao_ibfk_3` FOREIGN KEY (`usuario_notificado_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;