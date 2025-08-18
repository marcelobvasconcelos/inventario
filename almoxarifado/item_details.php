<?php
require_once '../includes/header.php';
require_once '../config/db.php';

$item_id = $_GET['id'];

$sql_item = "SELECT * FROM almoxarifado_materiais WHERE id = ?";

if($stmt_item = mysqli_prepare($link, $sql_item)){
    mysqli_stmt_bind_param($stmt_item, "i", $item_id);
    mysqli_stmt_execute($stmt_item);
    $result_item = mysqli_stmt_get_result($stmt_item);
    $item = mysqli_fetch_assoc($result_item);
    if(!$item) {
        echo "<div class='alert alert-danger'>Item não encontrado.</div>";
    }
} else {
    echo "Erro ao preparar a consulta de detalhes do item: " . mysqli_error($link);
    $item = false;
}

// Busca o histórico de entradas do item
$sql_entradas = "SELECT 
                    e.*, 
                    u.nome as usuario_nome
                FROM almoxarifado_entradas e
                JOIN usuarios u ON e.usuario_id = u.id
                WHERE e.material_id = ?
                ORDER BY e.data_entrada DESC, e.data_cadastro DESC";

$entradas = [];
if($stmt_entradas = mysqli_prepare($link, $sql_entradas)){
    mysqli_stmt_bind_param($stmt_entradas, "i", $item_id);
    mysqli_stmt_execute($stmt_entradas);
    $result_entradas = mysqli_stmt_get_result($stmt_entradas);
    while($row = mysqli_fetch_assoc($result_entradas)){
        $entradas[] = $row;
    }
    mysqli_stmt_close($stmt_entradas);
}

// Busca o histórico de saídas do item
$sql_saidas = "SELECT 
                    s.*, 
                    u.nome as usuario_nome
                FROM almoxarifado_saidas s
                JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.material_id = ?
                ORDER BY s.data_saida DESC, s.data_cadastro DESC";

$saidas = [];
if($stmt_saidas = mysqli_prepare($link, $sql_saidas)){
    mysqli_stmt_bind_param($stmt_saidas, "i", $item_id);
    mysqli_stmt_execute($stmt_saidas);
    $result_saidas = mysqli_stmt_get_result($stmt_saidas);
    while($row = mysqli_fetch_assoc($result_saidas)){
        $saidas[] = $row;
    }
    mysqli_stmt_close($stmt_saidas);
}

// Busca o histórico de movimentações do item
$sql_movimentacoes = "SELECT 
                        m.*, 
                        u.nome as usuario_nome
                    FROM almoxarifado_movimentacoes m
                    JOIN usuarios u ON m.usuario_id = u.id
                    WHERE m.material_id = ?
                    ORDER BY m.data_movimentacao DESC";

$movimentacoes = [];
if($stmt_mov = mysqli_prepare($link, $sql_movimentacoes)){
    mysqli_stmt_bind_param($stmt_mov, "i", $item_id);
    mysqli_stmt_execute($stmt_mov);
    $result_mov = mysqli_stmt_get_result($stmt_mov);
    while($row = mysqli_fetch_assoc($result_mov)){
        $movimentacoes[] = $row;
    }
    mysqli_stmt_close($stmt_mov);
}

?>

