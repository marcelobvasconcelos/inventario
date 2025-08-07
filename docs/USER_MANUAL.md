# Manual do Usuário do Sistema de Inventário

## 1. Introdução

Bem-vindo ao Sistema de Inventário! Esta aplicação foi desenvolvida para ajudar no gerenciamento e controle de itens, locais e movimentações dentro de um ambiente. Ele permite que você mantenha um registro organizado de seus ativos, saiba onde eles estão localizados e quem é o responsável por eles.

## 2. Requisitos do Sistema

Para utilizar o Sistema de Inventário, você precisará de:

*   Um navegador web moderno (Google Chrome, Mozilla Firefox, Microsoft Edge, Safari).
*   Conexão com a internet (se o sistema estiver hospedado online) ou acesso à rede local (se estiver em um servidor local como XAMPP).

## 3. Primeiros Passos

### 3.1. Acessando o Sistema

Para acessar o sistema, abra seu navegador web e digite o endereço fornecido pelo administrador (ex: `http://localhost/inventario` se estiver usando XAMPP).

### 3.2. Login e Logout

*   **Login:** Na tela inicial, insira seu **Email** e **Senha** nos campos designados e clique no botão "Login".
*   **Logout:** Para sair do sistema, clique no link "Sair" localizado no menu superior.

## 4. Visão Geral da Interface

Após o login, você verá a página inicial (Dashboard) com um menu de navegação na parte superior. No canto superior direito, você verá o nome do usuário logado, que agora funciona como um menu. Este menu permite acessar as diferentes seções do sistema:

*   **Início:** Retorna à página inicial (Dashboard).
*   **Itens:** Gerencia todos os itens do inventário.
*   **Locais:** Gerencia os locais de armazenamento.
*   **Movimentações:** Visualiza o histórico de movimentações de itens.
*   **Usuários:** (Apenas para Administradores) Gerencia os usuários do sistema.
*   **Meu Perfil:** Permite visualizar seus dados (Nome e Email) e alterar sua senha.
*   **Ajuda:** Acessa este manual do usuário.
*   **Sair:** Realiza o logout do sistema.

## 5. Funcionalidades Detalhadas

O sistema possui diferentes níveis de acesso, que determinam as ações que cada usuário pode realizar:

### 5.1. Perfis de Usuário

*   **Administrador:** Possui acesso total a todas as funcionalidades do sistema, incluindo gerenciamento de usuários, adição/edição/exclusão de itens e locais, e visualização completa de movimentações.
*   **Gestor:** Pode visualizar, adicionar e editar itens. Pode visualizar locais e movimentações. Não pode gerenciar usuários ou excluir itens/locais.
*   **Visualizador:** Pode apenas visualizar itens, locais e movimentações. Não pode adicionar, editar ou excluir nenhum dado.

### 5.2. Gerenciamento de Itens

Na seção "Itens", você pode:

*   **Visualizar Itens:** Uma lista de todos os itens cadastrados, com suas informações principais. Cada página exibe 20 itens e possui navegação por paginação.
*   **Pesquisar Itens:** (Apenas Administradores) Um campo de pesquisa está disponível para filtrar itens. Você pode selecionar o critério de pesquisa (ID, Patrimônio, Patrimônio Secundário, Local ou Responsável) e digitar um termo. A pesquisa suporta termos parciais.
*   **Adicionar Novo Item:** (Apenas Administradores e Gestores) Clique no botão "Adicionar Novo Item".
    *   **Para Administradores:** Preencha o formulário com os detalhes do item.
    *   **Para Gestores:** Se for o seu primeiro item e você ainda não tiver um local associado, o sistema solicitará que você selecione um local primeiro. Caso não encontre seu local na lista, você terá a opção de **Solicitar Novo Local**. Após selecionar ou solicitar o local, você poderá preencher o restante do formulário com os detalhes do item.
    Formulários de adição e edição agora incluem um botão "Cancelar" para retornar à página anterior sem salvar as alterações.
*   **Editar Item:** (Apenas Administradores e Gestores) Clique no link "Editar" ao lado do item desejado para modificar suas informações. Para **Administradores**, o campo "Responsável" é editável. Para **Gestores**, o campo "Responsável" é desativado e o gestor só pode editar itens pelos quais ele é o responsável. Formulários de adição e edição agora incluem um botão "Cancelar" para retornar à página anterior sem salvar as alterações.
*   **Excluir Item:** (Apenas Administradores) Clique no link "Excluir" ao lado do item desejado. Será solicitada uma confirmação. Para **Gestores** e **Visualizadores**, o ícone de exclusão (`<i class="fas fa-trash"></i>`) é desativado (`<i class="fas fa-trash disabled-icon"></i>`).

### 5.3. Gerenciamento de Locais

Na seção "Locais", você pode:

