<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Verifica se o usuário tem permissão para editar itens (Administrador)
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

$id = $_GET['id'];

// Busca o item completo no início do script
$sql_fetch_item = "SELECT * FROM almoxarifado_materiais WHERE id = ?";
if($stmt_fetch_item = mysqli_prepare($link, $sql_fetch_item)){
    mysqli_stmt_bind_param($stmt_fetch_item, "i", $id);
    mysqli_stmt_execute($stmt_fetch_item);
    $result_fetch_item = mysqli_stmt_get_result($stmt_fetch_item);
    $item = mysqli_fetch_assoc($result_fetch_item);
    mysqli_stmt_close($stmt_fetch_item);
    
    // Verifica se o item foi encontrado
    if (!$item) {
        echo "<div class='alert alert-danger'>Item não encontrado.</div>";
        require_once '../includes/footer.php';
        exit;
    }
} else {
    // Tratar erro se o item não for encontrado ou consulta falhar
    echo "<div class='alert alert-danger'>Erro ao buscar detalhes do item.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Inicializa variáveis para mensagens de erro
$codigo_err = $nome_err = $unidade_medida_err = $estoque_minimo_err = $estoque_atual_err = $valor_unitario_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validação e sanitização dos campos do formulário
    if(empty(trim($_POST["codigo"]))){
        $codigo_err = "Por favor, insira o código do item.";
    } else {
        $codigo = trim($_POST["codigo"]);
    }
    
    <?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Verifica se o usuário tem permissão para editar itens (Administrador)
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

$id = $_GET['id'];

// Buscar locais e responsáveis para os dropdowns
$locais = $pdo->query("SELECT id, nome FROM locais ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$responsaveis = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Busca o item completo no início do script
$sql_fetch_item = "SELECT * FROM almoxarifado_materiais WHERE id = ?";
if($stmt_fetch_item = mysqli_prepare($link, $sql_fetch_item)){
    mysqli_stmt_bind_param($stmt_fetch_item, "i", $id);
    mysqli_stmt_execute($stmt_fetch_item);
    $result_fetch_item = mysqli_stmt_get_result($stmt_fetch_item);
    $item = mysqli_fetch_assoc($result_fetch_item);
    mysqli_stmt_close($stmt_fetch_item);
    
    // Verifica se o item foi encontrado
    if (!$item) {
        echo "<div class='alert alert-danger'>Item não encontrado.</div>";
        require_once '../includes/footer.php';
        exit;
    }
} else {
    // Tratar erro se o item não for encontrado ou consulta falhar
    echo "<div class='alert alert-danger'>Erro ao buscar detalhes do item.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Inicializa variáveis para mensagens de erro
$nome_err = $local_id_err = $responsavel_id_err = $estado_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validação e sanitização dos campos do formulário
    if(empty(trim($_POST["nome"])))
        $nome_err = "Por favor, insira o nome do item.";
    } else {
        $nome = trim($_POST["nome"]);
    }
    
    if(empty(trim($_POST["local_id"])))
        $local_id_err = "Por favor, selecione o local.";
    } else {
        $local_id = trim($_POST["local_id"]);
    }

    if(empty(trim($_POST["responsavel_id"])))
        $responsavel_id_err = "Por favor, selecione o responsável.";
    } else {
        $responsavel_id = trim($_POST["responsavel_id"]);
    }

    if(empty(trim($_POST["estado"])))
        $estado_err = "Por favor, insira o estado do item.";
    } else {
        $estado = trim($_POST["estado"]);
    }
    
    // Se não houver erros de validação, atualiza o item no banco de dados
    if(empty($nome_err) && empty($local_id_err) && empty($responsavel_id_err) && empty($estado_err)){
        $sql_update = "UPDATE almoxarifado_materiais SET nome = ?, local_id = ?, responsavel_id = ?, estado = ? WHERE id = ?";
        
        if($stmt_update = mysqli_prepare($link, $sql_update)){
            mysqli_stmt_bind_param($stmt_update, "siisi", $nome, $local_id, $responsavel_id, $estado, $id);
            
            if(mysqli_stmt_execute($stmt_update)){
                header("location: itens.php");
                exit();
            } else{
                echo "<div class='alert alert-danger'>Oops! Algo deu errado. Por favor, tente novamente mais tarde.</div>";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            echo "Erro ao preparar a consulta de atualização: " . mysqli_error($link);
        }
    }
}
?>

<h2>Editar Item do Almoxarifado</h2>

