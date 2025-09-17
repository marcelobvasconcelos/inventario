#!/bin/bash
# Script de Limpeza - Atualização de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

echo "=== Script de Limpeza ==="
echo "Atualização de Segurança - Módulo Almoxarifado"
echo "Data: 12/09/2025"
echo ""

# Verificar se a atualização foi aplicada com sucesso
echo "Verificando se a atualização foi aplicada..."
if [ -f "almoxarifado/CHECK_SECURITY.bat" ]; then
    chmod +x almoxarifado/CHECK_SECURITY.bat
    ./almoxarifado/CHECK_SECURITY.bat > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Atualização aplicada com sucesso"
        
        # Remover scripts temporários (opcional)
        echo ""
        echo "Removendo scripts temporários..."
        rm -f almoxarifado/DEPLOY_AUTOMATICO.bat
        rm -f almoxarifado/DEPLOY_AUTOMATICO.ps1
        rm -f almoxarifado/VERIFICACAO_POS_DEPLOY.sh
        rm -f almoxarifado/VERIFICACAO_POS_DEPLOY.ps1
        rm -f almoxarifado/CHECK_SECURITY.bat
        rm -f almoxarifado/CHECK_SECURITY.ps1
        rm -f almoxarifado/INSTALAR_ATUALIZACAO.bat
        rm -f almoxarifado/INSTALAR_ATUALIZACAO.ps1
        
        echo "✅ Scripts temporários removidos"
        
        # Manter apenas os arquivos importantes
        echo ""
        echo "Mantendo arquivos importantes:"
        echo "- Documentação: ATUALIZACAO_SEGURANCA_NOTIFICACOES.md"
        echo "- Documentação: MIGRACAO_SEGURANCA.md" 
        echo "- Documentação: RESUMO_ATUALIZACAO_SEGURANCA.md"
        echo "- Changelog: docs/CHANGELOG.md"
        echo "- Backup: almoxarifado/notificacoes.php.backup.*"
        
        echo ""
        echo "✅ Limpeza concluída com sucesso!"
        
    else
        echo "❌ Atualização não aplicada corretamente"
        echo "❌ Limpeza cancelada por segurança"
        exit 1
    fi
else
    echo "⚠️  Script de verificação não encontrado"
    echo "⚠️  Limpeza cancelada por segurança"
    exit 1
fi