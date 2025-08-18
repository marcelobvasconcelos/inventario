# Módulo de Almoxarifado

## Visão Geral

Este módulo é responsável pela gestão de materiais em um almoxarifado, permitindo o cadastro de itens, controle de estoque e registro de entradas e saídas.

## Estrutura de Arquivos

- `index.php`: Página inicial do módulo com links para as principais funcionalidades.
- `itens.php`: Listagem de todos os materiais cadastrados.
- `item_add.php`: Formulário para adicionar novos materiais.
- `item_edit.php`: Formulário para editar materiais existentes.
- `item_delete.php`: Script para excluir materiais.
- `item_details.php`: Página com detalhes de um material específico e seu histórico.
- `entrada_add.php`: Formulário para registrar entrada de materiais.
- `saida_add.php`: Formulário para registrar saída de materiais.
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

### 3. Relatórios
- Funcionalidade em desenvolvimento.

## Estrutura do Banco de Dados

O módulo utiliza as seguintes tabelas:

1. `almoxarifado_materiais`: Armazena as informações dos materiais.
2. `almoxarifado_entradas`: Registra as entradas de materiais.
3. `almoxarifado_saidas`: Registra as saídas de materiais.
4. `almoxarifado_movimentacoes`: Registra todas as movimentações de estoque.

## Permissões

Apenas usuários com permissão de "Administrador" podem acessar e utilizar as funcionalidades do módulo de almoxarifado.