-- Arquivo de atualização do banco de dados para produção - Sistema de Inventário

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

-- 4. Verificar se a tabela notificacoes_movimentacao existe, se não existir, criá-la
CREATE TABLE IF NOT EXISTS notificacoes_movimentacao (
  id int(11) NOT NULL AUTO_INCREMENT,
  movimentacao_id int(11) NOT NULL,
  item_id int(11) NOT NULL,
  usuario_notificado_id int(11) NOT NULL,
  status_confirmacao enum('Pendente','Confirmado','Rejeitado','Replicado','Em Disputa','Movimento Desfeito') NOT NULL DEFAULT 'Pendente',
  justificativa_usuario text DEFAULT NULL,
  resposta_admin text DEFAULT NULL,
  data_notificacao timestamp NOT NULL DEFAULT current_timestamp(),
  data_atualizacao timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY movimentacao_id (movimentacao_id),
  KEY item_id (item_id),
  KEY usuario_notificado_id (usuario_notificado_id),
  CONSTRAINT notificacoes_movimentacao_ibfk_1 FOREIGN KEY (movimentacao_id) REFERENCES movimentacoes (id),
  CONSTRAINT notificacoes_movimentacao_ibfk_2 FOREIGN KEY (item_id) REFERENCES itens (id),
  CONSTRAINT notificacoes_movimentacao_ibfk_3 FOREIGN KEY (usuario_notificado_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Adicionar qualquer outra atualização de estrutura de tabelas, se necessário
-- (Adicione aqui outras alterações de estrutura de banco de dados conforme necessário)

-- Fim do script de atualização