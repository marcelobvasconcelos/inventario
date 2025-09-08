<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    require_once '../includes/footer.php';
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
$sql_select = "SELECT * FROM almoxarifado_categorias WHERE id = ?";
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
    $numero = trim($_POST["numero"]);
    $descricao = trim($_POST["descricao"]);
    
    if(empty($numero) || empty($descricao)){
        $error = "O número e a descrição da categoria são obrigatórios.";
    } else {
        // Verificar se já existe outra categoria com o mesmo número ou descrição
        $sql_check = "SELECT id FROM almoxarifado_categorias WHERE (numero = ? OR descricao = ?) AND id != ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$numero, $descricao, $id]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe uma outra categoria com este número ou descrição.";
        } else {
            // Atualizar categoria na nova tabela
            $sql_update = "UPDATE almoxarifado_categorias SET numero = ?, descricao = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            
            if($stmt_update->execute([$numero, $descricao, $id])){
                $message = "Categoria do almoxarifado atualizada com sucesso!";
                // Atualizar dados para exibição no formulário
                $categoria['numero'] = $numero;
                $categoria['descricao'] = $descricao;
            } else {
                $error = "Erro ao atualizar categoria. Tente novamente.";
            }
        }
    }
}
?>

<div class="container">
    <h2>Editar Categoria do Almoxarifado</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editando a Categoria #<?php echo htmlspecialchars($categoria['id']); ?></h3>
        </div>
        <div class="card-body">
            <form action="categoria_edit.php?id=<?php echo $id; ?>" method="post">
                <div class="form-group">
                    <label for="numero">Número:</label>
                    <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($categoria['numero']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo htmlspecialchars($categoria['descricao']); ?>" required>
                </div>
                <button type="submit" name="editar_categoria" class="btn btn-primary">Atualizar Categoria</button>
                <a href="categoria_add.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>