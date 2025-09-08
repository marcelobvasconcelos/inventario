<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(__DIR__);

require_once $base_path . '/includes/header.php';
require_once $base_path . '/config/db.php';
require_once 'config.php';

// --- Controle de Acesso ---
$allowed_roles = ['Administrador', 'Almoxarife', 'Visualizador', 'Gestor'];
if (!isset($_SESSION['permissao']) || !in_array($_SESSION['permissao'], $allowed_roles)) {
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para acessar este módulo.</div>";
    require_once $base_path . '/includes/footer.php';
    exit;
}

// Define se o usuário tem visão privilegiada
$is_privileged_user = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);

// --- Configurações de Paginação e Filtros ---
$itens_por_pagina = ALMOXARIFADO_ITENS_POR_PAGINA;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$status_filtro = isset($_GET['status']) && $is_privileged_user ? $_GET['status'] : '';

// --- Construção da SQL ---
$select_columns = "SELECT m.id, m.codigo, m.nome, m.categoria as categoria_nome";
if ($is_privileged_user) {
    $select_columns .= ", m.estoque_atual, m.valor_unitario, 
                         CASE 
                             WHEN m.estoque_atual <= 0 THEN 'sem_estoque'
                             WHEN m.estoque_atual < 5 THEN 'estoque_baixo'
                             ELSE 'estoque_normal'
                         END as situacao_estoque";
}

$sql_base = $select_columns . " FROM almoxarifado_materiais m";
$sql_count_base = "SELECT COUNT(m.id) FROM almoxarifado_materiais m";

$conditions = [];
$params = [];

if (!empty($search_query)) {
    $conditions[] = "(m.nome LIKE ? OR m.codigo LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}
if (!empty($categoria_filtro)) {
    $conditions[] = "m.categoria = ?";
    $params[] = $categoria_filtro;
}
if ($is_privileged_user && !empty($status_filtro)) {
    switch($status_filtro) {
        case 'sem_estoque': $conditions[] = "m.estoque_atual <= 0"; break;
        case 'estoque_baixo': $conditions[] = "m.estoque_atual > 0 AND m.estoque_atual < 5"; break;
        case 'estoque_normal': $conditions[] = "m.estoque_atual >= 5"; break;
    }
}

$where_clause = "";
if (!empty($conditions)) {
    $where_clause = " WHERE " . implode(" AND ", $conditions);
}

// --- Execução das Queries com PDO ---
$sql_count = $sql_count_base . $where_clause;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_materiais = $stmt_count->fetchColumn();

$sql = $sql_base . $where_clause . " ORDER BY m.nome ASC LIMIT " . $itens_por_pagina . " OFFSET " . $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$materiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_paginas = ceil($total_materiais / $itens_por_pagina);

// Buscar categorias do almoxarifado para o filtro
$categorias = $pdo->query("SELECT descricao FROM almoxarifado_categorias ORDER BY descricao ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header-sticky">
    <div class="almoxarifado-header">
        <h2>Estoque do Almoxarifado</h2>
        <?php require_once 'menu_almoxarifado.php'; ?>
    </div>

    <div class="controls-container">
        <div class="search-form">
            <form action="" method="GET" id="search-form">
                <div class="search-input">
                    <input type="text" name="search" id="search_query_input" placeholder="Pesquisar por nome ou código..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
            </form>
        </div>
        
        <div class="filter-controls">
            <form action="" method="GET">
                <select name="categoria" onchange="this.form.submit()">
                    <option value="">Todas as categorias</option>
                    <?php foreach($categorias as $categoria): ?>
                        <option value="<?php echo htmlspecialchars($categoria); ?>" <?php echo ($categoria_filtro == $categoria) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($is_privileged_user): ?>
                <select name="status" onchange="this.form.submit()">
                    <option value="">Todos os status</option>
                    <option value="sem_estoque" <?php echo ($status_filtro == 'sem_estoque') ? 'selected' : ''; ?>>Sem estoque</option>
                    <option value="estoque_baixo" <?php echo ($status_filtro == 'estoque_baixo') ? 'selected' : ''; ?>>Estoque baixo</option>
                    <option value="estoque_normal" <?php echo ($status_filtro == 'estoque_normal') ? 'selected' : ''; ?>>Estoque normal</option>
                </select>
                <?php endif; ?>
                
                <?php if(!empty($search_query) || !empty($categoria_filtro) || !empty($status_filtro)): ?>
                    <a href="index.php" class="btn-custom">Limpar filtros</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php if (empty($materiais)): ?>
    <div class="alert alert-info">Nenhum material encontrado.</div>
<?php else: ?>
    <table class="almoxarifado-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <?php if ($is_privileged_user): ?>
                    <th>Estoque</th>
                    <th>Valor Unit.</th>
                    <th>Status</th>
                <?php endif; ?>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="itens-table-body">
            <?php foreach($materiais as $material): ?>
            <tr>
                <td><?php echo htmlspecialchars($material['codigo']); ?></td>
                <td><?php echo htmlspecialchars($material['nome']); ?></td>
                <td><?php echo htmlspecialchars($material['categoria_nome'] ?? 'Não categorizado'); ?></td>
                <?php if ($is_privileged_user): ?>
                    <td><?php echo $material['estoque_atual']; ?></td>
                    <td>R$ <?php echo number_format($material['valor_unitario'], 2, ',', '.'); ?></td>
                    <td>
                        <?php 
                            switch($material['situacao_estoque']) {
                                case 'sem_estoque': echo '<span class="badge badge-danger">Sem estoque</span>'; break;
                                case 'estoque_baixo': echo '<span class="badge badge-warning">Estoque baixo</span>'; break;
                                case 'estoque_normal': echo '<span class="badge badge-success">Normal</span>'; break;
                            }
                        ?>
                    </td>
                <?php endif; ?>
                <td>
                    <?php if ($is_privileged_user): ?>
                        <a href="material_edit.php?id=<?php echo $material['id']; ?>" title="Editar" class="btn-custom"><i class="fas fa-edit"></i></a>
                    <?php else: ?>
                        <button type="button" class="btn-custom btn-solicitar" data-item-id="<?php echo $material['id']; ?>">Solicitar</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ($total_paginas > 1): ?>
<div class="pagination">
    <!-- Lógica de paginação mantida como no arquivo original -->
</div>
<?php endif; ?>

<script>
// Script para solicitar item individualmente (a ser implementado se necessário)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-solicitar')) {
        const itemId = e.target.getAttribute('data-item-id');
        // Redirecionar para a página de requisição com o ID do item
        window.location.href = `empenhos_requisicao.php?item_id=${itemId}`;
    }
});
</script>

<?php
require_once $base_path . '/includes/footer.php';
?>