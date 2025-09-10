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
$select_columns = "SELECT m.id, m.codigo, m.nome, m.categoria as categoria_nome, m.quantidade_maxima_requisicao";
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
$categorias = $pdo->query("SELECT CONCAT(id, ' - ', descricao) as categoria FROM almoxarifado_categorias ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header-sticky">
    <div class="almoxarifado-header">
        <h2>Estoque do Almoxarifado</h2>
        <?php require_once 'menu_almoxarifado.php'; ?>
    </div>

    <div class="controls-container">
        <div class="search-form">
            <div class="search-input">
                <input type="text" id="search_query_input" placeholder="Pesquisar por nome ou código..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
        </div>
        
        <div class="filter-controls">
            <select id="categoria_filter">
                <option value="">Todas as categorias</option>
                <?php foreach($categorias as $categoria): ?>
                    <option value="<?php echo htmlspecialchars($categoria); ?>" <?php echo ($categoria_filtro == $categoria) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($is_privileged_user): ?>
            <select id="status_filter">
                <option value="">Todos os status</option>
                <option value="sem_estoque" <?php echo ($status_filtro == 'sem_estoque') ? 'selected' : ''; ?>>Sem estoque</option>
                <option value="estoque_baixo" <?php echo ($status_filtro == 'estoque_baixo') ? 'selected' : ''; ?>>Estoque baixo</option>
                <option value="estoque_normal" <?php echo ($status_filtro == 'estoque_normal') ? 'selected' : ''; ?>>Estoque normal</option>
            </select>
            <?php endif; ?>

            <button id="clear_filters" class="btn-custom" style="display: none;">Limpar filtros</button>
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
                <th>Qtd. Máx. Requisição</th>
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
                <td><?php echo htmlspecialchars($material['quantidade_maxima_requisicao'] ?? 'N/A'); ?></td>
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
                         <?php if ($_SESSION['permissao'] == 'Administrador'): ?>
                             <a href="material_delete.php?id=<?php echo $material['id']; ?>" title="Excluir" class="btn-custom btn-danger" onclick="return confirm('Tem certeza que deseja excluir este material?')"><i class="fas fa-trash"></i></a>
                         <?php endif; ?>
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
let currentPage = 1;
let isPrivilegedUser = <?php echo $is_privileged_user ? 'true' : 'false'; ?>;

function loadMateriais(page = 1) {
    const searchQuery = document.getElementById('search_query_input').value;
    const categoria = document.getElementById('categoria_filter').value;
    const status = document.getElementById('status_filter') ? document.getElementById('status_filter').value : '';

    const params = new URLSearchParams({
        q: searchQuery,
        categoria: categoria,
        status: status,
        pagina: page
    });

    fetch('/inventario/api/search_materiais.php?' + params, { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTable(data.materiais);
                updatePagination(data.paginas, data.pagina_atual);
                currentPage = data.pagina_atual;

                // Mostrar/esconder botão de limpar filtros
                const clearBtn = document.getElementById('clear_filters');
                if (searchQuery || categoria || status) {
                    clearBtn.style.display = 'inline-block';
                } else {
                    clearBtn.style.display = 'none';
                }
            } else {
                console.error('Erro na busca:', data.error);
                // Não limpar a tabela, manter os dados carregados pelo PHP
            }
        })
        .catch(error => {
            console.error('Erro de rede:', error);
            // Não limpar a tabela, manter os dados carregados pelo PHP
        });
}

function updateTable(materiais) {
    const tbody = document.getElementById('itens-table-body');
    tbody.innerHTML = '';

    if (materiais.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="8" class="text-center">Nenhum material encontrado.</td>';
        tbody.appendChild(tr);
        return;
    }

    materiais.forEach(material => {
        const tr = document.createElement('tr');

        let html = `
            <td>${material.codigo}</td>
            <td>${material.nome}</td>
            <td>${material.categoria_nome || 'Não categorizado'}</td>
            <td>${material.quantidade_maxima_requisicao || 'N/A'}</td>
        `;

        if (isPrivilegedUser) {
            html += `
                <td>${material.estoque_atual}</td>
                <td>R$ ${parseFloat(material.valor_unitario).toFixed(2).replace('.', ',')}</td>
                <td>
            `;

            switch(material.situacao_estoque) {
                case 'sem_estoque':
                    html += '<span class="badge badge-danger">Sem estoque</span>';
                    break;
                case 'estoque_baixo':
                    html += '<span class="badge badge-warning">Estoque baixo</span>';
                    break;
                case 'estoque_normal':
                    html += '<span class="badge badge-success">Normal</span>';
                    break;
            }

            html += '</td>';
        }

        html += '<td>';

        if (isPrivilegedUser) {
            html += `<a href="material_edit.php?id=${material.id}" title="Editar" class="btn-custom"><i class="fas fa-edit"></i></a>`;

            <?php if ($_SESSION['permissao'] == 'Administrador'): ?>
            html += `<a href="material_delete.php?id=${material.id}" title="Excluir" class="btn-custom btn-danger" onclick="return confirm('Tem certeza que deseja excluir este material?')"><i class="fas fa-trash"></i></a>`;
            <?php endif; ?>
        } else {
            html += `<button type="button" class="btn-custom btn-solicitar" data-item-id="${material.id}">Solicitar</button>`;
        }

        html += '</td>';

        tr.innerHTML = html;
        tbody.appendChild(tr);
    });
}

function updatePagination(totalPages, currentPage) {
    // Implementar paginação se necessário
    // Por enquanto, manter simples
}

document.addEventListener('DOMContentLoaded', function() {
    // Carregar materiais iniciais
    loadMateriais();

    // Event listeners para busca
    let searchTimeout;
    document.getElementById('search_query_input').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadMateriais(1);
        }, 300);
    });

    // Event listeners para filtros
    document.getElementById('categoria_filter').addEventListener('change', function() {
        currentPage = 1;
        loadMateriais(1);
    });

    const statusFilter = document.getElementById('status_filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            currentPage = 1;
            loadMateriais(1);
        });
    }

    // Botão limpar filtros
    document.getElementById('clear_filters').addEventListener('click', function() {
        document.getElementById('search_query_input').value = '';
        document.getElementById('categoria_filter').value = '';
        if (statusFilter) {
            statusFilter.value = '';
        }
        currentPage = 1;
        loadMateriais(1);
    });

    // Event listener para botões de solicitar
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-solicitar')) {
            const itemId = e.target.getAttribute('data-item-id');
            window.location.href = `requisicao.php?item_id=${itemId}`;
        }
    });
});
</script>

<?php
require_once $base_path . '/includes/footer.php';
?>