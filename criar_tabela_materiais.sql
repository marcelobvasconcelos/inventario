-- Script para criar a tabela de materiais

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