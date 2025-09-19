<?php
// Menu fixo para páginas de empenhos
// Buscar estatísticas para o menu
$sql_categorias = "SELECT COUNT(*) as total FROM almoxarifado_categorias";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$total_categorias = $stmt_categorias->fetch(PDO::FETCH_ASSOC)['total'];

$sql_empenhos = "SELECT COUNT(*) as total FROM empenhos_insumos";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$total_empenhos = $stmt_empenhos->fetch(PDO::FETCH_ASSOC)['total'];

$sql_notas = "SELECT COUNT(*) as total FROM notas_fiscais";
$stmt_notas = $pdo->prepare($sql_notas);
$stmt_notas->execute();
$total_notas = $stmt_notas->fetch(PDO::FETCH_ASSOC)['total'];

$sql_materiais = "SELECT COUNT(*) as total FROM almoxarifado_materiais";
$stmt_materiais = $pdo->prepare($sql_materiais);
$stmt_materiais->execute();
$total_materiais = $stmt_materiais->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="empenhos-menu-fixo">
    <table class="table table-bordered table-sm">
        <thead class="thead-light">
            <tr>
                <th class="text-center">
                    <i class="fas fa-tags"></i> Categorias
                </th>
                <th class="text-center">
                    <i class="fas fa-file-invoice-dollar"></i> Empenhos
                </th>
                <th class="text-center">
                    <i class="fas fa-receipt"></i> Notas Fiscais
                </th>
                <th class="text-center">
                    <i class="fas fa-boxes"></i> Materiais
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">
                    <div class="menu-item">
                        <span class="badge badge-primary"><?php echo $total_categorias; ?></span>
                        <a href="categoria_add.php" class="btn btn-sm btn-primary ml-2" title="Gerenciar Categorias">
                            <i class="fas fa-cog"></i>
                        </a>
                    </div>
                </td>
                <td class="text-center">
                    <div class="menu-item">
                        <span class="badge badge-success"><?php echo $total_empenhos; ?></span>
                        <a href="empenho_add.php" class="btn btn-sm btn-success ml-2" title="Gerenciar Empenhos">
                            <i class="fas fa-cog"></i>
                        </a>
                    </div>
                </td>
                <td class="text-center">
                    <div class="menu-item">
                        <span class="badge badge-info"><?php echo $total_notas; ?></span>
                        <a href="nota_fiscal_add.php" class="btn btn-sm btn-info ml-2" title="Gerenciar Notas Fiscais">
                            <i class="fas fa-cog"></i>
                        </a>
                    </div>
                </td>
                <td class="text-center">
                    <div class="menu-item">
                        <span class="badge badge-warning"><?php echo $total_materiais; ?></span>
                        <a href="material_add.php" class="btn btn-sm btn-warning ml-1" title="Gerenciar Materiais">
                            <i class="fas fa-cog"></i>
                        </a>
                        <a href="atualizar_valores_materiais.php" class="btn btn-sm btn-secondary ml-1" title="Atualizar Valores">
                            <i class="fas fa-sync"></i>
                        </a>
                        <a href="debug_valores.php" class="btn btn-sm btn-info ml-1" title="Debug Valores">
                            <i class="fas fa-bug"></i>
                        </a>
                        <a href="sincronizar_estoque.php" class="btn btn-sm btn-success ml-1" title="Sincronizar Estoque">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<style>
.empenhos-menu-fixo {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 10px;
}

.empenhos-menu-fixo .table {
    margin-bottom: 0;
}

.menu-item {
    display: flex;
    align-items: center;
    justify-content: center;
}

.empenhos-menu-fixo .badge {
    font-size: 14px;
    padding: 6px 10px;
}

.empenhos-menu-fixo .btn {
    padding: 4px 8px;
}
</style>