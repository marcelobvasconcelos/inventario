# Script de Implantação Automatizada - Correção de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

# Verificar se estamos no diretório correto
if (-not (Test-Path "almoxarifado\\notificacoes.php")) {
    Write-Host "Erro: Diretório de instalação não encontrado!" -ForegroundColor Red
    Write-Host "Por favor, execute este script a partir do diretório raiz do sistema." -ForegroundColor Yellow
    exit 1
}

Write-Host "=== Script de Implantação Automatizada ===" -ForegroundColor Green
Write-Host "Correção de Segurança - Módulo Almoxarifado"
Write-Host "Data: 12/09/2025"
Write-Host ""

# 1. Criar backup dos arquivos
Write-Host "1. Criando backups..." -ForegroundColor Cyan
$DATA_HORA = Get-Date -Format "yyyyMMdd_HHmmss"
Copy-Item "almoxarifado\\notificacoes.php" "almoxarifado\\notificacoes.php.backup.$DATA_HORA"
Write-Host "Backup criado: almoxarifado\\notificacoes.php.backup.$DATA_HORA" -ForegroundColor Green

# 2. Aplicar correções no código PHP
Write-Host ""
Write-Host "2. Aplicando correções no código..." -ForegroundColor Cyan

# Verificar se a correção já foi aplicada
$conteudo = Get-Content "almoxarifado\\notificacoes.php" -Raw
if ($conteudo -match "ar\\.usuario_id = \\?") {
    Write-Host "Aviso: Correção já parece estar aplicada. Pulando etapa." -ForegroundColor Yellow
} else {
    # Fazer backup do script original antes da modificação
    Copy-Item "almoxarifado\\notificacoes.php" "almoxarifado\\notificacoes_original.php.$DATA_HORA"
    
    # Ler o conteúdo do arquivo
    $conteudo = Get-Content "almoxarifado\\notificacoes.php" -Raw
    
    # Aplicar as correções
    # Adicionar usuario_id na seleção de campos
    $conteudo = $conteudo -replace "(        ar\\.justificativa)", "`$1,`n        ar.usuario_id as requisicao_usuario_id"
    
    # Adicionar verificação de usuário criador
    $conteudo = $conteudo -replace "(WHERE arn\\.usuario_destino_id = \\?)", "`$1`n    AND ar.usuario_id = ?  -- Garante que o usuário só veja notificações de requisições que ele mesmo criou"
    
    # Atualizar a execução da consulta
    $conteudo = $conteudo -replace "(\\$stmt->execute\\(\\[\\\$usuario_logado_id, \\\$usuario_logado_id\\]\\);)", "`$stmt->execute([`$usuario_logado_id, `$usuario_logado_id, `$usuario_logado_id]);"
    
    # Salvar o conteúdo modificado
    $conteudo | Out-File -FilePath "almoxarifado\\notificacoes.php" -Encoding UTF8
    
    Write-Host "Correções aplicadas com sucesso!" -ForegroundColor Green
}

# 3. Verificar estrutura do banco de dados
Write-Host ""
Write-Host "3. Verificando estrutura do banco de dados..." -ForegroundColor Cyan
Write-Host "Execute o script VERIFICACAO_ESTRUTURA_BD.sql no seu cliente MySQL" -ForegroundColor Yellow

# 4. Mensagens finais
Write-Host ""
Write-Host "=== Implantação Concluída ===" -ForegroundColor Green
Write-Host ""
Write-Host "Resumo das alterações:" -ForegroundColor White
Write-Host "- Backup criado: almoxarifado\\notificacoes.php.backup.$DATA_HORA" -ForegroundColor White
Write-Host "- Correções de segurança aplicadas" -ForegroundColor White
Write-Host "- Documentação atualizada" -ForegroundColor White
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor White
Write-Host "1. Teste a funcionalidade como usuário comum" -ForegroundColor White
Write-Host "2. Teste a funcionalidade como administrador" -ForegroundColor White
Write-Host "3. Monitore os logs do sistema por eventuais problemas" -ForegroundColor White
Write-Host ""
Write-Host "Em caso de problemas, restaure o backup:" -ForegroundColor Yellow
Write-Host "Copy-Item almoxarifado\\notificacoes.php.backup.$DATA_HORA almoxarifado\\notificacoes.php" -ForegroundColor Yellow
Write-Host ""
Write-Host "Implantação concluída com sucesso!" -ForegroundColor Green