<?php
session_start(); // Inicia a sessão
require_once 'config/db.php';

// Apenas administradores podem aprovar locais
if(!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    exit;
}

if(isset($_GET['id']) && !empty(trim($_GET['id']))){ 
    $id = trim($_GET['id']);

    $sql = "UPDATE locais SET status = 'aprovado' WHERE id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: locais.php?status=pendente");
            exit();
        } else{
            echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($link);
} else {
    header("location: locais.php");
    exit();
}
?>