<form action="" method="post">
    <div>
        <label>Nome</label>
        <input type="text" name="nome" value="<?php echo htmlspecialchars($item['nome']); ?>" required>
        <span class="help-block"><?php echo $nome_err; ?></span>
    </div>
    <div>
        <label>Local</label>
        <select name="local_id" required>
            <option value="">Selecione um local</option>
            <?php foreach ($locais as $local): ?>
                <option value="<?php echo $local['id']; ?>" <?php echo ($item['local_id'] == $local['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($local['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="help-block"><?php echo $local_id_err; ?></span>
    </div>
    <div>
        <label>Responsável</label>
        <select name="responsavel_id" required>
            <option value="">Selecione um responsável</option>
            <?php foreach ($responsaveis as $responsavel): ?>
                <option value="<?php echo $responsavel['id']; ?>" <?php echo ($item['responsavel_id'] == $responsavel['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($responsavel['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="help-block"><?php echo $responsavel_id_err; ?></span>
    </div>
    <div>
        <label>Estado</label>
        <input type="text" name="estado" value="<?php echo htmlspecialchars($item['estado']); ?>" required>
        <span class="help-block"><?php echo $estado_err; ?></span>
    </div>
    <div>
        <input type="submit" value="Salvar Alterações">
        <a href="itens.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>
    
    $descricao = trim($_POST["descricao"]);
    
    if(empty(trim($_POST["unidade_medida"]))){
        $unidade_medida_err = "Por favor, insira a unidade de medida.";
    } else {
        $unidade_medida = trim($_POST["unidade_medida"]);
    }
    
    if(empty(trim($_POST["estoque_minimo"]))){
        $estoque_minimo_err = "Por favor, insira o estoque mínimo.";
    } else {
        $estoque_minimo = trim($_POST["estoque_minimo"]);
        if (!is_numeric($estoque_minimo) || $estoque_minimo < 0) {
            $estoque_minimo_err = "O estoque mínimo deve ser um número positivo.";
        }
    }
    
    if(empty(trim($_POST["estoque_atual"]))){
        $estoque_atual_err = "Por favor, insira o estoque atual.";
    } else {
        $estoque_atual = trim($_POST["estoque_atual"]);
        if (!is_numeric($estoque_atual) || $estoque_atual < 0) {
            $estoque_atual_err = "O estoque atual deve ser um número positivo.";
        }
    }
    
    if(empty(trim($_POST["valor_unitario"]))){
        $valor_unitario_err = "Por favor, insira o valor unitário.";
    } else {
        $valor_unitario = trim($_POST["valor_unitario"]);
        // Remove caracteres não numéricos, exceto vírgula e ponto
        $valor_unitario = str_replace(',', '.', str_replace('R$', '', $valor_unitario));
        if (!is_numeric($valor_unitario) || $valor_unitario < 0) {
            $valor_unitario_err = "O valor unitário deve ser um número positivo.";
        }
    }
    
    $categoria = trim($_POST["categoria"]);
    $status = $_POST["status"];
    
    // Se não houver erros de validação, atualiza o item no banco de dados
    if(empty($codigo_err) && empty($nome_err) && empty($unidade_medida_err) && empty($estoque_minimo_err) && empty($estoque_atual_err) && empty($valor_unitario_err)){
        $sql_update = "UPDATE almoxarifado_materiais SET codigo = ?, nome = ?, descricao = ?, unidade_medida = ?, estoque_minimo = ?, estoque_atual = ?, valor_unitario = ?, categoria = ?, status = ? WHERE id = ?";
        
        if($stmt_update = mysqli_prepare($link, $sql_update)){
            mysqli_stmt_bind_param($stmt_update, "ssssddsssi", $codigo, $nome, $descricao, $unidade_medida, $estoque_minimo, $estoque_atual, $valor_unitario, $categoria, $status, $id);
            
            if(mysqli_stmt_execute($stmt_update)){
                header("location: itens.php");
                exit();
            } else{
                // Verifica se o erro é de entrada duplicada
                if (mysqli_errno($link) == 1062 && strpos(mysqli_error($link), 'codigo') !== false) {
                    preg_match("/Duplicate entry '([^']+)' for key 'codigo'/", mysqli_error($link), $matches);
                    $codigo_duplicado = isset($matches[1]) ? $matches[1] : '';
                    echo "<div class='alert alert-danger'>Este código " . htmlspecialchars($codigo_duplicado) . " já está cadastrado!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Oops! Algo deu errado. Por favor, tente novamente mais tarde.</div>";
                }
            }
            mysqli_stmt_close($stmt_update);
        } else {
            echo "Erro ao preparar a consulta de atualização: " . mysqli_error($link);
        }
    }
}
?>

<h2>Editar Item do Almoxarifado</h2>

<form action="" method="post">
    <div>
        <label>Código</label>
        <input type="text" name="codigo" value="<?php echo htmlspecialchars($item['codigo']); ?>" required>
        <span class="help-block"><?php echo $codigo_err; ?></span>
    </div>
    <div>
        <label>Nome</label>
        <input type="text" name="nome" value="<?php echo htmlspecialchars($item['nome']); ?>" required>
        <span class="help-block"><?php echo $nome_err; ?></span>
    </div>
    <div>
        <label>Descrição (Opcional)</label>
        <textarea name="descricao"><?php echo htmlspecialchars($item['descricao']); ?></textarea>
    </div>
    <div>
        <label>Unidade de Medida</label>
        <input type="text" name="unidade_medida" value="<?php echo htmlspecialchars($item['unidade_medida']); ?>" required>
        <span class="help-block"><?php echo $unidade_medida_err; ?></span>
    </div>
    <div>
        <label>Estoque Mínimo</label>
        <input type="number" step="0.01" name="estoque_minimo" value="<?php echo htmlspecialchars($item['estoque_minimo']); ?>" required>
        <span class="help-block"><?php echo $estoque_minimo_err; ?></span>
    </div>
    <div>
        <label>Estoque Atual</label>
        <input type="number" step="0.01" name="estoque_atual" value="<?php echo htmlspecialchars($item['estoque_atual']); ?>" required>
        <span class="help-block"><?php echo $estoque_atual_err; ?></span>
    </div>
    <div>
        <label>Valor Unitário (R$)</label>
        <input type="text" name="valor_unitario" value="<?php echo htmlspecialchars($item['valor_unitario']); ?>" required>
        <span class="help-block"><?php echo $valor_unitario_err; ?></span>
    </div>
    <div>
        <label>Categoria (Opcional)</label>
        <input type="text" name="categoria" value="<?php echo htmlspecialchars($item['categoria']); ?>">
    </div>
    <div>
        <label>Status</label>
        <select name="status">
            <option value="ativo" <?php echo ($item['status'] == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
            <option value="inativo" <?php echo ($item['status'] == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
        </select>
    </div>
    <div>
        <input type="submit" value="Salvar Alterações">
        <a href="itens.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>