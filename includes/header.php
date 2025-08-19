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
// Conta diretamente os itens que pertencem ao usuário e estão pendentes
$notif_count = 0;
if (isset($_SESSION['id'])) {
    $sql_count = "SELECT COUNT(id) FROM itens WHERE responsavel_id = ? AND status_confirmacao = 'Pendente'";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute([$_SESSION['id']]);
    $notif_count = $stmt_count->fetchColumn();
}
?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Inventário</title>
    <?php
    // Gerar o caminho correto para o CSS, independentemente de onde a página esteja
    $css_path = '';
    $is_almoxarifado = (strpos($_SERVER['REQUEST_URI'], '/almoxarifado/') !== false);
    if ($is_almoxarifado) {
        $css_path = '../';
    }
    ?>
    <link rel="stylesheet" href="<?php echo $css_path; ?>css/style.css">
    <?php if($is_almoxarifado): ?>
        <link rel="stylesheet" href="<?php echo $css_path; ?>css/almoxarifado.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body<?php echo $is_almoxarifado ? ' class="almoxarifado"' : ''; ?>>
    <header class="main-header">
        <h1>Sistema de Inventário</h1>
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
                    <a href="/inventario/docs.php">Ajuda</a>
                    <a href="/inventario/logout.php">Sair</a>
                </div>
            </div>
        </div>
    </header>
    <main>