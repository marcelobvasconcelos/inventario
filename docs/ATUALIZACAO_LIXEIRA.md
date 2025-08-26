# Atualizações do Sistema de Inventário - Funcionalidade de Lixeira

## Visão Geral

Esta atualização implementa uma funcionalidade de "Lixeira" para o sistema de inventário, permitindo que itens excluídos sejam mantidos no sistema de forma segura e possam ser restaurados posteriormente. Também permite a exclusão de usuários que tenham tido itens sob sua responsabilidade, desde que esses itens tenham sido excluídos.

## Alterações Realizadas

### 1. Banco de Dados

- **atualizacao_bd_producao.sql**: Script único de atualização para produção
  - Adiciona a opção 'Excluido' ao enum 'estado' na tabela 'itens' (se ainda não existir)
  - Cria o usuário "Lixeira" para armazenar itens excluídos
  - Move todos os itens excluídos existentes para o usuário "Lixeira"
  - Verifica e cria a tabela `notificacoes_movimentacao` se não existir

### 2. Backend (PHP)

- **item_delete.php**: Modificado para mover itens para o usuário "Lixeira" em vez de apenas alterar o estado
  - Obtém o ID do usuário "Lixeira"
  - Atualiza o estado do item para 'Excluido' e atribui ao usuário "Lixeira"

- **excluir_itens_em_massa.php**: Modificado para aplicar a mesma lógica de exclusão em massa
  - Obtém o ID do usuário "Lixeira"
  - Atualiza o estado dos itens para 'Excluido' e atribui ao usuário "Lixeira"

- **usuario_delete.php**: Modificado para permitir a exclusão de usuários que tenham itens excluídos
  - Verifica se o usuário é responsável por algum item que NÃO esteja na lixeira
  - Permite a exclusão se todos os itens do usuário estiverem na lixeira

- **api/restaurar_item.php**: API para restaurar itens individuais da lixeira
  - Verifica permissões de administrador
  - Valida dados de entrada
  - Verifica se o item está realmente na lixeira e excluído
  - Restaura o item (muda estado e atribui novo responsável e local)
  - Registra a movimentação
  - Cria notificação para o novo responsável

- **api/restaurar_itens_em_massa.php**: Nova API para restaurar vários itens da lixeira
  - Verifica permissões de administrador
  - Valida dados de entrada
  - Verifica se os itens estão realmente na lixeira e excluídos
  - Restaura os itens (muda estado e atribui novo responsável e local)
  - Registra as movimentações
  - Cria notificações para o novo responsável para cada item

### 3. Frontend (HTML/JavaScript)

- **itens.php**: Adicionado botão para acessar itens excluídos
  - Adicionado botão discreto com apenas ícone para acessar itens excluídos
  - Reorganizada a interface para melhor usabilidade

- **itens_excluidos.php**: Nova página para visualizar itens excluídos
  - Lista itens com estado 'Excluido' atribuídos ao usuário "Lixeira"
  - Permite restaurar itens selecionando novo local e responsável
  - **Adicionada funcionalidade de seleção múltipla com checkboxes**
  - **Adicionada funcionalidade de restauração em massa**
  - Inclui modais para restauração individual e em massa

- **usuarios.php**: Modificado para ocultar o usuário "Lixeira" da listagem
  - Adicionado filtro WHERE para excluir o usuário "Lixeira" da listagem

### 4. Documentação

- **README.md**: Atualizado para incluir informações sobre a lixeira
  - Adicionada entrada para itens_excluidos.php na estrutura do projeto
  - Adicionada seção sobre a lixeira no uso do sistema

- **docs/USER_MANUAL.md**: Atualizado para incluir informações sobre a lixeira
  - Adicionada informação sobre itens excluídos na seção de gerenciamento completo de itens
  - Adicionada informação sobre exclusão de usuários com itens na seção de gerenciamento de usuários

## Testes Realizados

- **testar_exclusao_lixeira.php**: Testa a funcionalidade de exclusão de itens e movimentação para a lixeira
- **testar_restauracao_lixeira.php**: Testa a funcionalidade de restauração de itens da lixeira
- **testar_ocultar_lixeira.php**: Testa se o usuário "Lixeira" está oculto da listagem de usuários
- **verificar_perfis.php**: Verifica os perfis existentes e a existência do usuário "Lixeira"
- **verificar_itens_lixeira.php**: Verifica os itens na lixeira
- **testar_restauracao_com_notificacao.php**: Testa a restauração de itens com criação de notificação
- **testar_restauracao_em_massa.php**: Testa a restauração de itens em massa

## Instruções para Implantação

1. Execute o script `atualizacao_bd_producao.sql` no banco de dados de produção:
   ```
   mysql -u [usuario] -p [banco_de_dados] < atualizacao_bd_producao.sql
   ```

## Funcionalidades

1. **Exclusão de Itens**: Itens excluídos são movidos para o usuário "Lixeira" em vez de serem removidos permanentemente
2. **Visualização de Itens Excluídos**: Administradores podem acessar a página "Itens Excluídos" para ver todos os itens na lixeira
3. **Restauração de Itens**: Administradores podem restaurar itens da lixeira, selecionando um novo local e responsável
4. **Restauração em Massa**: Administradores podem selecionar vários itens e restaurá-los para o mesmo local e responsável
5. **Notificação na Restauração**: Quando um item é restaurado, uma notificação é enviada ao novo responsável
6. **Exclusão de Usuários**: Usuários que tenham tido itens podem ser excluídos se todos os seus itens estiverem na lixeira
7. **Ocultar Lixeira**: O usuário "Lixeira" é oculto da listagem de usuários normal
8. **Migração de Itens Excluídos**: Itens excluídos existentes são movidos para a lixeira automaticamente

## Considerações Finais

Esta atualização melhora significativamente a gestão de itens excluídos no sistema, permitindo que os administradores possam recuperar itens excluídos acidentalmente e também facilita a exclusão de usuários que já não são necessários no sistema. A funcionalidade de notificação garante que os novos responsáveis sejam informados quando um item é restaurado da lixeira. A restauração em massa facilita a gestão de múltiplos itens.