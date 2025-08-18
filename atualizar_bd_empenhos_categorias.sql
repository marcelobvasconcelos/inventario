-- Criação da tabela de categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Criação da tabela de empenhos
CREATE TABLE IF NOT EXISTS `empenhos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_empenho` varchar(50) NOT NULL,
  `data_emissao` date NOT NULL,
  `nome_fornecedor` varchar(150) NOT NULL,
  `cnpj_fornecedor` varchar(20) NOT NULL,
  `status` enum('Aberto','Fechado') NOT NULL DEFAULT 'Aberto',
  `categoria_id` int(11) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_empenho` (`numero_empenho`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `empenhos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar coluna 'empenho_id' na tabela 'itens' (se não existir)
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'inventario_db' AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'empenho_id');

-- Se a coluna não existir, adicioná-la
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE `itens` ADD COLUMN `empenho_id` int(11) DEFAULT NULL AFTER `cnpj_cpf_fornecedor`', 
    'SELECT ''Column already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar a chave estrangeira (se não existir)
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'inventario_db' AND TABLE_NAME = 'itens' AND REFERENCED_TABLE_NAME = 'empenhos' AND COLUMN_NAME = 'empenho_id');

SET @sql_fk = IF(@fk_exists = 0, 
    'ALTER TABLE `itens` ADD CONSTRAINT `itens_ibfk_4` FOREIGN KEY (`empenho_id`) REFERENCES `empenhos` (`id`)', 
    'SELECT ''Foreign key already exists''');

PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;