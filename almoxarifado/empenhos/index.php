<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../../includes/footer.php';
    exit;
}

// Buscar estatísticas
// Total de categorias
$sql_categorias = "SELECT COUNT(*) as total FROM categorias";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$total_categorias = $stmt_categorias->fetch(PDO::FETCH_ASSOC)['total'];

// Total de empenhos
$sql_empenhos = "SELECT COUNT(*) as total FROM empenhos_insumos";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$total_empenhos = $stmt_empenhos->fetch(PDO::FETCH_ASSOC)['total'];

// Total de notas fiscais
$sql_notas = "SELECT COUNT(*) as total FROM notas_fiscais";
$stmt_notas = $pdo->prepare($sql_notas);
$stmt_notas->execute();
$total_notas = $stmt_notas->fetch(PDO::FETCH_ASSOC)['total'];

// Total de materiais
$sql_materiais = "SELECT COUNT(*) as total FROM materiais";
$stmt_materiais = $pdo->prepare($sql_materiais);
$stmt_materiais->execute();
$total_materiais = $stmt_materiais->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="container">
    <h2>Módulo de Controle de Empenhos, Notas Fiscais e Materiais</h2>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Categorias</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_categorias; ?></h5>
                    <p class="card-text">Categorias cadastradas</p>
                    <a href="categoria_add.php" class="btn btn-light btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Empenhos</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_empenhos; ?></h5>
                    <p class="card-text">Empenhos cadastrados</p>
                    <a href="empenho_add.php" class="btn btn-light btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">Notas Fiscais</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_notas; ?></h5>
                    <p class="card-text">Notas fiscais cadastradas</p>
                    <a href="nota_fiscal_add.php" class="btn btn-light btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">Materiais</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $total_materiais; ?></h5>
                    <p class="card-text">Materiais cadastrados</p>
                    <a href="material_add.php" class="btn btn-light btn-sm">Gerenciar</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Fluxo de Trabalho</h3>
        </div>
        <div class="card-body">
            <ol>
                <li><strong>Cadastrar Categorias:</strong> Primeiro, cadastre as categorias de materiais no sistema.</li>
                <li><strong>Cadastrar Empenhos:</strong> Registre os empenhos recebidos, vinculando-os às categorias apropriadas.</li>
                <li><strong>Cadastrar Notas Fiscais:</strong> Para cada empenho, registre as notas fiscais correspondentes.</li>
                <li><strong>Cadastrar Materiais:</strong> Finalmente, cadastre os materiais, vinculando-os às notas fiscais e categorias.</li>
            </ol>
            <p class="text-muted">
                <i class="fas fa-info-circle"></i> Também é possível cadastrar materiais independentemente de notas fiscais, 
                apenas vinculando-os a uma categoria.
            </p>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>