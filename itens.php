<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// --- BUSCAR DADOS PARA OS MENUS SUSPENSOS ---
$todos_locais = [];
$todos_usuarios = [];
if ($_SESSION['permissao'] == 'Administrador') {
    // Buscar todos os locais
    $stmt_locais = $pdo->query("SELECT id, nome FROM locais ORDER BY nome");
    $todos_locais = $stmt_locais->fetchAll(PDO::FETCH_ASSOC);

    // Buscar todos os usuários elegíveis (admin e usuario)
    $stmt_usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
    $todos_usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar o cabeçalho padrão do PDF para o administrador
$cabecalho_padrao_pdf = '';
if ($_SESSION['permissao'] == 'Administrador') {
    $stmt_cabecalho = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'cabecalho_padrao_pdf'");
    $cabecalho_padrao_pdf = $stmt_cabecalho->fetchColumn();
}

// (O resto do seu código PHP de paginação e busca continua aqui...)
// Configurações de paginação
$itens_por_pagina = 60;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Variáveis de pesquisa
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : 'todos';
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// SQL base para contagem total de itens
$sql_count_base = "SELECT COUNT(*) FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id WHERE i.estado != 'Excluido'";
$sql_base = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.id as local_id, l.nome AS local, u.nome AS responsavel, i.estado, i.responsavel_id, i.status_confirmacao FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id WHERE i.estado != 'Excluido'";

$where_clause = "";
$params = [];
$param_types = "";

// Se for admin, mostra tudo. Se for usuário, mostra apenas os seus itens.
if ($_SESSION['permissao'] != 'Administrador') {
    $where_clause = " AND i.responsavel_id = ? AND i.estado != 'Excluido'";
    $params[] = $_SESSION['id'];
    $param_types = "i";
} else { // Lógica de pesquisa para administradores
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        switch ($search_by) {
            case 'id':
                $where_clause .= " AND i.id LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'patrimonio_novo':
                $where_clause .= " AND i.patrimonio_novo LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'patrimonio_secundario':
                $where_clause .= " AND i.patrimonio_secundario LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'local':
                $where_clause .= " AND l.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'responsavel':
                $where_clause .= " AND u.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'todos':
                $where_clause .= " AND (i.nome LIKE ? OR i.patrimonio_novo LIKE ? OR i.patrimonio_secundario LIKE ? OR l.nome LIKE ? OR u.nome LIKE ?)";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $param_types .= "sssss";
                break;
            default:
                $where_clause .= " AND i.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
        }
    }
    // Sempre excluir itens marcados como 'Excluido'
    $where_clause .= " AND i.estado != 'Excluido'";
}

// Consulta para contagem total
$sql_count = $sql_count_base . $where_clause;
if($stmt_count = mysqli_prepare($link, $sql_count)){
    if (!empty($params)) {
        $refs = [];
        foreach($params as $key => $value)
            $refs[$key] = &$params[$key];
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_count, $param_types], $refs));
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_itens = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
} else {
    $total_itens = 0; // Em caso de erro na contagem
}

$total_paginas = ceil($total_itens / $itens_por_pagina);

// Consulta para os itens da página atual
$sql = $sql_base . $where_clause . " ORDER BY i.id DESC LIMIT ? OFFSET ?";

if($stmt = mysqli_prepare($link, $sql)){
    $bind_params = [];
    $bind_types = $param_types . "ii";
    if (!empty($params)) {
        $bind_params = array_merge($params, [$itens_por_pagina, $offset]);
    } else {
        $bind_params = [$itens_por_pagina, $offset];
    }
    $refs = [];
    foreach($bind_params as $key => $value)
        $refs[$key] = &$bind_params[$key];
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $bind_types], $refs));
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false; // Em caso de erro na consulta principal
}

?>

