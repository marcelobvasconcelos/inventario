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
    $select_columns .= ", m.estoque_atual, 
                      COALESCE((
                          SELECT e.valor_unitario 
                          FROM almoxarifado_entradas e 
                          WHERE e.material_id = m.id AND e.valor_unitario > 0 
                          ORDER BY e.data_cadastro DESC 
                          LIMIT 1
                      ), 0) as valor_unitario,
                      m.estoque_minimo,
                      CASE
                          WHEN m.estoque_atual <= 0 THEN 'sem_estoque'
                          WHEN m.estoque_atual < m.estoque_minimo THEN 'estoque_baixo'
                          ELSE 'estoque_normal'
                      END as situacao_estoque,
                      (m.estoque_atual * COALESCE((
                          SELECT e.valor_unitario 
                          FROM almoxarifado_entradas e 
                          WHERE e.material_id = m.id AND e.valor_unitario > 0 
                          ORDER BY e.data_cadastro DESC 
                          LIMIT 1
                      ), 0)) as valor_total_estoque,
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

<?php if ($can_request): ?>
<div id="requisicao-actions" style="display: none; margin: 20px 0;">
    <button type="button" id="btn-requisicao" class="btn btn-primary">
        <i class="fas fa-shopping-cart"></i> Fazer Requisição dos Itens Selecionados
    </button>
    <span id="itens-selecionados-count" class="ml-2"></span>
</div>
<?php endif; ?>

