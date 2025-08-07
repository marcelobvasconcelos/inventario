<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas gestores podem solicitar novos locais
if($_SESSION["permissao"] != 'Gestor'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$nome_local = "";
$nome_local_err = "";
$success_message = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Valida o nome do local
    if(empty(trim($_POST["nome_local"]))){
        $nome_local_err = "Por favor, insira o nome do local.";
    } else {
        $nome_local = trim($_POST["nome_local"]);
        // Verifica se o local já existe (aprovado ou pendente)
        $sql_check = "SELECT id FROM locais WHERE nome = ? AND (status = 'aprovado' OR status = 'pendente')";
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            mysqli_stmt_bind_param($stmt_check, "s", $param_nome_local);
            $param_nome_local = $nome_local;
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if(mysqli_stmt_num_rows($stmt_check) == 1){
                $nome_local_err = "Este local já existe ou está pendente de aprovação.";
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // Se não houver erros, insere o novo local como pendente
    if(empty($nome_local_err)){
        $sql_insert = "INSERT INTO locais (nome, status, solicitado_por) VALUES (?, 'pendente', ?)";
        if($stmt_insert = mysqli_prepare($link, $sql_insert)){
            mysqli_stmt_bind_param($stmt_insert, "si", $param_nome, $param_solicitado_por);
            $param_nome = $nome_local;
            $param_solicitado_por = $_SESSION['id'];

            if(mysqli_stmt_execute($stmt_insert)){
                $success_message = "Sua solicitação para o local \"" . htmlspecialchars($nome_local) . "\" foi enviada para aprovação.";
                $nome_local = ""; // Limpa o campo após o sucesso
            } else{
                echo "Oops! Algo deu errado ao solicitar o local. Por favor, tente novamente mais tarde. " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt_insert);
        }
    }
}

?>

<h2>Solicitar Novo Local</h2>

<?php if(!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div>
        <label>Nome do Local</label>
        <input type="text" name="nome_local" value="<?php echo htmlspecialchars($nome_local); ?>" required>
        <span class="help-block"><?php echo $nome_local_err; ?></span>
    </div>
    <div>
        <input type="submit" value="Solicitar">
        <a href="item_add.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>