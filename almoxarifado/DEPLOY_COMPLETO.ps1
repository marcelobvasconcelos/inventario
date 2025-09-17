# Script de Deploy Completo - Atualização de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

Clear-Host
Write-Host \"===============================================\" -ForegroundColor Cyan
Write-Host \"  DEPLOY COMPLETO - ATUALIZAÇÃO DE SEGURANÇA   \" -ForegroundColor Cyan
Write-Host \"           MÓDULO ALMOXARIFADO                 \" -ForegroundColor Cyan
Write-Host \"              Versão 1.0                       \" -ForegroundColor Cyan
Write-Host \"            Data: 12/09/2025                  \" -ForegroundColor Cyan
Write-Host \"===============================================\" -ForegroundColor Cyan
Write-Host \"\"

# Função para pausar execução
function Pause {
    Write-Host \"Pressione Enter para continuar...\" -ForegroundColor Yellow
    Read-Host
}

# Verificar ambiente
Write-Host \"🔍 VERIFICANDO AMBIENTE...\" -ForegroundColor Cyan
if (-not (Test-Path \"almoxarifado\\notificacoes.php\")) {
    Write-Host \"❌ ERRO: Diretório raiz não encontrado!\" -ForegroundColor Red
    Write-Host \"Por favor, execute este script a partir do diretório raiz do sistema.\" -ForegroundColor Yellow
    exit 1
}
Write-Host \"✅ Ambiente verificado com sucesso\" -ForegroundColor Green
Write-Host \"\"

# Menu principal
do {
    Write-Host \"=== MENU PRINCIPAL ===\" -ForegroundColor White
    Write-Host \"1. Instalar atualização de segurança\" -ForegroundColor White
    Write-Host \"2. Verificar atualização instalada\" -ForegroundColor White
    Write-Host \"3. Verificação completa pós-deploy\" -ForegroundColor White
    Write-Host \"4. Limpar scripts temporários\" -ForegroundColor White
    Write-Host \"5. Informações sobre a atualização\" -ForegroundColor White
    Write-Host \"6. Sair\" -ForegroundColor White
    Write-Host \"\"
    $opcao = Read-Host \"Escolha uma opção (1-6)\"
    
    switch ($opcao) {
        1 {
            Write-Host \"\"
            Write-Host \"🔧 INSTALANDO ATUALIZAÇÃO...\" -ForegroundColor Cyan
            Write-Host \"\"
            
            # Criar backup
            Write-Host \"1. Criando backup...\" -ForegroundColor Cyan
            $DATA_HORA = Get-Date -Format \"yyyyMMdd_HHmmss\"
            Copy-Item \"almoxarifado\\notificacoes.php\" \"almoxarifado\\notificacoes.php.backup.$DATA_HORA\"
            Write-Host \"✅ Backup criado: almoxarifado\\notificacoes.php.backup.$DATA_HORA\" -ForegroundColor Green
            Write-Host \"\"
            
            # Instruções manuais
            Write-Host \"2. APLICANDO CORREÇÃO...\" -ForegroundColor Cyan
            Write-Host \"⚠️  ATENÇÃO: A aplicação da correção deve ser feita manualmente!\" -ForegroundColor Yellow
            Write-Host \"Por favor, consulte o arquivo MIGRACAO_SEGURANCA.md para instruções detalhadas.\" -ForegroundColor Yellow
            Write-Host \"\"
            Write-Host \"ALTERAÇÕES NECESSÁRIAS:\" -ForegroundColor White
            Write-Host \"- Adicionar 'ar.usuario_id as requisicao_usuario_id' à seleção de campos\" -ForegroundColor White
            Write-Host \"- Adicionar 'AND ar.usuario_id = ?' à cláusula WHERE\" -ForegroundColor White
            Write-Host \"- Atualizar execute() para receber 3 parâmetros em vez de 2\" -ForegroundColor White
            Write-Host \"\"
            Pause
            
            # Verificar aplicação
            Write-Host \"3. VERIFICANDO APLICAÇÃO...\" -ForegroundColor Cyan
            if (Test-Path \"almoxarifado\\CHECK_SECURITY.ps1\") {
                .\\almoxarifado\\CHECK_SECURITY.ps1
                if ($LASTEXITCODE -eq 0) {
                    Write-Host \"✅ Correção aplicada com sucesso!\" -ForegroundColor Green
                } else {
                    Write-Host \"❌ Falha na aplicação da correção\" -ForegroundColor Red
                    Write-Host \"Por favor, verifique manualmente o arquivo notificacoes.php\" -ForegroundColor Yellow
                }
            } else {
                Write-Host \"❌ Script de verificação não encontrado\" -ForegroundColor Red
            }
            Write-Host \"\"
            Pause
        }
        
        2 {
            Write-Host \"\"
            Write-Host \"🔍 VERIFICANDO ATUALIZAÇÃO INSTALADA...\" -ForegroundColor Cyan
            Write-Host \"\"
            
            if (Test-Path \"almoxarifado\\CHECK_SECURITY.ps1\") {
                .\\almoxarifado\\CHECK_SECURITY.ps1
            } else {
                Write-Host \"❌ Script de verificação não encontrado\" -ForegroundColor Red
            }
            Write-Host \"\"
            Pause
        }
        
        3 {
            Write-Host \"\"
            Write-Host \"📋 VERIFICAÇÃO COMPLETA PÓS-DEPLOY...\" -ForegroundColor Cyan
            Write-Host \"\"
            
            if (Test-Path \"almoxarifado\\VERIFICACAO_POS_DEPLOY.ps1\") {
                .\\almoxarifado\\VERIFICACAO_POS_DEPLOY.ps1
            } else {
                Write-Host \"❌ Script de verificação completa não encontrado\" -ForegroundColor Red
            }
            Write-Host \"\"
            Pause
        }
        
        4 {
            Write-Host \"\"
            Write-Host \"🧹 LIMPANDO SCRIPTS TEMPORÁRIOS...\" -ForegroundColor Cyan
            Write-Host \"\"
            
            if (Test-Path \"almoxarifado\\LIMPAR_SCRIPTS.ps1\") {
                .\\almoxarifado\\LIMPAR_SCRIPTS.ps1
            } else {
                Write-Host \"❌ Script de limpeza não encontrado\" -ForegroundColor Red
            }
            Write-Host \"\"
            Pause
        }
        
        5 {
            Write-Host \"\"
            Write-Host \"ℹ️  INFORMAÇÕES SOBRE A ATUALIZAÇÃO\" -ForegroundColor Cyan
            Write-Host \"\"
            Write-Host \"📄 Documentação disponível:\" -ForegroundColor White
            Write-Host \"   - MIGRACAO_SEGURANCA.md (Guia completo)\" -ForegroundColor White
            Write-Host \"   - ATUALIZACAO_SEGURANCA_NOTIFICACOES.md (Detalhes técnicos)\" -ForegroundColor White
            Write-Host \"   - RESUMO_ATUALIZACAO_SEGURANCA.md (Resumo executivo)\" -ForegroundColor White
            Write-Host \"\"
            Write-Host \"🛡️  Problema corrigido:\" -ForegroundColor White
            Write-Host \"   Usuários podiam ver notificações de outros usuários\" -ForegroundColor White
            Write-Host \"\"
            Write-Host \"🔧 Solução implementada:\" -ForegroundColor White
            Write-Host \"   Verificação dupla na consulta SQL:\" -ForegroundColor White
            Write-Host \"   1. Destinatário da notificação (usuario_destino_id)\" -ForegroundColor White
            Write-Host \"   2. Criador da requisição (usuario_id)\" -ForegroundColor White
            Write-Host \"\"
            Write-Host \"📅 Data da correção: 12/09/2025\" -ForegroundColor White
            Write-Host \"🔢 Versão: 1.0\" -ForegroundColor White
            Write-Host \"\"
            Pause
        }
        
        6 {
            Write-Host \"\"
            Write-Host \"👋 Até logo!\" -ForegroundColor Green
            Write-Host \"Obrigado por usar o Deploy Automático de Segurança - Almoxarifado\" -ForegroundColor Green
            exit 0
        }
        
        default {
            Write-Host \"\"
            Write-Host \"❌ Opção inválida. Por favor, escolha uma opção de 1 a 6.\" -ForegroundColor Red
            Write-Host \"\"
            Pause
        }
    }
    
    Write-Host \"===============================================\" -ForegroundColor Cyan
    Write-Host \"\"
} while ($true)