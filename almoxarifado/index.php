<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(__DIR__);

require_once $base_path . '/includes/header.php';
require_once $base_path . '/config/db.php';

// Verificar permissão de acesso (apenas administradores por enquanto)
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para acessar este módulo.</div>";
    require_once '../includes/footer.php';
    exit;
}
?>

<h2>Módulo Almoxarifado</h2>
<p>Bem-vindo ao módulo de almoxarifado. Aqui você poderá gerenciar os materiais e produtos do almoxarifado.</p>

<div class="atalhos-container">
    <a href="materiais.php" class="atalho-item">
        <i class="fas fa-boxes"></i>
        <span>Materiais</span>
    </a>
    <a href="entradas.php" class="atalho-item">
        <i class="fas fa-sign-in-alt"></i>
        <span>Entradas</span>
    </a>
    <a href="saidas.php" class="atalho-item">
        <i class="fas fa-sign-out-alt"></i>
        <span>Saídas</span>
    </a>
    <a href="estoque.php" class="atalho-item">
        <i class="fas fa-warehouse"></i>
        <span>Estoque</span>
    </a>
</div>

<?php
require_once $base_path . '/includes/footer.php';
?>