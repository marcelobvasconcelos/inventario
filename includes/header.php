<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
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
            <?php if($_SESSION["permissao"] == 'Administrador'): ?>
                <a href="usuarios.php">Usuários</a>
            <?php endif; ?>
        </nav>
        <div class="user-menu">
            <button class="user-menu-button">Bem-vindo, <?php echo $_SESSION['nome']; ?> <i class="fas fa-caret-down"></i></button>
            <div class="user-menu-content">
                <a href="usuario_perfil.php">Editar Perfil</a>
                <a href="docs.php">Ajuda</a>
                <a href="logout.php">Sair</a>
            </div>
        </div>
    </header>
    <main>