*   **Visualizar Locais:** Uma lista de todos os locais de armazenamento cadastrados, incluindo seu status (Aprovado, Pendente, Rejeitado). Cada página exibe 20 locais e possui navegação por paginação. As colunas 'ID' e 'Nome' podem ser clicadas para ordenar a lista visualmente (ordenação no lado do cliente).
*   **Adicionar Novo Local:** (Apenas Administradores) Clique no botão "Adicionar Novo Local" e preencha o formulário. Formulários de adição e edição agora incluem um botão "Cancelar" para retornar à página anterior sem salvar as alterações.
*   **Gerenciar Solicitações de Local:** (Apenas Administradores) Na página de Locais, você pode filtrar a lista por status (Aprovados, Pendentes, Rejeitados ou Todos). Locais com status "Pendente" foram solicitados por gestores e precisam de sua aprovação. Você pode **Aprovar** (`<i class="fas fa-check-circle"></i>`) ou **Rejeitar** (`<i class="fas fa-times-circle"></i>`) essas solicitações.
*   **Editar Local:** (Apenas Administradores) Clique no link "Editar" ao lado do local desejado para modificar suas informações. Formulários de adição e edição agora incluem um botão "Cancelar" para retornar à página anterior sem salvar as alterações.
*   **Excluir Local:** (Apenas Administradores) Clique no link "Excluir" ao lado do local desejado. Será solicitada uma confirmação.
*   **Visualizar Itens por Local:** Clique no nome de um local para ver todos os itens associados a ele.

### 5.4. Movimentações de Itens

Na seção "Movimentações", você pode:

*   **Visualizar Histórico:** Uma lista de todas as movimentações de itens realizadas no sistema. Cada página exibe 20 movimentações e possui navegação por paginação.
    *   **Para Administradores:** Todas as movimentações são visíveis.
    *   **Para Gestores e Visualizadores:** Apenas as movimentações de itens pelos quais o usuário é o responsável atual são visíveis.
*   **Registrar Nova Movimentação:** (Apenas Administradores) Clique no botão "Registrar Nova Movimentação".
    *   **Movimentação Individual:** Selecione o item, o local de destino e o novo responsável.
    *   **Movimentação em Massa:** Selecione o local de origem. Uma lista de itens desse local será carregada. Marque os itens que deseja movimentar, selecione o local de destino e o novo responsável. Todos os itens selecionados serão movidos para o mesmo destino e terão o mesmo responsável.

### 5.5. Gerenciamento de Usuários (Apenas Administradores)

Na seção "Usuários", os Administradores podem:

*   **Visualizar Usuários:** Uma lista de todos os usuários cadastrados. Cada página exibe 20 usuários e possui navegação por paginação.
*   **Adicionar Novo Usuário:** Crie novas contas de usuário, definindo seu nome, email, senha e perfil de permissão.
*   **Editar Usuário:** Modifique as informações de um usuário existente, incluindo seu perfil de permissão e senha.
*   **Excluir Usuário:** Remova uma conta de usuário. O sistema verificará se o usuário é responsável por itens ou movimentações antes de permitir a exclusão.
*   **Aprovar/Rejeitar/Pendente:** Gerencie o status de contas de usuário pendentes de aprovação.
    *   `Aprovar`: `<i class="fas fa-check-circle"></i>` - Altera o status do usuário para aprovado.
    *   `Rejeitar`: `<i class="fas fa-times-circle"></i>` - Altera o status do usuário para rejeitado.
    *   `Pendente`: `<i class="fas fa-hourglass-half"></i>` - Altera o status do usuário para pendente.

### 5.6. Meu Perfil

Na seção "Meu Perfil", você pode:

*   **Visualizar Dados:** Visualize seu Nome e Email cadastrados no sistema.
*   **Alterar Senha:** Altere sua senha de acesso. Para sua segurança, a senha deve ter no mínimo 6 caracteres e é recomendado usar uma combinação de letras maiúsculas, minúsculas, números e símbolos.

## 6. Ícones Utilizados

*   **Editar:** `<i class="fas fa-edit"></i>` - Usado para modificar informações de um registro.
*   **Excluir:** `<i class="fas fa-trash"></i>` - Usado para remover um registro. Este ícone pode aparecer desativado (`<i class="fas fa-trash disabled-icon"></i>`) se o usuário não tiver permissão para excluir.
*   **Aprovar:** `<i class="fas fa-check-circle"></i>` - Usado para aprovar um registro ou ação.
*   **Rejeitar:** `<i class="fas fa-times-circle"></i>` - Usado para rejeitar um registro ou ação.
*   **Pendente:** `<i class="fas fa-hourglass-half"></i>` - Usado para indicar um status pendente ou aguardando aprovação.

## 7. Perguntas Frequentes (FAQ)

*   **Minha conta está "Pendente". O que devo fazer?**
    Sua conta precisa ser aprovada por um administrador do sistema. Entre em contato com o administrador para que ele possa revisar e aprovar sua conta.

## 8. Solução de Problemas

*   **Não consigo fazer login:**
    *   Verifique se seu email e senha estão corretos.
    *   Certifique-se de que sua conta foi aprovada por um administrador.
    *   Se esqueceu sua senha, entre em contato com o administrador para redefini-la.

## 9. Informações do Sistema

Este sistema foi desenvolvido pela [Seção de Tecnologia da Informação (STI-UAST)](https://uast.ufrpe.br/sti).

---

**Fim do Manual do Usuário.**