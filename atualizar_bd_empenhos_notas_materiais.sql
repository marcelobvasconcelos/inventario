-- Script para atualizar o banco de dados com as tabelas de empenhos, notas fiscais e materiais

-- Criação da tabela de categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `codigo` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) NOT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Criação da tabela de empenhos
CREATE TABLE IF NOT EXISTS `empenhos_insumos` (
  `numero` varchar(50) NOT NULL,
  `data_emissao` date NOT NULL,
  `fornecedor` varchar(150) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `status` enum('Aberto','Fechado') NOT NULL DEFAULT 'Aberto',
  PRIMARY KEY (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Criação da tabela de notas fiscais
CREATE TABLE IF NOT EXISTS `notas_fiscais` (
  `nota_numero` varchar(50) NOT NULL,
  `nota_valor` decimal(10,2) NOT NULL,
  `empenho_numero` varchar(50) NOT NULL,
  PRIMARY KEY (`nota_numero`),
  KEY `empenho_numero` (`empenho_numero`),
  CONSTRAINT `notas_fiscais_ibfk_1` FOREIGN KEY (`empenho_numero`) REFERENCES `empenhos_insumos` (`numero`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Criação da tabela de materiais
CREATE TABLE IF NOT EXISTS `materiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `qtd` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `valor_unit` decimal(10,2) NOT NULL,
  `nota_no` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `nota_no` (`nota_no`),
  CONSTRAINT `materiais_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `materiais_ibfk_2` FOREIGN KEY (`nota_no`) REFERENCES `notas_fiscais` (`nota_numero`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;