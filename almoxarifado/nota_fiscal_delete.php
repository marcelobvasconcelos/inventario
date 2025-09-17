<?php
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    header('Location: ../index.php');
    exit;
}

$message = '';
$error = '';

// Verificar se foi passado um número de nota fiscal
if(!isset($_GET['nota']) || empty($_GET['nota'])){
    header('Location: nota_fiscal_add.php');
    exit;
}

$nota_numero = $_GET['nota'];

// Buscar nota fiscal no banco
$sql_select = "SELECT * FROM notas_fiscais WHERE nota_numero = ?";
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute([$nota_numero]);
$nota = $stmt_select->fetch(PDO::FETCH_ASSOC);

// Se não encontrar a nota fiscal, redirecionar
if(!$nota){
    header('Location: nota_fiscal_add.php');
    exit;
}

// Processar exclusão
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirmar_exclusao'])){
    // Verificar se há materiais relacionados
    $sql_check_materiais = "SELECT COUNT(*) as total FROM almoxarifado_materiais WHERE nota_fiscal = ?";
    $stmt_check_materiais = $pdo->prepare($sql_check_materiais);
    $stmt_check_materiais->execute([$nota_numero]);
    $total_materiais = $stmt_check_materiais->fetch(PDO::FETCH_ASSOC)['total'];

    if($total_materiais > 0){
        $error = "Não é possível excluir a nota fiscal porque existem $total_materiais material(is) relacionado(s).";
    } else {
        $pdo->beginTransaction();
        try {
            // Reverter o valor do saldo do empenho
            $sql_update_saldo = "UPDATE empenhos_insumos SET saldo = saldo + ? WHERE numero = ?";
            $stmt_update_saldo = $pdo->prepare($sql_update_saldo);
            $stmt_update_saldo->execute([$nota['nota_valor'], $nota['empenho_numero']]);

            // Excluir nota fiscal
            $sql_delete = "DELETE FROM notas_fiscais WHERE nota_numero = ?";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute([$nota_numero]);

            $pdo->commit();
            header("Location: nota_fiscal_add.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollback();
            $error = "Erro ao excluir nota fiscal. Tente novamente. Detalhes: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Excluir Nota Fiscal</h2>
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
            <p>Tem certeza que deseja excluir a nota fiscal <strong><?php echo htmlspecialchars($nota['nota_numero']); ?></strong> do fornecedor <strong><?php echo htmlspecialchars($nota['fornecedor'] ?? 'N/A'); ?></strong>?</p>
            <p class="text-danger">Esta ação não pode ser desfeita e irá reverter o valor da nota do saldo do empenho!</p>

            <form action="nota_fiscal_delete.php?nota=<?php echo $nota_numero; ?>" method="post">
                <button type="submit" name="confirmar_exclusao" class="btn btn-danger">Sim, Excluir</button>
                <a href="nota_fiscal_add.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>