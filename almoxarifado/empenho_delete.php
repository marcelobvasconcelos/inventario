<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    header('Location: ../index.php');
    exit;
}

$message = '';
$error = '';

// Verificar se foi passado um número de empenho
if(!isset($_GET['numero']) || empty($_GET['numero'])){
    header('Location: empenhos_index.php');
    exit;
}

$numero = $_GET['numero'];

// Buscar empenho no banco
$sql_select = "SELECT * FROM empenhos_insumos WHERE numero = ?";
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute([$numero]);
$empenho = $stmt_select->fetch(PDO::FETCH_ASSOC);

// Se não encontrar o empenho, redirecionar
if(!$empenho){
    header('Location: empenhos_index.php');
    exit;
}

// Processar exclusão
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirmar_exclusao'])){
    // Verificar se há notas fiscais relacionadas
    $sql_check_notas = "SELECT COUNT(*) as total FROM notas_fiscais WHERE empenho_numero = ?";
    $stmt_check_notas = $pdo->prepare($sql_check_notas);
    $stmt_check_notas->execute([$numero]);
    $total_notas = $stmt_check_notas->fetch(PDO::FETCH_ASSOC)['total'];

    if($total_notas > 0){
        $error = "Não é possível excluir o empenho porque existem $total_notas nota(s) fiscal(is) relacionada(s).";
    } else {
        // Excluir empenho
        $sql_delete = "DELETE FROM empenhos_insumos WHERE numero = ?";
        $stmt_delete = $pdo->prepare($sql_delete);

        if($stmt_delete->execute([$numero])){
            header("Location: empenhos_index.php");
            exit;
        } else {
            $error = "Erro ao excluir empenho. Tente novamente.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Excluir Empenho</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Confirmar Exclusão</h3>
        </div>
        <div class="card-body">
            <p>Tem certeza que deseja excluir o empenho <strong><?php echo htmlspecialchars($empenho['numero']); ?></strong> do fornecedor <strong><?php echo htmlspecialchars($empenho['fornecedor']); ?></strong>?</p>
            <p class="text-danger">Esta ação não pode ser desfeita!</p>

            <form action="empenho_delete.php?numero=<?php echo $numero; ?>" method="post">
                <button type="submit" name="confirmar_exclusao" class="btn btn-danger">Sim, Excluir</button>
                <a href="empenhos_index.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>