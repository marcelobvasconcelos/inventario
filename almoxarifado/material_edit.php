<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    require_once '../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Verificar se o ID do material foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID do material não informado.</div>";
    require_once '../includes/footer.php';
    exit;
}

$id = $_GET['id'];

// Buscar o material para edição
$sql_material = "SELECT * FROM almoxarifado_materiais WHERE id = ?";
$stmt_material = $pdo->prepare($sql_material);
$stmt_material->execute([$id]);
$material = $stmt_material->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    echo "<div class='alert alert-danger'>Material não encontrado.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Inicializar variáveis do formulário com os dados do material
$codigo = $material['codigo'];
$nome = $material['nome'];
$descricao = $material['descricao'];
$unidade_medida = $material['unidade_medida'];
$estoque_atual = $material['estoque_atual'];
$valor_unitario = $material['valor_unitario'];
$quantidade_maxima_requisicao = $material['quantidade_maxima_requisicao'];
$categoria_selecionada = $material['categoria'];
$status = $material['status'];

// Processar formulário de edição
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_material'])){
    // O código não será editado, mantém o código original
    $codigo = $material['codigo'];
    $nome = trim($_POST["nome"]);
    $descricao = trim($_POST["descricao"]);
    $unidade_medida = trim($_POST["unidade_medida"]);
    $estoque_atual = trim($_POST["estoque_atual"]);
    $valor_unitario = trim($_POST["valor_unitario"]);
    $quantidade_maxima_requisicao = !empty($_POST["quantidade_maxima_requisicao"]) ? (int)trim($_POST["quantidade_maxima_requisicao"]) : null;
    $categoria_selecionada = trim($_POST["categoria"]);
    $status = $_POST["status"];

    if(empty($nome) || empty($unidade_medida) || empty($categoria_selecionada)){
        $error = "Nome, Unidade de Medida e Categoria são obrigatórios.";
    } else {
        // Como o código não muda, não precisamos verificar se ele já existe
        $sql_update = "UPDATE almoxarifado_materiais SET codigo = ?, nome = ?, descricao = ?, unidade_medida = ?, estoque_atual = ?, valor_unitario = ?, quantidade_maxima_requisicao = ?, categoria = ?, status = ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        
        if($stmt_update->execute([$codigo, $nome, $descricao, $unidade_medida, $estoque_atual, $valor_unitario, $quantidade_maxima_requisicao, $categoria_selecionada, $status, $id])){
            $message = "Material atualizado com sucesso!";
            // Atualizar os valores das variáveis para refletir no formulário
            $material['nome'] = $nome;
            $material['descricao'] = $descricao;
            $material['unidade_medida'] = $unidade_medida;
            $material['estoque_atual'] = $estoque_atual;
            $material['valor_unitario'] = $valor_unitario;
            $material['quantidade_maxima_requisicao'] = $quantidade_maxima_requisicao;
            $material['categoria'] = $categoria_selecionada;
            $material['status'] = $status;
        } else {
            $error = "Erro ao atualizar material. Tente novamente.";
        }
    }
}

// Buscar todas as categorias do almoxarifado para o select
$sql_categorias = "SELECT descricao FROM almoxarifado_categorias ORDER BY descricao ASC";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$categorias_almoxarifado = $stmt_categorias->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Editar Material do Almoxarifado</h2>
        <?php
        // Apenas administradores podem acessar, então o usuário é privilegiado.
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Material: <?php echo htmlspecialchars($material['nome']); ?></h3>
        </div>
        <div class="card-body">
            <form action="material_edit.php?id=<?php echo $id; ?>" method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="codigo">Código:</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" value="<?php echo htmlspecialchars($codigo); ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="nome">Nome do Material:</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição Detalhada:</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="2"><?php echo htmlspecialchars($descricao); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="unidade_medida">Unidade de Medida:</label>
                        <input type="text" class="form-control" id="unidade_medida" name="unidade_medida" value="<?php echo htmlspecialchars($unidade_medida); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="estoque_atual">Estoque Atual:</label>
                        <input type="number" class="form-control" id="estoque_atual" name="estoque_atual" step="0.01" min="0" value="<?php echo htmlspecialchars($estoque_atual); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="valor_unitario">Valor Unitário:</label>
                        <input type="number" class="form-control" id="valor_unitario" name="valor_unitario" step="0.01" min="0" value="<?php echo htmlspecialchars($valor_unitario); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="quantidade_maxima_requisicao">Qtd. Máxima por Requisição:</label>
                        <input type="number" class="form-control" id="quantidade_maxima_requisicao" name="quantidade_maxima_requisicao" min="1" value="<?php echo htmlspecialchars($quantidade_maxima_requisicao); ?>">
                        <small class="form-text text-muted">Deixe em branco para não haver limite.</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="categoria">Categoria:</label>
                        <select class="form-control" id="categoria" name="categoria" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach($categorias_almoxarifado as $categoria_desc): ?>
                                <option value="<?php echo htmlspecialchars($categoria_desc); ?>" <?php echo ($categoria_selecionada == $categoria_desc) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria_desc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="status">Status:</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="ativo" <?php echo ($status == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                            <option value="inativo" <?php echo ($status == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="editar_material" class="btn btn-primary">Salvar Alterações</button>
                <a href="estoque.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>