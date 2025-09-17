m<?php
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

// Verificar mensagens via GET
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Inicializar variáveis do formulário
$nome = '';
$descricao = '';
$unidade_medida = '';
$estoque_minimo = 0;
$categoria_selecionada = '';
$quantidade_maxima_requisicao = null;

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_material'])){
    $nome = trim($_POST["nome"]);
    $descricao = trim($_POST["descricao"]);
    $unidade_medida = trim($_POST["unidade_medida"]);
    $estoque_minimo = trim($_POST["estoque_minimo"]);
    $quantidade_maxima_requisicao = !empty($_POST["quantidade_maxima_requisicao"]) ? (int)trim($_POST["quantidade_maxima_requisicao"]) : null;
    $categoria_selecionada = trim($_POST["categoria"]);

    // Manter o formato completo "numero - descricao"

    if(empty($nome) || empty($unidade_medida) || empty($categoria_selecionada)){
        $error = "Nome, Unidade de Medida e Categoria são obrigatórios.";
    } else {
        // Gerar código automaticamente (MAT- + próximo ID)
      if(empty($nome) || empty($unidade_medida) || empty($categoria_selecionada)){
    $error = "Nome, Unidade de Medida e Categoria são obrigatórios.";
} else {
    try {
        // Passo 1: Inserir o material sem o código e com estoque inicial 0
        $sql_insert = "INSERT INTO almoxarifado_materiais (nome, descricao, unidade_medida, estoque_atual, estoque_minimo, quantidade_maxima_requisicao, categoria) VALUES (?, ?, ?, 0, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$nome, $descricao, $unidade_medida, $estoque_minimo, $quantidade_maxima_requisicao, $categoria_selecionada]);

        // Passo 2: Obter o ID que o banco de dados acabou de gerar.
        $last_id = $pdo->lastInsertId();

        // Passo 3: Gerar o código com base no ID real.
        $codigo = 'MAT-' . str_pad($last_id, 5, '0', STR_PAD_LEFT);

        // Passo 4: Atualizar o registro com o código recém-gerado.
        $sql_update = "UPDATE almoxarifado_materiais SET codigo = ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$codigo, $last_id]);
        
        $message = "Material cadastrado com sucesso! Código gerado: " . $codigo;
        // Limpar os campos do formulário
        $nome = $descricao = $unidade_medida = $categoria_selecionada = '';
        $estoque_minimo = 0;
        $quantidade_maxima_requisicao = null;
    
    } catch (PDOException $e) {
        $error = "Erro ao cadastrar material. Tente novamente. Detalhes: " . $e->getMessage();

        // Para debug, você pode usar:
        // $error .= " Detalhes: " . $e->getMessage();
    }
}
    }
}

// Buscar todas as categorias do almoxarifado para o select
$sql_categorias = "SELECT CONCAT(COALESCE(numero, CAST(id AS CHAR)), ' - ', descricao) as categoria FROM almoxarifado_categorias ORDER BY id ASC";
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
    
    <?php require_once 'menu_empenhos.php'; ?>
    
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
                        <label for="nome">Nome do Material:</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                    </div>
                    <div class="form-group col-md-6" style="position: relative;">
                        <label for="categoria">Categoria:</label>
                        <input type="text" class="form-control" id="categoria" name="categoria" value="<?php echo htmlspecialchars($categoria_selecionada); ?>" required autocomplete="off">
                        <div id="categoria-suggestions" class="suggestions-list"></div>
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
                        <label for="estoque_minimo">Estoque Mínimo:</label>
                        <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo" step="0.01" min="0" value="<?php echo htmlspecialchars($estoque_minimo); ?>" required>
                        <small class="form-text text-muted">Usado apenas para alertas de estoque baixo. O estoque inicial será sempre 0.</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="quantidade_maxima_requisicao">Qtd. Máxima por Requisição:</label>
                    <input type="number" class="form-control" id="quantidade_maxima_requisicao" name="quantidade_maxima_requisicao" min="1" value="<?php echo htmlspecialchars($quantidade_maxima_requisicao); ?>">
                    <small class="form-text text-muted">Deixe em branco para não haver limite.</small>
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

                            <th>Ações</th>
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

                                <td>
                                    <a href="material_edit.php?id=<?php echo $material['id']; ?>" class="btn btn-warning btn-sm" title="Editar Material">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="excluirMaterial(<?php echo $material['id']; ?>, '<?php echo htmlspecialchars($material['nome'], ENT_QUOTES); ?>')" title="Excluir Material">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.suggestions-list {
    position: absolute;
    background: white;
    border: 1px solid #ccc;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    z-index: 1000;
    display: none;
}

.suggestions-list div {
    padding: 8px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.suggestions-list div:hover {
    background: #f8f9fa;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: 1px solid #dc3545;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoriaInput = document.getElementById('categoria');
    const suggestionsDiv = document.getElementById('categoria-suggestions');
    let timeout;

    categoriaInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();

        if (query.length >= 2) {
            timeout = setTimeout(() => {
                fetch(`../api/search_categorias.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsDiv.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(categoria => {
                                const div = document.createElement('div');
                                div.textContent = categoria;
                                div.addEventListener('click', () => {
                                    categoriaInput.value = categoria;
                                    suggestionsDiv.style.display = 'none';
                                });
                                suggestionsDiv.appendChild(div);
                            });
                            suggestionsDiv.style.display = 'block';
                        } else {
                            suggestionsDiv.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Erro na busca:', error);
                        suggestionsDiv.style.display = 'none';
                    });
            }, 300);
        } else {
            suggestionsDiv.style.display = 'none';
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!categoriaInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.style.display = 'none';
        }
    });
});

function excluirMaterial(id, nome) {
    if (confirm('Tem certeza que deseja excluir o material "' + nome + '"?\n\nEsta ação não pode ser desfeita!')) {
        window.location.href = 'material_delete.php?id=' + id;
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>