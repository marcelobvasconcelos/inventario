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

// Inicializar variáveis
$descricao = '';

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_categoria'])){
    $descricao = trim($_POST["descricao"]);
    
    // Validação
    if(empty($descricao)){
        $error = "A descrição da categoria é obrigatória.";
    } else {
        // Verificar se já existe uma categoria com a mesma descrição
        $sql_check = "SELECT codigo FROM categorias WHERE descricao = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$descricao]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe uma categoria com esta descrição.";
        } else {
            // Inserir nova categoria
            $sql_insert = "INSERT INTO categorias (descricao) VALUES (?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            if($stmt_insert->execute([$descricao])){
                $message = "Categoria cadastrada com sucesso!";
                // Limpar campos
                $descricao = '';
            } else {
                $error = "Erro ao cadastrar categoria. Tente novamente.";
            }
        }
    }
}

// Buscar todas as categorias cadastradas
$sql_select = "SELECT * FROM categorias ORDER BY id ASC";
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute();
$categorias = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2>Gerenciamento de Categorias</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Cadastrar Nova Categoria</h3>
        </div>
        <div class="card-body">
            <form action="categoria_add.php" method="post">
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo isset($descricao) ? htmlspecialchars($descricao) : ''; ?>" required>
                </div>
                <button type="submit" name="cadastrar_categoria" class="btn btn-primary">Cadastrar Categoria</button>
                <a href="index.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
    
    <?php if(!empty($categorias)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Categorias Cadastradas</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categorias as $categoria): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($categoria['id']); ?></td>
                                    <td><?php echo htmlspecialchars($categoria['descricao']); ?></td>
                                    <td>
                                        <a href="categoria_edit.php?id=<?php echo $categoria['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
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
require_once '../../includes/footer.php';
?>