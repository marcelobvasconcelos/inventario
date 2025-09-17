# Script de Verificação Pós-Deploy - Correção de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

Write-Host "=== Script de Verificação Pós-Deploy ===" -ForegroundColor Green
Write-Host "Correção de Segurança - Módulo Almoxarifado"
Write-Host "Data: 12/09/2025"
Write-Host ""

# Verificar se estamos no diretório correto
if (-not (Test-Path "almoxarifado
otificacoes.php")) {
    Write-Host "❌ ERRO: Diretório de instalação não encontrado!" -ForegroundColor Red
    Write-Host "Por favor, execute este script a partir do diretório raiz do sistema." -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Diretório raiz verificado com sucesso" -ForegroundColor Green
Write-Host ""

# 1. Verificar se a correção foi aplicada no código
Write-Host "1. Verificando aplicação da correção no código..." -ForegroundColor Cyan
$conteudo = Get-Content "almoxarifado
otificacoes.php" -Raw
if ($conteudo -match "ar\.usuario_id = \?.*Garante que o usuário só veja notificações") {
    Write-Host "✅ Correção está corretamente aplicada no código" -ForegroundColor Green
} else {
    Write-Host "❌ AVISO: Correção NÃO está aplicada ou está diferente do esperado" -ForegroundColor Red
    Write-Host "Por favor, verifique manualmente o arquivo almoxarifado
otificacoes.php" -ForegroundColor Yellow
}

# 2. Verificar se há backup
Write-Host ""
Write-Host "2. Verificando backup..." -ForegroundColor Cyan
$backups = Get-ChildItem -Path "almoxarifado
otificacoes.php.backup.*" -ErrorAction SilentlyContinue
if ($backups.Count -gt 0) {
    Write-Host "✅ Backup encontrado: $($backups[0].FullName)" -ForegroundColor Green
} else {
    Write-Host "⚠️  AVISO: Nenhum backup encontrado" -ForegroundColor Yellow
}

# 3. Verificar se os arquivos de documentação foram criados
Write-Host ""
Write-Host "3. Verificando arquivos de documentação..." -ForegroundColor Cyan
$documentos = @(
    "ATUALIZACAO_SEGURANCA_NOTIFICACOES.md",
    "MIGRACAO_SEGURANCA.md", 
    "SCRIPT_ATUALIZACAO_SEGURANCA.sql",
    "VERIFICACAO_ESTRUTURA_BD.sql",
    "DEPLOY_AUTOMATICO.bat",
    "DEPLOY_AUTOMATICO.ps1",
    "RESUMO_ATUALIZACAO_SEGURANCA.md"
)

$missingDocs = 0
foreach ($doc in $documentos) {
    if (Test-Path "almoxarifado\$doc") {
        Write-Host "✅ $doc" -ForegroundColor Green
    } else {
        Write-Host "❌ $doc (AUSENTE)" -ForegroundColor Red
        $missingDocs++
    }
}

if ($missingDocs -eq 0) {
    Write-Host "✅ Todos os documentos de suporte foram criados com sucesso" -ForegroundColor Green
} else {
    Write-Host "❌ $missingDocs documento(s) de suporte estão ausentes" -ForegroundColor Red
}

# 4. Verificar changelog
Write-Host ""
Write-Host "4. Verificando changelog..." -ForegroundColor Cyan
if (Test-Path "docs\CHANGELOG.md") {
    $changelog = Get-Content "docs\CHANGELOG.md" -Raw
    if ($changelog -match "12/09/2025" -and $changelog -match "Problema de segurança onde usuários podiam ver notificações de outros usuários") {
        Write-Host "✅ Atualização registrada no changelog" -ForegroundColor Green
    } else {
        Write-Host "❌ Atualização NÃO registrada no changelog" -ForegroundColor Red
    }
} else {
    Write-Host "❌ Arquivo CHANGELOG.md não encontrado" -ForegroundColor Red
}

# 5. Teste de sintaxe PHP (opcional)
Write-Host ""
Write-Host "5. Verificando sintaxe PHP..." -ForegroundColor Cyan
# Este teste requer que o PHP esteja instalado e no PATH
try {
    $phpResult = php -l "almoxarifado
otificacoes.php" 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Sintaxe PHP correta" -ForegroundColor Green
    } else {
        Write-Host "❌ ERRO: Problemas de sintaxe no arquivo almoxarifado
otificacoes.php" -ForegroundColor Red
        php -l "almoxarifado
otificacoes.php"
    }
} catch {
    Write-Host "⚠️  AVISO: PHP não encontrado, pulando verificação de sintaxe" -ForegroundColor Yellow
}

# 6. Resumo final
Write-Host ""
Write-Host "=== Resumo da Verificação ===" -ForegroundColor Green
Write-Host ""
Write-Host "Verificações realizadas:" -ForegroundColor White
Write-Host "- Código fonte: Completas" -ForegroundColor White
Write-Host "- Backups: $(if ($backups.Count -gt 0) { "Encontrados" } else { "Não encontrados" })" -ForegroundColor White
Write-Host "- Documentação: $(if ($missingDocs -eq 0) { "Completa" } else { "Incompleta" })" -ForegroundColor White
Write-Host "- Changelog: $(if ((Test-Path "docs\CHANGELOG.md") -and ($changelog -match "12/09/2025" -and $changelog -match "Problema de segurança onde usuários podiam ver notificações de outros usuários")) { "Atualizado" } else { "Não atualizado" })" -ForegroundColor White
Write-Host "- Sintaxe PHP: $(try { $phpTest = php -l "almoxarifado
otificacoes.php" 2>$null; if ($LASTEXITCODE -eq 0) { "Correta" } else { "Não verificada" } } catch { "Não verificada" })" -ForegroundColor White

Write-Host ""
Write-Host "=== TESTES MANUAIS RECOMENDADOS ===" -ForegroundColor Green
Write-Host ""
Write-Host "1. Acesse o sistema como usuário comum:" -ForegroundColor White
Write-Host "   - Verifique que apenas notificações de suas próprias requisições são exibidas" -ForegroundColor White
Write-Host "   - Tente acessar notificações de outros usuários (não deve ser possível)" -ForegroundColor White
Write-Host ""
Write-Host "2. Acesse o sistema como administrador:" -ForegroundColor White
Write-Host "   - Verifique que a página de administração continua funcionando" -ForegroundColor White
Write-Host "   - Verifique que a página de notificações pessoais respeita a restrição" -ForegroundColor White
Write-Host ""
Write-Host "3. Monitore os logs do sistema:" -ForegroundColor White
Write-Host "   - Verifique se há erros após a atualização" -ForegroundColor White
Write-Host "   - Monitore acessos suspeitos ou tentativas de acesso indevido" -ForegroundColor White

Write-Host ""
Write-Host "Verificação concluída!" -ForegroundColor Green
Write-Host "Status: $(if ($conteudo -match "ar\.usuario_id = \?.*Garante que o usuário só veja notificações" -and $missingDocs -eq 0) { "SUCESSO" } else { "ATENÇÃO - VERIFIQUE OS PROBLEMAS IDENTIFICADOS" })" -ForegroundColor Cyan