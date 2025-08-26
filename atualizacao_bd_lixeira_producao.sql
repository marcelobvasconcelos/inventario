-- Arquivo de atualização para produção - Funcionalidade de Lixeira

-- 1. Atualizar a estrutura da tabela itens para incluir o estado 'Excluido' (se ainda não existir)
ALTER TABLE itens MODIFY estado ENUM('Em uso','Ocioso','Recuperável','Inservível','Excluido') NOT NULL;

-- 2. Criar usuário "Lixeira" para armazenar itens excluídos (se não existir)
INSERT INTO usuarios (nome, email, senha, status, permissao_id) 
SELECT 'Lixeira', 'lixeira@inventario.local', '', 'aprovado', id 
FROM perfis 
WHERE nome = 'Visualizador'
AND NOT EXISTS (SELECT 1 FROM usuarios WHERE nome = 'Lixeira');

-- 3. Mover todos os itens excluídos existentes para o usuário "Lixeira"
UPDATE itens 
SET responsavel_id = (SELECT id FROM usuarios WHERE nome = 'Lixeira')
WHERE estado = 'Excluido' 
AND responsavel_id != (SELECT id FROM usuarios WHERE nome = 'Lixeira');