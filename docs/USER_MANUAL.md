
# Manual do Usuário do Sistema de Inventário

## [VISUALIZADOR] 1. Introdução

Bem-vindo ao Sistema de Inventário! Esta aplicação foi desenvolvida para ajudar no gerenciamento e controle de itens, locais e movimentações dentro de um ambiente. Ele permite que você mantenha um registro organizado de seus ativos, saiba onde eles estão localizados e quem é o responsável por eles.

Ao acessar o sistema, você será direcionado automaticamente para o **Dashboard**, que oferece uma visão geral de todas as funcionalidades disponíveis e estatísticas importantes do sistema.

## [VISUALIZADOR] 2. Requisitos do Sistema

Para utilizar o Sistema de Inventário, você precisará de:

*   Um navegador web moderno (Google Chrome, Mozilla Firefox, Microsoft Edge, Safari).
*   Conexão com a internet (se o sistema estiver hospedado online) ou acesso à rede local (se estiver em um servidor local como XAMPP).

## [VISUALIZADOR] 3. Primeiros Passos

### 3.1. Acessando o Sistema
Para acessar o sistema, abra seu navegador web e digite o endereço fornecido pelo administrador (ex: `http://localhost/inventario` se estiver usando XAMPP).

### 3.2. Login e Logout
*   **Login:** Na tela inicial, insira seu **Email** e **Senha** nos campos designados e clique no botão "Login".
*   **Logout:** Para sair do sistema, clique no link "Sair" localizado no menu superior.


## [VISUALIZADOR] 4. Visão Geral da Interface

Após o login, você verá a página inicial (Dashboard) com um menu de navegação na parte superior. **Cada usuário só visualiza as funcionalidades e seções permitidas pelo seu perfil**:
- Administradores veem todas as opções do sistema.
- Gestores veem apenas as funções de gestão de itens e locais sob sua responsabilidade.
- Visualizadores têm acesso apenas à consulta de dados.


## [VISUALIZADOR] 5. Perfis de Usuário

O sistema possui diferentes níveis de acesso:
- **Administrador:** Acesso total a todas as funcionalidades do sistema, incluindo gerenciamento de usuários, itens, locais e movimentações.
- **Gestor:** Pode gerenciar itens sob sua responsabilidade, solicitar novos locais, responder e justificar movimentações, e visualizar dados dos itens que gerencia.
- **Visualizador:** Pode apenas visualizar os dados do inventário, sem permissão para editar, adicionar ou excluir informações.

> **Importante:** O sistema exibe menus, botões e funcionalidades de acordo com o perfil do usuário logado. Se você não encontrar determinada função, provavelmente ela não está disponível para seu perfil.

## [VISUALIZADOR] 6. Funcionalidades Básicas (Todos os Usuários)

### 6.1. Visualizar Itens
Na seção "Itens", você pode ver uma lista de todos os itens cadastrados, com suas informações principais.

### 6.2. Visualizar Locais
Na seção "Locais", você pode ver uma lista de todos os locais de armazenamento cadastrados.

### 6.3. Visualizar Movimentações
Na seção "Movimentações", você pode visualizar o histórico de movimentações de itens. Gestores e Visualizadores veem apenas as movimentações dos itens pelos quais são responsáveis.

### 6.4. Meu Perfil
Na seção "Meu Perfil", você pode visualizar seus dados e alterar sua senha.


## [GESTOR] 7. Funcionalidades de Gestor

Como Gestor, além das funcionalidades básicas, você também pode:

### 7.1. Adicionar e Editar Itens
- **Adicionar Novo Item:** Na página "Itens", clique em "Adicionar Novo Item" para cadastrar um novo ativo sob sua responsabilidade.
- **Editar Item:** Você pode editar as informações dos itens pelos quais você é responsável.
- **Solicitar Novo Local:** Se um item precisa ser alocado em um local que não existe, o formulário de adição de item dará a opção de solicitar a criação de um novo local para o administrador.

