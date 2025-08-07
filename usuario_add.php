<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SESSION["permissao"] != 'Administrador'){
    echo "Acesso negado.";
    exit;
}

// Obter perfis disponíveis
$perfis_sql = "SELECT id, nome FROM perfis ORDER BY nome ASC";
$perfis_result = mysqli_query($link, $perfis_sql);

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nome, email, senha, permissao_id) VALUES (?, ?, ?, ?)";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "sssi", $_POST['nome'], $_POST['email'], $senha, $_POST['permissao_id']);
        
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
        <select name="permissao_id">
            <?php while($perfil = mysqli_fetch_assoc($perfis_result)): ?>
                <option value="<?php echo $perfil['id']; ?>"><?php echo $perfil['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <input type="submit" value="Adicionar">
    </div>
</form>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>