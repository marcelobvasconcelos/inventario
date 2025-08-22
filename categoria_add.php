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

// Inicializar variáveis
$numero = '';
$descricao = '';

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_categoria'])){
    $numero = trim($_POST["numero"]);
    $descricao = trim($_POST["descricao"]);
    
    // Validação
    if(empty($numero) || empty($descricao)){
        $error = "Todos os campos são obrigatórios.";
    } else {
        // Verificar se já existe uma categoria com o mesmo número
        $sql_check = "SELECT id FROM categorias WHERE numero = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$numero]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe uma categoria com este número.";
        } else {
            // Inserir nova categoria
            $sql_insert = "INSERT INTO categorias (numero, descricao) VALUES (?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            if($stmt_insert->execute([$numero, $descricao])){
                $message = "Categoria cadastrada com sucesso!";
                // Limpar campos
                $numero = '';
                $descricao = '';
            } else {
                $error = "Erro ao cadastrar categoria. Tente novamente.";
            }
        }
    }
}

// Buscar todas as categorias cadastradas
$sql_select = "SELECT * FROM categorias ORDER BY numero ASC";
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute();
$categorias = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Cadastro de Categorias</h2>

<?php if($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form action="categoria_add.php" method="post">
    <div class="form-grid">
        <div>
            <label>Número:</label>
            <input type="number" name="numero" value="<?php echo isset($numero) ? htmlspecialchars($numero) : ''; ?>" required>
        </div>
        <div>
            <label>Descrição:</label>
            <input type="text" name="descricao" value="<?php echo isset($descricao) ? htmlspecialchars($descricao) : ''; ?>" required>
        </div>
    </div>
    <div style="margin-top: 20px;">
        <input type="submit" name="cadastrar_categoria" value="Cadastrar Categoria" class="btn-custom">
        <a href="patrimonio_add.php" class="btn-custom">Voltar</a>
    </div>
</form>

<?php if(!empty($categorias)): ?>
    <h3 style="margin-top: 30px;">Categorias Cadastradas</h3>
    <div class="item-list">
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Descrição</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($categorias as $categoria): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($categoria['numero']); ?></td>
                        <td><?php echo htmlspecialchars($categoria['descricao']); ?></td>
                        <td>
                            <a href="categoria_edit.php?id=<?php echo $categoria['id']; ?>" class="btn-custom btn-small">Editar</a>
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
    
    .form-grid input {
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