### 7.2. Notificações e Disputas de Movimentação
- **Receber Notificações:** Sempre que um item for transferido para você, você receberá uma notificação.
- **Confirmar ou Recusar Movimentação:** Você pode aceitar a movimentação ou recusar, justificando o motivo.
- **Comunicação com o Administrador:** Caso recuse, inicia-se uma conversa (histórico de mensagens) entre você e o administrador, até que a situação seja resolvida.
- **Acompanhar Histórico:** Todo o histórico de justificativas e respostas fica disponível para consulta na tela da notificação.


## [ADMINISTRADOR] 8. Funcionalidades de Administrador

Como Administrador, você tem controle total sobre o sistema e pode:

### 8.1. Gerenciamento Completo de Itens
*   **Adicionar, Editar e Excluir Itens:** Você pode realizar todas as operações em qualquer item do inventário.
*   **Pesquisar Itens:** Utilize a barra de pesquisa para encontrar itens por qualquer critério.
*   **Itens Excluídos (Lixeira):** Itens excluídos não são removidos permanentemente do sistema. Eles são movidos para um usuário especial chamado "Lixeira" e podem ser restaurados a qualquer momento. Para acessar os itens excluídos, clique no botão "Ver Itens Excluídos" na página de itens.

### 8.2. Gerenciamento de Locais
*   **Adicionar, Editar e Excluir Locais:** Controle total sobre os locais de armazenamento.
*   **Aprovar/Rejeitar Solicitações:** Gerencie as solicitações de novos locais feitas pelos Gestores.


### 8.3. Gerenciamento de Movimentações
- **Registrar Movimentação:** Registre movimentações de itens individuais ou em massa entre locais e responsáveis.
- **Notificações e Disputas:** Ao transferir um item para um gestor, o sistema envia uma notificação. Caso o gestor recuse, você pode responder justificando a movimentação ou desfazer a transferência, retornando o item ao local e responsável anterior. Todo o histórico da comunicação fica registrado e visível para ambos.

### 8.4. Gerenciamento de Usuários
*   **Adicionar, Editar e Excluir Usuários:** Crie, modifique e remova contas de usuário.
*   **Gerenciar Permissões:** Defina o perfil de cada usuário (Administrador, Gestor, Visualizador).
*   **Aprovar Contas:** Aprove ou rejeite o registro de novas contas de usuário.
*   **Exclusão de Usuários com Itens:** Agora é possível excluir usuários que tenham tido itens sob sua responsabilidade, desde que esses itens tenham sido excluídos (movidos para a "Lixeira").
*   **Geração de Senha Temporária:** Ao criar um novo usuário, o sistema gera automaticamente uma senha temporária que o usuário deve alterar no primeiro acesso.


## [VISUALIZADOR] 9. Ícones Utilizados

*   **Editar:** `<i class="fas fa-edit"></i>`
*   **Excluir:** `<i class="fas fa-trash"></i>` (Pode estar desativado se você não tiver permissão)
*   **Aprovar:** `<i class="fas fa-check-circle"></i>`
*   **Rejeitar:** `<i class="fas fa-times-circle"></i>`
*   **Pendente:** `<i class="fas fa-hourglass-half"></i>`

## [VISUALIZADOR] 10. Perguntas Frequentes (FAQ)

*   **Minha conta está "Pendente". O que devo fazer?**
    Sua conta precisa ser aprovada por um administrador. Entre em contato com ele.

## [VISUALIZADOR] 11. Solução de Problemas

*   **Não consigo fazer login:** Verifique seus dados e se sua conta foi aprovada. Se esqueceu a senha, contate um administrador.

## [VISUALIZADOR] 12. Informações do Sistema

Este sistema foi desenvolvido pela [Seção de Tecnologia da Informação (STI-UAST)](https://uast.ufrpe.br/sti).

---


---

> **Observação:** Caso você tenha dúvidas sobre alguma funcionalidade que não aparece para seu perfil, entre em contato com o administrador do sistema.

**Fim do Manual do Usuário.**
