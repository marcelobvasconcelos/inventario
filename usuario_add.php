<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SESSION["permissao"] != 'admin'){
    echo "Acesso negado.";
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nome, email, senha, permissao) VALUES (?, ?, ?, ?)";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "ssss", $_POST['nome'], $_POST['email'], $senha, $_POST['permissao']);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: usuarios.php");
            exit();
        } else{
            echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
    }
}
?>

<h2>Adicionar Novo Usuário</h2>

<form action="" method="post">
    <div>
        <label>Nome</label>
        <input type="text" name="nome">
    </div>
    <div>
        <label>Email</label>
        <input type="email" name="email">
    </div>
    <div>
        <label>Senha</label>
        <input type="password" name="senha">
    </div>
    <div>
        <label>Permissão</label>
        <select name="permissao">
            <option value="usuario">Usuário</option>
            <option value="admin">Administrador</option>
        </select>
    </div>
    <div>
        <input type="submit" value="Adicionar">
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>