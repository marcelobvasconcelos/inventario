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
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Variáveis de pesquisa
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : '';
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// SQL base para contagem total de itens
$sql_count_base = "SELECT COUNT(*) FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id";
$sql_base = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.id as local_id, l.nome AS local, u.nome AS responsavel, i.estado, i.responsavel_id, i.status_confirmacao FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id";

$where_clause = "";
$params = [];
$param_types = "";

// Se for admin, mostra tudo. Se for usuário, mostra apenas os seus itens.
if ($_SESSION['permissao'] != 'Administrador') {
    $where_clause = " WHERE i.responsavel_id = ?";
    $params[] = $_SESSION['id'];
    $param_types = "i";
} else { // Lógica de pesquisa para administradores
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        switch ($search_by) {
            case 'id':
                $where_clause .= " WHERE i.id LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'patrimonio_novo':
                $where_clause .= " WHERE i.patrimonio_novo LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'patrimonio_secundario':
                $where_clause .= " WHERE i.patrimonio_secundario LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'local':
                $where_clause .= " WHERE l.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'responsavel':
                $where_clause .= " WHERE u.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            default:
                $where_clause .= " WHERE i.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
        }
    }
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
    <div class="actions-buttons">
        <?php if($_SESSION['permissao'] == 'Administrador' || $_SESSION['permissao'] == 'Gestor'): ?>
            <a href="item_add.php" class="btn-custom">Adicionar Novo Item</a>
        <?php endif; ?>
        <?php if($_SESSION['permissao'] == 'Administrador'): ?>
            <button id="movimentarBtn" class="btn-custom" style="display: none;"><i class="fas fa-exchange-alt"></i> Movimentar Selecionados</button>
        <?php endif; ?>
    </div>

    <?php if($_SESSION['permissao'] == 'Administrador'): ?>
    <div class="search-form">
        <form action="" method="GET">
            <div class="search-criteria">
                <label for="search_by">Pesquisar por:</label>
                <select name="search_by" id="search_by">
                    <option value="id" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'id') ? 'selected' : ''; ?>>ID</option>
                    <option value="patrimonio_novo" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'patrimonio_novo') ? 'selected' : ''; ?>>Patrimônio</option>
                    <option value="patrimonio_secundario" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'patrimonio_secundario') ? 'selected' : ''; ?>>Patrimônio Secundário</option>
                    <option value="local" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'local') ? 'selected' : ''; ?>>Local</option>
                    <option value="responsavel" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'responsavel') ? 'selected' : ''; ?>>Responsável</option>
                </select>
            </div>
            <div class="search-input">
                <input type="text" name="search_query" placeholder="Digite o termo de pesquisa" value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
                <input type="submit" value="Pesquisar">
                <?php if(isset($_GET['search_query'])): ?>
                    <a href="itens.php" class="btn-custom">Limpar Pesquisa</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php if($_SESSION['permissao'] == 'Administrador'): ?>
<div class="pdf-form-container card mt-4">
    <div class="card-body">
        <h5 class="card-title">Gerar Relatório PDF</h5>
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
<?php endif; ?>

<table>
    <thead>
        <tr>
            <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                <th><input type="checkbox" id="selectAll"></th>
            <?php endif; ?>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="nome">Nome <span class="sort-arrow"></span></th>
            <th data-column="patrimonio_novo">Patrimônio <span class="sort-arrow"></span></th>
            <th data-column="patrimonio_secundario">Patrimônio Secundário <span class="sort-arrow"></span></th>
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
    // ... (código de ordenação e seleção de itens permanece o mesmo) ...

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
     // --- Lógica de Seleção e Botão de Movimentar (existente) ---
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');

    function toggleMovimentarBtn() {
        const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
        movimentarBtn.style.display = anyChecked ? 'inline-block' : 'none';
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = this.checked);
            toggleMovimentarBtn();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', toggleMovimentarBtn);
    });
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>