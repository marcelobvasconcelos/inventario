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

// Verificar se foi passado um ID para edição
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = "ID do empenho não especificado.";
} else {
    $empenho_id = (int)$_GET['id'];
    
    // Buscar dados do empenho com sua categoria
    $sql_empenho = "SELECT e.*, c.numero as categoria_numero, c.descricao as categoria_descricao 
                    FROM empenhos e 
                    JOIN categorias c ON e.categoria_id = c.id 
                    WHERE e.id = ?";
    $stmt_empenho = $pdo->prepare($sql_empenho);
    $stmt_empenho->execute([$empenho_id]);
    $empenho = $stmt_empenho->fetch(PDO::FETCH_ASSOC);
    
    if (!$empenho) {
        $error = "Empenho não encontrado.";
    }
}

// Buscar todas as categorias para o select
$sql_categorias = "SELECT id, numero, descricao FROM categorias ORDER BY numero ASC";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário de edição
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_empenho'])){
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
        // Verificar se já existe outro empenho com o mesmo número
        $sql_check = "SELECT id FROM empenhos WHERE numero_empenho = ? AND id != ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$numero_empenho, $empenho_id]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe um empenho com este número.";
        } else {
            // Atualizar empenho
            $sql_update = "UPDATE empenhos SET numero_empenho = ?, data_emissao = ?, nome_fornecedor = ?, cnpj_fornecedor = ?, categoria_id = ?, status = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            
            if($stmt_update->execute([$numero_empenho, $data_emissao, $nome_fornecedor, $cnpj_fornecedor, $categoria_id, $status, $empenho_id])){
                $message = "Empenho atualizado com sucesso!";
                // Recarregar os dados do empenho após a atualização
                $stmt_empenho->execute([$empenho_id]);
                $empenho = $stmt_empenho->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Erro ao atualizar empenho. Tente novamente.";
            }
        }
    }
}

// Processar exclusão
if(isset($_POST['excluir_empenho'])){
    // Verificar se existem itens associados a este empenho
    $sql_check_itens = "SELECT COUNT(*) as count FROM itens WHERE empenho_id = ?";
    $stmt_check_itens = $pdo->prepare($sql_check_itens);
    $stmt_check_itens->execute([$empenho_id]);
    $result = $stmt_check_itens->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0){
        $error = "Não é possível excluir este empenho pois existem itens associados a ele.";
    } else {
        // Excluir empenho
        $sql_delete = "DELETE FROM empenhos WHERE id = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        
        if($stmt_delete->execute([$empenho_id])){
            $message = "Empenho excluído com sucesso!";
            // Redirecionar para a página de cadastro de empenhos após exclusão
            header("Location: empenho_add.php?message=" . urlencode("Empenho excluído com sucesso!"));
            exit;
        } else {
            $error = "Erro ao excluir empenho. Tente novamente.";
        }
    }
}
?>

<h2>Editar Empenho</h2>

<?php if($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if($error && !isset($_POST['excluir_empenho'])): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if($empenho): ?>
    <form action="empenho_edit.php?id=<?php echo $empenho_id; ?>" method="post">
        <div class="form-grid">
            <div>
                <label>Número do Empenho:</label>
                <input type="text" name="numero_empenho" value="<?php echo isset($_POST['numero_empenho']) ? htmlspecialchars($_POST['numero_empenho']) : htmlspecialchars($empenho['numero_empenho']); ?>" required>
            </div>
            <div>
                <label>Data de Emissão:</label>
                <input type="date" name="data_emissao" value="<?php echo isset($_POST['data_emissao']) ? htmlspecialchars($_POST['data_emissao']) : htmlspecialchars($empenho['data_emissao']); ?>" required>
            </div>
            <div>
                <label>Nome do Fornecedor:</label>
                <input type="text" name="nome_fornecedor" value="<?php echo isset($_POST['nome_fornecedor']) ? htmlspecialchars($_POST['nome_fornecedor']) : htmlspecialchars($empenho['nome_fornecedor']); ?>" required>
            </div>
            <div>
                <label>CNPJ do Fornecedor:</label>
                <input type="text" name="cnpj_fornecedor" value="<?php echo isset($_POST['cnpj_fornecedor']) ? htmlspecialchars($_POST['cnpj_fornecedor']) : htmlspecialchars($empenho['cnpj_fornecedor']); ?>" required>
            </div>
            <div>
                <label>Categoria:</label>
                <select name="categoria_id" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) || ($empenho['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['numero'] . ' - ' . $categoria['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Status:</label>
                <select name="status" required>
                    <option value="Aberto" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Aberto') || ($empenho['status'] == 'Aberto') ? 'selected' : 'selected'; ?>>Aberto</option>
                    <option value="Fechado" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Fechado') || ($empenho['status'] == 'Fechado') ? 'selected' : ''; ?>>Fechado</option>
                </select>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <input type="submit" name="editar_empenho" value="Atualizar Empenho" class="btn-custom">
            <a href="empenho_add.php" class="btn-custom">Voltar</a>
        </div>
    </form>
    
    <div style="margin-top: 30px;">
        <h3>Excluir Empenho</h3>
        <p>Tem certeza que deseja excluir este empenho? Esta ação não pode ser desfeita.</p>
        <form action="empenho_edit.php?id=<?php echo $empenho_id; ?>" method="post" onsubmit="return confirm('Tem certeza que deseja excluir este empenho? Esta ação não pode ser desfeita.')">
            <input type="submit" name="excluir_empenho" value="Excluir Empenho" class="btn-custom btn-danger">
        </form>
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
    
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
    }
</style>

<?php
require_once 'includes/footer.php';
?>