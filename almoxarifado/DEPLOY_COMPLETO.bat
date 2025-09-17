#!/bin/bash
# Script de Deploy Completo - Atualização de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

clear
echo "==============================================="
echo "  DEPLOY COMPLETO - ATUALIZAÇÃO DE SEGURANÇA   "
echo "           MÓDULO ALMOXARIFADO                 "
echo "              Versão 1.0                       "
echo "            Data: 12/09/2025                  "
echo "==============================================="
echo ""

# Função para pausar execução
pause() {
    read -p "Pressione Enter para continuar..."
}

# Verificar ambiente
echo "🔍 VERIFICANDO AMBIENTE..."
if [ ! -f "almoxarifado/notificacoes.php" ]; then
    echo "❌ ERRO: Diretório raiz não encontrado!"
    echo "Por favor, execute este script a partir do diretório raiz do sistema."
    exit 1
fi
echo "✅ Ambiente verificado com sucesso"
echo ""

# Menu principal
while true; do
    echo "=== MENU PRINCIPAL ==="
    echo "1. Instalar atualização de segurança"
    echo "2. Verificar atualização instalada"
    echo "3. Verificação completa pós-deploy"
    echo "4. Limpar scripts temporários"
    echo "5. Informações sobre a atualização"
    echo "6. Sair"
    echo ""
    read -p "Escolha uma opção (1-6): " opcao
    
    case $opcao in
        1)
            echo ""
            echo "🔧 INSTALANDO ATUALIZAÇÃO..."
            echo ""
            
            # Criar backup
            echo "1. Criando backup..."
            DATA_HORA=$(date +%Y%m%d_%H%M%S)
            cp almoxarifado/notificacoes.php almoxarifado/notificacoes.php.backup.$DATA_HORA
            echo "✅ Backup criado: almoxarifado/notificacoes.php.backup.$DATA_HORA"
            echo ""
            
            # Instruções manuais
            echo "2. APLICANDO CORREÇÃO..."
            echo "⚠️  ATENÇÃO: A aplicação da correção deve ser feita manualmente!"
            echo "Por favor, consulte o arquivo MIGRACAO_SEGURANCA.md para instruções detalhadas."
            echo ""
            echo "ALTERAÇÕES NECESSÁRIAS:"
            echo "- Adicionar 'ar.usuario_id as requisicao_usuario_id' à seleção de campos"
            echo "- Adicionar 'AND ar.usuario_id = ?' à cláusula WHERE"
            echo "- Atualizar execute() para receber 3 parâmetros em vez de 2"
            echo ""
            pause
            
            # Verificar aplicação
            echo "3. VERIFICANDO APLICAÇÃO..."
            if [ -f "almoxarifado/CHECK_SECURITY.bat" ]; then
                chmod +x almoxarifado/CHECK_SECURITY.bat
                ./almoxarifado/CHECK_SECURITY.bat
                if [ $? -eq 0 ]; then
                    echo "✅ Correção aplicada com sucesso!"
                else
                    echo "❌ Falha na aplicação da correção"
                    echo "Por favor, verifique manualmente o arquivo notificacoes.php"
                fi
            else
                echo "❌ Script de verificação não encontrado"
            fi
            echo ""
            pause
            ;;
            
        2)
            echo ""
            echo "🔍 VERIFICANDO ATUALIZAÇÃO INSTALADA..."
            echo ""
            
            if [ -f "almoxarifado/CHECK_SECURITY.bat" ]; then
                chmod +x almoxarifado/CHECK_SECURITY.bat
                ./almoxarifado/CHECK_SECURITY.bat
            else
                echo "❌ Script de verificação não encontrado"
            fi
            echo ""
            pause
            ;;
            
        3)
            echo ""
            echo "📋 VERIFICAÇÃO COMPLETA PÓS-DEPLOY..."
            echo ""
            
            if [ -f "almoxarifado/VERIFICACAO_POS_DEPLOY.sh" ]; then
                chmod +x almoxarifado/VERIFICACAO_POS_DEPLOY.sh
                ./almoxarifado/VERIFICACAO_POS_DEPLOY.sh
            else
                echo "❌ Script de verificação completa não encontrado"
            fi
            echo ""
            pause
            ;;
            
        4)
            echo ""
            echo "🧹 LIMPANDO SCRIPTS TEMPORÁRIOS..."
            echo ""
            
            if [ -f "almoxarifado/LIMPAR_SCRIPTS.bat" ]; then
                chmod +x almoxarifado/LIMPAR_SCRIPTS.bat
                ./almoxarifado/LIMPAR_SCRIPTS.bat
            else
                echo "❌ Script de limpeza não encontrado"
            fi
            echo ""
            pause
            ;;
            
        5)
            echo ""
            echo "ℹ️  INFORMAÇÕES SOBRE A ATUALIZAÇÃO"
            echo ""
            echo "📄 Documentação disponível:"
            echo "   - MIGRACAO_SEGURANCA.md (Guia completo)"
            echo "   - ATUALIZACAO_SEGURANCA_NOTIFICACOES.md (Detalhes técnicos)"
            echo "   - RESUMO_ATUALIZACAO_SEGURANCA.md (Resumo executivo)"
            echo ""
            echo "🛡️  Problema corrigido:"
            echo "   Usuários podiam ver notificações de outros usuários"
            echo ""
            echo "🔧 Solução implementada:"
            echo "   Verificação dupla na consulta SQL:"
            echo "   1. Destinatário da notificação (usuario_destino_id)"
            echo "   2. Criador da requisição (usuario_id)"
            echo ""
            echo "📅 Data da correção: 12/09/2025"
            echo "🔢 Versão: 1.0"
            echo ""
            pause
            ;;
            
        6)
            echo ""
            echo "👋 Até logo!"
            echo "Obrigado por usar o Deploy Automático de Segurança - Almoxarifado"
            exit 0
            ;;
            
        *)
            echo ""
            echo "❌ Opção inválida. Por favor, escolha uma opção de 1 a 6."
            echo ""
            pause
            ;;
    esac
    
    echo ""
    echo "==============================================="
    echo ""
done