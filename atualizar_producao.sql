-- Atualiza a estrutura da tabela `itens`
-- Verifica se a coluna `admin_reply` existe antes de adicioná-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'admin_reply');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column admin_reply already exists'';', 'ALTER TABLE `itens` ADD COLUMN `admin_reply` text DEFAULT NULL AFTER `status_confirmacao`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `admin_reply_date` existe antes de adicioná-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'admin_reply_date');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column admin_reply_date already exists'';', 'ALTER TABLE `itens` ADD COLUMN `admin_reply_date` timestamp NULL DEFAULT NULL AFTER `admin_reply`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualiza a estrutura da tabela `movimentacoes`
-- Verifica se a coluna `justificativa_usuario` existe antes de adicioná-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimentacoes' AND COLUMN_NAME = 'justificativa_usuario');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column justificativa_usuario already exists'';', 'ALTER TABLE `movimentacoes` ADD COLUMN `justificativa_usuario` text DEFAULT NULL AFTER `status_confirmacao`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `replica_admin` existe antes de adicioná-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimentacoes' AND COLUMN_NAME = 'replica_admin');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column replica_admin already exists'';', 'ALTER TABLE `movimentacoes` ADD COLUMN `replica_admin` text DEFAULT NULL AFTER `justificativa_usuario`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `data_acao_usuario` existe antes de adicioná-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimentacoes' AND COLUMN_NAME = 'data_acao_usuario');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column data_acao_usuario already exists'';', 'ALTER TABLE `movimentacoes` ADD COLUMN `data_acao_usuario` datetime DEFAULT NULL AFTER `replica_admin`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `data_replica_admin` existe antes de adicioná-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimentacoes' AND COLUMN_NAME = 'data_replica_admin');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column data_replica_admin already exists'';', 'ALTER TABLE `movimentacoes` ADD COLUMN `data_replica_admin` datetime DEFAULT NULL AFTER `data_acao_usuario`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `usuario_destino_id` existe antes de adicioná-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimentacoes' AND COLUMN_NAME = 'usuario_destino_id');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column usuario_destino_id already exists'';', 'ALTER TABLE `movimentacoes` ADD COLUMN `usuario_destino_id` int(11) DEFAULT NULL AFTER `data_replica_admin`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona chave estrangeira para `usuario_destino_id`
-- Verifica se a chave estrangeira `fk_usuario_destino` já existe antes de adicioná-la
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movimentacoes' AND CONSTRAINT_NAME = 'fk_usuario_destino' AND REFERENCED_TABLE_NAME IS NOT NULL);
SET @sql = IF(@fk_exists > 0, 'SELECT ''Foreign key fk_usuario_destino already exists'';', 'ALTER TABLE `movimentacoes` ADD CONSTRAINT `fk_usuario_destino` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`);');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualiza a estrutura da tabela `notificacoes` para refletir o database.sql
-- ATENÇÃO: Esta operação pode resultar em perda de dados se não forem tratados corretamente.
-- É altamente recomendável fazer um backup do banco de dados antes de executar este script.

-- Verifica se a coluna `data_envio` existe antes de adicioná-la
-- Note que o tipo da coluna no database.sql é `datetime`, mas no emproducao.sql é `timestamp`.
-- Esta operação não altera o tipo da coluna.
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'data_envio');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column data_envio already exists'';', 'ALTER TABLE `notificacoes` ADD COLUMN `data_envio` datetime DEFAULT current_timestamp() AFTER `status`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove colunas que não estão no database.sql, verificando se elas existem antes

