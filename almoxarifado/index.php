<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id'])) {
    header('Location: ../index.php');
    exit();
}
?>

<h2>Módulo de Almoxarifado</h2>

<div class="dashboard-container">
    <div class="dashboard-cards">
        <div class="card">
            <h3><i class="fas fa-boxes"></i> Itens</h3>
            <p>Gestão dos materiais do almoxarifado.</p>
            <a href="itens.php" class="btn-custom">Acessar</a>
        </div>
        
        <div class="card">
            <h3><i class="fas fa-arrow-down"></i> Entradas</h3>
            <p>Registrar entrada de materiais.</p>
            <a href="entrada_add.php" class="btn-custom">Acessar</a>
        </div>
        
        <div class="card">
            <h3><i class="fas fa-arrow-up"></i> Saídas</h3>
            <p>Registrar saída de materiais.</p>
            <a href="saida_add.php" class="btn-custom">Acessar</a>
        </div>
        
        <div class="card">
            <h3><i class="fas fa-chart-line"></i> Relatórios</h3>
            <p>Gerar relatórios de estoque e movimentações.</p>
            <a href="#" class="btn-custom" onclick="alert('Funcionalidade em desenvolvimento')">Acessar</a>
        </div>
    </div>
</div>

<style>
/* Tema específico para o módulo de almoxarifado */
body {
    background-color: #f8f9fa;
}

.main-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    border-bottom: 3px solid #1e7e34;
}

.main-header h1 {
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

.main-header nav a {
    color: rgba(255, 255, 255, 0.9);
    background-color: rgba(0, 0, 0, 0.1);
    margin-right: 5px;
    border-radius: 4px;
}

.main-header nav a:hover {
    background-color: rgba(0, 0, 0, 0.2);
    color: white;
}

.user-menu-button {
    background-color: #28a745;
    border: 1px solid #1e7e34;
}

.user-menu-content {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.user-menu-content a {
    color: #495057;
}

.user-menu-content a:hover {
    background-color: #e9ecef;
    color: #28a745;
}

/* Estilos do dashboard */
.dashboard-container {
    margin-top: 20px;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    border-top: 4px solid #28a745;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.card h3 {
    margin-top: 0;
    color: #28a745;
    font-size: 1.4em;
}

.card h3 i {
    margin-right: 10px;
    color: #20c997;
}

.card p {
    color: #6c757d;
    margin: 15px 0;
    font-size: 0.95em;
    line-height: 1.5;
}

.card .btn-custom {
    display: inline-block;
    padding: 10px 20px;
    background-color: #28a745;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
    border: none;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card .btn-custom:hover {
    background-color: #218838;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Título da página */
h2 {
    color: #28a745;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
    margin-bottom: 20px;
}
</style>

<?php
require_once '../includes/footer.php';
?>