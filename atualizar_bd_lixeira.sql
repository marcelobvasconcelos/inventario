-- Script para atualizar o banco de dados com a funcionalidade de lixeira

-- 1. Adicionar a opção 'Excluido' ao enum 'estado' na tabela 'itens' (se ainda não existir)
ALTER TABLE itens MODIFY estado ENUM('Em uso','Ocioso','Recuperável','Inservível','Excluido') NOT NULL;

-- 2. Criar usuário "Lixeira" para armazenar itens excluídos
INSERT INTO usuarios (nome, email, senha, status, permissao_id) 
SELECT 'Lixeira', 'lixeira@inventario.local', '', 'aprovado', id 
FROM perfis 
WHERE nome = 'usuario'
AND NOT EXISTS (SELECT 1 FROM usuarios WHERE nome = 'Lixeira');