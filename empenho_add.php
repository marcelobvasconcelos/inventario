<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem acessar esta página
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_empenho'])){
    $numero_empenho = trim($_POST["numero_empenho"]);
    $data_emissao = trim($_POST["data_emissao"]);
    $nome_fornecedor = trim($_POST["nome_fornecedor"]);
    $cnpj_fornecedor = trim($_POST["cnpj_fornecedor"]);
    $categoria_id = trim($_POST["categoria_id"]);
    $status = trim($_POST["status"]);
    
    // Validação
    if(empty($numero_empenho) || empty($data_emissao) || empty($nome_fornecedor) || empty($cnpj_fornecedor) || empty($categoria_id) || empty($status)){
        $error = "Todos os campos são obrigatórios.";
    } else {
        // Verificar se já existe um empenho com o mesmo número
        $sql_check = "SELECT id FROM empenhos WHERE numero_empenho = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$numero_empenho]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe um empenho com este número.";
        } else {
            // Inserir novo empenho
            $sql_insert = "INSERT INTO empenhos (numero_empenho, data_emissao, nome_fornecedor, cnpj_fornecedor, categoria_id, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            if($stmt_insert->execute([$numero_empenho, $data_emissao, $nome_fornecedor, $cnpj_fornecedor, $categoria_id, $status])){
                $message = "Empenho cadastrado com sucesso!";
                // Limpar campos
                $numero_empenho = '';
                $data_emissao = '';
                $nome_fornecedor = '';
                $cnpj_fornecedor = '';
                $categoria_id = '';
                $status = 'Aberto';
            } else {
                $error = "Erro ao cadastrar empenho. Tente novamente.";
            }
        }
    }
}

// Buscar todas as categorias para o select
$sql_categorias = "SELECT id, numero, descricao FROM categorias ORDER BY numero ASC";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os empenhos cadastrados com suas categorias
$sql_empenhos = "SELECT e.*, c.numero as categoria_numero, c.descricao as categoria_descricao 
                 FROM empenhos e 
                 JOIN categorias c ON e.categoria_id = c.id 
                 ORDER BY e.data_cadastro DESC";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$empenhos = $stmt_empenhos->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Cadastro de Empenhos</h2>

<?php if($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form action="empenho_add.php" method="post">
    <div class="form-grid">
        <div>
            <label>Número do Empenho:</label>
            <input type="text" name="numero_empenho" value="<?php echo isset($numero_empenho) ? htmlspecialchars($numero_empenho) : ''; ?>" required>
        </div>
        <div>
            <label>Data de Emissão:</label>
            <input type="date" name="data_emissao" value="<?php echo isset($data_emissao) ? htmlspecialchars($data_emissao) : ''; ?>" required>
        </div>
        <div>
            <label>Nome do Fornecedor:</label>
            <input type="text" name="nome_fornecedor" value="<?php echo isset($nome_fornecedor) ? htmlspecialchars($nome_fornecedor) : ''; ?>" required>
        </div>
        <div>
            <label>CNPJ do Fornecedor:</label>
            <input type="text" name="cnpj_fornecedor" value="<?php echo isset($cnpj_fornecedor) ? htmlspecialchars($cnpj_fornecedor) : ''; ?>" required>
        </div>
        <div>
            <label>Categoria:</label>
            <select name="categoria_id" required>
                <option value="">Selecione uma categoria</option>
                <?php foreach($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($categoria_id) && $categoria_id == $categoria['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['numero'] . ' - ' . $categoria['descricao']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Status:</label>
            <select name="status" required>
                <option value="Aberto" <?php echo (isset($status) && $status == 'Aberto') ? 'selected' : 'selected'; ?>>Aberto</option>
                <option value="Fechado" <?php echo (isset($status) && $status == 'Fechado') ? 'selected' : ''; ?>>Fechado</option>
            </select>
        </div>
    </div>
    <div style="margin-top: 20px;">
        <input type="submit" name="cadastrar_empenho" value="Cadastrar Empenho" class="btn-custom">
        <a href="patrimonio_add.php" class="btn-custom">Voltar</a>
    </div>
</form>

<?php if(!empty($empenhos)): ?>
    <h3 style="margin-top: 30px;">Empenhos Cadastrados</h3>
    <div class="item-list">
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Data de Emissão</th>
                    <th>Fornecedor</th>
                    <th>CNPJ</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Data de Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($empenhos as $empenho): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($empenho['numero_empenho']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($empenho['data_emissao'])); ?></td>
                        <td><?php echo htmlspecialchars($empenho['nome_fornecedor']); ?></td>
                        <td><?php echo htmlspecialchars($empenho['cnpj_fornecedor']); ?></td>
                        <td><?php echo htmlspecialchars($empenho['categoria_numero'] . ' - ' . $empenho['categoria_descricao']); ?></td>
                        <td><?php echo htmlspecialchars($empenho['status']); ?></td>
                        <td><?php echo isset($empenho['data_cadastro']) ? date('d/m/Y H:i', strtotime($empenho['data_cadastro'])) : 'N/A'; ?></td>
                        <td>
                            <a href="empenho_edit.php?id=<?php echo $empenho['id']; ?>" class="btn-custom btn-small">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .form-grid div {
        display: flex;
        flex-direction: column;
    }
    
    .form-grid label {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .form-grid input, .form-grid select {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    
    .item-list {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ccc;
        padding: 10px;
        margin-top: 10px;
    }
    
    .item-list table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .item-list th, .item-list td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    
    .item-list th {
        background-color: #f2f2f2;
    }
    .btn-small {
        padding: 5px 10px;
        font-size: 12px;
    }
</style>

<?php
require_once 'includes/footer.php';
?>