# Histórico de Atualizações do Sistema de Inventário

## [1.6.0] - 16/09/2025
### Adicionado
- Implementação de melhorias no módulo de almoxarifado:
  - Vinculação direta de materiais às notas fiscais
  - Campos de auditoria (data e usuário de criação) nos materiais
  - Histórico de alterações de saldo dos empenhos
  - Página para visualizar histórico de alterações de saldo dos empenhos

### Melhorado
- Interface do usuário do almoxarifado com informações adicionais de rastreabilidade
- Controle financeiro com histórico completo de movimentações de saldo

## [1.5.0] - 12/09/2025
### Adicionado
- Implementação do módulo de almoxarifado com funcionalidades completas
- Sistema de requisições de materiais
- Controle de estoque e movimentações
- Notificações e aprovações de requisições

### Corrigido
- Problema de segurança onde usuários podiam ver notificações de outros usuários
- Adicionada verificação dupla nas consultas de notificações
- Melhorias na interface estilo Gmail para melhor organização

### Segurança
- Restrição de acesso às notificações apenas para o usuário proprietário
- Verificação adicional de usuário criador da requisição
- Proteção contra exposição de dados de outros usuários

## [1.4.0] - 08/09/2025
### Adicionado
- Sistema de empenhos com controle de insumos
- Relatórios avançados de movimentações
- Exportação de dados em formato CSV

### Melhorado
- Interface do usuário com design responsivo
- Performance das consultas ao banco de dados
- Sistema de autenticação e autorização

## [1.3.0] - 01/09/2025
### Adicionado
- Módulo de lixeira para recuperação de itens excluídos
- Sistema de notificações em tempo real
- Controle de permissões granular

### Corrigido
- Problemas com exclusão de itens com dependências
- Erros de validação em formulários
- Inconsistências no cálculo de valores

## [1.2.0] - 25/08/2025
### Adicionado
- Sistema de gerenciamento de usuários
- Perfis de acesso (Administrador, Gestor, Visualizador)
- Recuperação de senha por email

### Melhorado
- Interface de login com validação aprimorada
- Sistema de temas claro/escuro
- Responsividade em dispositivos móveis

## [1.1.0] - 18/08/2025
### Adicionado
- Cadastro e gerenciamento de locais
- Movimentação de itens entre locais
- Impressão de etiquetas de patrimônio

### Corrigido
- Problemas com codificação de caracteres
- Erros de cálculo em relatórios
- Inconsistências na exibição de datas

## [1.0.0] - 10/08/2025
### Adicionado
- Sistema básico de inventário
- Cadastro de itens com controle de patrimônio
- Categorias e classificações de itens
- Relatórios simples de estoque

---
*Histórico de versões mantido automaticamente*