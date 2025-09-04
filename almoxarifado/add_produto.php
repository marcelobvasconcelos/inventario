<?php
// almoxarifado/add_produto.php - Adicionar ou editar produto
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Verificar se o usuário tem permissão de administrador ou almoxarife
if ($_SESSION["permissao"] != 'Administrador' && $_SESSION["permissao"] != 'Almoxarife') {
    header("location: index.php");
    exit;
}

// Variáveis para armazenar os dados do formulário
$nome = $descricao = $unidade_medida = "";
$estoque_atual = $estoque_minimo = 0;
$nome_err = $unidade_medida_err = "";

// Verificar se é uma edição
$editando = isset($_GET['id']) && !empty($_GET['id']);
$produto_id = 0;

if ($editando) {
    $produto_id = (int)$_GET['id'];
    
    // Buscar os dados do produto
    $sql = "SELECT * FROM almoxarifado_produtos WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $produto_id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)){
                $nome = $row['nome'];
                $descricao = $row['descricao'];
                $unidade_medida = $row['unidade_medida'];
                $estoque_atual = $row['estoque_atual'];
                $estoque_minimo = $row['estoque_minimo'];
            } else {
                // Produto não encontrado
                header("location: index.php");
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Processar dados do formulário quando o formulário é enviado
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validar nome
    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira o nome do produto.";
    } else{
        $nome = trim($_POST["nome"]);
    }
    
    // Validar unidade de medida
    if(empty(trim($_POST["unidade_medida"]))){
        $unidade_medida_err = "Por favor, insira a unidade de medida.";
    } else{
        $unidade_medida = trim($_POST["unidade_medida"]);
    }
    
    // Obter os outros valores do formulário
    $descricao = trim($_POST["descricao"]);
    $estoque_atual = (int)$_POST["estoque_atual"];
    $estoque_minimo = (int)$_POST["estoque_minimo"];
    
    // Verificar se não há erros antes de inserir/atualizar no banco de dados
    if(empty($nome_err) && empty($unidade_medida_err)){
        if ($editando) {
            // Atualizar produto existente
            $sql = "UPDATE almoxarifado_produtos SET nome = ?, descricao = ?, unidade_medida = ?, estoque_atual = ?, estoque_minimo = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssiii", $param_nome, $param_descricao, $param_unidade_medida, $param_estoque_atual, $param_estoque_minimo, $produto_id);
                
                // Definir parâmetros
                $param_nome = $nome;
                $param_descricao = $descricao;
                $param_unidade_medida = $unidade_medida;
                $param_estoque_atual = $estoque_atual;
                $param_estoque_minimo = $estoque_minimo;
                
                if(mysqli_stmt_execute($stmt)){
                    // Redirecionar para a página de produtos
                    header("location: index.php");
                    exit();
                } else{
                    echo "Ops! Algo deu errado ao atualizar o produto. Por favor, tente novamente mais tarde.";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            // Adicionar novo produto
            $sql = "INSERT INTO almoxarifado_produtos (nome, descricao, unidade_medida, estoque_atual, estoque_minimo) VALUES (?, ?, ?, ?, ?)";
            
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssii", $param_nome, $param_descricao, $param_unidade_medida, $param_estoque_atual, $param_estoque_minimo);
                
                // Definir parâmetros
                $param_nome = $nome;
                $param_descricao = $descricao;
                $param_unidade_medida = $unidade_medida;
                $param_estoque_atual = $estoque_atual;
                $param_estoque_minimo = $estoque_minimo;
                
                if(mysqli_stmt_execute($stmt)){
                    // Redirecionar para a página de produtos
                    header("location: index.php");
                    exit();
                } else{
                    echo "Ops! Algo deu errado ao adicionar o produto. Por favor, tente novamente mais tarde.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<div class="almoxarifado-header">
    <h2><?php echo $editando ? 'Editar Produto' : 'Adicionar Novo Produto'; ?></h2>
</div>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($editando ? '?id=' . $produto_id : ''); ?>" method="post">
    <div class="almoxarifado-form-section">
        <h3>Dados do Produto</h3>
        
        <div class="form-group <?php echo (!empty($nome_err)) ? 'has-error' : ''; ?>">
            <label>Nome do Produto</label>
            <input type="text" name="nome" class="form-control" value="<?php echo $nome; ?>">
            <span class="help-block"><?php echo $nome_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>Descrição</label>
            <textarea name="descricao" class="form-control"><?php echo $descricao; ?></textarea>
        </div>
        
        <div class="form-group <?php echo (!empty($unidade_medida_err)) ? 'has-error' : ''; ?>">
            <label>Unidade de Medida</label>
            <input type="text" name="unidade_medida" class="form-control" value="<?php echo $unidade_medida; ?>">
            <span class="help-block"><?php echo $unidade_medida_err; ?></span>
        </div>
        
        <div class="form-group">
            <label>Estoque Atual</label>
            <input type="number" name="estoque_atual" class="form-control" value="<?php echo $estoque_atual; ?>">
        </div>
        
        <div class="form-group">
            <label>Estoque Mínimo</label>
            <input type="number" name="estoque_minimo" class="form-control" value="<?php echo $estoque_minimo; ?>">
        </div>
        
        <input type="submit" class="btn-custom" value="<?php echo $editando ? 'Atualizar Produto' : 'Adicionar Produto'; ?>">
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>