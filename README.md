# Sistema de Inventário

## Visão Geral do Projeto

Este é um sistema de gerenciamento de inventário desenvolvido em PHP puro com MySQL. Ele permite o controle de itens, seus locais de armazenamento, responsáveis e o registro de movimentações. O sistema possui um sistema de autenticação de usuários com diferentes níveis de permissão (Administrador, Gestor, Visualizador).

## Configuração do Ambiente de Desenvolvimento

Para configurar e executar este projeto localmente, você precisará de um ambiente de servidor web que suporte PHP e MySQL, como o XAMPP.

### Requisitos

*   **PHP:** Versão 7.4 ou superior (testado com PHP 8.x)
*   **MySQL:** Versão 5.7 ou superior
*   **Servidor Web:** Apache (incluído no XAMPP)

### Instalação (Usando XAMPP)

1.  **Baixe e Instale o XAMPP:**
    *   Acesse o site oficial do XAMPP: [https://www.apachefriends.org/index.html](https://www.apachefriends.org/index.html)
    *   Baixe a versão compatível com seu sistema operacional.
    *   Siga as instruções de instalação.

2.  **Clone ou Copie o Projeto:**
    *   Se você estiver usando Git, clone este repositório para o diretório `htdocs` do seu XAMPP (ex: `C:\xampp\htdocs\inventario`).
    *   Se não estiver usando Git, copie todos os arquivos do projeto para `C:\xampp\htdocs\inventario`.

3.  **Inicie o Apache e MySQL:**
    *   Abra o "XAMPP Control Panel".
    *   Inicie os módulos "Apache" e "MySQL".

## Configuração do Banco de Dados

1.  **Acesse o phpMyAdmin:**
    *   No "XAMPP Control Panel", clique no botão "Admin" ao lado do módulo MySQL.
    *   Isso abrirá o phpMyAdmin no seu navegador.

2.  **Crie o Banco de Dados:**
    *   No phpMyAdmin, clique em "New" (Novo) no menu lateral esquerdo.
    *   Crie um novo banco de dados com o nome `inventario_db` (ou o nome que preferir, mas lembre-se de atualizar `config/db.php`).
    *   Defina o `Collation` para `utf8mb4_general_ci`.

3.  **Importe o Esquema do Banco de Dados:**
    *   Selecione o banco de dados `inventario_db` que você acabou de criar.
    *   Clique na aba "Import" (Importar).
    *   Clique em "Choose File" (Escolher arquivo) e selecione o arquivo `database.sql` localizado na raiz do projeto.
    *   Clique em "Go" (Executar) para importar o esquema e os dados iniciais.

4.  **Configure as Credenciais do Banco de Dados:**
    *   Abra o arquivo `config/db.php` no seu editor de código.
    *   Atualize as constantes `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD` e `DB_NAME` com as credenciais do seu banco de dados MySQL.
    *   Exemplo (para XAMPP padrão):
        ```php
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root'); // Usuário padrão do XAMPP
        define('DB_PASSWORD', '');     // Senha padrão do XAMPP (vazia)
        define('DB_NAME', 'inventario_db');
        ```

## Estrutura do Projeto

```
inventario/
├── api/                  # Scripts para APIs internas (ex: busca de itens)
├── config/               # Arquivos de configuração (ex: db.php)
├── css/                  # Arquivos CSS para estilização
├── docs/                 # Documentação do sistema (manual do usuário, etc.)
├── includes/             # Partes reutilizáveis do HTML (cabeçalho, rodapé)
├── database.sql          # Esquema e dados iniciais do banco de dados
├── index.php             # Página inicial do sistema (redireciona para dashboard)
├── dashboard.php         # Dashboard com visão geral do sistema
├── item_add.php          # Adicionar novo item
├── item_delete.php       # Excluir item
├── item_edit.php         # Editar item
├── item_details.php      # Detalhes do item
├── itens.php             # Listagem de itens
├── itens_excluidos.php   # Listagem de itens excluídos (lixeira)
├── locais.php            # Listagem de locais
├── local_add.php         # Adicionar novo local
├── local_delete.php      # Excluir local
├── local_edit.php        # Editar local
├── local_itens.php       # Itens por local
├── login.php             # Página de login
├── logout.php            # Logout do sistema
├── movimentacao_add.php  # Registrar nova movimentação
├── movimentacoes.php     # Listagem de movimentações
├── registro.php          # Página de registro de usuário
├── usuario_add.php       # Adicionar novo usuário
├── usuario_delete.php    # Excluir usuário
├── usuario_edit.php      # Editar usuário
├── usuario_itens.php     # Itens por usuário
├── usuarios.php          # Gerenciar usuários
└── README.md             # Este arquivo
```

## Uso do Sistema

Após a configuração, acesse o sistema pelo seu navegador (ex: `http://localhost/inventario`).

*   **Registro:** Novos usuários podem se registrar através da página `registro.php`. Contas recém-registradas precisam ser aprovadas por um administrador.
*   **Login:** Use as credenciais de um usuário aprovado para acessar o sistema.
*   **Níveis de Permissão:** O sistema possui perfis de permissão que controlam o acesso às funcionalidades:
    *   **Administrador:** Acesso total.
    *   **Gestor:** Acesso para adicionar/editar itens, visualizar locais e movimentações.
    *   **Visualizador:** Apenas visualização de itens, locais e movimentações (filtrado por responsabilidade).
*   **Lixeira:** Itens excluídos não são removidos permanentemente do sistema. Eles são movidos para um usuário especial chamado "Lixeira" e podem ser restaurados a qualquer momento. Apenas administradores podem acessar e restaurar itens da lixeira.

Para mais detalhes sobre o uso das funcionalidades, consulte o [Manual do Usuário](docs/USER_MANUAL.md).

---

**Fim do README.md**