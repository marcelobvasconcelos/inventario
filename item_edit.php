<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem editar itens
if($_SESSION["permissao"] != 'admin'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$id = $_GET['id'];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $sql = "UPDATE itens SET nome = ?, patrimonio_novo = ?, patrimonio_secundario = ?, local_id = ?, responsavel_id = ?, estado = ?, observacao = ? WHERE id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "sssiissi", $_POST['nome'], $_POST['patrimonio_novo'], $_POST['patrimonio_secundario'], $_POST['local_id'], $_POST['responsavel_id'], $_POST['estado'], $_POST['observacao'], $id);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: itens.php");
            exit();
        } else{
            echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
    }
}

$sql = "SELECT * FROM itens WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($result);
}

$locais = mysqli_query($link, "SELECT id, nome FROM locais");
$usuarios = mysqli_query($link, "SELECT id, nome FROM usuarios");

?>

<h2>Editar Item</h2>

<form action="" method="post">
    <div>
        <label>Nome</label>
        <input type="text" name="nome" value="<?php echo $item['nome']; ?>">
    </div>
    <div>
        <label>Patrimônio Novo</label>
        <input type="text" name="patrimonio_novo" value="<?php echo $item['patrimonio_novo']; ?>">
    </div>
    <div>
        <label>Patrimônio Secundário (Opcional)</label>
        <input type="text" name="patrimonio_secundario" value="<?php echo $item['patrimonio_secundario']; ?>">
    </div>
    <div>
        <label>Local</label>
        <select name="local_id">
            <?php while($local = mysqli_fetch_assoc($locais)): ?>
                <option value="<?php echo $local['id']; ?>" <?php echo ($local['id'] == $item['local_id']) ? 'selected' : ''; ?>><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label>Responsável</label>
        <select name="responsavel_id">
            <?php while($usuario = mysqli_fetch_assoc($usuarios)): ?>
                <option value="<?php echo $usuario['id']; ?>" <?php echo ($usuario['id'] == $item['responsavel_id']) ? 'selected' : ''; ?>><?php echo $usuario['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label>Estado</label>
        <select name="estado">
            <option value="Bom" <?php echo ($item['estado'] == 'Bom') ? 'selected' : ''; ?>>Bom</option>
            <option value="Razoável" <?php echo ($item['estado'] == 'Razoável') ? 'selected' : ''; ?>>Razoável</option>
            <option value="Inservível" <?php echo ($item['estado'] == 'Inservível') ? 'selected' : ''; ?>>Inservível</option>
        </select>
    </div>
    <div>
        <label>Observação (Opcional)</label>
        <textarea name="observacao"><?php echo $item['observacao']; ?></textarea>
    </div>
    <div>
        <input type="submit" value="Salvar Alterações">
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>