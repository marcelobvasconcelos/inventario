# INFORMAÇÕES DE TESTE - ATUALIZAÇÃO DE SEGURANÇA ALMOXARIFADO
Versão 1.0 - 12/09/2025

## OBJETIVO
Este arquivo contém informações para testar a correção de segurança implementada no módulo almoxarifado.

## CENÁRIOS DE TESTE

### Cenário 1: Usuário Comum
**Objetivo**: Verificar que o usuário só vê notificações de suas próprias requisições

**Passos**:
1. Faça login como usuário comum
2. Acesse a página de notificações (`almoxarifado/notificacoes.php`)
3. Verifique que apenas notificações de requisições criadas por este usuário são exibidas
4. Tente acessar notificações de outros usuários (não deve ser possível)

**Resultado esperado**: 
- ✅ Apenas notificações do próprio usuário são exibidas
- ❌ Não é possível acessar notificações de outros usuários

### Cenário 2: Administrador
**Objetivo**: Verificar que o administrador continua tendo acesso às funcionalidades administrativas

**Passos**:
1. Faça login como administrador
2. Acesse a página de administração de notificações (`almoxarifado/admin_notificacoes.php`)
3. Verifique que todas as notificações estão disponíveis
4. Acesse a página de notificações pessoais (`almoxarifado/notificacoes.php`)
5. Verifique que apenas notificações de requisições criadas por este administrador são exibidas

**Resultado esperado**: 
- ✅ Página administrativa funciona normalmente
- ✅ Página de notificações pessoais respeita a restrição de segurança

### Cenário 3: Usuário Almoxarife
**Objetivo**: Verificar que o almoxarife tem acesso apropriado

**Passos**:
1. Faça login como almoxarife
2. Acesse a página de administração de notificações (`almoxarifado/admin_notificacoes.php`)
3. Verifique que todas as notificações estão disponíveis
4. Acesse a página de notificações pessoais (`almoxarifado/notificacoes.php`)
5. Verifique que apenas notificações de requisições criadas por este almoxarife são exibidas

**Resultado esperado**: 
- ✅ Página administrativa funciona normalmente
- ✅ Página de notificações pessoais respeita a restrição de segurança

## DADOS DE TESTE

### Usuários de Teste
| Tipo de Usuário | Nome | Email | Senha |
|------------------|------|-------|-------|
| Administrador | Admin Teste | admin@teste.com | senha123 |
| Almoxarife | Almoxarife Teste | almoxarife@teste.com | senha123 |
| Usuário Comum | Usuario Teste | usuario@teste.com | senha123 |

### Requisições de Teste
| ID | Usuário Criador | Status | Descrição |
|----|-----------------|--------|-----------|
| REQ-001 | Admin Teste | Aprovada | Requisição de teste do administrador |
| REQ-002 | Almoxarife Teste | Pendente | Requisição de teste do almoxarife |
| REQ-003 | Usuario Teste | Rejeitada | Requisição de teste do usuário comum |

## VERIFICAÇÕES TÉCNICAS

### 1. Verificação da Consulta SQL
**Arquivo**: `almoxarifado/notificacoes.php`
**Linha**: ~137-151

**Consulta esperada**:
```sql
SELECT 
    arn.id as notificacao_id,
    arn.requisicao_id,
    arn.mensagem,
    arn.status as notificacao_status,
    arn.data_criacao,
    ar.status_notificacao as requisicao_status,
    ar.justificativa,
    ar.usuario_id as requisicao_usuario_id  -- Campo adicionado
FROM almoxarifado_requisicoes_notificacoes arn
JOIN almoxarifado_requisicoes ar ON arn.requisicao_id = ar.id
WHERE arn.usuario_destino_id = ?           -- Verificação do destinatário
AND ar.usuario_id = ?                       -- Verificação do criador (NOVO)
AND arn.id = (
    SELECT MAX(arn2.id)
    FROM almoxarifado_requisicoes_notificacoes arn2
    WHERE arn2.requisicao_id = arn.requisicao_id
    AND arn2.usuario_destino_id = ?
)
ORDER BY arn.data_criacao DESC
LIMIT 50
```

### 2. Verificação da Execução
**Arquivo**: `almoxarifado/notificacoes.php`
**Linha**: ~154

**Execução esperada**:
```php
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_logado_id, $usuario_logado_id, $usuario_logado_id]); // 3 parâmetros (NOVO)
```

## REGRESSÕES EVITADAS

### Antes da Correção
- ✅ Usuários podiam ver notificações de outros usuários
- ❌ Violação de privacidade de dados
- ❌ Exposição de informações sensíveis

### Depois da Correção
- ✅ Usuários só veem notificações de suas próprias requisições
- ✅ Privacidade de dados mantida
- ✅ Segurança reforçada
- ✅ Compatibilidade mantida com funcionalidades administrativas

## MONITORAMENTO

### Logs a Serem Monitorados
1. `logs/error.log` - Erros de acesso ou permissão
2. `logs/access.log` - Tentativas suspeitas de acesso
3. `logs/application.log` - Problemas na aplicação

### Alertas de Segurança
- Qualquer erro de "Access Denied" após a atualização
- Tentativas repetidas de acesso a notificações inexistentes
- Acessos fora do horário comercial suspeitos

## SUPORTE

### Em Caso de Problemas
1. Restaurar backup: `almoxarifado/notificacoes.php.backup.[DATA_HORA]`
2. Consultar documentação: `MIGRACAO_SEGURANCA.md`
3. Executar verificação: `CHECK_SECURITY.bat/.ps1`
4. Contatar equipe de desenvolvimento

---
*Documento criado em 12/09/2025 para fins de teste e verificação*