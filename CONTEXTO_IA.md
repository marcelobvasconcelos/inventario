### Título Sugerido: `Instruções para a IA - Projeto de Inventário`

### Conteúdo Recomendado:

**1. Contexto Geral do Projeto**
*   **Objetivo:** Sistema de gerenciamento de inventário com dois componentes principais: um sistema de controle de movimentação de itens (o sistema "legado") e um novo módulo de Almoxarifado para controle de estoque e requisições.
*   **Tecnologias:** PHP, MySQL, HTML/CSS/JavaScript.
*   **Padrão de Banco de Dados:** O projeto usa uma mistura de código `mysqli` (antigo) e `PDO` (novo). **A preferência é sempre usar PDO para novas funcionalidades ou correções.**

**2. Arquitetura e Arquivos-Chave (O Ponto Mais Importante)**
*   É crucial entender que existem **dois sistemas de notificação separados**:
    *   **a) Notificações de Inventário (Movimentações):**
        *   Tela do Admin: `notificacoes_admin.php` (na raiz do projeto).
        *   Tela do Usuário: `notificacoes_usuario.php` (na raiz do projeto).
        *   Este sistema lida com a confirmação de movimentação de itens entre usuários.
    *   **b) Notificações de Almoxarifado (Requisições):**
        *   Tela do Admin: `almoxarifado/admin_notificacoes.php`.
        *   Tela do Usuário: `almoxarifado/notificacoes.php`.
        *   Este sistema lida com o fluxo de aprovação, discussão e agendamento de requisições de materiais.
*   **Diretório `empenhos/`:** Esta pasta deve ser usada **apenas** para funcionalidades de cadastro (ex: `material_add.php`). O fluxo de requisições e notificações não deve ficar aqui.

**3. "Pegadinhas" do Banco de Dados (Essencial)**
*   As tabelas de histórico de conversa têm nomes de coluna de data **diferentes**. Isso causa erros fatais se não for observado.
    *   Tabela `almoxarifado_requisicoes_notificacoes`: a coluna de data se chama **`data_criacao`**.
    *   Tabela `almoxarifado_requisicoes_conversas`: a coluna de data se chama **`data_mensagem`**.
    *   Tabela `notificacoes_respostas_historico`: a coluna de data se chama **`data_resposta`**.

**4. Padrões de Código e Problemas Recorrentes**
*   **Erro de Resposta AJAX:** O sistema tem um problema recorrente onde um erro fatal no PHP faz com que o servidor retorne uma página HTML completa em vez de uma resposta JSON.
    *   **Solução Aplicada:** O JavaScript nos arquivos de notificação foi modificado para receber a resposta como `response.text()` e depois tentar extrair a mensagem de sucesso. **Não reverta isso para `response.json()`** sem antes encontrar e corrigir a causa raiz do erro fatal no PHP.