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
require_once __DIR__ . '/../config/db.php';

// Busca o número de notificações pendentes para o usuário logado
$notif_count = 0;
if (isset($_SESSION['id'])) {
    // Verificar notificações em ambas as tabelas
    $sql_count1 = "SELECT COUNT(id) FROM notificacoes WHERE usuario_id = ? AND status = 'Pendente'";
    $stmt_count1 = $pdo->prepare($sql_count1);
    $stmt_count1->execute([$_SESSION['id']]);
    $count1 = $stmt_count1->fetchColumn();
    
    $sql_count2 = "SELECT COUNT(id) FROM notificacoes_movimentacao WHERE usuario_notificado_id = ? AND status_confirmacao = 'Pendente'";
    $stmt_count2 = $pdo->prepare($sql_count2);
    $stmt_count2->execute([$_SESSION['id']]);
    $count2 = $stmt_count2->fetchColumn();
    
    $notif_count = $count1 + $count2;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Inventário</title>
    <?php
    // Gerar o caminho correto para o CSS, independentemente de onde a página esteja
    $css_path = '';
    if (strpos($_SERVER['REQUEST_URI'], '/almoxarifado/') !== false) {
        $css_path = '../';
    }
    ?>
    <link rel="stylesheet" href="<?php echo $css_path; ?>css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <h1>Sistema de Inventário</h1>
        <nav>
            <a href="index.php">Início</a>
            <a href="itens.php">Itens</a>
            <a href="locais.php">Locais</a>
            <a href="movimentacoes.php">Movimentações</a>
            <a href="almoxarifado/index.php">Almoxarifado</a>
            <a href="notificacoes_usuario.php">Notificações</a> <!-- Link para notificações -->
            <?php if($_SESSION["permissao"] == 'Administrador'): // Links visíveis apenas para Administradores ?>
                <a href="usuarios.php">Usuários</a>
                <a href="patrimonio_add.php">Patrimônio</a>
                <a href="notificacoes_admin.php">Gerenciar Notificações</a>
            <?php endif; ?>
        </nav>
        <div class="user-menu">
             <!-- Ícone de Notificação -->
            <a href="notificacoes_usuario.php" class="notification-bell">
                <i class="fas fa-bell"></i>
                <?php if($notif_count > 0): ?>
                    <span class="notification-badge"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </a>
            <div class="user-menu-dropdown">
                <button class="user-menu-button">Bem-vindo, <?php echo $_SESSION['nome']; ?> <i class="fas fa-caret-down"></i></button>
                <div class="user-menu-content">
                    <a href="usuario_perfil.php">Editar Perfil</a>
                    <a href="notificacoes_usuario.php">Minhas Notificações</a>
                    <?php if($_SESSION["permissao"] == 'Administrador'): // Link de configurações PDF visível apenas para Administradores ?>
                        <a href="configuracoes_pdf.php">Configurações PDF</a>
                    <?php endif; ?>
                    <a href="docs.php">Ajuda</a>
                    <a href="logout.php">Sair</a>
                </div>
            </div>
        </div>
    </header>
    <main>

    <script>
    // Função para atualizar o badge do sino de notificações
    function atualizarBadgeNotificacoes() {
        fetch('api/get_notificacoes_pendentes.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-bell .notification-badge');
                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count;
                    } else {
                        // Cria o badge se não existir
                        const bell = document.querySelector('.notification-bell');
                        if (bell) {
                            const span = document.createElement('span');
                            span.className = 'notification-badge';
                            span.textContent = data.count;
                            bell.appendChild(span);
                        }
                    }
                } else {
                    if (badge) badge.remove();
                }
            });
    }

    // Atualiza a cada 30 segundos
    setInterval(atualizarBadgeNotificacoes, 30000);
    // Atualiza ao carregar a página
    document.addEventListener('DOMContentLoaded', atualizarBadgeNotificacoes);

    // Exemplo: para atualizar após ações AJAX, chame atualizarBadgeNotificacoes() no sucesso do fetch das ações.
    window.atualizarBadgeNotificacoes = atualizarBadgeNotificacoes;
    </script>