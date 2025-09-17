#!/bin/bash
# Script de Verificação Pós-Deploy - Correção de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

echo "=== Script de Verificação Pós-Deploy ==="
echo "Correção de Segurança - Módulo Almoxarifado"
echo "Data: 12/09/2025"
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "almoxarifado/notificacoes.php" ]; then
    echo "❌ ERRO: Diretório de instalação não encontrado!"
    echo "Por favor, execute este script a partir do diretório raiz do sistema."
    exit 1
fi

echo "✅ Diretório raiz verificado com sucesso"
echo ""

# 1. Verificar se a correção foi aplicada no código
echo "1. Verificando aplicação da correção no código..."
if grep -q "ar.usuario_id = ?.*Garante que o usuário só veja notificações" almoxarifado/notificacoes.php; then
    echo "✅ Correção está corretamente aplicada no código"
else
    echo "❌ AVISO: Correção NÃO está aplicada ou está diferente do esperado"
    echo "Por favor, verifique manualmente o arquivo almoxarifado/notificacoes.php"
fi

# 2. Verificar se há backup
echo ""
echo "2. Verificando backup..."
BACKUP_EXISTS=$(ls almoxarifado/notificacoes.php.backup.* 2>/dev/null | wc -l)
if [ "$BACKUP_EXISTS" -gt 0 ]; then
    echo "✅ Backup encontrado: $(ls almoxarifado/notificacoes.php.backup.* | head -1)"
else
    echo "⚠️  AVISO: Nenhum backup encontrado"
fi

# 3. Verificar se os arquivos de documentação foram criados
echo ""
echo "3. Verificando arquivos de documentação..."
DOCUMENTOS=("ATUALIZACAO_SEGURANCA_NOTIFICACOES.md" "MIGRACAO_SEGURANCA.md" "SCRIPT_ATUALIZACAO_SEGURANCA.sql" "VERIFICACAO_ESTRUTURA_BD.sql" "DEPLOY_AUTOMATICO.bat" "DEPLOY_AUTOMATICO.ps1" "RESUMO_ATUALIZACAO_SEGURANCA.md")
MISSING_DOCS=0

for doc in "${DOCUMENTOS[@]}"; do
    if [ -f "almoxarifado/$doc" ]; then
        echo "✅ $doc"
    else
        echo "❌ $doc (AUSENTE)"
        MISSING_DOCS=$((MISSING_DOCS + 1))
    fi
done

if [ "$MISSING_DOCS" -eq 0 ]; then
    echo "✅ Todos os documentos de suporte foram criados com sucesso"
else
    echo "❌ $MISSING_DOCS documento(s) de suporte estão ausentes"
fi

# 4. Verificar changelog
echo ""
echo "4. Verificando changelog..."
if [ -f "docs/CHANGELOG.md" ]; then
    if grep -q "12/09/2025" docs/CHANGELOG.md && grep -q "Problema de segurança onde usuários podiam ver notificações de outros usuários" docs/CHANGELOG.md; then
        echo "✅ Atualização registrada no changelog"
    else
        echo "❌ Atualização NÃO registrada no changelog"
    fi
else
    echo "❌ Arquivo CHANGELOG.md não encontrado"
fi

# 5. Teste de sintaxe PHP
echo ""
echo "5. Verificando sintaxe PHP..."
if command -v php &> /dev/null; then
    php -l almoxarifado/notificacoes.php > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ Sintaxe PHP correta"
    else
        echo "❌ ERRO: Problemas de sintaxe no arquivo almoxarifado/notificacoes.php"
        php -l almoxarifado/notificacoes.php
    fi
else
    echo "⚠️  AVISO: PHP não encontrado, pulando verificação de sintaxe"
fi

# 6. Resumo final
echo ""
echo "=== Resumo da Verificação ==="
echo ""
echo "Verificações realizadas:"
echo "- Código fonte: Completas"  
echo "- Backups: $(if [ "$BACKUP_EXISTS" -gt 0 ]; then echo "Encontrados"; else echo "Não encontrados"; fi)"
echo "- Documentação: $(if [ "$MISSING_DOCS" -eq 0 ]; then echo "Completa"; else echo "Incompleta"; fi)"
echo "- Changelog: $(if [ -f "docs/CHANGELOG.md" ] && grep -q "12/09/2025" docs/CHANGELOG.md; then echo "Atualizado"; else echo "Não atualizado"; fi)"
echo "- Sintaxe PHP: $(if command -v php &> /dev/null && php -l almoxarifado/notificacoes.php > /dev/null 2>&1; then echo "Correta"; else echo "Não verificada"; fi)"

echo ""
echo "=== TESTES MANUAIS RECOMENDADOS ==="
echo ""
echo "1. Acesse o sistema como usuário comum:"
echo "   - Verifique que apenas notificações de suas próprias requisições são exibidas"
echo "   - Tente acessar notificações de outros usuários (não deve ser possível)"
echo ""
echo "2. Acesse o sistema como administrador:"
echo "   - Verifique que a página de administração continua funcionando"
echo "   - Verifique que a página de notificações pessoais respeita a restrição"
echo ""
echo "3. Monitore os logs do sistema:"
echo "   - Verifique se há erros após a atualização"
echo "   - Monitore acessos suspeitos ou tentativas de acesso indevido"

echo ""
echo "Verificação concluída!"
echo "Status: $(if grep -q "ar.usuario_id = ?.*Garante que o usuário só veja notificações" almoxarifado/notificacoes.php && [ "$MISSING_DOCS" -eq 0 ]; then echo "SUCESSO"; else echo "ATENÇÃO - VERIFIQUE OS PROBLEMAS IDENTIFICADOS"; fi)"