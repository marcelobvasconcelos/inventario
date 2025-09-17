<?php
// Inicia a sessão PHP para gerenciar o estado do usuário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o cabeçalho HTML padrão e a conexão com o banco de dados
require_once '../includes/header.php';
require_once '../config/db.php';

// Verifica se o usuário tem permissão para adicionar saídas (Administrador)
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Inicializa variáveis para os campos do formulário e mensagens de erro
$material_id = $quantidade = $setor_destino = $responsavel_saida = $data_saida = "";
$material_id_err = $quantidade_err = $data_saida_err = "";

// Busca os materiais disponíveis com estoque maior que zero
$sql_materiais = "SELECT id, codigo, nome, estoque_atual FROM almoxarifado_materiais WHERE estoque_atual > 0 ORDER BY nome ASC";
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
        } else {
            // Verifica se há estoque suficiente
            $sql_estoque = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
            if($stmt_estoque = mysqli_prepare($link, $sql_estoque)){
                mysqli_stmt_bind_param($stmt_estoque, "i", $material_id);
                mysqli_stmt_execute($stmt_estoque);
                $result_estoque = mysqli_stmt_get_result($stmt_estoque);
                $row_estoque = mysqli_fetch_assoc($result_estoque);
                if($row_estoque && $row_estoque['estoque_atual'] < $quantidade){
                    $quantidade_err = "Quantidade solicitada é maior que o estoque disponível (" . $row_estoque['estoque_atual'] . ").";
                }
                mysqli_stmt_close($stmt_estoque);
            }
        }
    }
    
    $setor_destino = trim($_POST["setor_destino"]);
    $responsavel_saida = trim($_POST["responsavel_saida"]);
    
    if(empty(trim($_POST["data_saida"]))){
        $data_saida_err = "Por favor, insira a data de saída.";
    } else {
        $data_saida = trim($_POST["data_saida"]);
        // Verifica se a data é válida
        $date = DateTime::createFromFormat('Y-m-d', $data_saida);
        if (!$date || $date->format('Y-m-d') !== $data_saida) {
            $data_saida_err = "Por favor, insira uma data válida.";
        }
    }
    
    // Se não houver erros de validação, insere a saída no banco de dados
    if(empty($material_id_err) && empty($quantidade_err) && empty($data_saida_err)){
        // Iniciar transação
        mysqli_begin_transaction($link);
        
        try {
            // Insere a saída
            $sql_saida = "INSERT INTO almoxarifado_saidas (material_id, quantidade, setor_destino, responsavel_saida, data_saida, data_cadastro, usuario_id) VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            
            if($stmt_saida = mysqli_prepare($link, $sql_saida)){
                mysqli_stmt_bind_param($stmt_saida, "idsssi", $material_id, $quantidade, $setor_destino, $responsavel_saida, $data_saida, $_SESSION['id']);
                
                if(mysqli_stmt_execute($stmt_saida)){
                    // Atualiza o estoque atual do material
                    $sql_atualiza_estoque = "UPDATE almoxarifado_materiais SET estoque_atual = estoque_atual - ? WHERE id = ?";
                    
                    if($stmt_atualiza_estoque = mysqli_prepare($link, $sql_atualiza_estoque)){
                        mysqli_stmt_bind_param($stmt_atualiza_estoque, "di", $quantidade, $material_id);
                        
                        if(mysqli_stmt_execute($stmt_atualiza_estoque)){
                            // Registra a movimentação
                            // Primeiro, busca o saldo atual (já atualizado)
                            $sql_saldo_atual = "SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?";
                            if($stmt_saldo = mysqli_prepare($link, $sql_saldo_atual)){
                                mysqli_stmt_bind_param($stmt_saldo, "i", $material_id);
                                mysqli_stmt_execute($stmt_saldo);
                                $result_saldo = mysqli_stmt_get_result($stmt_saldo);
                                $row_saldo = mysqli_fetch_assoc($result_saldo);
                                $saldo_atual = $row_saldo['estoque_atual']; // Já atualizado
                                $saldo_anterior = $saldo_atual + $quantidade; // Calcular o anterior
                                mysqli_stmt_close($stmt_saldo);
                            } else {
                                throw new Exception("Erro ao buscar saldo atual: " . mysqli_error($link));
                            }
                            
                            $sql_movimentacao = "INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id, referencia_id) VALUES (?, 'saida', ?, ?, ?, NOW(), ?, ?)";
                            
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
                    throw new Exception("Erro ao registrar saída: " . mysqli_error($link));
                }
                mysqli_stmt_close($stmt_saida);
            } else {
                throw new Exception("Erro ao preparar consulta de saída: " . mysqli_error($link));
            }
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            mysqli_rollback($link);
            echo "<div class='alert alert-danger'>Erro ao registrar saída: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2>Registrar Saída de Material</h2>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div>
        <label>Material</label>
        <select name="material_id" required>
            <option value="">Selecione um Material</option>
            <?php if($materiais_result): ?>
                <?php while($material = mysqli_fetch_assoc($materiais_result)): ?>
                    <option value="<?php echo $material['id']; ?>" data-estoque="<?php echo $material['estoque_atual']; ?>" <?php echo ($material['id'] == $material_id) ? 'selected' : ''; ?>>
                        <?php echo $material['codigo'] . ' - ' . $material['nome'] . ' (Estoque: ' . $material['estoque_atual'] . ')'; ?>
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
        <label>Setor de Destino (Opcional)</label>
        <input type="text" name="setor_destino" value="<?php echo htmlspecialchars($setor_destino); ?>">
    </div>
    <div>
        <label>Responsável pela Saída (Opcional)</label>
        <input type="text" name="responsavel_saida" value="<?php echo htmlspecialchars($responsavel_saida); ?>">
    </div>
    <div>
        <label>Data de Saída</label>
        <input type="date" name="data_saida" value="<?php echo htmlspecialchars($data_saida); ?>" required>
        <span class="help-block"><?php echo $data_saida_err; ?></span>
    </div>
    <div>
        <input type="submit" value="Registrar Saída">
        <a href="itens.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.querySelector('select[name="material_id"]');
    const quantidadeInput = document.querySelector('input[name="quantidade"]');
    
    materialSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const estoque = selectedOption.getAttribute('data-estoque');
        if (estoque) {
            quantidadeInput.max = estoque;
        }
    });
});
</script>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>