-- Adicionar a opção 'Excluido' ao enum 'estado' na tabela 'itens'
ALTER TABLE itens MODIFY estado ENUM('Em uso','Ocioso','Recuperável','Inservível','Excluido') NOT NULL;