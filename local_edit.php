<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$id = $_GET['id'];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $sql = "UPDATE locais SET nome = ? WHERE id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "si", $_POST['nome'], $id);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: locais.php");
            exit();
        } else{
            echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
    }
}

$sql = "SELECT * FROM locais WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $local = mysqli_fetch_assoc($result);
}

?>

<h2>Editar Local</h2>

<form action="" method="post">
    <div>
        <label>Nome do Local</label>
        <input type="text" name="nome" value="<?php echo $local['nome']; ?>">
    </div>
    <div>
        <input type="submit" value="Salvar Alterações">
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>