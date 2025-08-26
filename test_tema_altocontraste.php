<?php
// Inicia a sessão PHP se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redireciona para a página de login se o usuário não estiver logado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Inclui a conexão com o banco de dados para buscar notificações
require_once __DIR__ . '/config/db.php';

// Busca o número de notificações pendentes para o usuário logado
// Conta diretamente os itens que pertencem ao usuário e estão pendentes
$notif_count = 0;
$draft_count = 0;
$tema_usuario = 'padrao';

if (isset($_SESSION['id'])) {
    // Busca o tema preferido do usuário diretamente do banco de dados
    try {
        $stmt_tema = $pdo->prepare("SELECT tema_preferido FROM usuarios WHERE id = ?");
        $stmt_tema->execute([$_SESSION['id']]);
        $tema_result = $stmt_tema->fetchColumn();
        
        // Se houver um tema definido, usa ele, senão usa o padrão
        if ($tema_result) {
            $tema_usuario = $tema_result;
        }
        
        // Armazena o tema na sessão
        $_SESSION['tema_preferido'] = $tema_usuario;
    } catch (Exception $e) {
        $tema_usuario = 'padrao';
    }
    
    // Busca notificações pendentes
    $sql_count = "SELECT COUNT(id) FROM itens WHERE responsavel_id = ? AND status_confirmacao = 'Pendente'";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute([$_SESSION['id']]);
    $notif_count = $stmt_count->fetchColumn();
    
    // Conta o número de rascunhos (apenas para administradores)
    if ($_SESSION["permissao"] == 'Administrador') {
        $sql_draft_count = "SELECT COUNT(id) FROM rascunhos_itens";
        $stmt_draft_count = $pdo->prepare($sql_draft_count);
        $stmt_draft_count->execute();
        $draft_count = $stmt_draft_count->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste de Tema Alto Contraste</title>
    <?php
    // Gerar o caminho correto para o CSS, independentemente de onde a página esteja
    $css_path = '';
    $js_path = '';
    $is_almoxarifado = (strpos($_SERVER['REQUEST_URI'], '/almoxarifado/') !== false);
    if ($is_almoxarifado) {
        $css_path = '../';
        $js_path = '../';
    }
    ?>
    <link rel="stylesheet" href="<?php echo $css_path; ?>css/style.css">
    <!-- Inclui o CSS do tema selecionado -->
    <link rel="stylesheet" id="tema-css" href="<?php echo $css_path; ?>css/tema_<?php echo htmlspecialchars($tema_usuario); ?>.css">
    <!-- Inclui o CSS para os temas -->
    <link rel="stylesheet" href="<?php echo $css_path; ?>css/temas.css">
    <?php if($is_almoxarifado): ?>
        <link rel="stylesheet" href="<?php echo $css_path; ?>css/almoxarifado.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Estilo para ícones usando a cor do tema */
        .icone-tema {
            color: var(--cor-icones, var(--cor-primaria));
        }
        
        /* Estilos para teste de cores */
        .teste-cor {
            width: 100px;
            height: 100px;
            margin: 10px;
            display: inline-block;
            border: 2px solid black;
        }
        .cor-primaria { background-color: var(--cor-primaria, #000); }
        .cor-secundaria { background-color: var(--cor-secundaria, #000); }
        .cor-destaque { background-color: var(--cor-destaque, #000); }
        .cor-perigo { background-color: var(--cor-perigo, #000); }
        .cor-fundo { background-color: var(--cor-fundo, #000); }
        .cor-texto { background-color: var(--cor-texto, #000); }
        .cor-texto-claro { background-color: var(--cor-texto-claro, #000); }
        .cor-borda { background-color: var(--cor-borda, #000); }
        .sombra { background-color: var(--sombra, #000); }
        .sombra-forte { background-color: var(--sombra-forte, #000); }
    </style>
</head>
<body<?php echo $is_almoxarifado ? ' class="almoxarifado"' : ''; ?>>
    <header class="main-header">
        <h1>Teste de Tema Alto Contraste</h1>
        <nav>
            <a href="/inventario/index.php">Início</a>
            <a href="/inventario/itens.php">Itens</a>
            <a href="/inventario/locais.php">Locais</a>
            <a href="/inventario/movimentacoes.php">Movimentações</a>
            <div class="user-menu-dropdown">
                <a href="/inventario/almoxarifado/index.php" style="color: #007bff;">Almoxarifado</a>
                <div class="user-menu-content">
                    <a href="/inventario/almoxarifado/itens.php">Itens do Almoxarifado</a>
                    <a href="/inventario/almoxarifado/notificacoes.php">Notificações do Almoxarifado</a>
                    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
                        <a href="/inventario/almoxarifado/notificacoes_admin.php">Gerenciar Notificações do Almoxarifado</a>
                    <?php endif; ?>
                </div>
            </div>

            <a href="/inventario/notificacoes_usuario.php">Notificações</a>
            <?php if($_SESSION["permissao"] == 'Administrador'): ?>
                <a href="/inventario/rascunhos_itens.php">Rascunhos
                    <?php if($draft_count > 0): ?>
                        <span class="notification-badge"><?php echo $draft_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="/inventario/usuarios.php">Usuários</a>
                <a href="/inventario/patrimonio_add.php">Patrimônio</a>
                <a href="/inventario/notificacoes_admin.php">Gerenciar Notificações</a>
            <?php endif; ?>
        </nav>
        <div class="user-menu">
            <a href="/inventario/notificacoes_usuario.php" class="notification-bell">
                <i class="fas fa-bell"></i>
                <?php if($notif_count > 0): ?>
                    <span class="notification-badge"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </a>
            <div class="user-menu-dropdown">
                <button class="user-menu-button">Bem-vindo, <?php echo $_SESSION['nome']; ?> <i class="fas fa-caret-down"></i></button>
                <div class="user-menu-content">
                    <a href="/inventario/usuario_perfil.php">Editar Perfil</a>
                    <a href="/inventario/notificacoes_usuario.php">Minhas Notificações</a>
                    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
                        <a href="/inventario/configuracoes_pdf.php">Configurações PDF</a>
                    <?php endif; ?>
                    <!-- Link para selecionar tema -->
                    <a href="#" id="seletor-tema">Selecionar Tema</a>
                    <a href="/inventario/docs.php">Ajuda</a>
                    <a href="/inventario/logout.php">Sair</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Modal para seleção de tema -->
    <div id="modal-tema" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Selecionar Tema</h2>
            <div class="temas-container">
                <div class="tema-opcao" data-tema="padrao">
                    <div class="tema-preview padrao"></div>
                    <span>Padrão</span>
                </div>
                <div class="tema-opcao" data-tema="azul">
                    <div class="tema-preview azul"></div>
                    <span>Azul</span>
                </div>
                <div class="tema-opcao" data-tema="verde">
                    <div class="tema-preview verde"></div>
                    <span>Verde</span>
                </div>
                <div class="tema-opcao" data-tema="roxo">
                    <div class="tema-preview roxo"></div>
                    <span>Roxo</span>
                </div>
                <div class="tema-opcao" data-tema="altocontraste">
                    <div class="tema-preview altocontraste"></div>
                    <span>Alto Contraste</span>
                </div>
            </div>
        </div>
    </div>
    
    <main>
        <h2>Teste de Aplicação do Tema Alto Contraste</h2>
        
        <div>
            <h3>Cores do tema atual (<?php echo htmlspecialchars($tema_usuario); ?>):</h3>
            <div class="teste-cor cor-primaria"></div>
            <div class="teste-cor cor-secundaria"></div>
            <div class="teste-cor cor-destaque"></div>
            <div class="teste-cor cor-perigo"></div>
            <div class="teste-cor cor-fundo"></div>
            <div class="teste-cor cor-texto"></div>
            <div class="teste-cor cor-texto-claro"></div>
            <div class="teste-cor cor-borda"></div>
            <div class="teste-cor sombra"></div>
            <div class="teste-cor sombra-forte"></div>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>Elementos do sistema:</h3>
            <button class="btn btn-primary">Botão Primário</button>
            <button class="btn btn-success">Botão Secundário</button>
            <button class="btn btn-danger">Botão de Perigo</button>
            <button class="btn btn-warning">Botão de Aviso</button>
            <button class="btn btn-info">Botão de Informação</button>
            <button class="btn btn-custom">Botão Customizado</button>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>Badges de status:</h3>
            <span class="badge badge-warning">Pendente</span>
            <span class="badge badge-success">Confirmado</span>
            <span class="badge badge-danger">Não Confirmado</span>
            <span class="badge badge-info">Movimento Desfeito</span>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>Header do sistema:</h3>
            <p>O header acima deve mudar de cor de acordo com o tema selecionado.</p>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Sistema de Inventário</p>
        <p>Sistema desenvolvido pela <a href="https://uast.ufrpe.br/sti" target="_blank" style="color: white;"><strong>Seção de Tecnologia da Informação (STI-UAST)</strong></a></p>
    </footer>
    
    <!-- Inclui o JavaScript para os temas -->
    <script src="<?php echo $js_path; ?>js/temas.js"></script>
</body>
</html>