<?php
session_start();
require_once 'config/db.php';
require_once 'includes/header.php';

// Função para exibir uma página de erro padronizada e sair
function display_error($message) {
    echo '<main>';
    echo '<h2>Erro ao Excluir Usuário</h2>';
    echo '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
    echo '<a href="usuarios.php" class="btn-custom">Voltar para Usuários</a>';
    echo '</main>';
    require_once 'includes/footer.php';
    exit;
}

// Validação da sessão e permissão
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["permissao"] != 'Administrador'){
    display_error("Acesso negado.");
}

// Validação do ID do usuário
if(!isset($_GET['id']) || empty(trim($_GET['id'])) || !ctype_digit($_GET['id'])){
    display_error("ID de usuário inválido.");
}

$id = $_GET['id'];

// Impede que o administrador se auto-exclua
if($id == $_SESSION['id']){
    display_error("Você não pode excluir seu próprio usuário.");
}

// Verifica se o usuário é responsável por algum item
$check_itens_sql = "SELECT id FROM itens WHERE responsavel_id = ?";
if($stmt_check_itens = mysqli_prepare($link, $check_itens_sql)){
    mysqli_stmt_bind_param($stmt_check_itens, "i", $id);
    mysqli_stmt_execute($stmt_check_itens);
    mysqli_stmt_store_result($stmt_check_itens);
    if(mysqli_stmt_num_rows($stmt_check_itens) > 0){
        mysqli_stmt_close($stmt_check_itens);
        display_error("Não é possível excluir este usuário, pois ele é responsável por um ou mais itens. Reatribua os itens a outro usuário antes de excluir.");
    }
    mysqli_stmt_close($stmt_check_itens);
} else {
    display_error("Erro ao verificar os itens do usuário.");
}

// Verifica se o usuário realizou alguma movimentação
$check_mov_sql = "SELECT id FROM movimentacoes WHERE usuario_id = ?";
if($stmt_check_mov = mysqli_prepare($link, $check_mov_sql)){
    mysqli_stmt_bind_param($stmt_check_mov, "i", $id);
    mysqli_stmt_execute($stmt_check_mov);
    mysqli_stmt_store_result($stmt_check_mov);
    if(mysqli_stmt_num_rows($stmt_check_mov) > 0){
        mysqli_stmt_close($stmt_check_mov);
        display_error("Não é possível excluir este usuário, pois ele possui registros de movimentações. Considere desativar o usuário em vez de excluí-lo para manter o histórico.");
    }
    mysqli_stmt_close($stmt_check_mov);
} else {
    display_error("Erro ao verificar as movimentações do usuário.");
}

// Se todas as verificações passaram, prossiga com a exclusão
$sql = "DELETE FROM usuarios WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)){
        header("location: usuarios.php");
        exit();
    } else{
        display_error("Oops! Algo deu errado na exclusão. Por favor, tente novamente mais tarde.");
    }
    mysqli_stmt_close($stmt);
} else {
    display_error("Oops! Algo deu errado na preparação da exclusão. Por favor, tente novamente mais tarde.");
}

mysqli_close($link);
require_once 'includes/footer.php';
?>