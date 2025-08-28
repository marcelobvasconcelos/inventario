# Estrutura de Arquivos do Sistema de Inventário

Este documento descreve a finalidade de cada arquivo e diretório no projeto, como eles são chamados e em que contexto.

## Diretórios Principais

*   `api/`: Contém scripts para endpoints de API.
*   `config/`: Armazena arquivos de configuração do sistema.
*   `css/`: Contém arquivos de estilo CSS.
*   `docs/`: Armazena a documentação do projeto, incluindo este arquivo e o manual do usuário.
*   `includes/`: Contém partes reutilizáveis do código (cabeçalhos, rodapés, etc.).
*   `uploads/`: Usado para armazenar arquivos enviados pelo usuário, como logos.
*   `vendor/`: Contém bibliotecas de terceiros instaladas via Composer.

## Arquivos PHP na Raiz

### `index.php`
**Propósito:** Página inicial do sistema (dashboard) após o login.
**Chamado por:** Acesso direto via URL (`/inventario/index.php`) ou após um login bem-sucedido.
**Contexto:** Exibe um resumo das informações do inventário.

### `login.php`
**Propósito:** Gerencia o processo de autenticação do usuário.
**Chamado por:** Acesso direto via URL (`/inventario/login.php`) ou quando um usuário não autenticado tenta acessar uma página protegida.
**Contexto:** Valida credenciais e inicia a sessão do usuário.

### `logout.php`
**Propósito:** Encerra a sessão do usuário.
**Chamado por:** Clique no link "Sair" no menu do usuário.
**Contexto:** Destrói a sessão e redireciona para a página de login.

### `registro.php`
**Propósito:** Permite o registro de novos usuários no sistema.
**Chamado por:** Acesso direto via URL (`/inventario/registro.php`).
**Contexto:** Processa o formulário de registro e insere novos usuários no banco de dados.

### `itens.php`
**Propósito:** Exibe a lista de todos os itens do inventário.
**Chamado por:** Clique no link "Itens" no menu de navegação.
**Contexto:** Permite visualizar, pesquisar e, dependendo da permissão, adicionar, editar ou excluir itens.

### `item_add.php`
**Propósito:** Formulário para adicionar um novo item ao inventário.
**Chamado por:** Clique no botão "Adicionar Novo Item" na página `itens.php`.
**Contexto:** Processa o envio do formulário para inserir um novo item no banco de dados.

### `item_edit.php`
**Propósito:** Formulário para editar um item existente.
**Chamado por:** Clique no link "Editar" ao lado de um item na página `itens.php`.
**Contexto:** Preenche o formulário com os dados do item e processa as atualizações no banco de dados.

### `item_delete.php`
**Propósito:** Processa a exclusão de um item.
**Chamado por:** Clique no link "Excluir" ao lado de um item na página `itens.php`.
**Contexto:** Remove o item do banco de dados após confirmação.

### `item_details.php`
**Propósito:** Exibe os detalhes de um item específico.
**Chamado por:** Clique no nome de um item na página `itens.php`.
**Contexto:** Mostra todas as informações de um item, incluindo seu histórico de movimentações.

### `locais.php`
**Propósito:** Exibe a lista de locais de armazenamento.
**Chamado por:** Clique no link "Locais" no menu de navegação.
**Contexto:** Permite visualizar, e dependendo da permissão, adicionar, editar, excluir ou gerenciar solicitações de locais.

### `local_add.php`
**Propósito:** Formulário para adicionar um novo local.
**Chamado por:** Clique no botão "Adicionar Novo Local" na página `locais.php`.
**Contexto:** Processa o envio do formulário para inserir um novo local no banco de dados.

### `local_edit.php`
**Propósito:** Formulário para editar um local existente.
**Chamado por:** Clique no link "Editar" ao lado de um local na página `locais.php`.
**Contexto:** Preenche o formulário com os dados do local e processa as atualizações no banco de dados.

### `local_delete.php`
**Propósito:** Processa a exclusão de um local.
**Chamado por:** Clique no link "Excluir" ao lado de um local na página `locais.php`.
**Contexto:** Remove o local do banco de dados após confirmação.

### `local_request.php`
**Propósito:** Permite que gestores solicitem a criação de novos locais.
**Chamado por:** Formulário de adição de item (`item_add.php`) quando um gestor não encontra seu local.
**Contexto:** Registra uma solicitação de local para aprovação do administrador.

### `local_approve.php`
**Propósito:** Processa a aprovação de uma solicitação de local.
**Chamado por:** Clique no ícone "Aprovar" ao lado de um local pendente na página `locais.php` (apenas administradores).
**Contexto:** Altera o status do local para "Aprovado".

### `local_reject.php`
**Propósito:** Processa a rejeição de uma solicitação de local.
**Chamado por:** Clique no ícone "Rejeitar" ao lado de um local pendente na página `locais.php` (apenas administradores).
**Contexto:** Altera o status do local para "Rejeitado".

### `local_itens.php`
**Propósito:** Exibe os itens associados a um local específico.
**Chamado por:** Clique no nome de um local na página `locais.php`.
**Contexto:** Filtra e exibe todos os itens que estão atualmente naquele local.

### `movimentacoes.php`
**Propósito:** Exibe o histórico de movimentações de itens.
**Chamado por:** Clique no link "Movimentações" no menu de navegação.
**Contexto:** Lista todas as movimentações, filtradas por permissão do usuário.

### `movimentacao_add.php`
**Propósito:** Formulário para registrar uma nova movimentação de item (individual ou em massa).
**Chamado por:** Clique no botão "Registrar Nova Movimentação" na página `movimentacoes.php`.
**Contexto:** Processa o formulário para atualizar o local e/ou responsável de um ou mais itens.

