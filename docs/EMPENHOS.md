# Módulo de Controle de Empenhos, Notas Fiscais e Materiais

## Visão Geral

Este módulo permite o controle completo de empenhos, notas fiscais e materiais, fornecendo uma estrutura organizada para gerenciar essas entidades e seus relacionamentos.

## Estrutura de Arquivos

- `empenhos/index.php`: Página principal do módulo
- `empenhos/categoria_add.php`: Cadastro e gerenciamento de categorias
- `empenhos/categoria_edit.php`: Edição de categorias
- `empenhos/empenho_add.php`: Cadastro e gerenciamento de empenhos
- `empenhos/empenho_edit.php`: Edição de empenhos
- `empenhos/nota_fiscal_add.php`: Cadastro e gerenciamento de notas fiscais
- `empenhos/material_add.php`: Cadastro e gerenciamento de materiais

## Funcionalidades

### 1. Categorias
- Cadastro de categorias para classificação de materiais
- Listagem de categorias existentes
- Edição de categorias

### 2. Empenhos
- Cadastro de empenhos com informações como número, data de emissão, fornecedor e CNPJ
- Definição de status (Aberto/Fechado)
- Listagem de empenhos cadastrados
- Edição de empenhos

### 3. Notas Fiscais
- Cadastro de notas fiscais vinculadas a empenhos
- Registro de valor da nota fiscal
- Listagem de notas fiscais cadastradas

### 4. Materiais
- Cadastro de materiais com nome, quantidade, categoria e valor unitário
- Vinculação opcional a notas fiscais
- Listagem de materiais cadastrados

## Relacionamentos

- Cada empenho pode ter várias notas fiscais
- Cada nota fiscal pode conter vários materiais
- Cada material pertence a uma única categoria
- A categoria só se relaciona com materiais

## Fluxo de Trabalho

1. **Cadastrar Categorias**: Primeiro, cadastre as categorias de materiais no sistema.
2. **Cadastrar Empenhos**: Registre os empenhos recebidos, vinculando-os às categorias apropriadas.
3. **Cadastrar Notas Fiscais**: Para cada empenho, registre as notas fiscais correspondentes.
4. **Cadastrar Materiais**: Finalmente, cadastre os materiais, vinculando-os às notas fiscais e categorias.

Também é possível cadastrar materiais independentemente de notas fiscais, apenas vinculando-os a uma categoria.

## Permissões

Apenas usuários com perfil de **Administrador** podem acessar e utilizar as funcionalidades deste módulo.