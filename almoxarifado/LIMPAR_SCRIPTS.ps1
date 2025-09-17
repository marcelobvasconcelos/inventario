# Script de Limpeza - Atualização de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

Write-Host "=== Script de Limpeza ===" -ForegroundColor Green
Write-Host "Atualização de Segurança - Módulo Almoxarifado"
Write-Host "Data: 12/09/2025"
Write-Host ""

# Verificar se a atualização foi aplicada com sucesso
Write-Host "Verificando se a atualização foi aplicada..." -ForegroundColor Cyan
if (Test-Path "almoxarifado\CHECK_SECURITY.ps1") {
    .\almoxarifado\CHECK_SECURITY.ps1 > $null 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Atualização aplicada com sucesso" -ForegroundColor Green
        
        # Remover scripts temporários (opcional)
        Write-Host ""
        Write-Host "Removendo scripts temporários..." -ForegroundColor Cyan
        Remove-Item -Path "almoxarifado\DEPLOY_AUTOMATICO.bat" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\DEPLOY_AUTOMATICO.ps1" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\VERIFICACAO_POS_DEPLOY.sh" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\VERIFICACAO_POS_DEPLOY.ps1" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\CHECK_SECURITY.bat" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\CHECK_SECURITY.ps1" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\INSTALAR_ATUALIZACAO.bat" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\INSTALAR_ATUALIZACAO.ps1" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\LIMPAR_SCRIPTS.bat" -Force -ErrorAction SilentlyContinue
        Remove-Item -Path "almoxarifado\LIMPAR_SCRIPTS.ps1" -Force -ErrorAction SilentlyContinue
        
        Write-Host "✅ Scripts temporários removidos" -ForegroundColor Green
        
        # Manter apenas os arquivos importantes
        Write-Host ""
        Write-Host "Mantendo arquivos importantes:" -ForegroundColor White
        Write-Host "- Documentação: ATUALIZACAO_SEGURANCA_NOTIFICACOES.md" -ForegroundColor White
        Write-Host "- Documentação: MIGRACAO_SEGURANCA.md" -ForegroundColor White
        Write-Host "- Documentação: RESUMO_ATUALIZACAO_SEGURANCA.md" -ForegroundColor White
        Write-Host "- Changelog: docs/CHANGELOG.md" -ForegroundColor White
        Write-Host "- Backup: almoxarifado/notificacoes.php.backup.*" -ForegroundColor White
        
        Write-Host ""
        Write-Host "✅ Limpeza concluída com sucesso!" -ForegroundColor Green
        
    } else {
        Write-Host "❌ Atualização não aplicada corretamente" -ForegroundColor Red
        Write-Host "❌ Limpeza cancelada por segurança" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "⚠️  Script de verificação não encontrado" -ForegroundColor Yellow
    Write-Host "⚠️  Limpeza cancelada por segurança" -ForegroundColor Yellow
    exit 1
}