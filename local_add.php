<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Verifica se o usuário é administrador
if($_SESSION["permissao"] != 'Administrador'){
    header("location: index.php");
    exit;
}

$erro = ""; // Variável para armazenar mensagens de erro

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $sql = "INSERT INTO locais (nome) VALUES (?)";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $_POST['nome']);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: locais.php");
            exit();
        } else{
            $erro = "Erro ao executar a declaração: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $erro = "Erro ao preparar a declaração: " . mysqli_error($link);
    }
}
?>

<h2>Adicionar Novo Local</h2>

<?php if(!empty($erro)): ?>
    <div class="alert alert-danger"><?php echo $erro; ?></div>
<?php endif; ?>

<form action="" method="post">
    <div>
        <label>Nome do Local</label>
        <input type="text" name="nome" required>
    </div>
    <div>
        <input type="submit" value="Adicionar">
        <a href="locais.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>