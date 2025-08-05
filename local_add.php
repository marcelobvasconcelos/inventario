<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $sql = "INSERT INTO locais (nome) VALUES (?)";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $_POST['nome']);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: locais.php");
            exit();
        } else{
            echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
    }
}
?>

<h2>Adicionar Novo Local</h2>

<form action="" method="post">
    <div>
        <label>Nome do Local</label>
        <input type="text" name="nome">
    </div>
    <div>
        <input type="submit" value="Adicionar">
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>