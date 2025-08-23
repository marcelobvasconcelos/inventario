-- Adiciona coluna para armazenar o tema preferido do usuário
ALTER TABLE usuarios ADD COLUMN tema_preferido VARCHAR(20) DEFAULT 'padrao' AFTER permissao_id;