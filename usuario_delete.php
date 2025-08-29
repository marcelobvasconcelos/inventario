<?php
session_start();
require_once 'config/db.php';
require_once 'includes/header.php';

// Função para exibir uma página de erro padronizada e sair
function display_error($message) {
    echo '<main>';
    echo '<h2>Erro ao Rejeitar Usuário</h2>';
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
    display_error("Você não pode rejeitar seu próprio usuário.");
}

// Obter o ID do usuário "Lixeira"
try {
    $stmt_lixeira = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
    $stmt_lixeira->execute();
    $lixeira = $stmt_lixeira->fetch(PDO::FETCH_ASSOC);
    
    if (!$lixeira) {
        display_error("Usuário 'Lixeira' não encontrado. Execute o script de atualização do banco de dados.");
    }
    
    $lixeira_id = $lixeira['id'];
} catch (Exception $e) {
    display_error("Erro ao localizar usuário 'Lixeira': " . $e->getMessage());
}

// Verifica se o usuário é responsável por algum item que NÃO esteja na lixeira
$check_itens_sql = "SELECT id FROM itens WHERE responsavel_id = ? AND responsavel_id != ?";
if($stmt_check_itens = mysqli_prepare($link, $check_itens_sql)){
    mysqli_stmt_bind_param($stmt_check_itens, "ii", $id, $lixeira_id);
    mysqli_stmt_execute($stmt_check_itens);
    mysqli_stmt_store_result($stmt_check_itens);
    if(mysqli_stmt_num_rows($stmt_check_itens) > 0){
        mysqli_stmt_close($stmt_check_itens);
        display_error("Não é possível excluir este usuário, pois ele é responsável por um ou mais itens que não estão na lixeira. Reatribua os itens a outro usuário ou exclua-os antes de excluir o usuário.");
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
        // Se o usuário tem movimentações, apenas o rejeitamos em vez de excluí-lo
        mysqli_stmt_close($stmt_check_mov);
        
        // Atualiza o status do usuário para 'rejeitado'
        $update_sql = "UPDATE usuarios SET status = 'rejeitado' WHERE id = ?";
        if($stmt_update = mysqli_prepare($link, $update_sql)){
            mysqli_stmt_bind_param($stmt_update, "i", $id);
            
            if(mysqli_stmt_execute($stmt_update)){
                // Redireciona com mensagem de sucesso
                header("location: usuarios.php?status=usuario_rejeitado");
                exit();
            } else{
                display_error("Oops! Algo deu errado ao rejeitar o usuário. Por favor, tente novamente mais tarde.");
            }
            mysqli_stmt_close($stmt_update);
        } else {
            display_error("Oops! Algo deu errado na preparação da atualização. Por favor, tente novamente mais tarde.");
        }
        // Importante: sair aqui para não continuar com a exclusão
        exit();
    }
    mysqli_stmt_close($stmt_check_mov);
} else {
    display_error("Erro ao verificar as movimentações do usuário.");
}

// Se o usuário não tem movimentações, podemos excluí-lo permanentemente
$sql = "DELETE FROM usuarios WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)){
        header("location: usuarios.php?status=usuario_excluido");
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