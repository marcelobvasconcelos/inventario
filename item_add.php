<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem adicionar itens
if($_SESSION["permissao"] != 'admin'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$nome = $patrimonio_novo = $patrimonio_secundario = $local_id = $responsavel_id = $estado = $observacao = "";
$nome_err = $patrimonio_novo_err = $local_id_err = $responsavel_id_err = $estado_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validação dos campos
    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira o nome do item.";
    }

    if(empty(trim($_POST["patrimonio_novo"]))){
        $patrimonio_novo_err = "Por favor, insira o patrimônio.";
    }

    // ... (outras validações)

    if(empty($nome_err) && empty($patrimonio_novo_err) /* && ... */){
        $sql = "INSERT INTO itens (nome, patrimonio_novo, patrimonio_secundario, local_id, responsavel_id, estado, observacao) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssiiss", $_POST['nome'], $_POST['patrimonio_novo'], $_POST['patrimonio_secundario'], $_POST['local_id'], $_POST['responsavel_id'], $_POST['estado'], $_POST['observacao']);
            
            if(mysqli_stmt_execute($stmt)){
                header("location: itens.php");
                exit();
            } else{
                echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
        }
    }
}

$locais = mysqli_query($link, "SELECT id, nome FROM locais");
$usuarios = mysqli_query($link, "SELECT id, nome FROM usuarios");

?>

<h2>Adicionar Novo Item</h2>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div>
        <label>Nome</label>
        <input type="text" name="nome">
    </div>
    <div>
        <label>Patrimônio Novo</label>
        <input type="text" name="patrimonio_novo">
    </div>
    <div>
        <label>Patrimônio Secundário (Opcional)</label>
        <input type="text" name="patrimonio_secundario">
    </div>
    <div>
        <label>Local</label>
        <select name="local_id">
            <?php while($local = mysqli_fetch_assoc($locais)): ?>
                <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label>Responsável</label>
        <select name="responsavel_id">
            <?php while($usuario = mysqli_fetch_assoc($usuarios)): ?>
                <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label>Estado</label>
        <select name="estado">
            <option value="Bom">Bom</option>
            <option value="Razoável">Razoável</option>
            <option value="Inservível">Inservível</option>
        </select>
    </div>
    <div>
        <label>Observação (Opcional)</label>
        <textarea name="observacao"></textarea>
    </div>
    <div>
        <input type="submit" value="Adicionar">
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>