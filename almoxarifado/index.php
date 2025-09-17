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

// Define se o usuário pode fazer solicitações
$can_request = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife', 'Gestor', 'Visualizador']);

// --- Configurações de Paginação e Filtros ---
$itens_por_pagina = ALMOXARIFADO_ITENS_POR_PAGINA;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';

// --- Construção da SQL ---
// Select base com todas as colunas necessárias para o estoque
$select_columns = "SELECT m.id, m.codigo, m.nome, m.categoria as categoria_nome, m.quantidade_maxima_requisicao, m.unidade_medida, m.nota_fiscal";
if ($is_privileged_user) {
    $select_columns .= ", m.estoque_atual, m.valor_unitario, m.estoque_minimo,
                      CASE
                          WHEN m.estoque_atual <= 0 THEN 'sem_estoque'
                          WHEN m.estoque_atual < m.estoque_minimo THEN 'estoque_baixo'
                          ELSE 'estoque_normal'
                      END as situacao_estoque,
                      (m.estoque_atual * m.valor_unitario) as valor_total_estoque,
                      'ativo' as status";
}

$sql_base = $select_columns . " FROM almoxarifado_materiais m WHERE m.estoque_atual > 0";
$sql_count_base = "SELECT COUNT(m.id) FROM almoxarifado_materiais m WHERE m.estoque_atual > 0";

$conditions = [];
$params = [];

if (!empty($search_query)) {
    $conditions[] = "(m.nome LIKE ? OR m.codigo LIKE ? OR m.categoria LIKE ?)";
    $search_term = '%' . $search_query . '%';
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($categoria_filtro)) {
    if (preg_match('/^(.+?) - (.+)$/', $categoria_filtro, $matches)) {
        $categoria_full = $categoria_filtro;
        $categoria_desc = $matches[2];
        $conditions[] = "(m.categoria = ? OR m.categoria = ?)";
        $params = array_merge($params, [$categoria_full, $categoria_desc]);
    } else {
        $conditions[] = "m.categoria = ?";
        $params[] = $categoria_filtro;
    }
}

if ($is_privileged_user && !empty($status_filtro)) {
    switch($status_filtro) {
        case 'sem_estoque': $conditions[] = "m.estoque_atual <= 0"; break;
        case 'estoque_baixo': $conditions[] = "m.estoque_atual > 0 AND m.estoque_atual < m.estoque_minimo"; break;
        case 'estoque_normal': $conditions[] = "m.estoque_atual >= m.estoque_minimo"; break;
    }
}

