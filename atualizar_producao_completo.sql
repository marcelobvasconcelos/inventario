-- Script de atualização completo para o banco de dados de produção
-- Data: 25/08/2025
-- Versão: 1.0

-- 1. Atualizações de estrutura de banco de dados

-- 1.1. Adiciona coluna para identificar se o usuário está usando uma senha temporária
ALTER TABLE usuarios ADD COLUMN senha_temporaria BOOLEAN DEFAULT FALSE;

-- 1.2. Cria tabela para armazenar solicitações de recuperação de senha
CREATE TABLE IF NOT EXISTS solicitacoes_senha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'processada', 'cancelada') DEFAULT 'pendente',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- 1.3. Atualizações de empenhos e categorias
-- (Incluindo conteúdo de atualizar_bd_empenhos_categorias.sql)
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'empenhos_categorias');
SET @sql = IF(@table_exists > 0, 'SELECT ''Table empenhos_categorias already exists'';', 'CREATE TABLE `empenhos_categorias` (`id` int(11) NOT NULL AUTO_INCREMENT, `nome` varchar(100) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- (Incluindo conteúdo de atualizar_bd_patrimonio.sql)
ALTER TABLE itens ADD COLUMN patrimonio_novo VARCHAR(50) UNIQUE;
ALTER TABLE itens ADD COLUMN patrimonio_secundario VARCHAR(50);

-- (Incluindo conteúdo de atualizar_bd_estado_excluido.sql)
ALTER TABLE itens ADD COLUMN estado ENUM('Em uso', 'Ocioso', 'Recuperável', 'Inservível', 'Excluido') DEFAULT 'Em uso';

-- (Incluindo conteúdo de atualizar_bd_temas.sql)
ALTER TABLE usuarios ADD COLUMN tema_preferido VARCHAR(50) DEFAULT 'padrao';

-- (Incluindo conteúdo de atualizar_relacao_empenho_categoria.sql)
ALTER TABLE empenhos ADD COLUMN categoria_id INT(11);
ALTER TABLE empenhos ADD CONSTRAINT fk_empenho_categoria FOREIGN KEY (categoria_id) REFERENCES empenhos_categorias(id);

-- (Incluindo conteúdo de atualizar_tabelas_empenhos_categorias.sql)
-- Esta atualização já foi incluída acima

-- (Incluindo conteúdo de atualizar_tabela_rascunhos.sql)
CREATE TABLE IF NOT EXISTS rascunhos_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    categoria VARCHAR(50),
    localizacao VARCHAR(100),
    responsavel VARCHAR(100),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT(11),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- (Incluindo conteúdo de atualizar_tipo_aquisicao.sql)
ALTER TABLE itens ADD COLUMN tipo_aquisicao VARCHAR(50);

-- (Incluindo conteúdo de atualizar_empenhos_data_cadastro.sql)
ALTER TABLE empenhos ADD COLUMN data_cadastro DATE;

-- 2. Inserções de dados iniciais (se necessário)

-- 2.1. Categorias de empenho padrão (se a tabela estiver vazia)
INSERT IGNORE INTO empenhos_categorias (nome) VALUES 
('Material de Consumo'),
('Material Permanente'),
('Serviços de Terceiros'),
('Obras e Instalações');

-- 3. Atualizações de dados existentes (se necessário)

-- 3.1. Atualizar itens existentes para ter estado padrão se estiverem NULL
UPDATE itens SET estado = 'Em uso' WHERE estado IS NULL;

-- 3.2. Migrar dados de patrimônio antigo para o novo campo, se necessário
-- (Esta atualização deve ser feita com cuidado e verificada antes de executar)
-- UPDATE itens SET patrimonio_novo = patrimonio WHERE patrimonio_novo IS NULL OR patrimonio_novo = '';

-- 4. Criação de índices (se necessário para performance)

-- 4.1. Índice para status de confirmação
CREATE INDEX idx_status_confirmacao ON itens(status_confirmacao);

-- 4.2. Índice para estado dos itens
CREATE INDEX idx_estado ON itens(estado);

-- 4.3. Índice para patrimônio novo
CREATE INDEX idx_patrimonio_novo ON itens(patrimonio_novo);

-- 4.4. Índice para solicitações de senha por status
CREATE INDEX idx_solicitacoes_status ON solicitacoes_senha(status);

-- 5. Mensagem de conclusão
SELECT 'Atualização do banco de dados concluída com sucesso!' AS mensagem;