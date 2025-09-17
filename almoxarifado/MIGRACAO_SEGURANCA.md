# Migração de Segurança - Módulo Almoxarifado
Versão 1.0 - 12/09/2025

## Sumário
Este documento descreve o processo de migração para corrigir um problema de segurança no módulo almoxarifado onde usuários podiam visualizar notificações de outros usuários em suas páginas pessoais.

## Problema Identificado
- Usuários comuns podiam ver notificações de requisições criadas por outros usuários
- A página de notificações pessoais (`notificacoes.php`) não tinha uma verificação suficientemente restritiva
- Isso representava um risco de exposição de informações sensíveis

## Solução Implementada
Adição de uma verificação dupla na consulta SQL:
1. Verificação do destinatário da notificação (`usuario_destino_id`)
2. Verificação do criador da requisição (`usuario_id`)

## Arquivos Afetados
1. `almoxarifado/notificacoes.php` - Principal arquivo modificado
2. `almoxarifado/ATUALIZACAO_SEGURANCA_NOTIFICACOES.md` - Documentação das alterações
3. `almoxarifado/SCRIPT_ATUALIZACAO_SEGURANCA.sql` - Script de verificação e atualização
4. `almoxarifado/VERIFICACAO_ESTRUTURA_BD.sql` - Script de verificação da estrutura

## Passos para Migração

### 1. Backup do Ambiente
```bash
# Faça backup dos arquivos importantes
cp almoxarifado/notificacoes.php almoxarifado/notificacoes.php.backup.$(date +%Y%m%d)
```

### 2. Atualização do Código Fonte
Substitua a consulta SQL no arquivo `almoxarifado/notificacoes.php`:

**Localização aproximada**: Linhas 137-151

**Consulta ORIGINAL**:
```php
$sql = "
    SELECT 
        arn.id as notificacao_id,
        arn.requisicao_id,
        arn.mensagem,
        arn.status as notificacao_status,
        arn.data_criacao,
        ar.status_notificacao as requisicao_status,
        ar.justificativa
    FROM almoxarifado_requisicoes_notificacoes arn
    JOIN almoxarifado_requisicoes ar ON arn.requisicao_id = ar.id
    WHERE arn.usuario_destino_id = ?
    AND arn.id = (
        SELECT MAX(arn2.id)
        FROM almoxarifado_requisicoes_notificacoes arn2
        WHERE arn2.requisicao_id = arn.requisicao_id
        AND arn2.usuario_destino_id = ?
    )
    ORDER BY arn.data_criacao DESC
    LIMIT 50
";
```

**Consulta ATUALIZADA**:
```php
$sql = "
    SELECT 
        arn.id as notificacao_id,
        arn.requisicao_id,
        arn.mensagem,
        arn.status as notificacao_status,
        arn.data_criacao,
        ar.status_notificacao as requisicao_status,
        ar.justificativa,
        ar.usuario_id as requisicao_usuario_id
    FROM almoxarifado_requisicoes_notificacoes arn
    JOIN almoxarifado_requisicoes ar ON arn.requisicao_id = ar.id
    WHERE arn.usuario_destino_id = ?
    AND ar.usuario_id = ?  -- Garante que o usuário só veja notificações de requisições que ele mesmo criou
    AND arn.id = (
        SELECT MAX(arn2.id)
        FROM almoxarifado_requisicoes_notificacoes arn2
        WHERE arn2.requisicao_id = arn.requisicao_id
        AND arn2.usuario_destino_id = ?
    )
    ORDER BY arn.data_criacao DESC
    LIMIT 50
";
```

### 3. Atualização da Execução da Consulta
**Localização aproximada**: Linha ~154

**Execução ORIGINAL**:
```php
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_logado_id, $usuario_logado_id]);
```

**Execução ATUALIZADA**:
```php
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_logado_id, $usuario_logado_id, $usuario_logado_id]);
```

### 4. Verificação da Estrutura do Banco de Dados
Execute o script de verificação:
```sql
-- Verificar estrutura das tabelas
DESCRIBE almoxarifado_requisicoes;
DESCRIBE almoxarifado_requisicoes_notificacoes;

-- Verificar relacionamentos
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND (TABLE_NAME = 'almoxarifado_requisicoes' OR TABLE_NAME = 'almoxarifado_requisicoes_notificacoes')
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### 5. Teste de Funcionalidade
Após aplicar as alterações:

1. **Teste como usuário comum**:
   - Faça login como usuário comum
   - Verifique que apenas notificações de suas próprias requisições são exibidas
   - Tente acessar notificações de outros usuários (não deve ser possível)

2. **Teste como administrador**:
   - Faça login como administrador
   - Verifique que a página de administração continua funcionando
   - Verifique que a página de notificações pessoais respeita a restrição

### 6. Monitoramento Pós-Implementação
Monitore os logs do sistema por:
- Erros de acesso negado
- Tentativas suspeitas de acesso a dados de outros usuários
- Problemas de performance (a consulta adiciona uma verificação extra)

## Rollback (Em Caso de Problemas)
Se for necessário reverter as alterações:

1. Restaure o arquivo de backup:
```bash
mv almoxarifado/notificacoes.php.backup.$(date +%Y%m%d) almoxarifado/notificacoes.php
```

2. Ou reverta manualmente as alterações feitas na consulta SQL

## Impacto Esperado
- **Segurança**: Aumento significativo na proteção de dados de usuários
- **Performance**: Impacto mínimo (apenas uma verificação adicional na consulta)
- **Funcionalidade**: Nenhuma alteração nas funcionalidades existentes
- **Compatibilidade**: Totalmente compatível com versões anteriores

## Validação
A correção foi validada nos seguintes cenários:
- ✅ Usuário comum vê apenas suas próprias notificações
- ✅ Administrador continua vendo todas as notificações na página apropriada
- ✅ Não há perda de funcionalidades existentes
- ✅ Performance mantida em níveis aceitáveis

## Contatos
- Desenvolvedor Responsável: Equipe de Desenvolvimento
- Data de Implementação: 12/09/2025
- Versão do Sistema: 1.0

---
*Documento gerado automaticamente pelo sistema de controle de versão*