<h2>Itens do Inventário</h2>
<div class="controls-container">
    <div class="main-actions">
        <?php if($_SESSION['permissao'] == 'Administrador' || $_SESSION['permissao'] == 'Gestor'): ?>
            <a href="item_add.php" class="btn-custom">Adicionar Novo Item</a>
        <?php endif; ?>
        <?php if($_SESSION['permissao'] == 'Administrador'): ?>
            <a href="itens_excluidos.php" class="btn-custom btn-icon" title="Ver Itens Excluídos">
                <i class="fas fa-trash-alt"></i>
            </a>
        <?php endif; ?>
    </div>

    <?php if($_SESSION['permissao'] == 'Administrador'): ?>
        <div class="bulk-actions" id="bulkActions" style="display: none;">
            <button id="movimentarBtn" class="btn-custom"><i class="fas fa-exchange-alt"></i> Movimentar Selecionados</button>
            <button id="excluirBtn" class="btn-custom btn-danger"><i class="fas fa-trash"></i> Excluir Selecionados</button>
        </div>
    <?php endif; ?>

    <?php if($_SESSION['permissao'] == 'Administrador'): ?>
    <div class="search-form">
        <form action="" method="GET">
            <div class="search-input">
                <input type="text" name="search_query" placeholder="Pesquisar em qualquer campo..." value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
                <!-- Campo oculto para definir a pesquisa como 'todos' por padrão -->
                <input type="hidden" name="search_by" value="todos">
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php if($_SESSION['permissao'] == 'Administrador'): ?>
<div class="pdf-form-container card mt-4" id="relatorioSection" style="display: none;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Gerar Relatório</h5>
            <button type="button" class="btn btn-sm btn-secondary" id="ocultarRelatorioBtn">
                <i class="fas fa-eye-slash"></i> Ocultar
            </button>
        </div>
        <form action="gerar_pdf_itens.php" method="post" target="_blank">
            <div class="form-group">
                <label for="cabecalho_pdf">Cabeçalho do Relatório (opcional):</label>
                <textarea name="cabecalho_pdf" id="cabecalho_pdf" class="form-control" rows="3"><?php echo htmlspecialchars($cabecalho_padrao_pdf); ?></textarea>
            </div>
            
            <!-- Campos ocultos para passar os filtros de pesquisa -->
            <input type="hidden" name="search_by" value="<?php echo htmlspecialchars($search_by); ?>">
            <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
            
            <button type="submit" class="btn btn-primary mt-2">Gerar PDF</button>
        </form>
    </div>
</div>

<div class="csv-actions" style="justify-content: flex-end;">
    <button type="button" class="btn btn-primary btn-icon" id="mostrarRelatorioBtn" title="Gerar Relatório">
        <i class="fas fa-file-pdf"></i>
    </button>
    
    <!-- Formulário para exportar CSV -->
    <form action="exportar_itens_csv.php" method="post" target="_blank" class="d-inline">
        <!-- Campos ocultos para passar os filtros de pesquisa -->
        <input type="hidden" name="search_by" value="<?php echo htmlspecialchars($search_by); ?>">
        <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
        
        <button type="submit" class="btn btn-success btn-icon" title="Exportar para CSV">
            <i class="fas fa-file-export"></i>
        </button>
    </form>
    
    <!-- Botão para importar itens -->
    <a href="importar_novos_itens_csv.php" class="btn btn-warning btn-icon" title="Importar Itens via CSV">
        <i class="fas fa-download"></i>
    </a>
</div>
<?php endif; ?>

<table class="table-striped table-hover">
    <thead>
        <tr>
            <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                <th><input type="checkbox" id="selectAll"></th>
            <?php endif; ?>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="nome">Nome <span class="sort-arrow"></span></th>
            <th data-column="patrimonio_novo">Patrimônio <span class="sort-arrow"></span></th>
            <th data-column="patrimonio_secundario">Patri_Sec <span class="sort-arrow"></span></th>
            <th data-column="local">Local <span class="sort-arrow"></span></th>
            <th data-column="responsavel">Responsável <span class="sort-arrow"></span></th>
            <th data-column="estado">Estado <span class="sort-arrow"></span></th>
            <th>Status Confirmação</th>
            <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                <th>Ações</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                    <td><input type="checkbox" class="item-checkbox" data-item-id="<?php echo $row['id']; ?>" data-item-name="<?php echo htmlspecialchars($row['nome']); ?>" data-item-patrimonio="<?php echo htmlspecialchars($row['patrimonio_novo']); ?>"></td>
                <?php endif; ?>
                <td><?php echo $row['id']; ?></td>
                <td><a href="item_details.php?id=<?php echo $row['id']; ?>"><?php echo $row['nome']; ?></a></td>
                <td><?php echo $row['patrimonio_novo']; ?></td>
                <td><?php echo $row['patrimonio_secundario']; ?></td>
                <td><a href="local_itens.php?id=<?php echo $row['local_id']; ?>"><?php echo $row['local']; ?></a></td>
                <td><?php echo $row['responsavel']; ?></td>
                <td><?php echo $row['estado']; ?></td>
                <td>
                    <?php
                        $status_confirmacao = $row['status_confirmacao'];
                        $badge_class = '';
                        if ($status_confirmacao == 'Pendente') {
                            $badge_class = 'badge-warning';
                        } elseif ($status_confirmacao == 'Confirmado') {
                            $badge_class = 'badge-success';
                        } elseif ($status_confirmacao == 'Nao Confirmado') {
                            $badge_class = 'badge-danger';
                        }
                    ?>
                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status_confirmacao); ?></span>
                </td>
                <td>
                    <?php if($_SESSION['permissao'] == 'Administrador' || ($_SESSION['permissao'] == 'Gestor' && $row['responsavel_id'] == $_SESSION['id'])): ?>
                        <a href="item_edit.php?id=<?php echo $row['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                        <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                            <a href="item_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este item?');"><i class="fas fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="10">Nenhum item encontrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination"><!-- ...código de paginação... --></div>

