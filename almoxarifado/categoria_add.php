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
$numero = '';
$descricao = '';

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_categoria'])){
    $numero = trim($_POST["numero"]);
    $descricao = trim($_POST["descricao"]);
    
    if(empty($numero) || empty($descricao)){
        $error = "O número e a descrição da categoria são obrigatórios.";
    } else {
        // Verificar se o número ou a descrição já existem
        $sql_check = "SELECT id FROM almoxarifado_categorias WHERE numero = ? OR descricao = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$numero, $descricao]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe uma categoria com este número ou descrição.";
        } else {
            // Inserir na nova tabela
            $sql_insert = "INSERT INTO almoxarifado_categorias (numero, descricao) VALUES (?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            if($stmt_insert->execute([$numero, $descricao])){
                $message = "Categoria do almoxarifado cadastrada com sucesso!";
                $numero = '';
                $descricao = ''; // Limpar campos após sucesso
            } else {
                $error = "Erro ao cadastrar categoria. Tente novamente.";
            }
        }
    }
}

// Buscar todas as categorias da nova tabela
$sql_categorias = "SELECT * FROM almoxarifado_categorias ORDER BY descricao ASC";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2>Gerenciamento de Categorias do Almoxarifado</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Cadastrar Nova Categoria para o Almoxarifado</h3>
        </div>
        <div class="card-body">
            <form action="categoria_add.php" method="post">
                <div class="form-group">
                    <label for="numero">Número:</label>
                    <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars($numero); ?>" required>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo htmlspecialchars($descricao); ?>" required>
                </div>
                <button type="submit" name="cadastrar_categoria" class="btn btn-primary">Cadastrar Categoria</button>
                <a href="empenhos_index.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
    
    <?php if(!empty($categorias)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Categorias do Almoxarifado Cadastradas</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Número</th>
                                <th>Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categorias as $categoria): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($categoria['id']); ?></td>
                                    <td><?php echo htmlspecialchars($categoria['numero']); ?></td>
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
require_once '../includes/footer.php';
?>