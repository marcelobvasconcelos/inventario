# Script de Verificação Rápida - Correção de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

Write-Host \"=== Verificação Rápida de Segurança - Almoxarifado ===\" -ForegroundColor Green
Write-Host \"\"

# Verificar se o arquivo existe
if (-not (Test-Path \"almoxarifado\\notificacoes.php\")) {
    Write-Host \"❌ ERRO: Arquivo almoxarifado\\notificacoes.php não encontrado!\" -ForegroundColor Red
    exit 1
}

Write-Host \"✅ Arquivo encontrado\" -ForegroundColor Green

# Verificar se a correção está aplicada
$conteudo = Get-Content \"almoxarifado\\notificacoes.php\" -Raw
if ($conteudo -match \"ar\\.usuario_id = \\?.*Garante que o usuário só veja notificações\") {
    Write-Host \"✅ Correção de segurança aplicada\" -ForegroundColor Green
    
    # Verificar se a execução está correta
    if ($conteudo -match \"\\`$stmt->execute\\(\\[\\`$usuario_logado_id, \\`$usuario_logado_id, \\`$usuario_logado_id\\]\\);\") {
        Write-Host \"✅ Execução da consulta corretamente atualizada\" -ForegroundColor Green
        Write-Host \"\"
        Write-Host \"🎉 VERIFICAÇÃO CONCLUÍDA COM SUCESSO!\" -ForegroundColor Cyan
        Write-Host \"   A correção de segurança está corretamente aplicada.\" -ForegroundColor White
    } else {
        Write-Host \"❌ ERRO: Execução da consulta NÃO está atualizada\" -ForegroundColor Red
        Write-Host \"   A correção está parcialmente aplicada.\" -ForegroundColor Yellow
        exit 1
    }
} else {
    Write-Host \"❌ ERRO: Correção de segurança NÃO aplicada\" -ForegroundColor Red
    Write-Host \"   O arquivo precisa ser atualizado.\" -ForegroundColor Yellow
    exit 1
}