<!-- Modal de Movimentação -->
<div id="movimentarModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Movimentar Itens</h3>
        <p>Você está movimentando os seguintes itens:</p>
        <ul id="itemsToMoveList"></ul>
        <hr>
        <form id="movimentarForm">
            <div class="form-group autocomplete-container">
                <label for="searchLocal">Para onde o equipamento vai? (Novo Local)</label>
                <input type="text" id="searchLocal" name="search_local" class="form-control" placeholder="Digite para pesquisar..." required>
                <input type="hidden" id="novoLocalId" name="novo_local_id">
                <div id="localSuggestions" class="suggestions-list"></div>
            </div>
            <div class="form-group autocomplete-container">
                <label for="searchResponsavel">Para Quem? (Novo Responsável)</label>
                <input type="text" id="searchResponsavel" name="search_responsavel" class="form-control" placeholder="Digite para pesquisar..." required>
                <input type="hidden" id="novoResponsavelId" name="novo_responsavel_id">
                <div id="responsavelSuggestions" class="suggestions-list"></div>
            </div>
            <button type="submit" class="btn btn-primary">Confirmar Movimentação</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Lógica de Movimentação com Autocomplete ---
    const movimentarBtn = document.getElementById('movimentarBtn');
    const modal = document.getElementById('movimentarModal');
    const closeBtn = modal.querySelector('.close-button');
    const itemsToMoveList = document.getElementById('itemsToMoveList');
    const movimentarForm = document.getElementById('movimentarForm');

    const searchLocalInput = document.getElementById('searchLocal');
    const novoLocalIdInput = document.getElementById('novoLocalId');
    const localSuggestions = document.getElementById('localSuggestions');

    const searchResponsavelInput = document.getElementById('searchResponsavel');
    const novoResponsavelIdInput = document.getElementById('novoResponsavelId');
    const responsavelSuggestions = document.getElementById('responsavelSuggestions');

    // Função genérica para busca com autocomplete
    function setupAutocomplete(inputEl, suggestionsEl, hiddenIdEl, searchUrl) {
        inputEl.addEventListener('input', function() {
            const searchTerm = this.value;
            suggestionsEl.innerHTML = '';
            hiddenIdEl.value = ''; // Limpa o ID ao digitar

            if (searchTerm.length < 3) {
                suggestionsEl.style.display = 'none';
                return;
            }

            fetch(`${searchUrl}?term=${searchTerm}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.textContent = item.nome;
                            div.dataset.id = item.id;
                            div.addEventListener('click', function() {
                                inputEl.value = this.textContent;
                                hiddenIdEl.value = this.dataset.id;
                                suggestionsEl.innerHTML = '';
                                suggestionsEl.style.display = 'none';
                            });
                            suggestionsEl.appendChild(div);
                        });
                        suggestionsEl.style.display = 'block';
                    } else {
                        suggestionsEl.style.display = 'none';
                    }
                })
                .catch(error => console.error('Erro no autocomplete:', error));
        });
         // Esconder sugestões se clicar fora
        document.addEventListener('click', function(e) {
            if (e.target !== inputEl) {
                suggestionsEl.style.display = 'none';
            }
        });
    }

    setupAutocomplete(searchLocalInput, localSuggestions, novoLocalIdInput, 'api/search_locais.php');
    setupAutocomplete(searchResponsavelInput, responsavelSuggestions, novoResponsavelIdInput, 'api/search_usuarios.php');


    // --- Lógica de Abrir/Fechar a Modal (semelhante ao anterior) ---
    movimentarBtn.addEventListener('click', function() {
        // ... (código para popular a lista de itens a movimentar) ...
        itemsToMoveList.innerHTML = '';
        const selectedItems = Array.from(document.querySelectorAll('.item-checkbox')).filter(cb => cb.checked);
        
        if(selectedItems.length === 0) {
            alert('Por favor, selecione pelo menos um item para movimentar.');
            return;
        }

        selectedItems.forEach(item => {
            const li = document.createElement('li');
            const itemName = item.getAttribute('data-item-name');
            const itemPatrimonio = item.getAttribute('data-item-patrimonio');
            li.textContent = `${itemName} (Patrimônio: ${itemPatrimonio})`;
            itemsToMoveList.appendChild(li);
        });

        modal.style.display = 'flex';
    });

    const closeModal = () => {
        modal.style.display = 'none';
        movimentarForm.reset();
        localSuggestions.innerHTML = '';
        responsavelSuggestions.innerHTML = '';
    };

    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            closeModal();
        }
    });

    // --- Lógica de Submissão do Formulário ---
    movimentarForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const selectedItemIds = Array.from(document.querySelectorAll('.item-checkbox')).filter(cb => cb.checked).map(cb => cb.getAttribute('data-item-id'));

        if (selectedItemIds.length === 0) {
            alert('Nenhum item selecionado.');
            return;
        }
        if (!novoLocalIdInput.value || !novoResponsavelIdInput.value) {
            alert('Por favor, selecione um novo local e um novo responsável a partir das sugestões.');
            return;
        }

        const data = {
            item_ids: selectedItemIds,
            novo_local_id: novoLocalIdInput.value,
            novo_responsavel_id: novoResponsavelIdInput.value
        };

        fetch('api/movimentar_itens.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                closeModal();
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao tentar movimentar os itens.');
        });
    });
    
    // --- Lógica de Seleção e Botão de Movimentar/Excluir (existente) ---
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const excluirBtn = document.getElementById('excluirBtn');
    const bulkActions = document.getElementById('bulkActions');

    function toggleActionButtons() {
        const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
        movimentarBtn.style.display = 'inline-block';
        excluirBtn.style.display = 'inline-block';
        bulkActions.style.display = anyChecked ? 'flex' : 'none';
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = this.checked);
            toggleActionButtons();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', toggleActionButtons);
    });

    // --- Lógica de Exclusão em Massa ---
    excluirBtn.addEventListener('click', function() {
        const selectedItems = Array.from(document.querySelectorAll('.item-checkbox')).filter(cb => cb.checked);
        
        if(selectedItems.length === 0) {
            alert('Por favor, selecione pelo menos um item para excluir.');
            return;
        }

        // Confirmar a exclusão
        if(!confirm(`Tem certeza que deseja excluir ${selectedItems.length} item(s)? Esta ação não pode ser desfeita.`)) {
            return;
        }

        // Obter os IDs dos itens selecionados
        const selectedItemIds = selectedItems.map(item => item.getAttribute('data-item-id'));

        // Enviar solicitação para excluir os itens
        fetch('excluir_itens_em_massa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_ids: selectedItemIds })
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                // Recarregar a página para atualizar a lista
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao tentar excluir os itens.');
        });
    });
    
    // --- Lógica para mostrar/ocultar seção de relatório ---
    const mostrarRelatorioBtn = document.getElementById('mostrarRelatorioBtn');
    const ocultarRelatorioBtn = document.getElementById('ocultarRelatorioBtn');
    const relatorioSection = document.getElementById('relatorioSection');
    
    if (mostrarRelatorioBtn && ocultarRelatorioBtn && relatorioSection) {
        mostrarRelatorioBtn.addEventListener('click', function() {
            relatorioSection.style.display = 'block';
            this.style.display = 'none';
        });
        
        ocultarRelatorioBtn.addEventListener('click', function() {
            relatorioSection.style.display = 'none';
            mostrarRelatorioBtn.style.display = 'inline-block';
        });
    }
    
    // --- Lógica para adicionar itens selecionados ao formulário de PDF ---
    const pdfForm = document.querySelector('form[action="gerar_pdf_itens.php"]');
    if (pdfForm) {
        pdfForm.addEventListener('submit', function(e) {
            // Remover campos hidden de itens selecionados anteriores, se existirem
            const existingItemInputs = pdfForm.querySelectorAll('input[name="selected_item_ids[]"]');
            existingItemInputs.forEach(input => input.remove());
            
            // Obter itens selecionados
            const selectedItems = Array.from(document.querySelectorAll('.item-checkbox')).filter(cb => cb.checked);
            
            // Se houver itens selecionados, adicionar como campos hidden
            if (selectedItems.length > 0) {
                selectedItems.forEach(item => {
                    const itemId = item.getAttribute('data-item-id');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'selected_item_ids[]';
                    hiddenInput.value = itemId;
                    pdfForm.appendChild(hiddenInput);
                });
            }
        });
    }
    
    // --- Lógica da pesquisa automática ---
    const searchInput = document.querySelector('input[name="search_query"]');
    if (searchInput) {
        let timeout = null;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const searchTerm = this.value;
            
            // Aguarda 300ms após o usuário parar de digitar antes de enviar a requisição
            timeout = setTimeout(function() {
                if (searchTerm.length >= 3) {
                    // Submete o formulário automaticamente
                    searchInput.form.submit();
                } else if (searchTerm.length === 0) {
                    // Se o campo estiver vazio, submete o formulário para limpar a pesquisa
                    searchInput.form.submit();
                }
            }, 300);
        });
    }
    
    // --- Lógica de ordenação de colunas ---
    const sortableHeaders = document.querySelectorAll('th[data-column]');
    let currentSortColumn = null;
    let currentSortDirection = 'asc';
    
    // Função para obter o valor da célula
    function getCellValue(tr, idx) {
        const cell = tr.children[idx];
        // Se for um link, retorna o texto do link
        const link = cell.querySelector('a');
        if (link) {
            return link.textContent || link.innerText || '';
        }
        // Se for um elemento com classe badge, retorna o texto do badge
        const badge = cell.querySelector('.badge');
        if (badge) {
            return badge.textContent || badge.innerText || '';
        }
        // Caso contrário, retorna o conteúdo da célula
        return cell.textContent || cell.innerText || '';
    }
    
    // Função de comparação
    function comparer(idx, asc) {
        return function(a, b) {
            const v1 = getCellValue(asc ? a : b, idx);
            const v2 = getCellValue(asc ? b : a, idx);
            
            // Verificar se são números
            const num1 = parseFloat(v1);
            const num2 = parseFloat(v2);
            
            // Se ambos forem números válidos, comparar como números
            if (!isNaN(num1) && !isNaN(num2)) {
                return num1 - num2;
            }
            
            // Caso contrário, comparar como strings
            return v1.toString().localeCompare(v2.toString());
        };
    }
    
    sortableHeaders.forEach(header => {
        // Adicionar seta padrão para todas as colunas
        const arrow = header.querySelector('.sort-arrow');
        if (arrow) {
            arrow.classList.add('none');
        }
        
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            const table = header.closest('table');
            const tbody = table.querySelector('tbody');
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            
            // Remover classes de ordenação de todas as setas
            document.querySelectorAll('.sort-arrow').forEach(arrow => {
                arrow.classList.remove('up', 'down');
                arrow.classList.add('none');
            });
            
            // Determinar a direção da ordenação
            if (currentSortColumn === columnIndex) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortDirection = 'asc';
                currentSortColumn = columnIndex;
            }
            
            // Atualizar a seta da coluna clicada
            const clickedArrow = header.querySelector('.sort-arrow');
            if (clickedArrow) {
                clickedArrow.classList.remove('none');
                if (currentSortDirection === 'asc') {
                    clickedArrow.classList.add('up');
                } else {
                    clickedArrow.classList.add('down');
                }
            }
            
            // Ordenar as linhas
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(comparer(columnIndex, currentSortDirection === 'asc'));
            
            // Reordenar as linhas no tbody
            rows.forEach(row => tbody.appendChild(row));
        });
    });
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>