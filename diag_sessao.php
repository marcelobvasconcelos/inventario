<?php
// diag_sessao.php - Página para diagnosticar informações da sessão
session_start();
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Sessão</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <h1>Diagnóstico de Sessão</h1>
    </header>
    
    <main>
        <h2>Informações da Sessão</h2>
        
        <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
            <div class="alert alert-danger">
                <p>Usuário não está logado.</p>
                <p><a href="login.php">Faça login</a> para continuar.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h3>Dados do Usuário</h3>
                    <ul>
                        <li><strong>ID:</strong> <?php echo htmlspecialchars($_SESSION["id"] ?? 'Não definido'); ?></li>
                        <li><strong>Nome:</strong> <?php echo htmlspecialchars($_SESSION["nome"] ?? 'Não definido'); ?></li>
                        <li><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION["email"] ?? 'Não definido'); ?></li>
                        <li><strong>Perfil:</strong> <?php echo htmlspecialchars($_SESSION["permissao"] ?? 'Não definido'); ?></li>
                        <li><strong>Status:</strong> <?php echo htmlspecialchars($_SESSION["status"] ?? 'Não definido'); ?></li>
                    </ul>
                    
                    <h3>Verificação de Permissões</h3>
                    <?php
                    $perfil_usuario = $_SESSION["permissao"] ?? '';
                    $permissoes = [
                        'Administrador' => $perfil_usuario == 'Administrador',
                        'Almoxarife' => $perfil_usuario == 'Almoxarife',
                        'Visualizador' => $perfil_usuario == 'Visualizador'
                    ];
                    ?>
                    <ul>
                        <li><strong>Administrador:</strong> <?php echo $permissoes['Administrador'] ? 'Sim' : 'Não'; ?></li>
                        <li><strong>Almoxarife:</strong> <?php echo $permissoes['Almoxarife'] ? 'Sim' : 'Não'; ?></li>
                        <li><strong>Visualizador:</strong> <?php echo $permissoes['Visualizador'] ? 'Sim' : 'Não'; ?></li>
                    </ul>
                    
                    <h3>Menu de Almoxarifado</h3>
                    <?php
                    $mostrar_menu_almoxarifado = ($perfil_usuario == 'Administrador' || $perfil_usuario == 'Almoxarife' || $perfil_usuario == 'Visualizador');
                    ?>
                    <p>O menu de almoxarifado <?php echo $mostrar_menu_almoxarifado ? 'deve' : 'não deve'; ?> estar visível para este usuário.</p>
                    
                    <?php if ($perfil_usuario == 'Visualizador'): ?>
                        <div class="alert alert-success">
                            <p>✅ O usuário tem o perfil "Visualizador".</p>
                            <p>O menu de almoxarifado deveria estar visível.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>⚠ O usuário não tem o perfil "Visualizador".</p>
                            <p>Perfil atual: <?php echo htmlspecialchars($perfil_usuario); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <h3>Todas as Variáveis de Sessão</h3>
                    <pre><?php print_r($_SESSION); ?></pre>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-primary">Voltar ao Dashboard</a>
            <a href="logout.php" class="btn btn-secondary">Sair</a>
        </div>
    </main>
</body>
</html>