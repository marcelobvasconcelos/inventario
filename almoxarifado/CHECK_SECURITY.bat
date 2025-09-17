#!/bin/bash
# Script de Verificação Rápida - Correção de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

echo "=== Verificação Rápida de Segurança - Almoxarifado ==="
echo ""

# Verificar se o arquivo existe
if [ ! -f "almoxarifado/notificacoes.php" ]; then
    echo "❌ ERRO: Arquivo almoxarifado/notificacoes.php não encontrado!"
    exit 1
fi

echo "✅ Arquivo encontrado"

# Verificar se a correção está aplicada
if grep -q "ar.usuario_id = ?.*Garante que o usuário só veja notificações" almoxarifado/notificacoes.php; then
    echo "✅ Correção de segurança aplicada"
    
    # Verificar se a execução está correta
    if grep -q "\$stmt->execute(\[\$usuario_logado_id, \$usuario_logado_id, \$usuario_logado_id\]);" almoxarifado/notificacoes.php; then
        echo "✅ Execução da consulta corretamente atualizada"
        echo ""
        echo "🎉 VERIFICAÇÃO CONCLUÍDA COM SUCESSO!"
        echo "   A correção de segurança está corretamente aplicada."
    else
        echo "❌ ERRO: Execução da consulta NÃO está atualizada"
        echo "   A correção está parcialmente aplicada."
        exit 1
    fi
else
    echo "❌ ERRO: Correção de segurança NÃO aplicada"
    echo "   O arquivo precisa ser atualizado."
    exit 1
fi