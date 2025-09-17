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

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_empenho'])){
    $numero = trim($_POST["numero"]);
    $data_emissao = trim($_POST["data_emissao"]);
    $valor = trim($_POST["valor"]);
    $status = trim($_POST["status"]);

    // Validação
    if(empty($numero) || empty($data_emissao) || empty($valor) || empty($status)){
        $error = "Número, data de emissão, valor e status são obrigatórios.";
    } elseif(!is_numeric($valor) || $valor <= 0){
        $error = "Valor deve ser um número positivo.";
    } else {
        // Verificar se já existe um empenho com o mesmo número
        $sql_check = "SELECT numero FROM empenhos_insumos WHERE numero = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$numero]);

        if($stmt_check->rowCount() > 0){
            $error = "Já existe um empenho com este número.";
        } else {
            // Inserir novo empenho (sem fornecedor e cnpj)
            $sql_insert = "INSERT INTO empenhos_insumos (numero, data_emissao, valor, saldo, status) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);

            if($stmt_insert->execute([$numero, $data_emissao, $valor, $valor, $status])){
                $message = "Empenho cadastrado com sucesso!";
                // Limpar campos
                $numero = '';
                $data_emissao = '';
                $valor = '';
                $status = 'Aberto';
            } else {
                $error = "Erro ao cadastrar empenho. Tente novamente.";
            }
        }
    }
}

// Buscar todos os empenhos cadastrados
$sql_empenhos = "SELECT * FROM empenhos_insumos ORDER BY data_emissao DESC";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$empenhos = $stmt_empenhos->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Gerenciamento de Empenhos</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <?php require_once 'menu_empenhos.php'; ?>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Cadastrar Novo Empenho</h3>
        </div>
        <div class="card-body">
            <form action="empenho_add.php" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="numero">Número do Empenho:</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo isset($numero) ? htmlspecialchars($numero) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="data_emissao">Data de Emissão:</label>
                            <input type="date" class="form-control" id="data_emissao" name="data_emissao" value="<?php echo isset($data_emissao) ? htmlspecialchars($data_emissao) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="valor">Valor do Empenho:</label>
                            <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0" value="<?php echo isset($valor) ? htmlspecialchars($valor) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="Aberto" <?php echo (isset($status) && $status == 'Aberto') ? 'selected' : 'selected'; ?>>Aberto</option>
                                <option value="Fechado" <?php echo (isset($status) && $status == 'Fechado') ? 'selected' : ''; ?>>Fechado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="cadastrar_empenho" class="btn btn-primary">Cadastrar Empenho</button>
                <a href="empenhos_index.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
    
    <?php if(!empty($empenhos)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Empenhos Cadastrados</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Data de Emissão</th>
                                <th>Valor</th>
                                <th>Saldo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($empenhos as $empenho): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($empenho['numero']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($empenho['data_emissao'])); ?></td>
                                    <td>R$ <?php echo number_format($empenho['valor'] ?? 0, 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($empenho['saldo'] ?? 0, 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $empenho['status'] == 'Aberto' ? 'success' : 'secondary'; ?>">
                                            <?php echo htmlspecialchars($empenho['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="empenho_edit.php?numero=<?php echo $empenho['numero']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                        <a href="nota_fiscal_add.php?empenho=<?php echo $empenho['numero']; ?>" class="btn btn-sm btn-info">Adicionar Nota Fiscal</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