-- Verifica se a coluna `itens_ids` existe antes de removê-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'itens_ids');
SET @sql = IF(@col_exists = 0, 'SELECT ''Column itens_ids does not exist'';', 'ALTER TABLE `notificacoes` DROP COLUMN `itens_ids`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `justificativa` existe antes de removê-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'justificativa');
SET @sql = IF(@col_exists = 0, 'SELECT ''Column justificativa does not exist'';', 'ALTER TABLE `notificacoes` DROP COLUMN `justificativa`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `data_resposta` existe antes de removê-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'data_resposta');
SET @sql = IF(@col_exists = 0, 'SELECT ''Column data_resposta does not exist'';', 'ALTER TABLE `notificacoes` DROP COLUMN `data_resposta`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `admin_reply` existe antes de removê-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'admin_reply');
SET @sql = IF(@col_exists = 0, 'SELECT ''Column admin_reply does not exist'';', 'ALTER TABLE `notificacoes` DROP COLUMN `admin_reply`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se a coluna `admin_reply_date` existe antes de removê-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes' AND COLUMN_NAME = 'admin_reply_date');
SET @sql = IF(@col_exists = 0, 'SELECT ''Column admin_reply_date does not exist'';', 'ALTER TABLE `notificacoes` DROP COLUMN `admin_reply_date`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona coluna `data_resposta` (se não existir com o tipo correto)
-- Verifica se a coluna `data_resposta` existe antes de adicioná-la
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes_respostas_historico' AND COLUMN_NAME = 'data_resposta');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column data_resposta already exists'';', 'ALTER TABLE `notificacoes_respostas_historico` ADD COLUMN `data_resposta` datetime DEFAULT CURRENT_TIMESTAMP;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cria tabelas do módulo de almoxarifado, se elas não existirem

-- Cria tabela `almoxarifado_materiais`
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'almoxarifado_materiais');
SET @sql = IF(@table_exists > 0, 'SELECT ''Table almoxarifado_materiais already exists'';', 'CREATE TABLE `almoxarifado_materiais` (`id` int(11) NOT NULL AUTO_INCREMENT, `codigo` varchar(50) NOT NULL, `nome` varchar(150) NOT NULL, `descricao` text DEFAULT NULL, `unidade_medida` varchar(20) NOT NULL, `estoque_minimo` decimal(10,2) DEFAULT 0.00, `estoque_atual` decimal(10,2) DEFAULT 0.00, `valor_unitario` decimal(10,2) DEFAULT 0.00, `categoria` varchar(100) DEFAULT NULL, `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(), `status` enum(''ativo'',''inativo'') NOT NULL DEFAULT ''ativo'', PRIMARY KEY (`id`), UNIQUE KEY `codigo` (`codigo`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cria tabela `almoxarifado_entradas`
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'almoxarifado_entradas');
SET @sql = IF(@table_exists > 0, 'SELECT ''Table almoxarifado_entradas already exists'';', 'CREATE TABLE `almoxarifado_entradas` (`id` int(11) NOT NULL AUTO_INCREMENT, `material_id` int(11) NOT NULL, `quantidade` decimal(10,2) NOT NULL, `valor_unitario` decimal(10,2) NOT NULL, `fornecedor` varchar(150) DEFAULT NULL, `nota_fiscal` varchar(50) DEFAULT NULL, `data_entrada` date NOT NULL, `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(), `usuario_id` int(11) NOT NULL, PRIMARY KEY (`id`), KEY `material_id` (`material_id`), KEY `usuario_id` (`usuario_id`), CONSTRAINT `almoxarifado_entradas_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`), CONSTRAINT `almoxarifado_entradas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cria tabela `almoxarifado_saidas`
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'almoxarifado_saidas');
SET @sql = IF(@table_exists > 0, 'SELECT ''Table almoxarifado_saidas already exists'';', 'CREATE TABLE `almoxarifado_saidas` (`id` int(11) NOT NULL AUTO_INCREMENT, `material_id` int(11) NOT NULL, `quantidade` decimal(10,2) NOT NULL, `setor_destino` varchar(100) DEFAULT NULL, `responsavel_saida` varchar(150) DEFAULT NULL, `data_saida` date NOT NULL, `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(), `usuario_id` int(11) NOT NULL, PRIMARY KEY (`id`), KEY `material_id` (`material_id`), KEY `usuario_id` (`usuario_id`), CONSTRAINT `almoxarifado_saidas_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`), CONSTRAINT `almoxarifado_saidas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cria tabela `almoxarifado_movimentacoes`
SET @table_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'almoxarifado_movimentacoes');
SET @sql = IF(@table_exists > 0, 'SELECT ''Table almoxarifado_movimentacoes already exists'';', 'CREATE TABLE `almoxarifado_movimentacoes` (`id` int(11) NOT NULL AUTO_INCREMENT, `material_id` int(11) NOT NULL, `tipo` enum(''entrada'',''saida'') NOT NULL, `quantidade` decimal(10,2) NOT NULL, `saldo_anterior` decimal(10,2) NOT NULL, `saldo_atual` decimal(10,2) NOT NULL, `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(), `usuario_id` int(11) NOT NULL, `referencia_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`), KEY `material_id` (`material_id`), KEY `usuario_id` (`usuario_id`), CONSTRAINT `almoxarifado_movimentacoes_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `almoxarifado_materiais` (`id`), CONSTRAINT `almoxarifado_movimentacoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualiza a estrutura da tabela `notificacoes_itens_detalhes` (se necessário)
-- Verifica se a coluna `data_admin_reply` existe
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes_itens_detalhes' AND COLUMN_NAME = 'data_admin_reply');
SET @sql = IF(@col_exists > 0, 'SELECT ''Column data_admin_reply already exists'';', 'ALTER TABLE `notificacoes_itens_detalhes` ADD COLUMN `data_admin_reply` timestamp NULL DEFAULT NULL AFTER `admin_reply`;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona chave estrangeira para `notificacao_movimentacao_id`
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes_respostas_historico' AND COLUMN_NAME = 'notificacao_movimentacao_id' AND REFERENCED_TABLE_NAME IS NOT NULL);
SET @sql = IF(@fk_exists > 0, 'SELECT ''Foreign key for notificacao_movimentacao_id already exists'';', 'ALTER TABLE `notificacoes_respostas_historico` ADD CONSTRAINT `fk_notificacao_movimentacao` FOREIGN KEY (`notificacao_movimentacao_id`) REFERENCES `notificacoes_movimentacao` (`id`) ON DELETE CASCADE;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona chave estrangeira para `remetente_id`
SET @fk_exists2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notificacoes_respostas_historico' AND COLUMN_NAME = 'remetente_id' AND REFERENCED_TABLE_NAME IS NOT NULL);
SET @sql2 = IF(@fk_exists2 > 0, 'SELECT ''Foreign key for remetente_id already exists'';', 'ALTER TABLE `notificacoes_respostas_historico` ADD CONSTRAINT `fk_remetente` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;