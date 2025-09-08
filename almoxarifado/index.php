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
$select_columns = "SELECT m.id, m.nome, c.descricao as categoria_nome";
if ($is_privileged_user) {
    $select_columns .= ", m.qtd, m.valor_unit, 
                         CASE 
                             WHEN m.qtd <= 0 THEN 'sem_estoque'
                             WHEN m.qtd < 5 THEN 'estoque_baixo'
                             ELSE 'estoque_normal'
                         END as situacao_estoque";
}

$sql_base = $select_columns . " FROM materiais m LEFT JOIN categorias c ON m.categoria_id = c.id";
$sql_count_base = "SELECT COUNT(m.id) FROM materiais m LEFT JOIN categorias c ON m.categoria_id = c.id";

$conditions = [];
$params = [];

if (!empty($search_query)) {
    $conditions[] = "(m.nome LIKE ?)";
    $params[] = '%' . $search_query . '%';
}
if (!empty($categoria_filtro)) {
    $conditions[] = "m.categoria_id = ?";
    $params[] = $categoria_filtro;
}
if ($is_privileged_user && !empty($status_filtro)) {
    switch($status_filtro) {
        case 'sem_estoque': $conditions[] = "m.qtd <= 0"; break;
        case 'estoque_baixo': $conditions[] = "m.qtd > 0 AND m.qtd < 5"; break;
        case 'estoque_normal': $conditions[] = "m.qtd >= 5"; break;
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

// Buscar categorias para o filtro
$categorias = $pdo->query("SELECT id, descricao FROM categorias ORDER BY descricao ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header-sticky">
    <div class="almoxarifado-header">
        <h2>Estoque do Almoxarifado</h2>
        <a href="requisicao.php" id="btn-nova-requisicao" class="btn-custom"><i class="fas fa-plus"></i> Nova Requisição</a>
        <a href="notificacoes.php" class="btn-custom"><i class="fas fa-bell"></i> Minhas Notificações</a>
        <?php if ($is_privileged_user): ?>
            <a href="empenhos/material_add.php" class="btn-custom">Adicionar Material</a>
        <?php endif; ?>
        <?php if ($_SESSION["permissao"] == 'Administrador'): ?>
            <a href="admin_notificacoes.php" class="btn-custom">Gerenciar Requisições</a>
            <a href="empenhos/index.php" class="btn-custom">Gerenciar Empenhos</a>
        <?php endif; ?>
    </div>

    <div class="controls-container">
        <div class="search-form">
            <form action="" method="GET" id="search-form">
                <div class="search-input">
                    <input type="text" name="search" id="search_query_input" placeholder="Pesquisar materiais..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <!-- Botão de pesquisa removido para pesquisa automática -->
                </div>
            </form>
        </div>
        
        <div class="filter-controls">
            <form action="" method="GET">
                <select name="categoria" onchange="this.form.submit()">
                    <option value="">Todas as categorias</option>
                    <?php foreach($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" <?php echo ($categoria_filtro == $categoria['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['descricao']); ?>
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
    
    <!-- Botão para requisição de itens selecionados -->
    <div id="bulk-action-buttons" style="display: none; margin-top: 10px;">
        <button type="button" id="btn-requisitar-selecionados" class="btn-custom">
            <i class="fas fa-paper-plane"></i> Requisitar Itens Selecionados
        </button>
    </div>
</div>

<form id="itens-form" method="POST" action="empenhos/requisicao.php">
    <?php if (empty($materiais)): ?>
        <div class="alert alert-info">Nenhum material encontrado.</div>
    <?php else: ?>
        <table class="almoxarifado-table">
            <thead>
                <tr>
                    <th style="width: 5%;"><input type="checkbox" id="select-all-checkbox"></th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <?php if ($is_privileged_user): ?>
                        <th>Quantidade</th>
                        <th>Valor Unitário</th>
                        <th>Status</th>
                    <?php endif; ?>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="itens-table-body">
                <?php foreach($materiais as $material): ?>
                <tr>
                    <td><input type="checkbox" name="item_ids[]" value="<?php echo $material['id']; ?>" class="item-checkbox"></td>
                    <td><?php echo htmlspecialchars($material['nome']); ?></td>
                    <td><?php echo htmlspecialchars($material['categoria_nome'] ?? 'Não categorizado'); ?></td>
                    <?php if ($is_privileged_user): ?>
                        <td><?php echo $material['qtd']; ?></td>
                        <td>R$ <?php echo number_format($material['valor_unit'], 2, ',', '.'); ?></td>
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
                            <a href="empenhos/material_add.php?id=<?php echo $material['id']; ?>" title="Editar" class="btn-custom"><i class="fas fa-edit"></i></a>
                        <?php else: ?>
                            <button type="button" class="btn-custom btn-solicitar" data-item-id="<?php echo $material['id']; ?>">Solicitar</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</form>

<?php if ($total_paginas > 1): ?>
<div class="pagination">
    <!-- Lógica de paginação mantida como no arquivo original -->
</div>
<?php endif; ?>

<script>
// --- Lógica para Pesquisa Dinâmica (AJAX) ---
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded and parsed');
    
    const searchInput = document.getElementById('search_query_input');
    const tableBody = document.getElementById('itens-table-body');
    const pagination = document.querySelector('.pagination');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const bulkActionButtons = document.getElementById('bulk-action-buttons');
    const btnRequisitarSelecionados = document.getElementById('btn-requisitar-selecionados');
    const btnNovaRequisicao = document.getElementById('btn-nova-requisicao');
    
    // Função para atualizar visibilidade dos botões de ação em massa
    function updateBulkActionButtons() {
        const checkedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkedCheckboxes.length > 0) {
            bulkActionButtons.style.display = 'block';
        } else {
            bulkActionButtons.style.display = 'none';
        }
    }
    
    // Evento para selecionar todos os checkboxes
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButtons();
        });
    }
    
    // Evento para atualizar botões quando um checkbox é marcado/desmarcado
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-checkbox')) {
            updateBulkActionButtons();
        }
    });
    
    // Evento para o botão de requisição de itens selecionados
    if (btnRequisitarSelecionados) {
        btnRequisitarSelecionados.addEventListener('click', function() {
            const checkedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
            if (checkedCheckboxes.length > 0) {
                const itemIds = Array.from(checkedCheckboxes).map(cb => cb.value);
                
                // Criar um formulário temporário para enviar os IDs
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'empenhos/requisicao.php';
                
                // Adicionar os IDs como campos hidden
                itemIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'item_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                // Adicionar um campo para indicar que é uma requisição de itens selecionados
                const bulkRequest = document.createElement('input');
                bulkRequest.type = 'hidden';
                bulkRequest.name = 'bulk_request';
                bulkRequest.value = '1';
                form.appendChild(bulkRequest);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    
    // Eventos para os botões de solicitação individuais
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-solicitar')) {
            const itemId = e.target.getAttribute('data-item-id');
            
            // Criar um formulário temporário para enviar o ID do item
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'empenhos/requisicao.php';
            
            // Adicionar o ID como campo hidden
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'item_ids[]';
            input.value = itemId;
            form.appendChild(input);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    console.log('Search input element:', searchInput);
    
    if (searchInput) {
        console.log('Search input element found in JavaScript');
        
        searchInput.addEventListener('input', function() {
            console.log('Input event triggered');
            const searchTerm = this.value.trim();
            console.log('Search term:', searchTerm);
            
            if (searchTerm.length >= 2) {
                console.log('Search term length >= 2, making API call');
                // Caminho absoluto
                const apiUrl = '/inventario/api/almoxarifado_search_materiais.php?term=' + encodeURIComponent(searchTerm);
                console.log('Fetching data from:', apiUrl);
                
                fetch(apiUrl)
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data);
                        tableBody.innerHTML = '';
                        if (pagination) pagination.style.display = 'none';

                        if (data.length > 0) {
                            data.forEach(item => {
                                const isPrivileged = <?php echo json_encode($is_privileged_user); ?>;
                                let rowHtml = `<tr>
                                    <td><input type="checkbox" name="item_ids[]" value="${item.id}" class="item-checkbox"></td>
                                    <td>${item.nome}</td>
                                    <td>${item.categoria_nome || 'Não categorizado'}</td>`;
                                
                                if (isPrivileged) {
                                    rowHtml += `<td>${item.qtd}</td>
                                                <td>R$ ${item.valor_unit}</td>
                                                <td>${item.status_badge}</td>`;
                                }

                                rowHtml += `<td>`;
                                if (isPrivileged) {
                                    rowHtml += `<a href="empenhos/material_add.php?id=${item.id}" title="Editar" class="btn-custom"><i class="fas fa-edit"></i></a>`;
                                } else {
                                    rowHtml += `<button type="button" class="btn-custom btn-solicitar" data-item-id="${item.id}">Solicitar</button>`;
                                }
                                rowHtml += `</td></tr>`;
                                tableBody.innerHTML += rowHtml;
                            });
                        } else {
                            tableBody.innerHTML = `<tr><td colspan="100%">Nenhum material encontrado.</td></tr>`;
                        }
                        
                        // Atualizar visibilidade dos botões de ação em massa
                        updateBulkActionButtons();
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                    });
            } else if (searchTerm.length === 0) {
                console.log('Search term length === 0, reloading page');
                // Recarrega a página para restaurar a lista original com paginação
                window.location.href = window.location.pathname;
            } else {
                console.log('Search term length < 2, not enough characters');
            }
        });
    } else {
        console.log('Search input element NOT found in JavaScript');
    }
});
</script>

<?php
require_once $base_path . '/includes/footer.php';
?>