<?php if (empty($materiais)): ?>
    <div class="alert alert-info">Nenhum material encontrado.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="almoxarifado-table estoque-table">
            <thead>
                <tr>
                    <?php if ($can_request): ?>
                    <th class="col-checkbox"><input type="checkbox" id="select-all"></th>
                    <?php endif; ?>
                    <th class="col-nome">Nome</th>
                    <th class="col-unidade">Unidade</th>
                    <?php if ($is_privileged_user): ?>
                    <th class="col-numero text-right">Estoque Mínimo</th>
                    <th class="col-numero text-right">Estoque Atual</th>
                    <th class="col-numero text-right">Valor Unitário</th>
                    <th class="col-numero text-right">Valor Total</th>
                    <th class="col-nota">Nota Fiscal</th>
                    <th class="col-situacao text-center">Situação</th>
                    <?php else: ?>
                    <th class="col-numero text-right">Qtd. Máxima por Requisição</th>
                    <?php endif; ?>
                    <th class="col-acoes text-center">Ações</th>
                </tr>
            </thead>
        <tbody id="itens-table-body">
            <?php foreach($materiais as $material): ?>
            <tr>
                <?php if ($can_request): ?>
                <td class="col-checkbox"><input type="checkbox" class="item-checkbox" data-id="<?php echo $material['id']; ?>" data-nome="<?php echo htmlspecialchars($material['nome']); ?>"></td>
                <?php endif; ?>
                <td class="col-nome" title="<?php echo htmlspecialchars($material['codigo'] . ' - ' . ($material['categoria_nome'] ?? 'Não categorizado')); ?>"><?php echo htmlspecialchars($material['nome']); ?></td>
                <td class="col-unidade"><?php echo htmlspecialchars($material['unidade_medida'] ?? 'N/A'); ?></td>
                <?php if ($is_privileged_user): ?>
                <td class="col-numero text-right"><?php echo formatar_quantidade($material['estoque_minimo']); ?></td>
                <td class="col-numero text-right"><?php echo formatar_quantidade($material['estoque_atual']); ?></td>
                <td class="col-numero text-right">R$ <?php echo number_format($material['valor_unitario'], 2, ',', '.'); ?></td>
                <td class="col-numero text-right">R$ <?php echo number_format($material['estoque_atual'] * $material['valor_unitario'], 2, ',', '.'); ?></td>
                <td class="col-nota">
                    <?php if (!empty($material['nota_fiscal'])): ?>
                        <a href="nota_fiscal_detalhes.php?nota=<?php echo urlencode($material['nota_fiscal']); ?>" class="badge badge-info" title="Ver detalhes da nota fiscal">
                            <?php echo htmlspecialchars($material['nota_fiscal']); ?>
                        </a>
                    <?php else: ?>
                        <span class="badge badge-secondary">N/A</span>
                    <?php endif; ?>
                </td>
                <td class="col-situacao text-center">
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
                <?php else: ?>
                <td class="col-numero text-right"><?php echo formatar_quantidade($material['quantidade_maxima_requisicao'] ?? 0); ?></td>
                <?php endif; ?>
                <td class="col-acoes text-center">
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
    </div>
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
        let colspan = isPrivilegedUser ? '10' : '6';
        if (canRequest) colspan++; // Adicionar 1 para a coluna do checkbox
        tr.innerHTML = `<td colspan="${colspan}" class="text-center">Nenhum material encontrado.</td>`;
        tbody.appendChild(tr);
        return;
    }

    materiais.forEach(material => {
        const tr = document.createElement('tr');

        let html = '';
        if (canRequest) {
            html += `<td><input type="checkbox" class="item-checkbox" data-id="${material.id}" data-nome="${material.nome}"></td>`;
        }
        html += `
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
                        `<a href="nota_fiscal_detalhes.php?nota=${encodeURIComponent(notaFiscal)}" class="badge badge-info" title="Ver detalhes da nota fiscal">${notaFiscal}</a>` : 
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
    
    // Gerenciar checkboxes
    <?php if ($can_request): ?>
    const selectAll = document.getElementById('select-all');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const requisicaoActions = document.getElementById('requisicao-actions');
    const btnRequisicao = document.getElementById('btn-requisicao');
    const countSpan = document.getElementById('itens-selecionados-count');
    
    function updateRequisicaoButton() {
        const selected = document.querySelectorAll('.item-checkbox:checked');
        if (selected.length > 0) {
            requisicaoActions.style.display = 'block';
            countSpan.textContent = `(${selected.length} item${selected.length > 1 ? 'ns' : ''} selecionado${selected.length > 1 ? 's' : ''})`;
        } else {
            requisicaoActions.style.display = 'none';
        }
    }
    
    // Selecionar/deselecionar todos
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateRequisicaoButton();
        });
    }
    
    // Gerenciar seleção individual
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRequisicaoButton();
            
            // Atualizar checkbox "selecionar todos"
            if (selectAll) {
                const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                const noneChecked = Array.from(itemCheckboxes).every(cb => !cb.checked);
                selectAll.checked = allChecked;
                selectAll.indeterminate = !allChecked && !noneChecked;
            }
        });
    });
    
    // Botão de requisição
    if (btnRequisicao) {
        btnRequisicao.addEventListener('click', function() {
            const selected = document.querySelectorAll('.item-checkbox:checked');
            const itens = [];
            
            selected.forEach(checkbox => {
                itens.push({
                    id: checkbox.dataset.id,
                    nome: checkbox.dataset.nome
                });
            });
            
            // Redirecionar para página de requisição com parâmetros
            const params = new URLSearchParams();
            itens.forEach((item, index) => {
                params.append(`material_id[${index}]`, item.id);
                params.append(`material_nome[${index}]`, item.nome);
            });
            
            window.location.href = 'requisicao.php?' + params.toString();
        });
    }
    <?php endif; ?>

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

/* Estilo para link da nota fiscal */
.badge.badge-info {
    text-decoration: none;
    transition: all 0.2s ease;
}

.badge.badge-info:hover {
    text-decoration: none;
    background-color: #138496;
    transform: scale(1.05);
}

/* Estilos específicos para tabela de estoque */
.estoque-table {
    table-layout: auto;
    width: 100%;
    min-width: 800px;
}

.estoque-table td,
.estoque-table th {
    padding: 12px 8px;
    vertical-align: middle;
    line-height: 1.4;
}

/* Larguras das colunas */
.col-checkbox { width: 35px; }
.col-nome { width: 35%; min-width: 200px; }
.col-unidade { width: 70px; }
.col-numero { width: 100px; }
.col-nota { width: 100px; }
.col-situacao { width: 100px; }
.col-acoes { width: 110px; }

/* Quebra de linha para nome */
.col-nome {
    word-wrap: break-word;
    white-space: normal;
    overflow-wrap: break-word;
    hyphens: auto;
}

/* Alinhamentos */
.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

/* Ações centralizadas */
.action-buttons-horizontal {
    display: flex;
    justify-content: center;
    gap: 5px;
    align-items: center;
}

/* Responsividade */
@media (max-width: 1200px) {
    .col-nome { width: 30%; min-width: 180px; }
    .col-numero { width: 90px; }
}

@media (max-width: 992px) {
    .table-responsive {
        overflow-x: auto;
    }
    
    .col-nome { width: 25%; min-width: 150px; }
    .col-unidade { width: 60px; }
    .col-numero { width: 80px; }
    .col-nota { width: 90px; }
    .col-situacao { width: 90px; }
    .col-acoes { width: 100px; }
}

@media (max-width: 768px) {
    .estoque-table td,
    .estoque-table th {
        padding: 8px 4px;
        font-size: 0.9em;
    }
    
    .col-nome {
        min-width: 140px;
        max-width: 200px;
    }
}

</style>

<?php
require_once $base_path . '/includes/footer.php';
?>
