<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$id = $_GET['id'];

// Busca o item completo no início do script
$sql_fetch_item = "SELECT i.*, u.nome AS responsavel_nome FROM itens i JOIN usuarios u ON i.responsavel_id = u.id WHERE i.id = ?";
if($stmt_fetch_item = mysqli_prepare($link, $sql_fetch_item)){
    mysqli_stmt_bind_param($stmt_fetch_item, "i", $id);
    mysqli_stmt_execute($stmt_fetch_item);
    $result_fetch_item = mysqli_stmt_get_result($stmt_fetch_item);
    $item = mysqli_fetch_assoc($result_fetch_item);
    mysqli_stmt_close($stmt_fetch_item);
} else {
    // Tratar erro se o item não for encontrado ou consulta falhar
    echo "<div class='alert alert-danger'>Erro ao buscar detalhes do item.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Verifica permissão
if($_SESSION["permissao"] != 'Administrador' && !($_SESSION["permissao"] == 'Gestor' && $item['responsavel_id'] == $_SESSION['id'])){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $nome = trim($_POST['nome']);
    $patrimonio_novo = trim($_POST['patrimonio_novo']);
    $patrimonio_secundario = trim($_POST['patrimonio_secundario']);
    $local_id = $_POST['local_id'];
    $estado = $_POST['estado'];
    $observacao = trim($_POST['observacao']);

    $sql_update = "UPDATE itens SET nome = ?, patrimonio_novo = ?, patrimonio_secundario = ?, local_id = ?, estado = ?, observacao = ? WHERE id = ?";
    $bind_types = "sssiiss";
    $bind_params = [$nome, $patrimonio_novo, $patrimonio_secundario, $local_id, $estado, $observacao, $id];

    // Se o usuário for administrador, permite alterar o responsável
    if($_SESSION["permissao"] == 'Administrador'){
        $responsavel_id_to_update = $_POST['responsavel_id'];
        $sql_update = "UPDATE itens SET nome = ?, patrimonio_novo = ?, patrimonio_secundario = ?, local_id = ?, responsavel_id = ?, estado = ?, observacao = ? WHERE id = ?";
        $bind_types = "sssiissi";
        // Insere o responsavel_id na posição correta
        array_splice($bind_params, 4, 0, $responsavel_id_to_update);
    }

    if($stmt_update = mysqli_prepare($link, $sql_update)){
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_update, $bind_types], $bind_params));
        
        if(mysqli_stmt_execute($stmt_update)){
            header("location: itens.php");
            exit();
        } else{
            echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
        mysqli_stmt_close($stmt_update); // Close the statement
    } else {
        echo "Erro ao preparar a consulta de atualização: " . mysqli_error($link);
    }
}

$locais = mysqli_query($link, "SELECT id, nome FROM locais");
$usuarios = mysqli_query($link, "SELECT id, nome FROM usuarios");

?>

<h2>Editar Item</h2>

<form action="" method="post">
    <div>
        <label>Nome</label>
        <input type="text" name="nome" value="<?php echo $item['nome']; ?>">
    </div>
    <div>
        <label>Patrimônio Novo</label>
        <input type="text" name="patrimonio_novo" value="<?php echo $item['patrimonio_novo']; ?>">
    </div>
    <div>
        <label>Patrimônio Secundário (Opcional)</label>
        <input type="text" name="patrimonio_secundario" value="<?php echo $item['patrimonio_secundario']; ?>">
    </div>
    <div>
        <label>Local</label>
        <select name="local_id">
            <?php while($local = mysqli_fetch_assoc($locais)): ?>
                <option value="<?php echo $local['id']; ?>" <?php echo ($local['id'] == $item['local_id']) ? 'selected' : ''; ?>><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label>Responsável</label>
        <?php if($_SESSION['permissao'] == 'Administrador'): ?>
            <select name="responsavel_id">
                <?php while($usuario = mysqli_fetch_assoc($usuarios)): ?>
                    <option value="<?php echo $usuario['id']; ?>" <?php echo ($usuario['id'] == $item['responsavel_id']) ? 'selected' : ''; ?>><?php echo $usuario['nome']; ?></option>
                <?php endwhile; ?>
            </select>
        <?php else: // Gestor ou Visualizador ?>
            <input type="text" value="<?php echo isset($item['responsavel_nome']) ? $item['responsavel_nome'] : ''; ?>" disabled>
            <input type="hidden" name="responsavel_id" value="<?php echo $item['responsavel_id']; ?>">
        <?php endif; ?>
    </div>
    <div>
        <label>Estado</label>
        <select name="estado">
            <option value="Bom" <?php echo ($item['estado'] == 'Bom') ? 'selected' : ''; ?>>Bom</option>
            <option value="Razoável" <?php echo ($item['estado'] == 'Razoável') ? 'selected' : ''; ?>>Razoável</option>
            <option value="Inservível" <?php echo ($item['estado'] == 'Inservível') ? 'selected' : ''; ?>>Inservível</option>
        </select>
    </div>
    <div>
        <label>Observação (Opcional)</label>
        <textarea name="observacao"><?php echo $item['observacao']; ?></textarea>
    </div>
    <div>
        <input type="submit" value="Salvar Alterações">
        <a href="itens.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>