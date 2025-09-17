# Nova Lógica de Entrada de Materiais no Almoxarifado

## Visão Geral

Esta documentação descreve a nova lógica de entrada de materiais no sistema de almoxarifado, que permitirá:

1. Registrar entradas de materiais vinculadas a notas fiscais específicas
2. Manter múltiplas entradas do mesmo material em diferentes notas fiscais
3. Controlar o estoque de forma acumulativa, independentemente do valor unitário
4. Aplicar o princípio PEPS (Primeiro que Entra, Primeiro que Sai) na saída dos materiais

## Estrutura de Dados

### Tabelas Envolvidas

1. **almoxarifado_materiais** - Tabela principal de materiais
2. **almoxarifado_entradas** - Tabela de registros de entrada de materiais
3. **notas_fiscais** - Tabela de notas fiscais
4. **empenhos_insumos** - Tabela de empenhos

### Campos Importantes

#### Tabela `almoxarifado_entradas`
- `id` (INT, PK) - Identificador único da entrada
- `material_id` (INT, FK) - Referência ao material
- `nota_fiscal` (VARCHAR) - Número da nota fiscal
- `quantidade` (DECIMAL) - Quantidade recebida
- `valor_unitario` (DECIMAL) - Valor unitário na nota fiscal
- `data_entrada` (DATE) - Data da entrada
- `data_cadastro` (TIMESTAMP) - Data de registro
- `usuario_id` (INT) - Usuário que registrou a entrada

## Funcionalidades

### 1. Registro de Entrada de Materiais

#### Fluxo de Trabalho
1. Usuário acessa a funcionalidade de entrada de materiais
2. Seleciona um material da lista de materiais cadastrados
3. Informa a nota fiscal (previamente cadastrada)
4. Informa a quantidade recebida
5. Informa o valor unitário do material naquela nota
6. Sistema registra a entrada e atualiza o estoque

#### Regras de Negócio
- O material deve estar previamente cadastrado
- A nota fiscal deve estar cadastrada e vinculada a um empenho
- A quantidade informada será somada ao estoque atual do material
- O valor unitário é registrado para histórico, mas não afeta o valor unitário do material

### 2. Controle de Estoque

#### Atualização de Estoque
- Ao registrar uma entrada, o sistema soma a quantidade informada ao `estoque_atual` do material
- O valor unitário da entrada é registrado apenas para histórico
- O campo `valor_unitario` do material permanece inalterado

#### Exemplo de Fluxo
1. Material "Parafuso Sextavado" com estoque atual = 100 unidades
2. Entrada de 50 unidades com valor R$ 0,25 cada
3. Novo estoque = 100 + 50 = 150 unidades
4. O valor R$ 0,25 é registrado apenas no histórico da entrada

### 3. Princípio PEPS

#### Funcionamento
- Ao registrar uma saída, o sistema utilizará o princípio PEPS
- As saídas consumirão primeiro as quantidades das entradas mais antigas
- O sistema manterá o controle de qual lote (entrada) está sendo consumido

#### Exemplo de Aplicação
1. Entrada 01: 100 parafusos em 01/01/2025 com valor R$ 0,20
2. Entrada 02: 50 parafusos em 15/01/2025 com valor R$ 0,25
3. Saída de 120 parafusos:
   - Primeiro consome os 100 da Entrada 01
   - Depois consome 20 da Entrada 02
   - Restam 30 parafusos da Entrada 02 no estoque

## Interface do Usuário

### Tela de Entrada de Materiais

#### Componentes
1. **Seleção de Material**
   - Campo de busca com autocomplete
   - Lista de materiais cadastrados
   - Validação de material selecionado

2. **Informações da Nota Fiscal**
   - Campo para informar o número da nota fiscal
   - Validação de nota fiscal cadastrada

3. **Dados da Entrada**
   - Quantidade recebida
   - Valor unitário na nota fiscal
   - Data da entrada (default = data atual)

### Tela de Detalhes do Material

#### Informações Exibidas
1. **Dados Gerais do Material**
   - Código, nome, descrição
   - Unidade de medida
   - Estoque atual
   - Estoque mínimo

2. **Histórico de Entradas**
   - Lista de todas as entradas do material
   - Nota fiscal, quantidade, valor unitário
   - Data da entrada
   - Usuário que registrou

3. **Lotes em Estoque (PEPS)**
   - Lista dos lotes atuais ordenados por data (mais antigo primeiro)
   - Quantidade disponível em cada lote
   - Valor unitário de cada lote
   - Data da entrada

## Implementação Técnica

### Modificações Necessárias

#### 1. Banco de Dados
- A tabela `almoxarifado_entradas` já possui a estrutura necessária
- Nenhuma modificação estrutural é necessária

#### 2. Backend (PHP)
- Criar novo arquivo `entrada_material.php` para a funcionalidade
- Implementar validações de material e nota fiscal
- Atualizar o estoque do material ao registrar entrada
- Manter histórico completo das entradas

#### 3. Frontend
- Criar interface intuitiva para registro de entradas
- Implementar autocomplete para seleção de materiais
- Validar campos obrigatórios
- Exibir confirmação de registro

### Controle de Acesso

#### Permissões
- Apenas usuários com perfil "Administrador" poderão acessar
- A funcionalidade estará disponível no menu do almoxarifado

## Benefícios da Nova Lógica

1. **Melhor Controle de Estoque**
   - Permite ter o mesmo material em diferentes notas fiscais
   - Mantém histórico completo de todas as entradas

2. **Precisão Financeira**
   - Cada entrada mantém seu valor unitário original
   - Facilita análise de custos por fornecedor/nota fiscal

3. **Gestão PEPS Automática**
   - Sistema aplica automaticamente o princípio PEPS
   - Maior controle sobre validade e custo dos materiais consumidos

4. **Rastreabilidade Completa**
   - É possível identificar exatamente de qual nota/empenho veio cada material
   - Facilita auditorias e investigações de estoque

## Considerações Finais

Esta nova lógica proporcionará um controle muito mais preciso e completo do estoque do almoxarifado, permitindo análises mais detalhadas e uma melhor gestão dos recursos materiais.