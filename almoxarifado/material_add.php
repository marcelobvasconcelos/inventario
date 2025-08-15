<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(__DIR__);

require_once $base_path . '/includes/header.php';
require_once $base_path . '/config/db.php';
require_once 'config.php';

// Verificar permissão de acesso
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para acessar este módulo.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Inicializa variáveis para os campos do formulário e mensagens de erro
$codigo = $nome = $descricao = $unidade_medida = $estoque_minimo = $valor_unitario = $categoria = "";
$codigo_err = $nome_err = $unidade_medida_err = $estoque_minimo_err = $valor_unitario_err = "";

// Processa o formulário quando ele é submetido (método POST)
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validação e sanitização dos campos do formulário
    if(empty(trim($_POST["codigo"]))){
        $codigo_err = "Por favor, insira o código do material.";
    } else {
        $codigo = trim($_POST["codigo"]);
        // Verifica se o código já existe
        $sql_check = "SELECT id FROM almoxarifado_materiais WHERE codigo = ?";
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            mysqli_stmt_bind_param($stmt_check, "s", $codigo);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $codigo_err = "Este código já está em uso.";
            }
            mysqli_stmt_close($stmt_check);
        }
    }
    
    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira o nome do material.";
    } else {
        $nome = trim($_POST["nome"]);
    }
    
    $descricao = trim($_POST["descricao"]);
    
    if(empty(trim($_POST["unidade_medida"]))){
        $unidade_medida_err = "Por favor, insira a unidade de medida.";
    } else {
        $unidade_medida = trim($_POST["unidade_medida"]);
    }
    
    $estoque_minimo = !empty($_POST["estoque_minimo"]) ? $_POST["estoque_minimo"] : 0;
    $valor_unitario = !empty($_POST["valor_unitario"]) ? $_POST["valor_unitario"] : 0;
    $categoria = trim($_POST["categoria"]);
    
    // Se não houver erros de validação, insere o material no banco de dados
    if(empty($codigo_err) && empty($nome_err) && empty($unidade_medida_err)){
        $sql = "INSERT INTO almoxarifado_materiais (codigo, nome, descricao, unidade_medida, estoque_minimo, valor_unitario, categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssddd", $codigo, $nome, $descricao, $unidade_medida, $estoque_minimo, $valor_unitario, $categoria);
            
            if(mysqli_stmt_execute($stmt)){
                header("location: materiais.php");
                exit();
            } else{
                echo "<div class='alert alert-danger'>Oops! Algo deu errado. Por favor, tente novamente mais tarde.</div>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<h2>Adicionar Novo Material</h2>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-grid">
        <div>
            <label>Código *</label>
            <input type="text" name="codigo" value="<?php echo htmlspecialchars($codigo); ?>" required>
            <span class="help-block"><?php echo $codigo_err; ?></span>
        </div>
        <div>
            <label>Nome *</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
            <span class="help-block"><?php echo $nome_err; ?></span>
        </div>
        <div>
            <label>Descrição</label>
            <textarea name="descricao"><?php echo htmlspecialchars($descricao); ?></textarea>
        </div>
        <div>
            <label>Unidade de Medida *</label>
            <input type="text" name="unidade_medida" value="<?php echo htmlspecialchars($unidade_medida); ?>" required>
            <span class="help-block"><?php echo $unidade_medida_err; ?></span>
        </div>
        <div>
            <label>Estoque Mínimo</label>
            <input type="number" step="0.01" name="estoque_minimo" value="<?php echo htmlspecialchars($estoque_minimo); ?>">
            <span class="help-block"><?php echo $estoque_minimo_err; ?></span>
        </div>
        <div>
            <label>Valor Unitário (R$)</label>
            <input type="number" step="0.01" name="valor_unitario" value="<?php echo htmlspecialchars($valor_unitario); ?>">
            <span class="help-block"><?php echo $valor_unitario_err; ?></span>
        </div>
        <div>
            <label>Categoria</label>
            <input type="text" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>">
        </div>
    </div>
    <div>
        <input type="submit" value="Adicionar Material">
        <a href="materiais.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once $base_path . '/includes/footer.php';
?>