<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../../includes/footer.php';
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
    $fornecedor = trim($_POST["fornecedor"]);
    $cnpj = trim($_POST["cnpj"]);
    $status = trim($_POST["status"]);
    
    // Validação
    if(empty($data_emissao) || empty($fornecedor) || empty($cnpj) || empty($status)){
        $error = "Todos os campos são obrigatórios.";
    } else {
        // Atualizar empenho
        $sql_update = "UPDATE empenhos_insumos SET data_emissao = ?, fornecedor = ?, cnpj = ?, status = ? WHERE numero = ?";
        $stmt_update = $pdo->prepare($sql_update);
        
        if($stmt_update->execute([$data_emissao, $fornecedor, $cnpj, $status, $numero])){
            $message = "Empenho atualizado com sucesso!";
            // Atualizar os dados do empenho
            $empenho['data_emissao'] = $data_emissao;
            $empenho['fornecedor'] = $fornecedor;
            $empenho['cnpj'] = $cnpj;
            $empenho['status'] = $status;
        } else {
            $error = "Erro ao atualizar empenho. Tente novamente.";
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
                            <label for="fornecedor">Fornecedor:</label>
                            <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?php echo isset($fornecedor) ? htmlspecialchars($fornecedor) : htmlspecialchars($empenho['fornecedor']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cnpj">CNPJ:</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?php echo isset($cnpj) ? htmlspecialchars($cnpj) : htmlspecialchars($empenho['cnpj']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
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
require_once '../../includes/footer.php';
?>