<style>
    .almoxarifado .details-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .almoxarifado .details-section {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .almoxarifado .details-section h3 {
        margin-top: 0;
        color: #28a745;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
        font-weight: 600;
    }
    .almoxarifado .details-section p {
        margin: 10px 0;
        line-height: 1.6;
    }
    .almoxarifado .details-section p strong {
        color: #555;
        min-width: 180px;
        display: inline-block;
        font-weight: 600;
    }
</style>

<h2><i class="fas fa-info-circle"></i> Detalhes do Item: <?php echo $item ? $item['nome'] : ''; ?></h2>
<p><a href="javascript:history.back()" class="btn-custom"><i class="fas fa-arrow-left"></i> Voltar</a></p>

<?php if($item): ?>
    <div class="details-container">
        <div class="details-section">
            <h3><i class="fas fa-clipboard-list"></i> Dados Gerais</h3>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($item['id'] ?? 'Não preenchido'); ?></p>
            <p><strong>Código:</strong> <?php echo htmlspecialchars($item['codigo'] ?? 'Não preenchido'); ?></p>
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($item['nome'] ?? 'Não preenchido'); ?></p>
            <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($item['descricao'] ?? 'Não preenchido')); ?></p>
            <p><strong>Categoria:</strong> <?php echo htmlspecialchars($item['categoria'] ?? 'Não preenchido'); ?></p>
            <p><strong>Unidade de Medida:</strong> <?php echo htmlspecialchars($item['unidade_medida'] ?? 'Não preenchido'); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($item['status'] ?? 'Não preenchido'); ?></p>
            <p><strong>Data de Cadastro:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($item['data_cadastro'] ?? 'now'))); ?></p>
        </div>

        <div class="details-section">
            <h3><i class="fas fa-box"></i> Estoque</h3>
            <p><strong>Estoque Mínimo:</strong> <?php echo htmlspecialchars($item['estoque_minimo'] ?? '0'); ?></p>
            <p><strong>Estoque Atual:</strong> <?php echo htmlspecialchars($item['estoque_atual'] ?? '0'); ?></p>
            <p><strong>Valor Unitário:</strong> R$ <?php echo htmlspecialchars(number_format($item['valor_unitario'] ?? 0, 2, ',', '.')); ?></p>
        </div>
    </div>

    <div class="details-section">
        <h3><i class="fas fa-truck-loading"></i> Histórico de Entradas</h3>
        <?php if(!empty($entradas)): ?>
            <table class="table-striped table-hover">
                <thead>
                    <tr>
                        <th>Data de Entrada</th>
                        <th>Quantidade</th>
                        <th>Valor Unitário</th>
                        <th>Fornecedor</th>
                        <th>Nota Fiscal</th>
                        <th>Registrado Por</th>
                        <th>Data de Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($entradas as $entrada): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($entrada['data_entrada']))); ?></td>
                        <td><?php echo htmlspecialchars($entrada['quantidade']); ?></td>
                        <td>R$ <?php echo htmlspecialchars(number_format($entrada['valor_unitario'], 2, ',', '.')); ?></td>
                        <td><?php echo htmlspecialchars($entrada['fornecedor'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($entrada['nota_fiscal'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($entrada['usuario_nome']); ?></td>
                        <td><?php echo isset($entrada['data_cadastro']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($entrada['data_cadastro']))) : 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma entrada registrada para este item.</p>
        <?php endif; ?>
    </div>

    <div class="details-section">
        <h3><i class="fas fa-truck"></i> Histórico de Saídas</h3>
        <?php if(!empty($saidas)): ?>
            <table class="table-striped table-hover">
                <thead>
                    <tr>
                        <th>Data de Saída</th>
                        <th>Quantidade</th>
                        <th>Setor de Destino</th>
                        <th>Responsável pela Saída</th>
                        <th>Registrado Por</th>
                        <th>Data de Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($saidas as $saida): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($saida['data_saida']))); ?></td>
                        <td><?php echo htmlspecialchars($saida['quantidade']); ?></td>
                        <td><?php echo htmlspecialchars($saida['setor_destino'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($saida['responsavel_saida'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($saida['usuario_nome']); ?></td>
                        <td><?php echo isset($saida['data_cadastro']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($saida['data_cadastro']))) : 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma saída registrada para este item.</p>
        <?php endif; ?>
    </div>

    <div class="details-section">
        <h3><i class="fas fa-exchange-alt"></i> Histórico de Movimentações</h3>
        <?php if(!empty($movimentacoes)): ?>
            <table class="table-striped table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Quantidade</th>
                        <th>Saldo Anterior</th>
                        <th>Saldo Atual</th>
                        <th>Registrado Por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($movimentacoes as $mov): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($mov['data_movimentacao']))); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($mov['tipo'])); ?></td>
                        <td><?php echo htmlspecialchars($mov['quantidade']); ?></td>
                        <td><?php echo htmlspecialchars($mov['saldo_anterior']); ?></td>
                        <td><?php echo htmlspecialchars($mov['saldo_atual']); ?></td>
                        <td><?php echo htmlspecialchars($mov['usuario_nome']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma movimentação registrada para este item.</p>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>