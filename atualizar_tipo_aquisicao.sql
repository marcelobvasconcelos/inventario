-- Adicionar colunas para tipo de aquisição na tabela 'itens' (se não existirem)
SET @column_tipo_aquisicao_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'tipo_aquisicao');
SET @column_tipo_aquisicao_descricao_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'tipo_aquisicao_descricao');
SET @column_numero_documento_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'numero_documento');

-- Adicionar coluna 'tipo_aquisicao' se não existir
SET @sql_tipo_aquisicao = IF(@column_tipo_aquisicao_exists = 0, 
    'ALTER TABLE `itens` ADD COLUMN `tipo_aquisicao` enum(''compra'',''outra'') DEFAULT ''compra'' AFTER `valor`', 
    'SELECT ''Column tipo_aquisicao already exists''');

PREPARE stmt_tipo_aquisicao FROM @sql_tipo_aquisicao;
EXECUTE stmt_tipo_aquisicao;
DEALLOCATE PREPARE stmt_tipo_aquisicao;

-- Adicionar coluna 'tipo_aquisicao_descricao' se não existir
SET @sql_tipo_aquisicao_descricao = IF(@column_tipo_aquisicao_descricao_exists = 0, 
    'ALTER TABLE `itens` ADD COLUMN `tipo_aquisicao_descricao` varchar(100) DEFAULT NULL AFTER `tipo_aquisicao`', 
    'SELECT ''Column tipo_aquisicao_descricao already exists''');

PREPARE stmt_tipo_aquisicao_descricao FROM @sql_tipo_aquisicao_descricao;
EXECUTE stmt_tipo_aquisicao_descricao;
DEALLOCATE PREPARE stmt_tipo_aquisicao_descricao;

-- Adicionar coluna 'numero_documento' se não existir
SET @sql_numero_documento = IF(@column_numero_documento_exists = 0, 
    'ALTER TABLE `itens` ADD COLUMN `numero_documento` varchar(100) DEFAULT NULL AFTER `tipo_aquisicao_descricao`', 
    'SELECT ''Column numero_documento already exists''');

PREPARE stmt_numero_documento FROM @sql_numero_documento;
EXECUTE stmt_numero_documento;
DEALLOCATE PREPARE stmt_numero_documento;