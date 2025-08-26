# Resumo das Atualizações - Funcionalidade de Lixeira

## Banco de Dados
- Adicionada opção 'Excluido' ao enum 'estado' na tabela 'itens'
- Criado usuário "Lixeira" com perfil 'Visualizador'
- Movidos todos os itens excluídos existentes para o usuário "Lixeira"
- Consolidado em um único arquivo de atualização: `atualizacao_bd_producao.sql`
- Verificação da tabela `notificacoes_movimentacao`

## Backend (PHP)
- Modificado item_delete.php para mover itens excluídos para a lixeira
- Modificado excluir_itens_em_massa.php para mover itens excluídos para a lixeira
- Modificado usuario_delete.php para permitir exclusão de usuários com itens na lixeira
- Criada API restaurar_item.php para restaurar itens da lixeira
- **Adicionada criação de notificação quando um item é restaurado da lixeira**

## Frontend (HTML/JavaScript)
- Adicionado botão discreto (apenas ícone) para acessar itens excluídos na página itens.php
- Criada página itens_excluidos.php para visualizar e restaurar itens excluídos
- Modificado usuarios.php para ocultar o usuário "Lixeira" da listagem
- Reorganizada a interface para melhor usabilidade

## Documentação
- Atualizado README.md com informações sobre a lixeira
- Atualizado docs/USER_MANUAL.md com informações sobre a lixeira
- Atualizado docs/ATUALIZACAO_LIXEIRA.md com detalhes das atualizações
- Mantido um único arquivo de atualização do banco de dados para produção

## Scripts de Utilidade
- Removidos scripts antigos para evitar confusão
- Consolidado em um único script de atualização do banco de dados
- Criado script de teste para verificar a funcionalidade de restauração com notificação

## Testes
- Verificado funcionamento da exclusão de itens
- Verificado funcionamento da restauração de itens
- Verificado ocultação do usuário "Lixeira" na listagem
- Verificado movimentação de itens excluídos existentes para a lixeira
- **Verificado envio de notificação quando um item é restaurado da lixeira**