<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Inicializar variáveis
$nome = '';
$qtd = '';
$categoria_id = '';
$valor_unit = '';
$nota_no = '';

// Se foi passada uma nota fiscal via GET, preencher o campo
if(isset($_GET['nota']) && !empty($_GET['nota'])){
    $nota_no = $_GET['nota'];
}

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_material'])){
    $nome = trim($_POST["nome"]);
    $qtd = trim($_POST["qtd"]);
    $categoria_id = trim($_POST["categoria_id"]);
    $valor_unit = trim($_POST["valor_unit"]);
    $nota_no = trim($_POST["nota_no"]);
    
    // Validação
    if(empty($nome) || empty($qtd) || empty($categoria_id) || empty($valor_unit)){
        $error = "Nome, quantidade, categoria e valor unitário são obrigatórios.";
    } else {
        // Se foi fornecida uma nota fiscal, verificar se ela existe
        if(!empty($nota_no)){
            $sql_check_nota = "SELECT nota_numero FROM notas_fiscais WHERE nota_numero = ?";
            $stmt_check_nota = $pdo->prepare($sql_check_nota);
            $stmt_check_nota->execute([$nota_no]);
            
            if($stmt_check_nota->rowCount() == 0){
                $error = "Nota fiscal não encontrada.";
            } else {
                // Inserir novo material com nota fiscal
                $sql_insert = "INSERT INTO materiais (nome, qtd, categoria_id, valor_unit, nota_no) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $pdo->prepare($sql_insert);
                
                if($stmt_insert->execute([$nome, $qtd, $categoria_id, $valor_unit, $nota_no])){
                    $message = "Material cadastrado com sucesso!";
                    // Limpar campos
                    $nome = '';
                    $qtd = '';
                    $categoria_id = '';
                    $valor_unit = '';
                    // Manter a nota fiscal selecionada
                } else {
                    $error = "Erro ao cadastrar material. Tente novamente.";
                }
            }
        } else {
            // Inserir novo material sem nota fiscal
            $sql_insert = "INSERT INTO materiais (nome, qtd, categoria_id, valor_unit) VALUES (?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            if($stmt_insert->execute([$nome, $qtd, $categoria_id, $valor_unit])){
                $message = "Material cadastrado com sucesso!";
                // Limpar campos
                $nome = '';
                $qtd = '';
                $categoria_id = '';
                $valor_unit = '';
            } else {
                $error = "Erro ao cadastrar material. Tente novamente.";
            }
        }
    }
}

// Buscar todas as categorias para o select
$sql_categorias = "SELECT id, descricao FROM categorias ORDER BY descricao ASC";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as notas fiscais para o select
$sql_notas = "SELECT nota_numero FROM notas_fiscais ORDER BY nota_numero ASC";
$stmt_notas = $pdo->prepare($sql_notas);
$stmt_notas->execute();
$notas_fiscais = $stmt_notas->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os materiais cadastrados com suas categorias e notas fiscais
$sql_materiais = "SELECT m.*, c.descricao as categoria_descricao, nf.nota_numero 
                  FROM materiais m 
                  LEFT JOIN categorias c ON m.categoria_id = c.id 
                  LEFT JOIN notas_fiscais nf ON m.nota_no = nf.nota_numero 
                  ORDER BY m.nome ASC";
$stmt_materiais = $pdo->prepare($sql_materiais);
$stmt_materiais->execute();
$materiais = $stmt_materiais->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2>Gerenciamento de Materiais</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Cadastrar Novo Material</h3>
        </div>
        <div class="card-body">
            <form action="material_add.php" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nome">Nome do Material:</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="qtd">Quantidade:</label>
                            <input type="number" class="form-control" id="qtd" name="qtd" min="1" value="<?php echo isset($qtd) ? htmlspecialchars($qtd) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="categoria_id">Categoria:</label>
                            <select class="form-control" id="categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['id']); ?>" <?php echo (isset($categoria_id) && $categoria_id == $categoria['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['descricao']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="valor_unit">Valor Unitário:</label>
                            <input type="number" class="form-control" id="valor_unit" name="valor_unit" step="0.01" min="0" value="<?php echo isset($valor_unit) ? htmlspecialchars($valor_unit) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nota_no">Nota Fiscal (opcional):</label>
                            <select class="form-control" id="nota_no" name="nota_no">
                                <option value="">Selecione uma nota fiscal (opcional)</option>
                                <?php foreach($notas_fiscais as $nota): ?>
                                    <option value="<?php echo htmlspecialchars($nota['nota_numero']); ?>" <?php echo (isset($nota_no) && $nota_no == $nota['nota_numero']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nota['nota_numero']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="cadastrar_material" class="btn btn-primary">Cadastrar Material</button>
                <a href="index.php" class="btn btn-secondary">Voltar</a>
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
                                <th>Nome</th>
                                <th>Quantidade</th>
                                <th>Categoria</th>
                                <th>Valor Unitário</th>
                                <th>Nota Fiscal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($materiais as $material): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($material['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($material['qtd']); ?></td>
                                    <td><?php echo htmlspecialchars($material['categoria_descricao']); ?></td>
                                    <td>R$ <?php echo number_format($material['valor_unit'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($material['nota_numero'] ?? 'Não vinculada'); ?></td>
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
require_once '../../includes/footer.php';
?>