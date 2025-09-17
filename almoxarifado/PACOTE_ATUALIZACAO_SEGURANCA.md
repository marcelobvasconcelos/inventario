# PACOTE DE ATUALIZAÇÃO DE SEGURANÇA - ALMOXARIFADO
Versão 1.0 - 12/09/2025

## CONTEÚDO DO PACOTE

### 1. Arquivo Principal Atualizado
- `notificacoes.php` - Arquivo com a correção de segurança aplicada

### 2. Documentação
- `ATUALIZACAO_SEGURANCA_NOTIFICACOES.md` - Documentação técnica da correção
- `MIGRACAO_SEGURANCA.md` - Guia completo de migração
- `RESUMO_ATUALIZACAO_SEGURANCA.md` - Resumo executivo da atualização
- `README_ATUALIZACAO_SEGURANCA.md` - Instruções de uso deste pacote

### 3. Scripts de Banco de Dados
- `SCRIPT_ATUALIZACAO_SEGURANCA.sql` - Script de verificação e atualização
- `VERIFICACAO_ESTRUTURA_BD.sql` - Script de verificação da estrutura

### 4. Scripts de Implantação
- `DEPLOY_AUTOMATICO.bat` - Script para Linux/Unix
- `DEPLOY_AUTOMATICO.ps1` - Script para Windows PowerShell

### 5. Scripts de Verificação
- `VERIFICACAO_POS_DEPLOY.sh` - Verificação para Linux/Unix
- `VERIFICACAO_POS_DEPLOY.ps1` - Verificação para Windows PowerShell

## INSTRUÇÕES DE INSTALAÇÃO

### 1. Backup
Antes de aplicar qualquer alteração, faça backup do arquivo:
```bash
cp almoxarifado/notificacoes.php almoxarifado/notificacoes.php.backup.$(date +%Y%m%d)
```

### 2. Aplicação da Correção
Substitua o conteúdo da consulta SQL no arquivo `almoxarifado/notificacoes.php` conforme instruído no `MIGRACAO_SEGURANCA.md`.

### 3. Verificação
Execute os scripts de verificação para garantir que a atualização foi aplicada corretamente.

## SUPORTE
Para suporte técnico, consulte a documentação em `MIGRACAO_SEGURANCA.md` ou entre em contato com a equipe de desenvolvimento.

---
*Pacote criado em 12/09/2025*