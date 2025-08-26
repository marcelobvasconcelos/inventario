-- Criar usuário "Lixeira" para armazenar itens excluídos
INSERT INTO usuarios (nome, email, senha, status, permissao_id) 
VALUES ('Lixeira', 'lixeira@inventario.local', '', 'aprovado', 
        (SELECT id FROM perfis WHERE nome = 'usuario'));