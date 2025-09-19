<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    require_once '../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Verificar se foi passado ID da entrada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$entrada_id = (int)$_GET['id'];

// Buscar dados da entrada
$sql_entrada = "SELECT e.*, m.nome as material_nome, m.codigo as material_codigo, nf.saldo as nota_saldo
                FROM almoxarifado_entradas e
                JOIN almoxarifado_materiais m ON e.material_id = m.id
                LEFT JOIN notas_fiscais nf ON e.nota_fiscal = nf.nota_numero
                WHERE e.id = ?";
$stmt_entrada = $pdo->prepare($sql_entrada);
$stmt_entrada->execute([$entrada_id]);
$entrada = $stmt_entrada->fetch(PDO::FETCH_ASSOC);

if (!$entrada) {
    header("Location: index.php");
    exit;
}

// Processar formulário
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_entrada'])){
    $nova_quantidade = (float)trim($_POST["quantidade"]);
    $novo_valor_unitario = (float)trim($_POST["valor_unitario"]);
    
    if($nova_quantidade <= 0 || $novo_valor_unitario < 0){
        $error = "Quantidade deve ser maior que zero e valor não pode ser negativo.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calcular diferenças
            $diff_quantidade = $nova_quantidade - $entrada['quantidade'];
            $diff_valor = ($nova_quantidade * $novo_valor_unitario) - ($entrada['quantidade'] * $entrada['valor_unitario']);
            
            // Verificar saldo da nota fiscal se houver aumento de valor
            if ($diff_valor > 0 && !empty($entrada['nota_fiscal'])) {
                $saldo_disponivel = $entrada['nota_saldo'];
                if ($saldo_disponivel < $diff_valor) {
                    throw new Exception("Saldo insuficiente na nota fiscal. Saldo disponível: R$ " . number_format($saldo_disponivel, 2, ',', '.') . ". Diferença necessária: R$ " . number_format($diff_valor, 2, ',', '.'));
                }
            }
            
            // Atualizar entrada
            $sql_update = "UPDATE almoxarifado_entradas SET quantidade = ?, valor_unitario = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$nova_quantidade, $novo_valor_unitario, $entrada_id]);
            
            // Atualizar estoque do material
            $sql_estoque = "UPDATE almoxarifado_materiais SET estoque_atual = estoque_atual + ? WHERE id = ?";
            $stmt_estoque = $pdo->prepare($sql_estoque);
            $stmt_estoque->execute([$diff_quantidade, $entrada['material_id']]);
            
            // Atualizar saldo da nota fiscal se houver
            if (!empty($entrada['nota_fiscal'])) {
                $sql_nota = "UPDATE notas_fiscais SET saldo = saldo - ? WHERE nota_numero = ?";
                $stmt_nota = $pdo->prepare($sql_nota);
                $stmt_nota->execute([$diff_valor, $entrada['nota_fiscal']]);
            }
            
            // Registrar movimentação se houver diferença de quantidade
            if ($diff_quantidade != 0) {
                $sql_saldo = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
                $stmt_saldo = $pdo->prepare($sql_saldo);
                $stmt_saldo->execute([$entrada['material_id']]);
                $saldo_atual = $stmt_saldo->fetchColumn();
                $saldo_anterior = $saldo_atual - $diff_quantidade;
                
                $tipo_mov = $diff_quantidade > 0 ? 'entrada' : 'saida';
                $sql_mov = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id, referencia_id) 
                           VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
                $stmt_mov = $pdo->prepare($sql_mov);
                $stmt_mov->execute([$entrada['material_id'], $tipo_mov, abs($diff_quantidade), $saldo_anterior, $saldo_atual, $_SESSION['id'], $entrada_id]);
            }
            
            $pdo->commit();
            $message = "Entrada atualizada com sucesso!";
            
            // Recarregar dados
            $stmt_entrada->execute([$entrada_id]);
            $entrada = $stmt_entrada->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Editar Entrada de Material</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Entrada #<?php echo $entrada_id; ?></h3>
        </div>
        <div class="card-body">
            <form action="entrada_edit.php?id=<?php echo $entrada_id; ?>" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Material:</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($entrada['material_codigo'] . ' - ' . $entrada['material_nome']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nota Fiscal:</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($entrada['nota_fiscal'] ?? 'N/A'); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="quantidade">Quantidade:</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" step="0.01" min="0.01" value="<?php echo $entrada['quantidade']; ?>" required>
                            <small class="text-muted">Atual: <?php echo $entrada['quantidade']; ?></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="valor_unitario">Valor Unitário (R$):</label>
                            <input type="number" class="form-control" id="valor_unitario" name="valor_unitario" step="0.01" min="0" value="<?php echo $entrada['valor_unitario']; ?>" required>
                            <small class="text-muted">Atual: R$ <?php echo number_format($entrada['valor_unitario'], 2, ',', '.'); ?></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Valor Total:</label>
                            <input type="text" class="form-control" id="valor_total" readonly>
                            <small class="text-muted">Atual: R$ <?php echo number_format($entrada['quantidade'] * $entrada['valor_unitario'], 2, ',', '.'); ?></small>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($entrada['nota_fiscal'])): ?>
                <div class="alert alert-info">
                    <strong>Saldo da Nota Fiscal:</strong> R$ <?php echo number_format($entrada['nota_saldo'], 2, ',', '.'); ?>
                </div>
                <?php endif; ?>
                
                <button type="submit" name="editar_entrada" class="btn btn-primary">Salvar Alterações</button>
                <a href="material_edit.php?id=<?php echo $entrada['material_id']; ?>" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
</div>

<script>
function calcularValorTotal() {
    const quantidade = parseFloat(document.getElementById('quantidade').value) || 0;
    const valorUnitario = parseFloat(document.getElementById('valor_unitario').value) || 0;
    const valorTotal = quantidade * valorUnitario;
    document.getElementById('valor_total').value = 'R$ ' + valorTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

document.getElementById('quantidade').addEventListener('input', calcularValorTotal);
document.getElementById('valor_unitario').addEventListener('input', calcularValorTotal);

// Calcular valor inicial
calcularValorTotal();
</script>

<?php
require_once '../includes/footer.php';
?>