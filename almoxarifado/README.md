# Módulo de Almoxarifado

## Visão Geral

Este módulo é responsável pela gestão de materiais em um almoxarifado, permitindo o cadastro de itens, controle de estoque, registro de entradas e saídas, e sistema de notificações para requisições.

## Estrutura de Arquivos

- `index.php`: Página inicial do módulo com links para as principais funcionalidades.
- `itens.php`: Listagem de todos os materiais cadastrados.
- `item_add.php`: Formulário para adicionar novos materiais.
- `item_edit.php`: Formulário para editar materiais existentes.
- `item_delete.php`: Script para excluir materiais.
- `item_details.php`: Página com detalhes de um material específico e seu histórico.
- `entrada_add.php`: Formulário para registrar entrada de materiais.
- `saida_add.php`: Formulário para registrar saída de materiais.
- `requisicao.php`: Formulário para criar requisições de materiais.
- `minhas_notificacoes.php`: Interface para usuários visualizarem e responderem a notificações.
- `admin_notificacoes.php`: Interface para administradores gerenciarem requisições e notificações.
- `database.sql`: Script SQL para criar as tabelas do módulo.

## Funcionalidades

### 1. Gestão de Itens
- **Listar Itens**: Visualização paginada de todos os materiais com opções de pesquisa.
- **Adicionar Item**: Cadastro de novos materiais com informações como código, nome, descrição, unidade de medida, estoque mínimo, estoque atual, valor unitário e categoria.
- **Editar Item**: Atualização das informações de um material existente.
- **Excluir Item**: Remoção de um material do sistema (exclui também registros relacionados em entradas, saídas e movimentações).
- **Detalhes do Item**: Visualização detalhada de um material, incluindo seu histórico de entradas, saídas e movimentações.

### 2. Controle de Estoque
- **Entradas**: Registro de entrada de materiais com informações como quantidade, valor unitário, fornecedor, nota fiscal e data de entrada.
- **Saídas**: Registro de saída de materiais com informações como quantidade, setor de destino, responsável pela saída e data de saída.
- **Movimentações**: Registro automático de todas as movimentações de estoque (entradas e saídas) com saldo anterior e saldo atual.

### 3. Sistema de Notificações para Requisições
- **Nova Requisição**: Usuários podem criar requisições de materiais especificando itens e quantidades necessárias.
- **Notificações em Tempo Real**: Sistema de notificações que alerta administradores sobre novas requisições.
- **Aprovação/Rejeição**: Administradores podem aprovar ou rejeitar requisições com justificativa.
- **Solicitação de Informações**: Administradores podem solicitar mais informações sobre uma requisição.
- **Histórico de Conversas**: Registra todas as interações entre usuários e administradores sobre uma requisição.
- **Agendamento de Entrega**: Após aprovação, usuários podem agendar a entrega dos materiais.

### 4. Relatórios
- Funcionalidade em desenvolvimento.

## Estrutura do Banco de Dados

O módulo utiliza as seguintes tabelas:

1. `almoxarifado_materiais`: Armazena as informações dos materiais.
2. `almoxarifado_entradas`: Registra as entradas de materiais.
3. `almoxarifado_saidas`: Registra as saídas de materiais.
4. `almoxarifado_movimentacoes`: Registra todas as movimentações de estoque.
5. `almoxarifado_requisicoes`: Registra as requisições de materiais.
6. `almoxarifado_requisicoes_itens`: Registra os itens de cada requisição.
7. `almoxarifado_requisicoes_notificacoes`: Registra as notificações relacionadas às requisições.
8. `almoxarifado_requisicoes_conversas`: Registra o histórico de conversas sobre as requisições.
9. `almoxarifado_agendamentos`: Registra os agendamentos de entrega após aprovação de requisições.

## Permissões

- **Usuários (Administrador, Almoxarife, Visualizador, Gestor)**: Podem criar requisições e visualizar suas notificações.
- **Administradores**: Podem gerenciar todas as requisições, aprovar/rejeitar e solicitar informações adicionais.