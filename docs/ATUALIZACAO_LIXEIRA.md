# Atualizações do Sistema de Inventário - Funcionalidade de Lixeira

## Visão Geral

Esta atualização implementa uma funcionalidade de "Lixeira" para o sistema de inventário, permitindo que itens excluídos sejam mantidos no sistema de forma segura e possam ser restaurados posteriormente. Também permite a exclusão de usuários que tenham tido itens sob sua responsabilidade, desde que esses itens tenham sido excluídos.

## Alterações Realizadas

### 1. Banco de Dados

- **atualizar_bd_lixeira.sql**: Script SQL para atualizar a estrutura do banco de dados
  - Adiciona a opção 'Excluido' ao enum 'estado' na tabela 'itens' (se ainda não existir)
  - Cria o usuário "Lixeira" para armazenar itens excluídos

- **atualizar_bd_usuario_lixeira.php**: Script PHP para criar o usuário "Lixeira" no banco de dados
  - Verifica se o perfil 'Visualizador' existe
  - Cria o usuário "Lixeira" com perfil 'Visualizador' se ele não existir

- **atualizacao_bd_lixeira_producao.sql**: Script de atualização para produção
  - Inclui todas as mudanças necessárias para implementar a funcionalidade de lixeira em ambiente de produção
  - Inclui comando para mover itens excluídos existentes para o usuário "Lixeira"

- **mover_itens_excluidos_para_lixeira.php**: Script PHP para mover itens excluídos existentes para o usuário "Lixeira"
  - Verifica a existência do usuário "Lixeira"
  - Move todos os itens com estado 'Excluido' para o usuário "Lixeira"

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

- **api/restaurar_item.php**: Nova API para restaurar itens da lixeira
  - Verifica permissões de administrador
  - Valida dados de entrada
  - Verifica se o item está realmente na lixeira e excluído
  - Restaura o item (muda estado e atribui novo responsável e local)
  - Registra a movimentação

### 3. Frontend (HTML/JavaScript)

- **itens.php**: Adicionado botão para acessar itens excluídos
  - Adicionado botão "Ver Itens Excluídos" para administradores

- **itens_excluidos.php**: Nova página para visualizar itens excluídos
  - Lista itens com estado 'Excluido' atribuídos ao usuário "Lixeira"
  - Permite restaurar itens selecionando novo local e responsável
  - Inclui modal para restauração de itens

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
- **mover_itens_excluidos_para_lixeira.php**: Testa a movimentação de itens excluídos existentes para a lixeira

## Instruções para Implantação

1. Execute o script `atualizar_bd_usuario_lixeira.php` para criar o usuário "Lixeira" no banco de dados:
   ```
   php atualizar_bd_usuario_lixeira.php
   ```

2. Ou execute o script SQL `atualizar_bd_lixeira.sql` diretamente no banco de dados:
   ```
   mysql -u [usuario] -p [banco_de_dados] < atualizar_bd_lixeira.sql
   ```

3. Para mover itens excluídos existentes para a lixeira, execute:
   ```
   php mover_itens_excluidos_para_lixeira.php
   ```

4. Para implantação em produção, use o script `atualizacao_bd_lixeira_producao.sql`:
   ```
   mysql -u [usuario] -p [banco_de_dados] < atualizacao_bd_lixeira_producao.sql
   ```

## Funcionalidades

1. **Exclusão de Itens**: Itens excluídos são movidos para o usuário "Lixeira" em vez de serem removidos permanentemente
2. **Visualização de Itens Excluídos**: Administradores podem acessar a página "Itens Excluídos" para ver todos os itens na lixeira
3. **Restauração de Itens**: Administradores podem restaurar itens da lixeira, selecionando um novo local e responsável
4. **Exclusão de Usuários**: Usuários que tenham tido itens podem ser excluídos se todos os seus itens estiverem na lixeira
5. **Ocultar Lixeira**: O usuário "Lixeira" é oculto da listagem de usuários normal
6. **Migração de Itens Excluídos**: Itens excluídos existentes podem ser movidos para a lixeira com um único comando

## Considerações Finais

Esta atualização melhora significativamente a gestão de itens excluídos no sistema, permitindo que os administradores possam recuperar itens excluídos acidentalmente e também facilita a exclusão de usuários que já não são necessários no sistema.