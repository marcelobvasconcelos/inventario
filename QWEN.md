# Instruções para a IA (Gemini) - Projeto Inventário

Este documento contém diretrizes e informações importantes para a IA ao interagir com o projeto de sistema de inventário.

## Contexto do Projeto

*   **Tecnologias:** PHP (backend), MySQL (banco de dados), HTML/CSS/JavaScript (frontend).
*   **Objetivo:** Sistema de gerenciamento de inventário para controle de itens, locais e movimentações.
*   **Conexão com Banco de Dados:** O projeto utiliza tanto `mysqli` (para código legado) quanto `PDO` (para novas implementações). **Priorize o uso de PDO para novas interações com o banco de dados.**

## Diretrizes de Operação da IA

1.  **Idioma:** Sempre responda e interaja em **Português**.
2.  **Comentários no Código:** Ao modificar ou criar arquivos PHP, adicione comentários **simples e claros** para explicar a lógica e a finalidade das seções do código. O objetivo é facilitar o entendimento para outros desenvolvedores.
3.  **Convenções:** Mantenha-se alinhado com as convenções de código existentes no projeto (formatação, estrutura, etc.).
4.  **Documentação:** Mantenha a documentação do projeto atualizada, especialmente o `docs/FILE_STRUCTURE.md` e o `docs/USER_MANUAL.md`.
5.  **Testes:** Sempre que possível e relevante, mencione a importância de testar as funcionalidades após as alterações.

# Regras para Edição de Código PHP

1. Sempre manter a estrutura original do código, alterando apenas o necessário.
2. Nunca remover ou mover chaves `{` e `}` sem verificar se a contagem total bate.
3. Antes de entregar o código, verificar se:
   - Todas as chaves estão abertas e fechadas corretamente.
   - O código abre e fecha corretamente `<?php` e `?>` (se houver).
   - O número de `{` é igual ao número de `}`.
4. Se encontrar mais de um problema, corrigir todos de uma só vez.
5. Nunca repetir o mesmo trecho mais de uma vez na mesma resposta.
6. Antes de gerar código, fazer um resumo claro das mudanças que serão feitas.
7. Se não tiver certeza da alteração, perguntar antes de modificar.

## Estado Atual do Projeto (Últimas Implementações Relevantes)

*   **Sistema de Notificações:** Uma funcionalidade de notificação e confirmação de movimentações de inventário foi implementada.
    *   Notificações são geradas em `item_add.php` e `movimentacao_add.php`.
    *   Usuários podem confirmar/rejeitar notificações em `notificacoes_usuario.php`.
    *   Administradores podem gerenciar notificações em `notificacoes_admin.php`.
    *   O status de confirmação (`status_confirmacao`) é exibido em `itens.php`, `usuario_itens.php` e `local_itens.php`.
*   **Manual do Usuário:** O `docs/USER_MANUAL.md` foi reestruturado para exibir conteúdo baseado nas permissões do usuário (`docs.php`).
*   **Configurações PDF:** A funcionalidade de geração de PDF (`gerar_pdf_itens.php`, `configuracoes_pdf.php`) está presente.
*   **Leitura de Código de Barras:** A tentativa de implementar a leitura de código de barras via câmera foi abortada e o código relacionado foi removido.


## Próximos Passos (Sugestões)

*   Continuar aprimorando a documentação do projeto.
*   Revisar e otimizar o código existente.
*   Implementar novas funcionalidades conforme solicitado pelo usuário.
*   Sempre que atualizar o bd inserir em unico arquivo chamado atualização de bd para produção.


