# Atualização do Sistema de Empenhos e Notas Fiscais

## Resumo das Alterações

Foram realizadas alterações no sistema para mover as informações de fornecedor do empenho para a nota fiscal e implementar o controle de saldo nas notas fiscais. As principais mudanças incluem:

1. **Atualização da Estrutura do Banco de Dados**:
   - Criação de script SQL para adicionar colunas `fornecedor`, `cnpj` e `saldo` na tabela `notas_fiscais`
   - Remoção das colunas `fornecedor` e `cnpj` da tabela `empenhos_insumos`

2. **Atualização dos Arquivos PHP**:
   - Modificação dos arquivos de nota fiscal para funcionar com a nova estrutura
   - Remoção dos campos de fornecedor dos arquivos de empenho
   - Implementação da funcionalidade de desconto automático do saldo da nota fiscal ao registrar entradas de materiais

## Detalhamento das Alterações

### Banco de Dados

Foi criado o arquivo `atualizacao_estrutura_banco.sql` com os seguintes comandos:

1. Adicionar colunas `fornecedor`, `cnpj` e `saldo` na tabela `notas_fiscais`
2. Copiar dados de fornecedor de `empenhos_insumos` para `notas_fiscais` (opcional, dependendo dos dados existentes)
3. Remover colunas `fornecedor` e `cnpj` da tabela `empenhos_insumos`
4. Atualizar triggers para manter o saldo das notas fiscais sincronizado

### Arquivos PHP

#### Nota Fiscal (almoxarifado/nota_fiscal_add.php e almoxarifado/nota_fiscal_edit.php)

- Atualização dos scripts de inserção e atualização para incluir as colunas `fornecedor` e `cnpj`
- Adição de tratamento de erro para funcionar com ambas as estruturas (antes e depois da atualização do banco)

#### Empenho (almoxarifado/empenho_add.php e almoxarifado/empenho_edit.php)

- Remoção dos campos de formulário para `fornecedor` e `cnpj`
- Atualização das queries de inserção e atualização para não incluir mais essas colunas
- Remoção das colunas da exibição na tabela de empenhos

#### Entrada de Materiais (almoxarifado/entrada_material.php)

- Implementação da verificação de saldo disponível na nota fiscal antes de registrar a entrada
- Adição da funcionalidade de desconto automático do saldo da nota fiscal ao registrar entradas de materiais
- Atualização das mensagens de erro para incluir informações sobre saldo insuficiente

#### Adicionar Material (almoxarifado/material_add.php)

- Simplificação do formulário para conter apenas informações básicas do material
- Remoção dos campos relacionados a notas fiscais, empenhos e valores financeiros
- Manutenção apenas dos campos: nome, descrição, unidade de medida, estoque inicial e quantidade máxima por requisição
- Adição de botão para importar materiais via CSV
- Atualização da tabela de materiais cadastrados:
  - Remoção da coluna "Valor Unit."
  - Adição da coluna "Ações" com botões para editar e excluir materiais
  - Adição de ícones descritivos aos botões de ação para facilitar o entendimento rápido

#### Gestão de Notas Fiscais (almoxarifado/nota_fiscal_add.php)

- Modificação do botão "Adicionar Materiais" para "+ Entrada"
- O botão "+ Entrada" agora abre diretamente a página de registro de entrada de materiais
- A nota fiscal é pré-selecionada automaticamente ao abrir a página de entrada

#### Registro de Entrada de Materiais (almoxarifado/entrada_material.php)

- Adição de suporte para pré-seleção de nota fiscal via parâmetro GET
- Quando acessado via link da página de notas fiscais, a nota fiscal correspondente é automaticamente selecionada
- Melhoria na experiência do usuário ao registrar entradas de materiais

#### Menu do Almoxarifado (almoxarifado/menu_almoxarifado.php)

- Remoção do botão "Importar Materiais CSV" do menu principal
- Remoção do botão "Adicionar Material" do menu principal (acessível através de "Gestão Financeira")
- Modificação do nome do botão "Gerenciar Empenhos" para "Gestão Financeira"
- Adição de ícones descritivos a todos os botões do menu para facilitar o entendimento rápido
- O botão de importação agora está disponível apenas na página de cadastro de materiais

## Instruções para Implementação

1. Execute o script `atualizacao_estrutura_banco.sql` no banco de dados
2. Execute o script `atualizar_saldos_notas_fiscais.sql` para corrigir os saldos existentes
3. Verifique se os arquivos PHP foram atualizados corretamente
4. Teste o cadastro e edição de empenhos e notas fiscais
5. Teste a funcionalidade de entrada de materiais, verificando se o saldo da nota fiscal é atualizado corretamente

## Instruções para Execução do Script de Banco de Dados

Para aplicar as alterações na estrutura do banco de dados:

1. Acesse o phpMyAdmin ou outro cliente MySQL
2. Selecione o banco de dados do sistema
3. Execute o conteúdo do arquivo `atualizacao_estrutura_banco.sql`
4. Execute o conteúdo do arquivo `atualizar_saldos_notas_fiscais.sql`
5. Verifique se as colunas foram adicionadas corretamente nas tabelas

**Importante**: Faça um backup do banco de dados antes de executar os scripts.

## Considerações Finais

Essas alterações atendem ao requisito de mover as informações de fornecedor da nota fiscal e implementar o controle de saldo nas notas fiscais. Agora, sempre que uma entrada de material for registrada, o valor será automaticamente descontado do saldo da nota fiscal associada, mantendo a consistência dos dados e a funcionalidade do sistema.