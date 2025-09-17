<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Verificar se foi passado um número de empenho
if(!isset($_GET['numero']) || empty($_GET['numero'])){
    header('Location: empenho_add.php');
    exit;
}

$numero = $_GET['numero'];

// Buscar empenho no banco
$sql_select = "SELECT * FROM empenhos_insumos WHERE numero = ?";
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute([$numero]);
$empenho = $stmt_select->fetch(PDO::FETCH_ASSOC);

// Se não encontrar o empenho, redirecionar
if(!$empenho){
    header('Location: empenho_add.php');
    exit;
}

// Processar formulário de edição
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_empenho'])){
    $data_emissao = trim($_POST["data_emissao"]);
    $valor = trim($_POST["valor"]);
    $status = trim($_POST["status"]);

    // Validação
    if(empty($data_emissao) || empty($valor) || empty($status)){
        $error = "Data de emissão, valor e status são obrigatórios.";
    } elseif(!is_numeric($valor) || $valor <= 0){
        $error = "Valor deve ser um número positivo.";
    } else {
        // Verificar se há notas fiscais vinculadas e calcular valor utilizado
        $sql_utilizado = "SELECT COALESCE(SUM(nota_valor), 0) as valor_utilizado 
                         FROM notas_fiscais WHERE empenho_numero = ?";
        $stmt_utilizado = $pdo->prepare($sql_utilizado);
        $stmt_utilizado->execute([$numero]);
        $valor_utilizado = $stmt_utilizado->fetchColumn();
        
        // Verificar se o novo valor não é menor que o valor já utilizado
        if($valor < $valor_utilizado){
            $error = "Não é possível atualizar o empenho. O novo valor (R$ " . number_format($valor, 2, ',', '.') . ") é menor que o valor já utilizado em notas fiscais (R$ " . number_format($valor_utilizado, 2, ',', '.') . ").";
        } else {
            $pdo->beginTransaction();
            try {
                // Calcular novo saldo
                $novo_saldo = $valor - $valor_utilizado;
                
                // Atualizar empenho com novo saldo
                $sql_update = "UPDATE empenhos_insumos SET data_emissao = ?, valor = ?, saldo = ?, status = ? WHERE numero = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$data_emissao, $valor, $novo_saldo, $status, $numero]);
                
                $pdo->commit();
                $message = "Empenho atualizado com sucesso! Saldo recalculado.";
                // Atualizar os dados do empenho
                $empenho['data_emissao'] = $data_emissao;
                $empenho['valor'] = $valor;
                $empenho['saldo'] = $novo_saldo;
                $empenho['status'] = $status;
            } catch (Exception $e) {
                $pdo->rollback();
                $error = "Erro ao atualizar empenho. Tente novamente. Detalhes: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <h2>Editar Empenho</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Empenho #<?php echo $empenho['numero']; ?></h3>
        </div>
        <div class="card-body">
            <form action="empenho_edit.php?numero=<?php echo $numero; ?>" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="numero">Número do Empenho:</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($empenho['numero']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="data_emissao">Data de Emissão:</label>
                            <input type="date" class="form-control" id="data_emissao" name="data_emissao" value="<?php echo isset($data_emissao) ? htmlspecialchars($data_emissao) : htmlspecialchars($empenho['data_emissao']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="valor">Valor do Empenho:</label>
                            <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0" value="<?php echo isset($valor) ? htmlspecialchars($valor) : htmlspecialchars($empenho['valor'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="Aberto" <?php echo (isset($status) && $status == 'Aberto') || $empenho['status'] == 'Aberto' ? 'selected' : ''; ?>>Aberto</option>
                                <option value="Fechado" <?php echo (isset($status) && $status == 'Fechado') || $empenho['status'] == 'Fechado' ? 'selected' : ''; ?>>Fechado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="editar_empenho" class="btn btn-primary">Atualizar Empenho</button>
                <a href="empenho_add.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
