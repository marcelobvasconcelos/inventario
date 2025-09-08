# Manual do Usuário do Sistema de Inventário

## [VISUALIZADOR] 1. Introdução

Bem-vindo ao Sistema de Inventário! Esta aplicação foi desenvolvida para ajudar no gerenciamento e controle de itens, locais e movimentações dentro de um ambiente. Ele permite que você mantenha um registro organizado de seus ativos, saiba onde eles estão localizados e quem é o responsável por eles.

Ao acessar o sistema, você será direcionado automaticamente para o **Dashboard**, que oferece uma visão geral de todas as funcionalidades disponíveis e estatísticas importantes do sistema.

## [VISUALIZADOR] 2. Requisitos do Sistema

Para utilizar o Sistema de Inventário, você precisará de:

*   Um navegador web moderno (Google Chrome, Mozilla Firefox, Microsoft Edge, Safari).
*   Conexão com a internet (se o sistema estiver hospedado online) ou acesso à rede local.

## [VISUALIZADOR] 3. Primeiros Passos

### 3.1. Acessando o Sistema
Para acessar o sistema, abra seu navegador web e digite o endereço fornecido pelo administrador.

### 3.2. Login e Logout
*   **Login:** Na tela inicial, insira seu **Email** e **Senha** nos campos designados e clique no botão "Login".
*   **Logout:** Para sair do sistema, clique no link "Sair" localizado no menu superior.

## [VISUALIZADOR] 4. Visão Geral da Interface

Após o login, você verá a página inicial (Dashboard) com um menu de navegação na parte superior. **Cada usuário só visualiza as funcionalidades e seções permitidas pelo seu perfil**:
- Administradores veem todas as opções do sistema.
- Gestores veem apenas as funções de gestão de itens e locais sob sua responsabilidade.
- Visualizadores têm acesso apenas à consulta de dados.
- Almoxarifes têm acesso às funcionalidades do módulo de almoxarifado.

## [VISUALIZADOR] 5. Perfis de Usuário

O sistema possui diferentes níveis de acesso:
- **Administrador:** Acesso total a todas as funcionalidades do sistema, incluindo gerenciamento de usuários, itens, locais e movimentações.
- **Gestor:** Pode gerenciar itens sob sua responsabilidade, solicitar novos locais, responder e justificar movimentações, e visualizar dados dos itens que gerencia.
- **Visualizador:** Pode apenas visualizar os dados do inventário e requisitar itens do almoxarifado, sem permissão para editar, adicionar ou excluir informações.
- **Almoxarife:** Pode gerenciar produtos, requisições e estoque do almoxarifado.

> **Importante:** O sistema exibe menus, botões e funcionalidades de acordo com o perfil do usuário logado. Se você não encontrar determinada função, provavelmente ela não está disponível para seu perfil.

## [VISUALIZADOR] 6. Funcionalidades Básicas (Todos os Usuários)

### 6.1. Visualizar Itens
Na seção "Itens", você pode ver uma lista de todos os itens cadastrados, com suas informações principais.

### 6.2. Visualizar Locais
Na seção "Locais", você pode ver uma lista de todos os locais de armazenamento cadastrados e utilizar a barra de pesquisa para encontrar locais por nome.

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
*   **Pesquisar Locais:** Utilize a barra de pesquisa para encontrar locais por nome.

### 8.3. Gerenciamento de Movimentações
- **Registrar Movimentação:** Registre movimentações de itens individuais ou em massa entre locais e responsáveis.
- **Notificações e Disputas:** Ao transferir um item para um gestor, o sistema envia uma notificação. Caso o gestor recuse, você pode responder justificando a movimentação ou desfazer a transferência, retornando o item ao local e responsável anterior. Todo o histórico da comunicação fica registrado e visível para ambos.
- **Ações em Lote:** Na página de notificações, você pode selecionar várias notificações de itens "Não Confirmados" e realizar ações em lote, como responder a todos os usuários, desfazer movimentações ou atribuir novos responsáveis.
- **Filtros de Notificações:** Na página de gerenciamento de notificações, você pode filtrar as notificações por status: "Todos", "Pendentes", "Confirmados" e "Não Confirmados", facilitando a gestão das notificações.

### 8.4. Gerenciamento de Usuários
*   **Adicionar, Editar e Excluir Usuários:** Crie, modifique e remova contas de usuário.
*   **Gerenciar Permissões:** Defina o perfil de cada usuário (Administrador, Gestor, Visualizador).
*   **Aprovar Contas:** Aprove ou rejeite o registro de novas contas de usuário.
*   **Exclusão de Usuários com Itens:** Agora é possível excluir usuários que tenham tido itens sob sua responsabilidade, desde que esses itens tenham sido excluídos (movidos para a "Lixeira").
*   **Geração de Senha Temporária:** Ao criar um novo usuário, o sistema gera automaticamente uma senha temporária que o usuário deve alterar no primeiro acesso.
*   **Gerenciamento de Usuários por Status:** Os usuários são organizados em seções por status (Aprovados, Pendentes de Aceitação, Rejeitados) para facilitar a gestão.
*   **Pesquisa de Usuários:** Utilize a barra de pesquisa para encontrar usuários por nome em tempo real.
*   **Rejeição de Usuários:** Ao excluir um usuário que tenha realizado movimentações, ele será movido para o status "Rejeitado" em vez de ser excluído permanentemente, preservando o histórico de movimentações.