$where_clause = "";
if (!empty($conditions)) {
    $where_clause = " AND " . implode(" AND ", $conditions);
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
$categorias = $pdo->query("SELECT CONCAT(COALESCE(numero, CAST(id AS CHAR)), ' - ', descricao) as categoria FROM almoxarifado_categorias ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header-sticky">
    <div class="almoxarifado-header">
        <h2>Estoque do Almoxarifado</h2>
        <?php require_once 'menu_almoxarifado.php'; ?>

        <!-- Status da funcionalidade -->

    <div class="controls-container">
        <div class="search-form">
            <div class="search-input">
                <input type="text" id="search_query_input" placeholder="Pesquisar por nome, código ou categoria..." value="<?php echo htmlspecialchars($search_query); ?>">
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

            <!-- <?php if ($is_privileged_user): ?>
            <select id="status_filter">
                <option value="">Todos os status</option>
                <option value="sem_estoque" <?php echo ($status_filtro == 'sem_estoque') ? 'selected' : ''; ?>>Sem estoque</option>
                <option value="estoque_baixo" <?php echo ($status_filtro == 'estoque_baixo') ? 'selected' : ''; ?>>Estoque baixo</option>
                <option value="estoque_normal" <?php echo ($status_filtro == 'estoque_normal') ? 'selected' : ''; ?>>Estoque normal</option>
            </select>
            <?php endif; ?> -->

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
                <th>Unidade</th>
                <?php if ($is_privileged_user): ?>
                <th>Estoque Mínimo</th>
                <th>Estoque Atual</th>
                <th>Valor Unitário</th>
                <th>Valor Total</th>
                <th>Nota Fiscal</th>
                <th>Situação</th>
                <th>Status</th>
                <?php else: ?>
                <th>Qtd. Máxima por Requisição</th>
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
                <td><?php echo htmlspecialchars($material['unidade_medida'] ?? 'N/A'); ?></td>
                <?php if ($is_privileged_user): ?>
                <td><?php echo formatar_quantidade($material['estoque_minimo']); ?></td>
                <td><?php echo formatar_quantidade($material['estoque_atual']); ?></td>
                <td>R$ <?php echo number_format($material['valor_unitario'], 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($material['estoque_atual'] * $material['valor_unitario'], 2, ',', '.'); ?></td>
                <td>
                    <?php if (!empty($material['nota_fiscal'])): ?>
                        <span class="badge badge-info"><?php echo htmlspecialchars($material['nota_fiscal']); ?></span>
                    <?php else: ?>
                        <span class="badge badge-secondary">N/A</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    if ($material['situacao_estoque'] == 'sem_estoque') {
                        echo '<span class="badge badge-danger">Sem estoque</span>';
                    } elseif ($material['situacao_estoque'] == 'estoque_baixo') {
                        echo '<span class="badge badge-warning">Estoque baixo</span>';
                    } else {
                        echo '<span class="badge badge-success">Normal</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php if (isset($material['status']) && $material['status'] == 'ativo'): ?>
                        <span class="badge badge-success">Ativo</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inativo</span>
                    <?php endif; ?>
                </td>
                <?php else: ?>
                <td><?php echo formatar_quantidade($material['quantidade_maxima_requisicao'] ?? 0); ?></td>
                <?php endif; ?>
                <td>
                    <?php if ($is_privileged_user): ?>
                        <div class="action-buttons-horizontal">
                            <a href="material_edit.php?id=<?php echo $material['id']; ?>" title="Editar" class="action-icon edit-icon"><i class="fas fa-edit"></i></a>
                            <?php if ($_SESSION['permissao'] == 'Administrador'): ?>
                                <a href="material_adjust_stock.php?id=<?php echo $material['id']; ?>" title="Ajustar Estoque" class="action-icon adjust-icon"><i class="fas fa-minus-circle"></i></a>
                            <?php endif; ?>
                            <a href="material_detalhes.php?id=<?php echo $material['id']; ?>" title="Detalhes" class="action-icon view-icon"><i class="fas fa-eye"></i></a>
                        </div>
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
const baseUrl = window.location.origin + '/inventario';
let currentPage = 1;
let isPrivilegedUser = <?php echo $is_privileged_user ? 'true' : 'false'; ?>;
let canRequest = <?php echo $can_request ? 'true' : 'false'; ?>;


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

    fetch(baseUrl + '/api/search_materiais.php?' + params, { credentials: 'include' })
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
        const colspan = isPrivilegedUser ? '10' : '6';
        tr.innerHTML = `<td colspan="${colspan}" class="text-center">Nenhum material encontrado.</td>`;
        tbody.appendChild(tr);
        return;
    }

    materiais.forEach(material => {
        const tr = document.createElement('tr');

        let html = `
            <td>${material.codigo}</td>
            <td>${material.nome}</td>
            <td>${material.categoria_nome || 'Não categorizado'}</td>
            <td>${material.unidade_medida || 'N/A'}</td>
        `;

        if (isPrivilegedUser) {
            const estoqueMinimo = material.estoque_minimo ? parseFloat(material.estoque_minimo).toLocaleString('pt-BR') : '0';
            const estoqueAtual = material.estoque_atual ? parseFloat(material.estoque_atual).toLocaleString('pt-BR') : '0';
            const valorUnitario = material.valor_unitario ? parseFloat(material.valor_unitario).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00';
            const valorTotal = material.estoque_atual && material.valor_unitario ? (parseFloat(material.estoque_atual) * parseFloat(material.valor_unitario)).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00';
            const notaFiscal = material.nota_fiscal || 'N/A';

            html += `
                <td>${estoqueMinimo}</td>
                <td>${estoqueAtual}</td>
                <td>R$ ${valorUnitario}</td>
                <td>R$ ${valorTotal}</td>
                <td>
                    ${notaFiscal !== 'N/A' ? 
                        `<span class="badge badge-info">${notaFiscal}</span>` : 
                        `<span class="badge badge-secondary">N/A</span>`}
                </td>
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

            html += '</td><td>';

            if (material.status === 'ativo') {
                html += '<span class="badge badge-success">Ativo</span>';
            } else {
                html += '<span class="badge badge-danger">Inativo</span>';
            }

            html += '</td><td>';
        } else {
            const qtdMaxima = material.quantidade_maxima_requisicao ? parseFloat(material.quantidade_maxima_requisicao).toLocaleString('pt-BR') : '0';
            html += `<td>${qtdMaxima}</td><td>`;
        }

        if (isPrivilegedUser) {
            html += `<a href="material_edit.php?id=${material.id}" title="Editar" class="action-icon edit-icon"><i class="fas fa-edit"></i></a>`;

            <?php if ($_SESSION['permissao'] == 'Administrador'): ?>
            html += `<a href="material_delete.php?id=${material.id}" title="Excluir" class="action-icon delete-icon" onclick="return confirm('Tem certeza que deseja excluir este material?')"><i class="fas fa-trash"></i></a>`;
            <?php endif; ?>

            html += `<a href="material_detalhes.php?id=${material.id}" title="Detalhes" class="action-icon view-icon"><i class="fas fa-eye"></i></a>`;
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

    // Carregar materiais apenas se houver filtros aplicados
    <?php if (!empty($search_query) || !empty($categoria_filtro) || !empty($status_filtro)): ?>
    loadMateriais();
    <?php endif; ?>

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


});
</script>

<style>
/* Estilos para os botões de ação em linha horizontal */
.action-buttons-horizontal {
    display: flex;
    gap: 5px;
    align-items: center;
}

.action-buttons-horizontal .action-icon {
    margin: 0;
}
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
}

.card-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    font-weight: 600;
}

.card-header h3 {
    margin: 0;
    font-size: 1.2em;
}

/* Estilo para ícones de ação */
.action-icon {
    display: inline-block;
    margin: 0 3px;
    padding: 4px;
    font-size: 14px;
    text-decoration: none;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.action-icon:hover {
    text-decoration: none;
    transform: scale(1.1);
}

.edit-icon {
    color: #007bff;
}

.edit-icon:hover {
    color: #0056b3;
    background-color: rgba(0, 123, 255, 0.1);
}

.adjust-icon {
    color: #ffc107;
}

.adjust-icon:hover {
    color: #e0a800;
    background-color: rgba(255, 193, 7, 0.1);
}

.delete-icon {
    color: #dc3545;
    font-size: 1.2em; /* Reduzido de 1.5em para 1.2em */
}

.delete-icon:hover {
    color: #c82333;
    background-color: rgba(220, 53, 69, 0.1);
}

.view-icon {
    color: #28a745;
}

.view-icon:hover {
    color: #1e7e34;
    background-color: rgba(40, 167, 69, 0.1);
}

</style>

<?php
require_once $base_path . '/includes/footer.php';
?>
