# Resumo das Atualizações - Funcionalidade de Lixeira

## Banco de Dados
- Adicionada opção 'Excluido' ao enum 'estado' na tabela 'itens'
- Criado usuário "Lixeira" com perfil 'Visualizador'
- Movidos todos os itens excluídos existentes para o usuário "Lixeira"

## Backend (PHP)
- Modificado item_delete.php para mover itens excluídos para a lixeira
- Modificado excluir_itens_em_massa.php para mover itens excluídos para a lixeira
- Modificado usuario_delete.php para permitir exclusão de usuários com itens na lixeira
- Criada API restaurar_item.php para restaurar itens da lixeira

## Frontend (HTML/JavaScript)
- Adicionado botão "Ver Itens Excluídos" na página itens.php
- Criada página itens_excluidos.php para visualizar e restaurar itens excluídos
- Modificado usuarios.php para ocultar o usuário "Lixeira" da listagem

## Documentação
- Atualizado README.md com informações sobre a lixeira
- Atualizado docs/USER_MANUAL.md com informações sobre a lixeira
- Criado docs/ATUALIZACAO_LIXEIRA.md com detalhes das atualizações

## Scripts de Utilidade
- Criado script para mover itens excluídos para a lixeira
- Criado script para verificar itens na lixeira
- Criado script de atualização para produção

## Testes
- Verificado funcionamento da exclusão de itens
- Verificado funcionamento da restauração de itens
- Verificado ocultação do usuário "Lixeira" na listagem
- Verificado movimentação de itens excluídos existentes para a lixeira