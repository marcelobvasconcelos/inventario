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

// Inicializar variáveis do formulário
$codigo = '';
$nome = '';
$descricao = '';
$unidade_medida = '';
$estoque_atual = 0;
$valor_unitario = 0;
$categoria_selecionada = '';

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_material'])){
    $codigo = trim($_POST["codigo"]);
    $nome = trim($_POST["nome"]);
    $descricao = trim($_POST["descricao"]);
    $unidade_medida = trim($_POST["unidade_medida"]);
    $estoque_atual = trim($_POST["estoque_atual"]);
    $valor_unitario = trim($_POST["valor_unitario"]);
    $categoria_selecionada = trim($_POST["categoria"]);

    if(empty($codigo) || empty($nome) || empty($unidade_medida) || empty($categoria_selecionada)){
        $error = "Código, Nome, Unidade de Medida e Categoria são obrigatórios.";
    } else {
        // Verificar se o código já existe
        $sql_check = "SELECT id FROM almoxarifado_materiais WHERE codigo = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$codigo]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe um material com este código.";
        } else {
            $sql_insert = "INSERT INTO almoxarifado_materiais (codigo, nome, descricao, unidade_medida, estoque_atual, valor_unitario, categoria, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo')";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            if($stmt_insert->execute([$codigo, $nome, $descricao, $unidade_medida, $estoque_atual, $valor_unitario, $categoria_selecionada])){
                $message = "Material cadastrado com sucesso!";
                // Limpar campos
                $codigo = $nome = $descricao = $unidade_medida = $categoria_selecionada = '';
                $estoque_atual = $valor_unitario = 0;
            } else {
                $error = "Erro ao cadastrar material. Tente novamente.";
            }
        }
    }
}

// Buscar todas as categorias do almoxarifado para o select
$sql_categorias = "SELECT descricao FROM almoxarifado_categorias ORDER BY descricao ASC";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$categorias_almoxarifado = $stmt_categorias->fetchAll(PDO::FETCH_COLUMN);

// Buscar todos os materiais cadastrados
$sql_materiais = "SELECT * FROM almoxarifado_materiais ORDER BY nome ASC";
$stmt_materiais = $pdo->prepare($sql_materiais);
$stmt_materiais->execute();
$materiais = $stmt_materiais->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Gerenciamento de Materiais do Almoxarifado</h2>
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
            <h3>Cadastrar Novo Material</h3>
        </div>
        <div class="card-body">
            <form action="material_add.php" method="post">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="codigo">Código:</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" value="<?php echo htmlspecialchars($codigo); ?>" required>
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
                    <div class="form-group col-md-4">
                        <label for="unidade_medida">Unidade de Medida:</label>
                        <input type="text" class="form-control" id="unidade_medida" name="unidade_medida" value="<?php echo htmlspecialchars($unidade_medida); ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="estoque_atual">Estoque Inicial:</label>
                        <input type="number" class="form-control" id="estoque_atual" name="estoque_atual" step="0.01" min="0" value="<?php echo htmlspecialchars($estoque_atual); ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="valor_unitario">Valor Unitário:</label>
                        <input type="number" class="form-control" id="valor_unitario" name="valor_unitario" step="0.01" min="0" value="<?php echo htmlspecialchars($valor_unitario); ?>" required>
                    </div>
                </div>
                <div class="form-group">
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
                <button type="submit" name="cadastrar_material" class="btn btn-primary">Cadastrar Material</button>
                <a href="empenhos_index.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
    
    <?php if(!empty($materiais)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h3>Materiais Cadastrados</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Estoque</th>
                            <th>Un.</th>
                            <th>Categoria</th>
                            <th>Valor Unit.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($materiais as $material): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($material['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($material['nome']); ?></td>
                                <td><?php echo htmlspecialchars($material['estoque_atual']); ?></td>
                                <td><?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                <td><?php echo htmlspecialchars($material['categoria']); ?></td>
                                <td>R$ <?php echo number_format($material['valor_unitario'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>