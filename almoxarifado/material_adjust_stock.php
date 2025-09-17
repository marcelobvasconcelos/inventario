<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Buscar o material
$sql_material = "SELECT * FROM almoxarifado_materiais WHERE id = ?";
$stmt_material = $pdo->prepare($sql_material);
$stmt_material->execute([$id]);
$material = $stmt_material->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    header("Location: index.php");
    exit;
}

// Processar zerar estoque
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['zerar_estoque'])){
    $motivo = trim($_POST['motivo_zerar']);
    
    if (empty($motivo)) {
        $error = "O motivo é obrigatório.";
    } else {
        $pdo->beginTransaction();
        try {
            $quantidade_zerada = $material['estoque_atual'];
            
            // Zerar estoque
            $sql_update = "UPDATE almoxarifado_materiais SET estoque_atual = 0 WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$id]);

            // Registrar movimentação
            $sql_mov = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id) VALUES (?, 'saida', ?, ?, 0, NOW(), ?)";
            $stmt_mov = $pdo->prepare($sql_mov);
            $stmt_mov->execute([$id, $quantidade_zerada, $quantidade_zerada, $_SESSION['id']]);

            $pdo->commit();
            $message = "Estoque zerado com sucesso.";
            $stmt_material->execute([$id]);
            $material = $stmt_material->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erro ao zerar estoque: " . $e->getMessage();
        }
    }
}

// Processar ajuste de estoque
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajustar_estoque'])){
    $quantidade = floatval($_POST['quantidade']);
    $motivo = trim($_POST['motivo']);

    if ($quantidade <= 0) {
        $error = "A quantidade deve ser maior que zero.";
    } elseif ($quantidade > $material['estoque_atual']) {
        $error = "A quantidade não pode ser maior que o estoque atual.";
    } elseif (empty($motivo)) {
        $error = "O motivo é obrigatório.";
    } else {
        // Iniciar transação
        $pdo->beginTransaction();

        try {
            // Atualizar estoque
            $novo_estoque = $material['estoque_atual'] - $quantidade;
            $sql_update = "UPDATE almoxarifado_materiais SET estoque_atual = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$novo_estoque, $id]);

            // Registrar movimentação
            $sql_mov = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id) VALUES (?, 'saida', ?, ?, ?, NOW(), ?)";
            $stmt_mov = $pdo->prepare($sql_mov);
            $stmt_mov->execute([$id, $quantidade, $material['estoque_atual'], $novo_estoque, $_SESSION['id']]);

            $pdo->commit();
            $message = "Estoque ajustado com sucesso.";
            // Recarregar dados do material
            $stmt_material->execute([$id]);
            $material = $stmt_material->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erro ao ajustar estoque: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Ajustar Estoque do Material</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3><?php echo htmlspecialchars($material['nome']); ?> (Código: <?php echo htmlspecialchars($material['codigo']); ?>)</h3>
        </div>
        <div class="card-body">
            <p><strong>Estoque Atual:</strong> <?php echo number_format($material['estoque_atual'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></p>
            <p><strong>Valor Unitário:</strong> R$ <?php echo number_format($material['valor_unitario'], 2, ',', '.'); ?></p>

            <form action="material_adjust_stock.php?id=<?php echo $id; ?>" method="post">
                <div class="form-group">
                    <label for="quantidade">Quantidade a Reduzir:</label>
                    <input type="number" id="quantidade" name="quantidade" step="0.01" min="0" max="<?php echo $material['estoque_atual']; ?>" required>
                    <small class="form-text text-muted">Máximo: <?php echo number_format($material['estoque_atual'], 2, ',', '.'); ?></small>
                </div>

                <div class="form-group">
                    <label for="motivo">Motivo da Redução:</label>
                    <textarea id="motivo" name="motivo" rows="3" required placeholder="Ex: Perda, avaria, correção de inventário, etc."></textarea>
                </div>

                <button type="submit" name="ajustar_estoque" class="btn btn-warning">Reduzir Estoque</button>
                <button type="button" class="btn btn-danger" onclick="mostrarZerarEstoque()">Zerar Estoque</button>
                <a href="index.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
    
    <!-- Modal para zerar estoque -->
    <div id="modalZerarEstoque" class="modal" style="display: none;">
        <div class="modal-content">
            <h4>Confirmar Zerar Estoque</h4>
            <p>Esta ação irá zerar completamente o estoque do material <strong><?php echo htmlspecialchars($material['nome']); ?></strong>.</p>
            <p>Estoque atual: <strong><?php echo number_format($material['estoque_atual'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></strong></p>
            
            <form action="material_adjust_stock.php?id=<?php echo $id; ?>" method="post">
                <div class="form-group">
                    <label for="motivo_zerar">Motivo para zerar o estoque:</label>
                    <textarea id="motivo_zerar" name="motivo_zerar" rows="3" required placeholder="Ex: Inventário, perda total, descarte, etc."></textarea>
                </div>
                
                <button type="submit" name="zerar_estoque" class="btn btn-danger">Confirmar Zerar</button>
                <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
            </form>
        </div>
    </div>
</div>

<style>
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input, .form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-text {
    color: #6c757d;
    font-size: 0.875em;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin-right: 10px;
}

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background-color: #e0a800;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
}

.modal-content h4 {
    margin-top: 0;
    color: #dc3545;
}
</style>

<script>
function mostrarZerarEstoque() {
    document.getElementById('modalZerarEstoque').style.display = 'block';
}

function fecharModal() {
    document.getElementById('modalZerarEstoque').style.display = 'none';
}

// Fechar modal ao clicar fora dele
window.onclick = function(event) {
    const modal = document.getElementById('modalZerarEstoque');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>