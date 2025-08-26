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
    // Obter o ID do usuário "Lixeira"
    $sql_lixeira = "SELECT id FROM usuarios WHERE nome = 'Lixeira'";
    $result_lixeira = mysqli_query($link, $sql_lixeira);
    
    if (!$result_lixeira || mysqli_num_rows($result_lixeira) == 0) {
        throw new Exception("Usuário 'Lixeira' não encontrado. Execute o script de atualização do banco de dados.");
    }
    
    $lixeira_id = mysqli_fetch_assoc($result_lixeira)['id'];
    
    // Em vez de excluir as movimentações, vamos mantê-las para manter o histórico
    
    // Atualizar o estado do item para 'Excluido' e atribuir ao usuário "Lixeira"
    $sql_item = "UPDATE itens SET estado = 'Excluido', responsavel_id = ? WHERE id = ?";
    if($stmt_item = mysqli_prepare($link, $sql_item)){
        mysqli_stmt_bind_param($stmt_item, "ii", $lixeira_id, $id);
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