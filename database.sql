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
  PRIMARY KEY (`id`),
  UNIQUE KEY `patrimonio_novo` (`patrimonio_novo`),
  KEY `local_id` (`local_id`),
  KEY `responsavel_id` (`responsavel_id`),
  CONSTRAINT `itens_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `locais` (`id`),
  CONSTRAINT `itens_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`)
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