### `usuarios.php`
**Propósito:** Gerencia os usuários do sistema (apenas administradores).
**Chamado por:** Clique no link "Usuários" no menu de navegação (apenas administradores).
**Contexto:** Permite visualizar, adicionar, editar, excluir e gerenciar o status de usuários.

### `usuario_add.php`
**Propósito:** Formulário para adicionar um novo usuário.
**Chamado por:** Clique no botão "Adicionar Novo Usuário" na página `usuarios.php`.
**Contexto:** Processa o formulário para inserir um novo usuário no banco de dados.

### `usuario_edit.php`
**Propósito:** Formulário para editar um usuário existente.
**Chamado por:** Clique no link "Editar" ao lado de um usuário na página `usuarios.php`.
**Contexto:** Preenche o formulário com os dados do usuário e processa as atualizações no banco de dados.

### `usuario_delete.php`
**Propósito:** Processa a exclusão de um usuário.
**Chamado por:** Clique no link "Excluir" ao lado de um usuário na página `usuarios.php`.
**Contexto:** Remove o usuário do banco de dados após confirmação, com validações para evitar exclusão de usuários com itens associados.

### `usuario_perfil.php`
**Propósito:** Permite que o usuário logado visualize e edite seu próprio perfil (nome, email, senha).
**Chamado por:** Clique no link "Editar Perfil" no menu suspenso do usuário.
**Contexto:** Processa as atualizações do perfil do usuário logado.

### `usuario_itens.php`
**Propósito:** Exibe os itens pelos quais um usuário específico é responsável.
**Chamado por:** Clique no nome de um usuário na página `usuarios.php`.
**Contexto:** Filtra e exibe todos os itens que estão sob a responsabilidade daquele usuário.

### `patrimonio_add.php`
**Propósito:** Adiciona um novo patrimônio (item) ao sistema.
**Chamado por:** Clique no link "Patrimônio" no menu de navegação (apenas administradores).
**Contexto:** Formulário para registrar um novo item, similar a `item_add.php` mas possivelmente com foco em bens patrimoniais.

### `configuracoes_pdf.php`
**Propósito:** Permite configurar o cabeçalho e a logo para os relatórios PDF.
**Chamado por:** Clique no link "Configurações PDF" no menu suspenso do usuário (apenas administradores).
**Contexto:** Processa o upload da logo e a atualização do texto do cabeçalho no banco de dados.

### `gerar_pdf_itens.php`
**Propósito:** Gera um relatório PDF dos itens do inventário.
**Chamado por:** Submissão de formulário na página `itens.php` (botão "Gerar PDF").
**Contexto:** Coleta os dados dos itens (com base em filtros), formata-os e gera um arquivo PDF para download.

### `docs.php`
**Propósito:** Exibe o manual do usuário, filtrando o conteúdo com base nas permissões do usuário.
**Chamado por:** Clique no link "Ajuda" no menu suspenso do usuário.
**Contexto:** Lê o `USER_MANUAL.md`, processa-o com Parsedown e exibe as seções permitidas.

## Arquivos em `api/`

### `api/get_items_by_location.php`
**Propósito:** Endpoint de API para buscar itens por localização.
**Chamado por:** Requisições AJAX de scripts JavaScript no frontend (ex: formulários de movimentação).
**Contexto:** Retorna dados de itens em formato JSON para uso dinâmico na interface.

### `api/search_locais_nome.php`
**Propósito:** Endpoint de API para buscar locais por nome (autocomplete).
**Chamado por:** Requisições AJAX de scripts JavaScript no frontend (ex: campo de pesquisa de locais).
**Contexto:** Retorna dados de locais em formato JSON para uso dinâmico na interface.

## Arquivos em `docs/`

### `docs/FILE_STRUCTURE.md`
**Propósito:** Documenta a estrutura de arquivos do projeto.
**Chamado por:** Referência dos desenvolvedores.
**Contexto:** Fornece informações sobre a organização dos arquivos do sistema.

### `docs/USER_MANUAL.md`
**Propósito:** Manual do usuário do sistema.
**Chamado por:** Link "Ajuda" no menu do usuário.
**Contexto:** Explica como usar o sistema com base nas permissões do usuário.

### `docs/MELHORIAS_USUARIOS.md`
**Propósito:** Documenta melhorias identificadas para a página de gerenciamento de usuários.
**Chamado por:** Referência dos desenvolvedores e administradores.
**Contexto:** Fornece diretrizes para aprimoramento da funcionalidade de usuários.

## Arquivos em `config/`

### `config/db.php`
**Propósito:** Contém as configurações de conexão com o banco de dados.
**Chamado por:** Incluído (`require_once`) por quase todos os scripts PHP que interagem com o banco de dados.
**Contexto:** Estabelece a conexão PDO com o MySQL.

## Arquivos em `includes/`

### `includes/header.php`
**Propósito:** Contém o cabeçalho HTML de todas as páginas, incluindo a navegação principal e a lógica de sessão.
**Chamado por:** Incluído (`require_once`) no início de quase todas as páginas PHP.
**Contexto:** Garante que o usuário esteja logado e exibe o menu de navegação.

### `includes/footer.php`
**Propósito:** Contém o rodapé HTML de todas as páginas.
**Chamado por:** Incluído (`require_once`) no final de quase todas as páginas PHP.
**Contexto:** Fecha as tags HTML e pode incluir scripts JavaScript globais.

---

Esta documentação será atualizada conforme novas funcionalidades forem adicionadas ou modificações significativas forem feitas.