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

// Processar formulário de edição
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_material'])){
    // Processar apenas campos editáveis
    $nome = trim($_POST["nome"]);
    $descricao = trim($_POST["descricao"]);
    $unidade_medida = trim($_POST["unidade_medida"]);
    $estoque_minimo = !empty($_POST["estoque_minimo"]) ? (float)trim($_POST["estoque_minimo"]) : 0;
    $quantidade_maxima_requisicao = !empty($_POST["quantidade_maxima_requisicao"]) ? (int)trim($_POST["quantidade_maxima_requisicao"]) : null;
    $categoria_selecionada = trim($_POST["categoria"]);

    // Manter o formato completo "numero - descricao"



    if(empty($nome) || empty($unidade_medida) || empty($categoria_selecionada)){
        $error = "Nome, Unidade de Medida e Categoria são obrigatórios.";
    } else {
        // Atualizar apenas campos básicos do material (não estoque, valor ou nota fiscal)
        $sql_update = "UPDATE almoxarifado_materiais SET nome = ?, descricao = ?, unidade_medida = ?, estoque_minimo = ?, quantidade_maxima_requisicao = ?, categoria = ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        
        if($stmt_update->execute([$nome, $descricao, $unidade_medida, $estoque_minimo, $quantidade_maxima_requisicao, $categoria_selecionada, $id])){
            $message = "Material atualizado com sucesso!";
            // Atualizar os valores das variáveis para refletir no formulário
            $material['nome'] = $nome;
            $material['descricao'] = $descricao;
            $material['unidade_medida'] = $unidade_medida;
            $material['estoque_atual'] = $estoque_atual;
            $material['valor_unitario'] = $valor_unitario;
            $material['quantidade_maxima_requisicao'] = $quantidade_maxima_requisicao;
            $material['categoria'] = $categoria_selecionada;
        } else {
            $error = "Erro ao atualizar material. Tente novamente.";
        }
    }
}

// Buscar todas as categorias do almoxarifado para o select
$sql_categorias = "SELECT CONCAT(COALESCE(numero, CAST(id AS CHAR)), ' - ', descricao) as categoria FROM almoxarifado_categorias ORDER BY id ASC";
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
                    <div class="form-group col-md-4">
                        <label for="unidade_medida">Unidade de Medida:</label>
                        <input type="text" class="form-control" id="unidade_medida" name="unidade_medida" value="<?php echo htmlspecialchars($unidade_medida); ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="estoque_minimo">Estoque Mínimo:</label>
                        <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo" step="0.01" min="0" value="<?php echo htmlspecialchars($material['estoque_minimo'] ?? 0); ?>">
                        <small class="form-text text-muted">Quantidade mínima para alerta.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="quantidade_maxima_requisicao">Qtd. Máxima por Requisição:</label>
                        <input type="number" class="form-control" id="quantidade_maxima_requisicao" name="quantidade_maxima_requisicao" min="1" value="<?php echo htmlspecialchars($quantidade_maxima_requisicao); ?>">
                        <small class="form-text text-muted">Deixe em branco para não haver limite.</small>
                    </div>
                </div>
                <div class="form-group" style="position: relative;">
                    <label for="categoria">Categoria:</label>
                    <input type="text" class="form-control" id="categoria" name="categoria" value="<?php echo htmlspecialchars($categoria_selecionada); ?>" required autocomplete="off">
                    <div id="categoria-suggestions" class="suggestions-list"></div>
                </div>
                
                <!-- Informações somente leitura -->
                <div class="alert alert-info">
                    <h5>Informações de Estoque (Somente Leitura)</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Estoque Atual:</strong> <?php echo number_format($estoque_atual, 2, ',', '.'); ?> <?php echo htmlspecialchars($unidade_medida); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Valor Unitário:</strong> R$ <?php echo number_format($valor_unitario, 2, ',', '.'); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Nota Fiscal:</strong> <?php echo $material['nota_fiscal'] ? htmlspecialchars($material['nota_fiscal']) : 'Nenhuma'; ?>
                        </div>
                    </div>
                    <small class="text-muted">Para alterar estoque ou valor, use "Entrada de Material" ou "Ajustar Estoque".</small>
                </div>
                <button type="submit" name="editar_material" class="btn btn-primary">Salvar Alterações</button>
                <a href="#" class="btn btn-info" onclick="toggleEntradas()">Ver/Editar Entradas</a>
                <a href="index.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
    
    <!-- Seção de Entradas -->
    <div id="entradas-section" class="card mt-4" style="display: none;">
        <div class="card-header">
            <h3>Entradas do Material</h3>
        </div>
        <div class="card-body">
            <?php
            // Buscar entradas do material
            $sql_entradas = "SELECT e.*, nf.saldo as nota_saldo 
                            FROM almoxarifado_entradas e
                            LEFT JOIN notas_fiscais nf ON e.nota_fiscal = nf.nota_numero
                            WHERE e.material_id = ? 
                            ORDER BY e.data_entrada DESC";
            $stmt_entradas = $pdo->prepare($sql_entradas);
            $stmt_entradas->execute([$id]);
            $entradas = $stmt_entradas->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (!empty($entradas)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Nota Fiscal</th>
                                <th>Quantidade</th>
                                <th>Valor Unitário</th>
                                <th>Valor Total</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($entradas as $entrada): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($entrada['data_entrada'])); ?></td>
                                    <td><?php echo htmlspecialchars($entrada['nota_fiscal'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($entrada['quantidade'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($entrada['valor_unitario'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($entrada['quantidade'] * $entrada['valor_unitario'], 2, ',', '.'); ?></td>
                                    <td>
                                        <a href="entrada_edit.php?id=<?php echo $entrada['id']; ?>" class="btn btn-sm btn-warning" title="Editar Entrada">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Nenhuma entrada encontrada para este material.</div>
            <?php endif; ?>
        </div>
    </div>
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
</style>

<script>
const baseUrl = '<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])); ?>';
document.addEventListener('DOMContentLoaded', function() {
    const categoriaInput = document.getElementById('categoria');
    const suggestionsDiv = document.getElementById('categoria-suggestions');
    let timeout;

    categoriaInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();

        if (query.length >= 3) {
            timeout = setTimeout(() => {
                fetch(`${baseUrl}/api/search_categorias.php?q=${encodeURIComponent(query)}`)
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
</script>

<script>
function toggleEntradas() {
    const section = document.getElementById('entradas-section');
    if (section.style.display === 'none') {
        section.style.display = 'block';
        section.scrollIntoView({ behavior: 'smooth' });
    } else {
        section.style.display = 'none';
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>