## [ALMOXARIFE] 9. Módulo de Almoxarifado

O módulo de almoxarifado permite gerenciar produtos, requisições e estoque de materiais.

### 9.1. Acesso ao Módulo
O módulo de almoxarifado está disponível no menu de navegação superior para usuários com perfil de Administrador, Almoxarife, Visualizador ou Gestor.

### 9.2. Gerenciamento de Materiais (Administradores e Almoxarifes)
- **Visualizar Materiais:** Na página principal do almoxarifado, você pode ver uma lista de todos os materiais cadastrados, com suas informações principais.
- **Adicionar Material:** Clique no botão "Adicionar Material" para cadastrar um novo material no estoque.
- **Editar Material:** Clique no ícone de edição ao lado de um material para atualizar suas informações.

### 9.3. Gerenciamento de Categorias do Almoxarifado (Administradores e Almoxarifes)
- **Cadastrar Categorias:** Registre categorias para classificação dos materiais do almoxarifado. Estas categorias são **exclusivas** deste módulo e não se misturam com as categorias do inventário geral.
- **Editar Categorias:** Atualize as informações das categorias existentes.
- **Visualizar Categorias:** Veja a lista de todas as categorias cadastradas para o almoxarifado.

### 9.4. Requisições de Produtos (Todos os Usuários)
- **Criar Requisição:** Clique no botão "Nova Requisição" para solicitar produtos do almoxarifado.
- **Visualizar Requisições:** Na seção "Minhas Notificações", você pode ver o status das suas requisições.
- **Confirmar Recebimento:** Quando uma requisição for aprovada, você poderá confirmar o recebimento dos produtos.
- **Código da Requisição:** Cada requisição possui um código único no formato "REQ-ANO-XXXXXX" que pode ser usado para rastrear o status da requisição.

### 9.5. Aprovação de Requisições (Administradores e Almoxarifes)
- **Visualizar Requisições Pendentes:** Na seção "Gerenciar Requisições", você pode ver as requisições pendentes.
- **Aprovar Requisições:** Clique no botão "Aprovar" para autorizar a retirada dos produtos.
- **Rejeitar Requisições:** Clique no botão "Rejeitar" para negar a requisição, se necessário.

## [ADMINISTRADOR] 10. Módulo de Gestão (Empenhos)

O módulo de gestão de empenhos permite gerenciar empenhos e notas fiscais vinculadas aos materiais do almoxarifado.

### 10.1. Acesso ao Módulo
O módulo está disponível na página principal do Almoxarifado para usuários com perfil de Administrador.

### 10.2. Gerenciamento de Empenhos
- **Cadastrar Empenhos:** Registre os empenhos recebidos com informações como número, data de emissão, fornecedor e CNPJ.
- **Editar Empenhos:** Atualize as informações dos empenhos existentes.
- **Definir Status:** Marque os empenhos como "Aberto" ou "Fechado".
- **Visualizar Empenhos:** Veja a lista de todos os empenhos cadastrados.

### 10.3. Gerenciamento de Notas Fiscais
- **Cadastrar Notas Fiscais:** Registre as notas fiscais vinculadas aos empenhos.
- **Visualizar Notas Fiscais:** Veja a lista de todas as notas fiscais cadastradas.

## [VISUALIZADOR] 12. Ícones Utilizados

*   **Editar:** `<i class="fas fa-edit"></i>`
*   **Excluir:** `<i class="fas fa-trash"></i>` (Pode estar desativado se você não tiver permissão)
*   **Aprovar:** `<i class="fas fa-check-circle"></i>`
*   **Rejeitar:** `<i class="fas fa-times-circle"></i>`
*   **Pendente:** `<i class="fas fa-hourglass-half"></i>`

## [VISUALIZADOR] 13. Perguntas Frequentes (FAQ)

*   **Minha conta está "Pendente". O que devo fazer?**
    Sua conta precisa ser aprovada por um administrador. Entre em contato com ele.

## [VISUALIZADOR] 14. Solução de Problemas

*   **Não consigo fazer login:** Verifique seus dados e se sua conta foi aprovada. Se esqueceu a senha:
    1. Clique no link "Esqueceu sua senha?" na página de login
    2. Preencha seu nome completo e email
    3. Um administrador receberá sua solicitação e gerará uma nova senha temporária para você
    4. A senha temporária será enviada para o seu email
    5. Ao fazer login com a senha temporária, você será solicitado a criar uma nova senha

## [VISUALIZADOR] 15. Informações do Sistema

Este sistema foi desenvolvido pela [Seção de Tecnologia da Informação (STI-UAST)](https://uast.ufrpe.br/sti).

---

> **Observação:** Caso você tenha dúvidas sobre alguma funcionalidade que não aparece para seu perfil, entre em contato com o administrador do sistema.

**Fim do Manual do Usuário.**
