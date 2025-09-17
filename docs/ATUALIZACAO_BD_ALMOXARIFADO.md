# Atualização do Banco de Dados - Módulo de Almoxarifado

## Versão: 1.0
## Data: 16/09/2025

## Descrição
Este documento descreve as últimas modificações realizadas no banco de dados para implementação das melhorias no módulo de almoxarifado, conforme especificado no documento `docs/ALMOXARIFADO_FINANCEIRO.md`.

## Alterações Realizadas

### 1. Campos de Vinculação Direta
- **Tabela**: `almoxarifado_materiais`
- **Campo adicionado**: `nota_fiscal` (VARCHAR 50, NULL)
- **Chave estrangeira**: Vinculada ao campo `nota_numero` da tabela `notas_fiscais`
- **Objetivo**: Permitir vincular diretamente os materiais às notas fiscais

### 2. Campos de Auditoria
- **Tabela**: `almoxarifado_materiais`
- **Campos adicionados**:
  - `data_criacao` (TIMESTAMP, NULL)
  - `usuario_criacao` (INT 11, NULL)
- **Chave estrangeira**: Vinculada ao campo `id` da tabela `usuarios`
- **Objetivo**: Registrar quando e por quem cada material foi criado

### 3. Tabela de Histórico de Alterações de Saldo
- **Tabela criada**: `empenhos_saldo_historico`
- **Campos**:
  - `id` (INT 11, AUTO_INCREMENT, PK)
  - `empenho_numero` (VARCHAR 50, FK para `empenhos_insumos.numero`)
  - `saldo_anterior` (DECIMAL 10,2)
  - `saldo_novo` (DECIMAL 10,2)
  - `valor_alteracao` (DECIMAL 10,2)
  - `tipo_alteracao` (ENUM: entrada, saida, ajuste)
  - `referencia_id` (INT 11, NULL)
  - `referencia_tipo` (VARCHAR 50, NULL)
  - `usuario_id` (INT 11, FK para `usuarios.id`)
  - `data_alteracao` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
  - `descricao` (TEXT, NULL)
- **Objetivo**: Armazenar o histórico completo de alterações de saldo dos empenhos

### 4. Índices Otimizados
- **Tabela**: `almoxarifado_materiais`
- **Índices adicionados**:
  - `idx_nota_fiscal` no campo `nota_fiscal`
  - `idx_usuario_criacao` no campo `usuario_criacao`
- **Objetivo**: Melhorar a performance das consultas

### 5. Atualização de Dados Existentes
- **Registros atualizados**: Todos os registros da tabela `almoxarifado_materiais`
- **Campos preenchidos**:
  - `data_criacao`: Preenchido com a data de cadastro existente
  - `usuario_criacao`: Preenchido com o ID do usuário administrador padrão (ID 2)
  - `nota_fiscal`: Preenchido com base nas entradas registradas

## Script de Atualização
O script completo de atualização está disponível em: `bd/atualizacao_bd_almoxarifado_producao.sql`

## Instruções para Implementação em Produção

1. **Backup do Banco de Dados**: Faça um backup completo do banco de dados antes de aplicar as alterações

2. **Execução do Script**: Execute o script `bd/atualizacao_bd_almoxarifado_producao.sql` no banco de dados de produção:
   ```bash
   mysql -u [usuario] -p [banco_de_dados] < bd/atualizacao_bd_almoxarifado_producao.sql
   ```

3. **Atualização dos Scripts PHP**: Certifique-se de que todos os scripts PHP foram atualizados para utilizar os novos campos

4. **Atualização da Interface do Usuário**: Verifique se a interface do usuário foi atualizada para exibir e gerenciar as novas funcionalidades

5. **População do Histórico**: Execute o script PHP para popular o histórico de alterações de saldo dos empenhos:
   ```bash
   cd inventario/ignorar
   php popular_historico_empenhos.php
   ```

## Considerações Finais
Estas alterações implementam as melhorias sugeridas no documento `docs/ALMOXARIFADO_FINANCEIRO.md`, resolvendo as limitações identificadas anteriormente e aprimorando significativamente a rastreabilidade e o controle financeiro do módulo de almoxarifado.