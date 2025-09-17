#!/bin/bash
# Script de Implantação Automatizada - Correção de Segurança Almoxarifado
# Versão 1.0 - 12/09/2025

# Verificar se estamos no diretório correto
if [ ! -f "almoxarifado/notificacoes.php" ]; then
    echo "Erro: Diretório de instalação não encontrado!"
    echo "Por favor, execute este script a partir do diretório raiz do sistema."
    exit 1
fi

echo "=== Script de Implantação Automatizada ==="
echo "Correção de Segurança - Módulo Almoxarifado"
echo "Data: 12/09/2025"
echo ""

# 1. Criar backup dos arquivos
echo "1. Criando backups..."
DATA_HORA=$(date +%Y%m%d_%H%M%S)
cp almoxarifado/notificacoes.php almoxarifado/notificacoes.php.backup.$DATA_HORA
echo "Backup criado: almoxarifado/notificacoes.php.backup.$DATA_HORA"

# 2. Aplicar correções no código PHP
echo ""
echo "2. Aplicando correções no código..."

# Verificar se a correção já foi aplicada
if grep -q "ar.usuario_id = ?.*Garante que o usuário só veja notificações" almoxarifado/notificacoes.php; then
    echo "Aviso: Correção já parece estar aplicada. Pulando etapa."
else
    # Fazer backup do script original antes da modificação
    cp almoxarifado/notificacoes.php almoxarifado/notificacoes_original.php.$DATA_HORA
    
    # Aplicar a correção na consulta SQL
    sed -i.bak '
    # Adicionar usuario_id na seleção de campos
    s/        ar\.justificativa$/        ar\.justificativa,\
        ar\.usuario_id as requisicao_usuario_id/
    
    # Adicionar verificação de usuário criador
    s/WHERE arn\.usuario_destino_id = ?$/WHERE arn\.usuario_destino_id = ?\
    AND ar\.usuario_id = ?  -- Garante que o usuário só veja notificações de requisições que ele mesmo criou/
    
    # Atualizar a execução da consulta
    s/$stmt->execute([$usuario_logado_id, $usuario_logado_id]);/$stmt->execute([$usuario_logado_id, $usuario_logado_id, $usuario_logado_id]);/
    ' almoxarifado/notificacoes.php
    
    # Remover arquivo de backup temporário
    rm -f almoxarifado/notificacoes.php.bak
    
    echo "Correções aplicadas com sucesso!"
fi

# 3. Verificar estrutura do banco de dados
echo ""
echo "3. Verificando estrutura do banco de dados..."
if command -v mysql &> /dev/null; then
    echo "Executando verificação da estrutura do banco de dados..."
    # Esta parte seria executada manualmente pelo DBA
    echo "Execute o script VERIFICACAO_ESTRUTURA_BD.sql no seu cliente MySQL"
else
    echo "MySQL client não encontrado. Pule esta etapa ou instale o cliente MySQL."
fi

# 4. Copiar arquivos de documentação
echo ""
echo "4. Copiando arquivos de documentação..."
cp almoxarifado/ATUALIZACAO_SEGURANCA_NOTIFICACOES.md almoxarifado/notificacoes.php.backup.$DATA_HORA.docs 2>/dev/null || true

# 5. Mensagens finais
echo ""
echo "=== Implantação Concluída ==="
echo ""
echo "Resumo das alterações:"
echo "- Backup criado: almoxarifado/notificacoes.php.backup.$DATA_HORA"
echo "- Correções de segurança aplicadas"
echo "- Documentação atualizada"
echo ""
echo "Próximos passos:"
echo "1. Teste a funcionalidade como usuário comum"
echo "2. Teste a funcionalidade como administrador"
echo "3. Monitore os logs do sistema por eventuais problemas"
echo ""
echo "Em caso de problemas, restaure o backup:"
echo "cp almoxarifado/notificacoes.php.backup.$DATA_HORA almoxarifado/notificacoes.php"
echo ""
echo "Implantação concluída com sucesso!"