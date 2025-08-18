-- Adicionar coluna 'data_cadastro' na tabela 'empenhos' (se não existir)
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'inventario_db' AND TABLE_NAME = 'empenhos' AND COLUMN_NAME = 'data_cadastro');

-- Se a coluna não existir, adicioná-la
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE `empenhos` ADD COLUMN `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP', 
    'SELECT ''Column already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;