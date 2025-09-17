# Sistema de Controle Financeiro do Almoxarifado

## Relação entre Empenhos, Notas Fiscais e Materiais

Após analisar o código do sistema, esta documentação descreve como funciona a relação entre empenhos, notas fiscais e materiais no módulo de almoxarifado.

### 1. Fluxo de Trabalho

O sistema segue uma hierarquia específica no controle financeiro e de materiais:

1. **Empenhos**: São registrados primeiro, representando um compromisso financeiro com um fornecedor
2. **Notas Fiscais**: São vinculadas aos empenhos, representando as compras realizadas contra aquele empenho
3. **Materiais**: São cadastrados e vinculados às notas fiscais, representando os itens físicos adquiridos

### 2. Cálculos e Controles de Saldo

#### Controle de Saldo no Empenho
- Quando um empenho é criado, o campo `saldo` é inicializado com o mesmo valor do campo `valor`
- Cada vez que uma nota fiscal é cadastrada vinculada a esse empenho:
  - O sistema verifica se há saldo suficiente: `saldo_atual >= nota_valor`
  - Se houver saldo suficiente, deduz o valor da nota do saldo do empenho: `novo_saldo = saldo_atual - nota_valor`
  - Atualiza o campo `saldo` na tabela `empenhos_insumos`

#### Exemplo Prático:
1. Empenho #2023001 criado com valor de R$ 10.000,00
   - Saldo inicial: R$ 10.000,00
2. Nota Fiscal #NF-001 de R$ 3.000,00 vinculada ao empenho
   - Novo saldo: R$ 10.000,00 - R$ 3.000,00 = R$ 7.000,00
3. Nota Fiscal #NF-002 de R$ 5.000,00 vinculada ao mesmo empenho
   - Novo saldo: R$ 7.000,00 - R$ 5.000,00 = R$ 2.000,00

### 3. Integração entre Tabelas

#### Estrutura de Relacionamento:
- `empenhos_insumos` (tabela de empenhos)
  - Campo `numero` (chave primária)
  - Campos `valor` e `saldo` para controle financeiro
- `notas_fiscais` (tabela de notas fiscais)
  - Campo `nota_numero` (chave primária)
  - Campo `empenho_numero` (chave estrangeira para `empenhos_insumos.numero`)
  - Campo `nota_valor` (valor da nota)
- `almoxarifado_materiais` (tabela de materiais)
  - Campo `id` (chave primária)
  - Campo `codigo` (código único do material)
  - Não há vínculo direto com empenhos ou notas fiscais nas tabelas principais

#### Pontos Importantes:
1. **Vinculação Indireta**: Os materiais são vinculados às notas fiscais de forma indireta - o sistema não armazena explicitamente qual material pertence a qual nota fiscal na estrutura das tabelas
2. **Controle Manual**: A ligação entre materiais e notas fiscais é feita de forma conceitual durante o processo de cadastro, mas não há chave estrangeira direta nas tabelas
3. **Hierarquia Financeira**: O controle financeiro é rigoroso (empenho → nota), mas o controle físico (nota → material) é mais solto

### 4. Limitações Identificadas

1. **Falta de Rastreabilidade Direta**: Não há campo na tabela de materiais que indique diretamente de qual nota fiscal ou empenho o material veio
2. **Controle Conceitual**: A ligação entre materiais e notas fiscais é feita conceitualmente durante o cadastro, mas não é armazenada de forma estrutural no banco de dados
3. **Relatórios Limitados**: Isso pode dificultar a geração de relatórios que cruzem informações de empenhos, notas fiscais e materiais de forma automatizada

### 5. Melhorias Implementadas

1. **Adicionar Chave Estrangeira**: 
   - Incluído um campo `nota_fiscal` na tabela `almoxarifado_materiais` para vincular diretamente os materiais às notas fiscais
   - Criada chave estrangeira para garantir a integridade referencial
   - Atualizados todos os scripts PHP para utilizar o novo campo de vinculação direta
   - Modificada a interface do usuário para exibir e gerenciar a vinculação direta

2. **Campos de Auditoria**: 
   - Adicionados campos `data_criacao` e `usuario_criacao` na tabela `almoxarifado_materiais`
   - Criada chave estrangeira para vincular ao usuário de criação
   - Atualizados os scripts PHP para registrar automaticamente as informações de auditoria
   - Modificada a interface do usuário para exibir as informações de auditoria

3. **Histórico de Saldo**: 
   - Criada tabela `empenhos_saldo_historico` para armazenar o histórico de alterações de saldo dos empenhos
   - Implementada página para visualizar o histórico de alterações
   - Modificado o processo de entrada de materiais para registrar automaticamente as alterações de saldo
   - Criado script para migrar dados existentes para o histórico

### 6. Benefícios Obtidos

1. **Melhor Rastreabilidade**: Agora é possível identificar exatamente de qual nota/empenho veio cada material
2. **Controle Financeiro Aprimorado**: O histórico de alterações de saldo permite auditorias mais precisas
3. **Auditoria Completa**: As informações de criação permitem rastrear quando e por quem cada material foi criado
4. **Relatórios Melhorados**: As melhorias facilitam a geração de relatórios que cruzam informações de empenhos, notas fiscais e materiais