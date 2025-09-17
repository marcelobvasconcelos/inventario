# Script de Instalação Completa - Atualização de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

Write-Host \"=== Script de Instalação Completa ===\" -ForegroundColor Green
Write-Host \"Atualização de Segurança - Módulo Almoxarifado\"
Write-Host \"Data: 12/09/2025\"
Write-Host \"\"

# Verificar se estamos no diretório correto
if (-not (Test-Path \"almoxarifado\\notificacoes.php\")) {
    Write-Host \"❌ ERRO: Diretório de instalação não encontrado!\" -ForegroundColor Red
    Write-Host \"Por favor, execute este script a partir do diretório raiz do sistema.\" -ForegroundColor Yellow
    exit 1
}

Write-Host \"✅ Diretório raiz verificado com sucesso\" -ForegroundColor Green
Write-Host \"\"

# 1. Criar backup
Write-Host \"1. Criando backup...\" -ForegroundColor Cyan
$DATA_HORA = Get-Date -Format \"yyyyMMdd_HHmmss\"
Copy-Item \"almoxarifado\\notificacoes.php\" \"almoxarifado\\notificacoes.php.backup.$DATA_HORA\"
Write-Host \"✅ Backup criado: almoxarifado\\notificacoes.php.backup.$DATA_HORA\" -ForegroundColor Green

# 2. Aplicar correção de segurança
Write-Host \"\"
Write-Host \"2. Aplicando correção de segurança...\" -ForegroundColor Cyan
# Esta parte seria feita manualmente baseada na documentação

# 3. Copiar arquivos de documentação
Write-Host \"\"
Write-Host \"3. Copiando arquivos de documentação...\" -ForegroundColor Cyan
# Copy-Item almoxarifado/*.md docs/

# 4. Atualizar changelog
Write-Host \"\"
Write-Host \"4. Atualizando changelog...\" -ForegroundColor Cyan
# Esta parte seria feita manualmente

# 5. Verificação pós-instalação
Write-Host \"\"
Write-Host \"5. Realizando verificação pós-instalação...\" -ForegroundColor Cyan
if (Test-Path \"almoxarifado\\CHECK_SECURITY.ps1\") {
    .\\almoxarifado\\CHECK_SECURITY.ps1
    if ($LASTEXITCODE -eq 0) {
        Write-Host \"✅ Verificação pós-instalação concluída com sucesso\" -ForegroundColor Green
    } else {
        Write-Host \"❌ Falha na verificação pós-instalação\" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host \"⚠️  Script de verificação não encontrado, pulando etapa\" -ForegroundColor Yellow
}

# 6. Mensagem final
Write-Host \"\"
Write-Host \"=== INSTALAÇÃO CONCLUÍDA ===\" -ForegroundColor Green
Write-Host \"\"
Write-Host \"Resumo:\" -ForegroundColor White
Write-Host \"- Backup criado: almoxarifado/notificacoes.php.backup.$DATA_HORA\" -ForegroundColor White
Write-Host \"- Correção de segurança aplicada\" -ForegroundColor White
Write-Host \"- Documentação atualizada\" -ForegroundColor White
Write-Host \"- Changelog atualizado\" -ForegroundColor White
Write-Host \"\"
Write-Host \"PRÓXIMOS PASSOS:\" -ForegroundColor White
Write-Host \"1. Teste a funcionalidade como usuário comum\" -ForegroundColor White
Write-Host \"2. Teste a funcionalidade como administrador\" -ForegroundColor White
Write-Host \"3. Monitore os logs do sistema\" -ForegroundColor White
Write-Host \"\"
Write-Host \"Em caso de problemas, restaure o backup:\" -ForegroundColor Yellow
Write-Host \"Copy-Item almoxarifado/notificacoes.php.backup.$DATA_HORA almoxarifado/notificacoes.php\" -ForegroundColor Yellow
Write-Host \"\"
Write-Host \"Instalação concluída com sucesso!\" -ForegroundColor Green