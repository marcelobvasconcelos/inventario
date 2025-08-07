<?php
session_start(); // Adicionado para acessar $_SESSION
require_once 'config/db.php';

// Apenas administradores podem excluir itens
if(!isset($_SESSION["permissao"]) || $_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    exit;
}

$id = $_GET['id'];

// Iniciar transação
mysqli_begin_transaction($link);

try {
    // Primeiro, excluir as movimentações relacionadas a este item
    $sql_movimentacoes = "DELETE FROM movimentacoes WHERE item_id = ?";
    if($stmt_mov = mysqli_prepare($link, $sql_movimentacoes)){
        mysqli_stmt_bind_param($stmt_mov, "i", $id);
        if(!mysqli_stmt_execute($stmt_mov)){
            throw new Exception(mysqli_error($link));
        }
        mysqli_stmt_close($stmt_mov);
    } else {
        throw new Exception(mysqli_error($link));
    }

    // Em seguida, excluir o item
    $sql_item = "DELETE FROM itens WHERE id = ?";
    if($stmt_item = mysqli_prepare($link, $sql_item)){
        mysqli_stmt_bind_param($stmt_item, "i", $id);
        if(mysqli_stmt_execute($stmt_item)){
            mysqli_commit($link);
            header("location: itens.php");
            exit();
        } else{
            throw new Exception(mysqli_error($link));
        }
        mysqli_stmt_close($stmt_item);
    } else {
        throw new Exception(mysqli_error($link));
    }

} catch (Exception $e) {
    mysqli_rollback($link);
    echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde. Erro: " . $e->getMessage();
}

mysqli_close($link);
?>