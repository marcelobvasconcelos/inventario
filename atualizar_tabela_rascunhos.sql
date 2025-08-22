-- Atualizar a estrutura da tabela rascunhos_itens para corresponder à tabela itens

-- Adicionar colunas que estão faltando
ALTER TABLE rascunhos_itens 
ADD COLUMN processo_documento VARCHAR(100) NULL AFTER id,
ADD COLUMN descricao_detalhada VARCHAR(200) NULL AFTER nome,
ADD COLUMN numero_serie VARCHAR(100) NULL AFTER descricao_detalhada,
ADD COLUMN patrimonio_novo VARCHAR(50) NULL AFTER numero_serie,
ADD COLUMN patrimonio_secundario VARCHAR(50) NULL AFTER patrimonio_novo,
ADD COLUMN responsavel_id INT(11) NULL AFTER local_id,
ADD COLUMN estado ENUM('Bom','Razoável','Inservível') NOT NULL DEFAULT 'Bom' AFTER responsavel_id,
ADD COLUMN observacao TEXT NULL AFTER estado,
ADD COLUMN usuario_anterior_id INT(11) NULL AFTER observacao,
ADD COLUMN data_emissao_empenho DATE NULL AFTER empenho,
ADD COLUMN fornecedor VARCHAR(150) NULL AFTER data_emissao_empenho,
ADD COLUMN cnpj_cpf_fornecedor VARCHAR(20) NULL AFTER fornecedor,
ADD COLUMN cnpj_fornecedor VARCHAR(20) NULL AFTER cnpj_cpf_fornecedor,
ADD COLUMN categoria VARCHAR(100) NULL AFTER cnpj_fornecedor,
ADD COLUMN valor_nf DECIMAL(10,2) NULL AFTER categoria,
ADD COLUMN nd_nota_despesa VARCHAR(100) NULL AFTER valor_nf,
ADD COLUMN unidade_medida VARCHAR(50) NULL AFTER nd_nota_despesa,
ADD COLUMN valor DECIMAL(10,2) NULL AFTER unidade_medida,
ADD COLUMN tipo_aquisicao ENUM('compra','outra') NULL DEFAULT 'compra' AFTER valor,
ADD COLUMN tipo_aquisicao_descricao VARCHAR(100) NULL AFTER tipo_aquisicao,
ADD COLUMN numero_documento VARCHAR(100) NULL AFTER tipo_aquisicao_descricao,
ADD COLUMN nota_fiscal_documento VARCHAR(100) NULL AFTER numero_documento,
ADD COLUMN data_entrada_aceitacao DATE NULL AFTER nota_fiscal_documento,
ADD COLUMN status_confirmacao ENUM('Pendente','Confirmado','Nao Confirmado','Movimento Desfeito') NULL DEFAULT 'Pendente' AFTER data_entrada_aceitacao,
ADD COLUMN admin_reply TEXT NULL AFTER status_confirmacao,
ADD COLUMN admin_reply_date TIMESTAMP NULL AFTER admin_reply;

-- Remover colunas que não estão na tabela itens
ALTER TABLE rascunhos_itens 
DROP FOREIGN KEY rascunhos_itens_ibfk_1,
DROP COLUMN categoria_id,
DROP COLUMN descricao,
DROP COLUMN valor_unitario,
DROP COLUMN usuario_id,
DROP COLUMN data_cadastro;

-- Adicionar chaves estrangeiras que estão faltando
ALTER TABLE rascunhos_itens 
ADD CONSTRAINT fk_rascunhos_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios(id),
ADD CONSTRAINT fk_rascunhos_usuario_anterior FOREIGN KEY (usuario_anterior_id) REFERENCES usuarios(id);

-- Adicionar índice para patrimonio_novo
ALTER TABLE rascunhos_itens ADD UNIQUE KEY patrimonio_novo (patrimonio_novo);