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

// Verificar se foi passado um ID de categoria
if(!isset($_GET['id']) || empty($_GET['id'])){
    header('Location: categoria_add.php');
    exit;
}

$id = $_GET['id'];

// Buscar categoria no banco
$sql_select = "SELECT * FROM categorias WHERE id = ?";
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute([$id]);
$categoria = $stmt_select->fetch(PDO::FETCH_ASSOC);

// Se não encontrar a categoria, redirecionar
if(!$categoria){
    header('Location: categoria_add.php');
    exit;
}

// Processar formulário de edição
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_categoria'])){
    $descricao = trim($_POST["descricao"]);
    
    // Validação
    if(empty($descricao)){
        $error = "A descrição da categoria é obrigatória.";
    } else {
        // Verificar se já existe outra categoria com a mesma descrição
        $sql_check = "SELECT id FROM categorias WHERE descricao = ? AND id != ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$descricao, $id]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe uma categoria com esta descrição.";
        } else {
            // Atualizar categoria
            $sql_update = "UPDATE categorias SET descricao = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            
            if($stmt_update->execute([$descricao, $id])){
                $message = "Categoria atualizada com sucesso!";
                // Atualizar os dados da categoria
                $categoria['descricao'] = $descricao;
            } else {
                $error = "Erro ao atualizar categoria. Tente novamente.";
            }
        }
    }
}
?>

<div class="container">
    <h2>Editar Categoria</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Categoria #<?php echo $categoria['id']; ?></h3>
        </div>
        <div class="card-body">
            <form action="categoria_edit.php?id=<?php echo $id; ?>" method="post">
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo isset($descricao) ? htmlspecialchars($descricao) : htmlspecialchars($categoria['descricao']); ?>" required>
                </div>
                <button type="submit" name="editar_categoria" class="btn btn-primary">Atualizar Categoria</button>
                <a href="categoria_add.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>