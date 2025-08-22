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
    $error = "ID da categoria não especificado.";
} else {
    $categoria_id = (int)$_GET['id'];
    
    // Buscar dados da categoria
    $sql_categoria = "SELECT * FROM categorias WHERE id = ?";
    $stmt_categoria = $pdo->prepare($sql_categoria);
    $stmt_categoria->execute([$categoria_id]);
    $categoria = $stmt_categoria->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoria) {
        $error = "Categoria não encontrada.";
    }
}

// Processar formulário de edição
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_categoria'])){
    $numero = trim($_POST["numero"]);
    $descricao = trim($_POST["descricao"]);
    
    // Validação
    if(empty($numero) || empty($descricao)){
        $error = "Todos os campos são obrigatórios.";
    } else {
        // Verificar se já existe outra categoria com o mesmo número
        $sql_check = "SELECT id FROM categorias WHERE numero = ? AND id != ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$numero, $categoria_id]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe uma categoria com este número.";
        } else {
            // Atualizar categoria
            $sql_update = "UPDATE categorias SET numero = ?, descricao = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            
            if($stmt_update->execute([$numero, $descricao, $categoria_id])){
                $message = "Categoria atualizada com sucesso!";
                // Recarregar os dados da categoria após a atualização
                $stmt_categoria->execute([$categoria_id]);
                $categoria = $stmt_categoria->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Erro ao atualizar categoria. Tente novamente.";
            }
        }
    }
}

// Processar exclusão
if(isset($_POST['excluir_categoria'])){
    // Verificar se existem empenhos associados a esta categoria
    $sql_check_empenhos = "SELECT COUNT(*) as count FROM empenhos WHERE categoria_id = ?";
    $stmt_check_empenhos = $pdo->prepare($sql_check_empenhos);
    $stmt_check_empenhos->execute([$categoria_id]);
    $result = $stmt_check_empenhos->fetch(PDO::FETCH_ASSOC);
    
    if($result['count'] > 0){
        $error = "Não é possível excluir esta categoria pois existem empenhos associados a ela.";
    } else {
        // Excluir categoria
        $sql_delete = "DELETE FROM categorias WHERE id = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        
        if($stmt_delete->execute([$categoria_id])){
            $message = "Categoria excluída com sucesso!";
            // Redirecionar para a página de cadastro de categorias após exclusão
            header("Location: categoria_add.php?message=" . urlencode("Categoria excluída com sucesso!"));
            exit;
        } else {
            $error = "Erro ao excluir categoria. Tente novamente.";
        }
    }
}
?>

<h2>Editar Categoria</h2>

<?php if($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if($error && !isset($_POST['excluir_categoria'])): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if($categoria): ?>
    <form action="categoria_edit.php?id=<?php echo $categoria_id; ?>" method="post">
        <div class="form-grid">
            <div>
                <label>Número:</label>
                <input type="number" name="numero" value="<?php echo isset($_POST['numero']) ? htmlspecialchars($_POST['numero']) : htmlspecialchars($categoria['numero']); ?>" required>
            </div>
            <div>
                <label>Descrição:</label>
                <input type="text" name="descricao" value="<?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : htmlspecialchars($categoria['descricao']); ?>" required>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <input type="submit" name="editar_categoria" value="Atualizar Categoria" class="btn-custom">
            <a href="categoria_add.php" class="btn-custom">Voltar</a>
        </div>
    </form>
    
    <div style="margin-top: 30px;">
        <h3>Excluir Categoria</h3>
        <p>Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita.</p>
        <form action="categoria_edit.php?id=<?php echo $categoria_id; ?>" method="post" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita.')">
            <input type="submit" name="excluir_categoria" value="Excluir Categoria" class="btn-custom btn-danger">
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
    
    .form-grid input {
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