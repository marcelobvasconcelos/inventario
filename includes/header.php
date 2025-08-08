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
require_once 'config/db.php';

// Busca o número de notificações pendentes para o usuário logado
$notif_count = 0;
if (isset($_SESSION['id'])) {
    $sql_count = "SELECT COUNT(id) FROM notificacoes WHERE usuario_id = ? AND status = 'Pendente'";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute([$_SESSION['id']]);
    $notif_count = $stmt_count->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Inventário</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <header>
        <h1>Sistema de Inventário</h1>
        <nav>
            <a href="index.php">Início</a>
            <a href="itens.php">Itens</a>
            <a href="locais.php">Locais</a>
            <a href="movimentacoes.php">Movimentações</a>
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