<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SESSION["permissao"] != 'admin'){
    echo "Acesso negado.";
    exit;
}

$id = $_GET['id'];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $sql = "UPDATE usuarios SET nome = ?, email = ?, permissao = ? WHERE id = ?";
    // Senha é atualizada separadamente

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "sssi", $_POST['nome'], $_POST['email'], $_POST['permissao'], $id);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: usuarios.php");
            exit();
        } else{
            echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
    }
}

$sql = "SELECT * FROM usuarios WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($result);
}

?>

<h2>Editar Usuário</h2>

<form action="" method="post">
    <div>
        <label>Nome</label>
        <input type="text" name="nome" value="<?php echo $usuario['nome']; ?>">
    </div>
    <div>
        <label>Email</label>
        <input type="email" name="email" value="<?php echo $usuario['email']; ?>">
    </div>
    <div>
        <label>Permissão</label>
        <select name="permissao">
            <option value="usuario" <?php echo ($usuario['permissao'] == 'usuario') ? 'selected' : ''; ?>>Usuário</option>
            <option value="admin" <?php echo ($usuario['permissao'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
        </select>
    </div>
    <div>
        <input type="submit" value="Salvar Alterações">
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>