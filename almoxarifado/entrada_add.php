<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(__DIR__);

require_once $base_path . '/includes/header.php';
require_once $base_path . '/config/db.php';
require_once 'config.php';

// Verificar permissão de acesso
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para acessar este módulo.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Inicializa variáveis para os campos do formulário e mensagens de erro
$material_id = $quantidade = $valor_unitario = $fornecedor = $nota_fiscal = $data_entrada = "";
$material_id_err = $quantidade_err = $valor_unitario_err = $data_entrada_err = "";

// Buscar materiais ativos para o dropdown
$materiais_result = mysqli_query($link, "SELECT id, codigo, nome FROM almoxarifado_materiais WHERE status = 'ativo' ORDER BY nome ASC");

// Processa o formulário quando ele é submetido (método POST)
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validação e sanitização dos campos do formulário
    if(empty($_POST["material_id"])){
        $material_id_err = "Por favor, selecione um material.";
    } else {
        $material_id = $_POST["material_id"];
    }
    
    if(empty($_POST["quantidade"]) || $_POST["quantidade"] <= 0){
        $quantidade_err = "Por favor, insira uma quantidade válida.";
    } else {
        $quantidade = $_POST["quantidade"];
    }
    
    if(empty($_POST["valor_unitario"]) || $_POST["valor_unitario"] < 0){
        $valor_unitario_err = "Por favor, insira um valor unitário válido.";
    } else {
        $valor_unitario = $_POST["valor_unitario"];
    }
    
    $fornecedor = trim($_POST["fornecedor"]);
    $nota_fiscal = trim($_POST["nota_fiscal"]);
    
    if(empty($_POST["data_entrada"])){
        $data_entrada_err = "Por favor, insira a data de entrada.";
    } else {
        $data_entrada = $_POST["data_entrada"];
    }
    
    // Se não houver erros de validação, insere a entrada no banco de dados
    if(empty($material_id_err) && empty($quantidade_err) && empty($valor_unitario_err) && empty($data_entrada_err)){
        // Iniciar transação
        mysqli_begin_transaction($link);
        
        try {
            // 1. Inserir a entrada
            $sql_entrada = "INSERT INTO almoxarifado_entradas (material_id, quantidade, valor_unitario, fornecedor, nota_fiscal, data_entrada, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_entrada = mysqli_prepare($link, $sql_entrada);
            mysqli_stmt_bind_param($stmt_entrada, "iddsssi", $material_id, $quantidade, $valor_unitario, $fornecedor, $nota_fiscal, $data_entrada, $_SESSION['id']);
            mysqli_stmt_execute($stmt_entrada);
            $entrada_id = mysqli_insert_id($link);
            mysqli_stmt_close($stmt_entrada);
            
            // 2. Atualizar o estoque do material
            // Primeiro, buscar o estoque atual
            $sql_estoque_atual = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
            $stmt_estoque = mysqli_prepare($link, $sql_estoque_atual);
            mysqli_stmt_bind_param($stmt_estoque, "i", $material_id);
            mysqli_stmt_execute($stmt_estoque);
            $result_estoque = mysqli_stmt_get_result($stmt_estoque);
            $material = mysqli_fetch_assoc($result_estoque);
            $estoque_anterior = $material['estoque_atual'];
            $novo_estoque = $estoque_anterior + $quantidade;
            mysqli_stmt_close($stmt_estoque);
            
            // Atualizar o estoque
            $sql_update_estoque = "UPDATE almoxarifado_materiais SET estoque_atual = ? WHERE id = ?";
            $stmt_update_estoque = mysqli_prepare($link, $sql_update_estoque);
            mysqli_stmt_bind_param($stmt_update_estoque, "di", $novo_estoque, $material_id);
            mysqli_stmt_execute($stmt_update_estoque);
            mysqli_stmt_close($stmt_update_estoque);
            
            // 3. Registrar a movimentação
            $sql_movimentacao = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, usuario_id, referencia_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_movimentacao = mysqli_prepare($link, $sql_movimentacao);
            mysqli_stmt_bind_param($stmt_movimentacao, "isidddi", $material_id, ALMOXARIFADO_ENTRADA, $quantidade, $estoque_anterior, $novo_estoque, $_SESSION['id'], $entrada_id);
            mysqli_stmt_execute($stmt_movimentacao);
            mysqli_stmt_close($stmt_movimentacao);
            
            // Confirmar transação
            mysqli_commit($link);
            
            header("location: entradas.php");
            exit();
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            mysqli_rollback($link);
            echo "<div class='alert alert-danger'>Erro ao registrar entrada: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2>Registrar Nova Entrada</h2>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-grid">
        <div>
            <label>Material *</label>
            <select name="material_id" required>
                <option value="">Selecione um material</option>
                <?php while($material = mysqli_fetch_assoc($materiais_result)): ?>
                    <option value="<?php echo $material['id']; ?>" <?php echo ($material_id == $material['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($material['codigo'] . ' - ' . $material['nome']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <span class="help-block"><?php echo $material_id_err; ?></span>
        </div>
        <div>
            <label>Quantidade *</label>
            <input type="number" step="0.01" name="quantidade" value="<?php echo htmlspecialchars($quantidade); ?>" required>
            <span class="help-block"><?php echo $quantidade_err; ?></span>
        </div>
        <div>
            <label>Valor Unitário (R$) *</label>
            <input type="number" step="0.01" name="valor_unitario" value="<?php echo htmlspecialchars($valor_unitario); ?>" required>
            <span class="help-block"><?php echo $valor_unitario_err; ?></span>
        </div>
        <div>
            <label>Fornecedor</label>
            <input type="text" name="fornecedor" value="<?php echo htmlspecialchars($fornecedor); ?>">
        </div>
        <div>
            <label>Nota Fiscal</label>
            <input type="text" name="nota_fiscal" value="<?php echo htmlspecialchars($nota_fiscal); ?>">
        </div>
        <div>
            <label>Data de Entrada *</label>
            <input type="date" name="data_entrada" value="<?php echo htmlspecialchars($data_entrada); ?>" required>
            <span class="help-block"><?php echo $data_entrada_err; ?></span>
        </div>
    </div>
    <div>
        <input type="submit" value="Registrar Entrada">
        <a href="entradas.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once $base_path . '/includes/footer.php';
?>