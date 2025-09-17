<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Verificar se foi passado um número de nota fiscal
if(!isset($_GET['nota']) || empty($_GET['nota'])){
    header('Location: nota_fiscal_add.php');
    exit;
}

$nota_numero = $_GET['nota'];

// Buscar dados da nota fiscal
$sql_nota = "SELECT * FROM notas_fiscais WHERE nota_numero = ?";
$stmt_nota = $pdo->prepare($sql_nota);
$stmt_nota->execute([$nota_numero]);
$nota = $stmt_nota->fetch(PDO::FETCH_ASSOC);

if(!$nota){
    echo "<div class='alert alert-danger'>Nota fiscal não encontrada.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Buscar todas as entradas de materiais desta nota fiscal
$sql_entradas = "SELECT ae.*, am.codigo, am.nome as material_nome, am.unidade_medida
                 FROM almoxarifado_entradas ae
                 JOIN almoxarifado_materiais am ON ae.material_id = am.id
                 WHERE ae.nota_fiscal = ?
                 ORDER BY ae.data_entrada DESC, ae.id DESC";
$stmt_entradas = $pdo->prepare($sql_entradas);
$stmt_entradas->execute([$nota_numero]);
$entradas = $stmt_entradas->fetchAll(PDO::FETCH_ASSOC);

// Calcular totais
$total_entradas = count($entradas);
$valor_total_entradas = 0;
foreach($entradas as $entrada){
    $valor_total_entradas += $entrada['quantidade'] * $entrada['valor_unitario'];
}
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Detalhes da Nota Fiscal</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Nota Fiscal #<?php echo htmlspecialchars($nota['nota_numero']); ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Número:</strong> <?php echo htmlspecialchars($nota['nota_numero']); ?></p>
                    <p><strong>Valor da Nota:</strong> R$ <?php echo number_format($nota['nota_valor'], 2, ',', '.'); ?></p>
                    <p><strong>Saldo Disponível:</strong> R$ <?php echo number_format($nota['saldo'] ?? 0, 2, ',', '.'); ?></p>
                    <p><strong>Empenho:</strong> <?php echo htmlspecialchars($nota['empenho_numero']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fornecedor:</strong> <?php echo htmlspecialchars($nota['fornecedor'] ?? 'N/A'); ?></p>
                    <p><strong>CNPJ:</strong> <?php echo htmlspecialchars($nota['cnpj'] ?? 'N/A'); ?></p>
                    <p><strong>Total de Entradas:</strong> <?php echo $total_entradas; ?></p>
                    <p><strong>Valor Total das Entradas:</strong> R$ <?php echo number_format($valor_total_entradas, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if(!empty($entradas)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h3>Entradas de Materiais</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Código</th>
                            <th>Material</th>
                            <th>Quantidade</th>
                            <th>Valor Unitário</th>
                            <th>Valor Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($entradas as $entrada): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($entrada['data_entrada'])); ?></td>
                                <td><?php echo htmlspecialchars($entrada['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($entrada['material_nome']); ?></td>
                                <td><?php echo number_format($entrada['quantidade'], 2, ',', '.'); ?> <?php echo htmlspecialchars($entrada['unidade_medida']); ?></td>
                                <td>R$ <?php echo number_format($entrada['valor_unitario'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($entrada['quantidade'] * $entrada['valor_unitario'], 2, ',', '.'); ?></td>
                                <td>
                                    <a href="entrada_edit.php?id=<?php echo $entrada['id']; ?>" class="btn btn-warning btn-sm" title="Editar Entrada">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="excluirEntrada(<?php echo $entrada['id']; ?>, '<?php echo htmlspecialchars($entrada['material_nome'], ENT_QUOTES); ?>')" title="Excluir Entrada">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card mt-4">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Nenhuma entrada de material foi registrada para esta nota fiscal ainda.
                <br><br>
                <a href="entrada_material.php?nota=<?php echo $nota['nota_numero']; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Registrar Entrada de Material
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="nota_fiscal_add.php" class="btn btn-secondary">Voltar</a>
        <a href="entrada_material.php?nota=<?php echo $nota['nota_numero']; ?>" class="btn btn-success">
            <i class="fas fa-plus"></i> Adicionar Material
        </a>
    </div>
</div>

<script>
function excluirEntrada(id, materialNome) {
    if (confirm('Tem certeza que deseja excluir a entrada do material "' + materialNome + '"?\n\nEsta ação irá reverter o estoque e não pode ser desfeita!')) {
        window.location.href = 'entrada_delete.php?id=' + id + '&volta=' + encodeURIComponent('nota_fiscal_detalhes.php?nota=<?php echo $nota_numero; ?>');
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>