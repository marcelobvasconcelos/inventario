<?php
session_start(); // Adicionado para acessar $_SESSION
require_once 'config/db.php';

// Apenas administradores podem excluir itens
if(!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'admin'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    exit;
}

$id = $_GET['id'];

$sql = "DELETE FROM itens WHERE id = ?";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)){
        header("location: itens.php");
        exit();
    } else{
        echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
    }
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>