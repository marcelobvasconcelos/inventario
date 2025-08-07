<?php
session_start();
require_once 'includes/header.php';
require_once 'config/db.php';

// Verifica se o usuário é administrador
if($_SESSION["permissao"] != 'Administrador'){
    header("location: index.php");
    exit;
}

$id = $_GET['id'];

// Adicionar verificação se o local está sendo usado por algum item

$sql = "DELETE FROM locais WHERE id = ?";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)){
        header("location: locais.php");
        exit();
    } else{
        echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
    }
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>