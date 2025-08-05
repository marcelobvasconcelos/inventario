<?php
require_once 'config/db.php';

if($_SESSION["permissao"] != 'admin'){
    echo "Acesso negado.";
    exit;
}

$id = $_GET['id'];

// Adicionar verificação para não excluir o próprio usuário

$sql = "DELETE FROM usuarios WHERE id = ?";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)){
        header("location: usuarios.php");
        exit();
    } else{
        echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
    }
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>