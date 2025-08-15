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
$material_id = $quantidade = $setor_destino = $responsavel_saida = $data_saida = "";
$material_id_err = $quantidade_err = $data_saida_err = "";

// Buscar materiais ativos para o dropdown
$materiais_result = mysqli_query($link, "SELECT id, codigo, nome, estoque_atual FROM almoxarifado_materiais WHERE status = 'ativo' ORDER BY nome ASC");

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
        // Verificar se há estoque suficiente
        $sql_check_estoque = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
        if($stmt_check = mysqli_prepare($link, $sql_check_estoque)){
            mysqli_stmt_bind_param($stmt_check, "i", $material_id);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            $material = mysqli_fetch_assoc($result_check);
            if($material['estoque_atual'] < $quantidade){
                $quantidade_err = "Quantidade solicitada maior que o estoque disponível (" . formatar_quantidade($material['estoque_atual']) . ").";
            }
            mysqli_stmt_close($stmt_check);
        }
    }
    
    $setor_destino = trim($_POST["setor_destino"]);
    $responsavel_saida = trim($_POST["responsavel_saida"]);
    
    if(empty($_POST["data_saida"])){
        $data_saida_err = "Por favor, insira a data de saída.";
    } else {
        $data_saida = $_POST["data_saida"];
    }
    
    // Se não houver erros de validação, insere a saída no banco de dados
    if(empty($material_id_err) && empty($quantidade_err) && empty($data_saida_err)){
        // Iniciar transação
        mysqli_begin_transaction($link);
        
        try {
            // 1. Inserir a saída
            $sql_saida = "INSERT INTO almoxarifado_saidas (material_id, quantidade, setor_destino, responsavel_saida, data_saida, usuario_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_saida = mysqli_prepare($link, $sql_saida);
            mysqli_stmt_bind_param($stmt_saida, "idsssi", $material_id, $quantidade, $setor_destino, $responsavel_saida, $data_saida, $_SESSION['id']);
            mysqli_stmt_execute($stmt_saida);
            $saida_id = mysqli_insert_id($link);
            mysqli_stmt_close($stmt_saida);
            
            // 2. Atualizar o estoque do material
            // Primeiro, buscar o estoque atual
            $sql_estoque_atual = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
            $stmt_estoque = mysqli_prepare($link, $sql_estoque_atual);
            mysqli_stmt_bind_param($stmt_estoque, "i", $material_id);
            mysqli_stmt_execute($stmt_estoque);
            $result_estoque = mysqli_stmt_get_result($stmt_estoque);
            $material = mysqli_fetch_assoc($result_estoque);
            $estoque_anterior = $material['estoque_atual'];
            $novo_estoque = $estoque_anterior - $quantidade;
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
            mysqli_stmt_bind_param($stmt_movimentacao, "isidddi", $material_id, ALMOXARIFADO_SAIDA, $quantidade, $estoque_anterior, $novo_estoque, $_SESSION['id'], $saida_id);
            mysqli_stmt_execute($stmt_movimentacao);
            mysqli_stmt_close($stmt_movimentacao);
            
            // Confirmar transação
            mysqli_commit($link);
            
            header("location: saidas.php");
            exit();
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            mysqli_rollback($link);
            echo "<div class='alert alert-danger'>Erro ao registrar saída: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2>Registrar Nova Saída</h2>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-grid">
        <div>
            <label>Material *</label>
            <select name="material_id" required>
                <option value="">Selecione um material</option>
                <?php 
                mysqli_data_seek($materiais_result, 0); // Resetar ponteiro
                while($material = mysqli_fetch_assoc($materiais_result)): ?>
                    <option value="<?php echo $material['id']; ?>" <?php echo ($material_id == $material['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($material['codigo'] . ' - ' . $material['nome'] . ' (Estoque: ' . formatar_quantidade($material['estoque_atual']) . ')'); ?>
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
            <label>Setor Destino</label>
            <input type="text" name="setor_destino" value="<?php echo htmlspecialchars($setor_destino); ?>">
        </div>
        <div>
            <label>Responsável pela Saída</label>
            <input type="text" name="responsavel_saida" value="<?php echo htmlspecialchars($responsavel_saida); ?>">
        </div>
        <div>
            <label>Data de Saída *</label>
            <input type="date" name="data_saida" value="<?php echo htmlspecialchars($data_saida); ?>" required>
            <span class="help-block"><?php echo $data_saida_err; ?></span>
        </div>
    </div>
    <div>
        <input type="submit" value="Registrar Saída">
        <a href="saidas.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once $base_path . '/includes/footer.php';
?>