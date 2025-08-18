<?php
// Inicia a sessão PHP para gerenciar o estado do usuário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o cabeçalho HTML padrão e a conexão com o banco de dados
require_once '../includes/header.php';
require_once '../config/db.php';

// Verifica se o usuário tem permissão para adicionar entradas (Administrador)
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Inicializa variáveis para os campos do formulário e mensagens de erro
$material_id = $quantidade = $valor_unitario = $fornecedor = $nota_fiscal = $data_entrada = "";
$material_id_err = $quantidade_err = $valor_unitario_err = $data_entrada_err = "";

// Busca os materiais disponíveis
$sql_materiais = "SELECT id, codigo, nome FROM almoxarifado_materiais WHERE status = 'ativo' ORDER BY nome ASC";
$materiais_result = mysqli_query($link, $sql_materiais);

// Processa o formulário quando ele é submetido (método POST)
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validação e sanitização dos campos do formulário
    if(empty(trim($_POST["material_id"]))){
        $material_id_err = "Por favor, selecione um material.";
    } else {
        $material_id = trim($_POST["material_id"]);
    }
    
    if(empty(trim($_POST["quantidade"]))){
        $quantidade_err = "Por favor, insira a quantidade.";
    } else {
        $quantidade = trim($_POST["quantidade"]);
        if (!is_numeric($quantidade) || $quantidade <= 0) {
            $quantidade_err = "A quantidade deve ser um número positivo.";
        }
    }
    
    if(empty(trim($_POST["valor_unitario"]))){
        $valor_unitario_err = "Por favor, insira o valor unitário.";
    } else {
        $valor_unitario = trim($_POST["valor_unitario"]);
        // Remove caracteres não numéricos, exceto vírgula e ponto
        $valor_unitario = str_replace(',', '.', str_replace('R$', '', $valor_unitario));
        if (!is_numeric($valor_unitario) || $valor_unitario < 0) {
            $valor_unitario_err = "O valor unitário deve ser um número positivo.";
        }
    }
    
    $fornecedor = trim($_POST["fornecedor"]);
    $nota_fiscal = trim($_POST["nota_fiscal"]);
    
    if(empty(trim($_POST["data_entrada"]))){
        $data_entrada_err = "Por favor, insira a data de entrada.";
    } else {
        $data_entrada = trim($_POST["data_entrada"]);
        // Verifica se a data é válida
        $date = DateTime::createFromFormat('Y-m-d', $data_entrada);
        if (!$date || $date->format('Y-m-d') !== $data_entrada) {
            $data_entrada_err = "Por favor, insira uma data válida.";
        }
    }
    
    // Se não houver erros de validação, insere a entrada no banco de dados
    if(empty($material_id_err) && empty($quantidade_err) && empty($valor_unitario_err) && empty($data_entrada_err)){
        // Iniciar transação
        mysqli_begin_transaction($link);
        
        try {
            // Insere a entrada
            $sql_entrada = "INSERT INTO almoxarifado_entradas (material_id, quantidade, valor_unitario, fornecedor, nota_fiscal, data_entrada, data_cadastro, usuario_id) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            
            if($stmt_entrada = mysqli_prepare($link, $sql_entrada)){
                mysqli_stmt_bind_param($stmt_entrada, "iddsssi", $material_id, $quantidade, $valor_unitario, $fornecedor, $nota_fiscal, $data_entrada, $_SESSION['id']);
                
                if(mysqli_stmt_execute($stmt_entrada)){
                    // Atualiza o estoque atual do material
                    $sql_atualiza_estoque = "UPDATE almoxarifado_materiais SET estoque_atual = estoque_atual + ? WHERE id = ?";
                    
                    if($stmt_atualiza_estoque = mysqli_prepare($link, $sql_atualiza_estoque)){
                        mysqli_stmt_bind_param($stmt_atualiza_estoque, "di", $quantidade, $material_id);
                        
                        if(mysqli_stmt_execute($stmt_atualiza_estoque)){
                            // Registra a movimentação
                            // Primeiro, busca o saldo anterior
                            $sql_saldo_anterior = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
                            if($stmt_saldo = mysqli_prepare($link, $sql_saldo_anterior)){
                                mysqli_stmt_bind_param($stmt_saldo, "i", $material_id);
                                mysqli_stmt_execute($stmt_saldo);
                                $result_saldo = mysqli_stmt_get_result($stmt_saldo);
                                $row_saldo = mysqli_fetch_assoc($result_saldo);
                                $saldo_anterior = $row_saldo['estoque_atual'];
                                mysqli_stmt_close($stmt_saldo);
                            } else {
                                throw new Exception("Erro ao buscar saldo anterior: " . mysqli_error($link));
                            }
                            
                            // Calcula o novo saldo (saldo anterior + quantidade da entrada)
                            $saldo_atual = $saldo_anterior;
                            
                            $sql_movimentacao = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id, referencia_id) VALUES (?, 'entrada', ?, ?, ?, NOW(), ?, ?)";
                            
                            if($stmt_movimentacao = mysqli_prepare($link, $sql_movimentacao)){
                                mysqli_stmt_bind_param($stmt_movimentacao, "iddidi", $material_id, $quantidade, $saldo_anterior, $saldo_atual, $_SESSION['id'], mysqli_insert_id($link));
                                
                                if(mysqli_stmt_execute($stmt_movimentacao)){
                                    // Confirma a transação
                                    mysqli_commit($link);
                                    header("location: itens.php");
                                    exit();
                                } else {
                                    throw new Exception("Erro ao registrar movimentação: " . mysqli_error($link));
                                }
                                mysqli_stmt_close($stmt_movimentacao);
                            } else {
                                throw new Exception("Erro ao preparar consulta de movimentação: " . mysqli_error($link));
                            }
                        } else {
                            throw new Exception("Erro ao atualizar estoque: " . mysqli_error($link));
                        }
                        mysqli_stmt_close($stmt_atualiza_estoque);
                    } else {
                        throw new Exception("Erro ao preparar consulta de atualização de estoque: " . mysqli_error($link));
                    }
                } else {
                    throw new Exception("Erro ao registrar entrada: " . mysqli_error($link));
                }
                mysqli_stmt_close($stmt_entrada);
            } else {
                throw new Exception("Erro ao preparar consulta de entrada: " . mysqli_error($link));
            }
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            mysqli_rollback($link);
            echo "<div class='alert alert-danger'>Erro ao registrar entrada: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2>Registrar Entrada de Material</h2>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div>
        <label>Material</label>
        <select name="material_id" required>
            <option value="">Selecione um Material</option>
            <?php if($materiais_result): ?>
                <?php while($material = mysqli_fetch_assoc($materiais_result)): ?>
                    <option value="<?php echo $material['id']; ?>" <?php echo ($material['id'] == $material_id) ? 'selected' : ''; ?>>
                        <?php echo $material['codigo'] . ' - ' . $material['nome']; ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
        <span class="help-block"><?php echo $material_id_err; ?></span>
    </div>
    <div>
        <label>Quantidade</label>
        <input type="number" step="0.01" name="quantidade" value="<?php echo htmlspecialchars($quantidade); ?>" required>
        <span class="help-block"><?php echo $quantidade_err; ?></span>
    </div>
    <div>
        <label>Valor Unitário (R$)</label>
        <input type="text" name="valor_unitario" value="<?php echo htmlspecialchars($valor_unitario); ?>" required>
        <span class="help-block"><?php echo $valor_unitario_err; ?></span>
    </div>
    <div>
        <label>Fornecedor (Opcional)</label>
        <input type="text" name="fornecedor" value="<?php echo htmlspecialchars($fornecedor); ?>">
    </div>
    <div>
        <label>Nota Fiscal (Opcional)</label>
        <input type="text" name="nota_fiscal" value="<?php echo htmlspecialchars($nota_fiscal); ?>">
    </div>
    <div>
        <label>Data de Entrada</label>
        <input type="date" name="data_entrada" value="<?php echo htmlspecialchars($data_entrada); ?>" required>
        <span class="help-block"><?php echo $data_entrada_err; ?></span>
    </div>
    <div>
        <input type="submit" value="Registrar Entrada">
        <a href="itens.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>