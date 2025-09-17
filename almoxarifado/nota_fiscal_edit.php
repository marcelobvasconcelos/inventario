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

// Verificar se foi passado um número de nota fiscal
if(!isset($_GET['nota']) || empty($_GET['nota'])){
    header('Location: nota_fiscal_add.php');
    exit;
}

$nota_numero = $_GET['nota'];

// Buscar nota fiscal no banco
$sql_select = "SELECT * FROM notas_fiscais WHERE nota_numero = ?";
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute([$nota_numero]);
$nota = $stmt_select->fetch(PDO::FETCH_ASSOC);

// Se não encontrar a nota fiscal, redirecionar
if(!$nota){
    header('Location: nota_fiscal_add.php');
    exit;
}

// Processar formulário de edição
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_nota_fiscal'])){
    $nota_valor = trim($_POST["nota_valor"]);
    $empenho_numero = trim($_POST["empenho_numero"]);
    $fornecedor = trim($_POST["fornecedor"]);
    $cnpj = trim($_POST["cnpj"]);

    // Validação
    if(empty($nota_valor) || empty($empenho_numero) || empty($fornecedor) || empty($cnpj)){
        $error = "Todos os campos são obrigatórios.";
    } elseif(!is_numeric($nota_valor) || $nota_valor <= 0){
        $error = "Valor deve ser um número positivo.";
    } else {
        // Verificar se o empenho existe
        $sql_check_empenho = "SELECT numero FROM empenhos_insumos WHERE numero = ?";
        $stmt_check_empenho = $pdo->prepare($sql_check_empenho);
        $stmt_check_empenho->execute([$empenho_numero]);

        if($stmt_check_empenho->rowCount() == 0){
            $error = "Empenho não encontrado.";
        } else {
            // Atualizar nota fiscal e recalcular saldo
            $pdo->beginTransaction();
            try {
                // Calcular valor já utilizado em entradas
                $sql_utilizado = "SELECT COALESCE(SUM(quantidade * valor_unitario), 0) as valor_utilizado 
                                 FROM almoxarifado_entradas WHERE nota_fiscal = ?";
                $stmt_utilizado = $pdo->prepare($sql_utilizado);
                $stmt_utilizado->execute([$nota_numero]);
                $valor_utilizado = $stmt_utilizado->fetchColumn();
                
                // Calcular novo saldo
                $novo_saldo = $nota_valor - $valor_utilizado;
                
                // Verificar se o saldo ficaria negativo
                if($novo_saldo < 0){
                    throw new Exception("Não é possível atualizar a nota fiscal. O novo valor (R$ " . number_format($nota_valor, 2, ',', '.') . ") é menor que o valor já utilizado em entradas (R$ " . number_format($valor_utilizado, 2, ',', '.') . "). Saldo resultante seria negativo.");
                }
                
                // Atualizar nota fiscal com novo saldo
                $sql_update = "UPDATE notas_fiscais SET nota_valor = ?, empenho_numero = ?, fornecedor = ?, cnpj = ?, saldo = ? WHERE nota_numero = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $result = $stmt_update->execute([$nota_valor, $empenho_numero, $fornecedor, $cnpj, $novo_saldo, $nota_numero]);
                
                // Verificar se o saldo do empenho ficaria negativo
                $sql_check_empenho = "SELECT ei.valor - COALESCE((
                                         SELECT SUM(nf.nota_valor)
                                         FROM notas_fiscais nf
                                         WHERE nf.empenho_numero = ei.numero
                                     ), 0) as novo_saldo_empenho
                                     FROM empenhos_insumos ei
                                     WHERE ei.numero = ?";
                $stmt_check_empenho = $pdo->prepare($sql_check_empenho);
                $stmt_check_empenho->execute([$empenho_numero]);
                $novo_saldo_empenho = $stmt_check_empenho->fetchColumn();
                
                if($novo_saldo_empenho < 0){
                    throw new Exception("Não é possível atualizar a nota fiscal. O saldo do empenho ficaria negativo (R$ " . number_format($novo_saldo_empenho, 2, ',', '.') . ").");
                }
                
                // Recalcular saldo do empenho
                $sql_saldo_empenho = "UPDATE empenhos_insumos ei 
                                     SET saldo = ei.valor - COALESCE((
                                         SELECT SUM(nf.nota_valor)
                                         FROM notas_fiscais nf
                                         WHERE nf.empenho_numero = ei.numero
                                     ), 0)
                                     WHERE ei.numero = ?";
                $stmt_saldo_empenho = $pdo->prepare($sql_saldo_empenho);
                $stmt_saldo_empenho->execute([$empenho_numero]);
                
                $pdo->commit();
                $message = "Nota fiscal atualizada com sucesso! Saldo recalculado.";
                // Atualizar os dados da nota
                $nota['nota_valor'] = $nota_valor;
                $nota['empenho_numero'] = $empenho_numero;
                $nota['fornecedor'] = $fornecedor;
                $nota['cnpj'] = $cnpj;
                $nota['saldo'] = $novo_saldo;
            } catch (Exception $e) {
                $pdo->rollback();
                $error = "Erro ao atualizar nota fiscal. Tente novamente. Detalhes: " . $e->getMessage();
            }
        }
    }
}

// Buscar todos os empenhos para o select
$sql_empenhos = "SELECT numero, saldo FROM empenhos_insumos WHERE status = 'Aberto' ORDER BY numero ASC";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$empenhos = $stmt_empenhos->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Editar Nota Fiscal</h2>
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
            <h3>Editar Nota Fiscal #<?php echo $nota['nota_numero']; ?></h3>
        </div>
        <div class="card-body">
            <form action="nota_fiscal_edit.php?nota=<?php echo $nota_numero; ?>" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nota_numero">Número da Nota Fiscal:</label>
                            <input type="text" class="form-control" id="nota_numero" name="nota_numero" value="<?php echo htmlspecialchars($nota['nota_numero']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nota_valor">Valor da Nota Fiscal:</label>
                            <input type="number" class="form-control" id="nota_valor" name="nota_valor" step="0.01" min="0" value="<?php echo isset($nota_valor) ? htmlspecialchars($nota_valor) : htmlspecialchars($nota['nota_valor']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="empenho_numero">Empenho:</label>
                            <select class="form-control" id="empenho_numero" name="empenho_numero" required>
                                <option value="">Selecione um empenho</option>
                                <?php foreach($empenhos as $empenho): ?>
                                    <option value="<?php echo htmlspecialchars($empenho['numero']); ?>" <?php echo ($nota['empenho_numero'] == $empenho['numero']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($empenho['numero'] . ' (Saldo: R$ ' . number_format($empenho['saldo'], 2, ',', '.')); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fornecedor">Fornecedor:</label>
                            <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?php echo isset($fornecedor) ? htmlspecialchars($fornecedor) : htmlspecialchars($nota['fornecedor'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cnpj">CNPJ do Fornecedor:</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?php echo isset($cnpj) ? htmlspecialchars($cnpj) : htmlspecialchars($nota['cnpj'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <button type="submit" name="editar_nota_fiscal" class="btn btn-primary">Atualizar Nota Fiscal</button>
                <a href="nota_fiscal_add.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>