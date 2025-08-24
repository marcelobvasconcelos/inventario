<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$total_movimentacoes = 0;
$sql_count_base = "SELECT COUNT(*) FROM movimentacoes m JOIN itens i ON m.item_id = i.id JOIN locais lo ON m.local_origem_id = lo.id JOIN locais ld ON m.local_destino_id = ld.id JOIN usuarios u_resp ON i.responsavel_id = u_resp.id LEFT JOIN usuarios u_ant ON i.usuario_anterior_id = u_ant.id";
$sql_fetch_base = "SELECT 
                m.id, 
                i.id AS item_id,
                i.nome AS item, 
                lo.id AS origem_id, 
                lo.nome AS origem, 
                ld.id AS destino_id, 
                ld.nome AS destino, 
                u_ant.id AS usuario_origem_id, 
                u_ant.nome AS usuario_origem, 
                u_resp.id AS usuario_destino_id, 
                u_resp.nome AS usuario_destino, 
                m.data_movimentacao
            FROM 
                movimentacoes m
            JOIN itens i ON m.item_id = i.id
            JOIN locais lo ON m.local_origem_id = lo.id
            JOIN locais ld ON m.local_destino_id = ld.id
            JOIN usuarios u_resp ON i.responsavel_id = u_resp.id
            LEFT JOIN usuarios u_ant ON i.usuario_anterior_id = u_ant.id";

$where_clause = "";
$params = [];
$param_types = "";

// Se for admin, mostra tudo. Se for usuário, mostra apenas as movimentações relacionadas a ele.
if ($_SESSION['permissao'] != 'Administrador') {
    $usuario_id = $_SESSION['id'];
    $where_clause = " WHERE i.responsavel_id = ? OR i.usuario_anterior_id = ?";
    $params[] = $usuario_id;
    $params[] = $usuario_id;
    $param_types = "ii";
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
    $total_movimentacoes = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
}

$total_paginas = ceil($total_movimentacoes / $itens_por_pagina);

// Consulta para os itens da página atual
$sql = $sql_fetch_base . $where_clause . " ORDER BY m.data_movimentacao DESC LIMIT ? OFFSET ?";

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
    $result = false;
}

?>

<h2>Movimentações de Itens</h2>
<?php if($_SESSION['permissao'] == 'Administrador'): // Mostra o botão apenas para admins ?>
    <!-- Botão para abrir o modal de pesquisa de itens -->
    <button id="pesquisarItensBtn" class="btn-custom">
        <i class="fas fa-search"></i> Pesquisar Itens para Movimentação
    </button>
<?php endif; ?>

<!-- Modal de Pesquisa de Itens -->
<div id="pesquisarItensModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Pesquisar Itens para Movimentação</h3>
        <p>Pesquise por ID, nome, patrimônio ou responsável do item.</p>
        
        <!-- Formulário de Pesquisa -->
        <form id="searchForm">
            <div class="form-group">
                <label for="searchTerm">Pesquisar Itens:</label>
                <input type="text" id="searchTerm" name="search_term" class="form-control" placeholder="Digite para pesquisar itens..." autocomplete="off">
                <div class="help-text">Pesquise por ID, nome, patrimônio ou responsável</div>
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
        
        <!-- Lista de Itens Encontrados -->
        <div id="itensEncontradosContainer" style="margin-top: 20px; display: none;">
            <h4>Itens Encontrados:</h4>
            <div id="itensList"></div>
        </div>
        
        <!-- Seção de Movimentação (aparece após selecionar itens) -->
        <div id="movimentacaoSection" style="margin-top: 20px; display: none;">
            <hr>
            <h4>Movimentar Itens Selecionados:</h4>
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
</div>

<table class="table-striped table-hover">
    <thead>
        <tr>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="item">Item <span class="sort-arrow"></span></th>
            <th data-column="origem">Local de Origem <span class="sort-arrow"></span></th>
            <th data-column="usuario_origem">Usuário de Origem <span class="sort-arrow"></span></th>
            <th data-column="destino">Local de Destino <span class="sort-arrow"></span></th>
            <th data-column="usuario_destino">Usuário de Destino <span class="sort-arrow"></span></th>
            <th data-column="data_movimentacao">Data <span class="sort-arrow"></span></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><a href="item_details.php?id=<?php echo $row['item_id']; ?>"><?php echo $row['item']; ?></a></td>
                <td><a href="local_itens.php?id=<?php echo $row['origem_id']; ?>"><?php echo $row['origem']; ?></a></td>
                <td>
                    <?php if($row['usuario_origem']): ?>
                        <a href="usuario_itens.php?id=<?php echo $row['usuario_origem_id']; ?>"><?php echo $row['usuario_origem']; ?></a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><a href="local_itens.php?id=<?php echo $row['destino_id']; ?>"><?php echo $row['destino']; ?></a></td>
                <td><a href="usuario_itens.php?id=<?php echo $row['usuario_destino_id']; ?>"><?php echo $row['usuario_destino']; ?></a></td>
                <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_movimentacao'])); ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Nenhuma movimentação encontrada.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($total_paginas > 1): ?>
        <?php if ($pagina_atual > 1): ?>
            <a href="?pagina=<?php echo $pagina_atual - 1; ?>">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?php echo $i; ?>" class="<?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($pagina_atual < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina_atual + 1; ?>">Próxima</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Lógica de Ordenação (existente) ---
    const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;

    const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
        v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

    document.querySelectorAll('th[data-column]').forEach(th => {
        th.addEventListener('click', (() => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const column = Array.from(th.parentNode.children).indexOf(th);
            const currentIsAsc = th.classList.contains('asc');

            // Remove sorting classes from all headers
            document.querySelectorAll('th[data-column]').forEach(header => {
                header.classList.remove('asc', 'desc');
                header.querySelector('.sort-arrow').innerText = '';
            });

            // Add sorting class to the clicked header
            if (currentIsAsc) {
                th.classList.add('desc');
                th.querySelector('.sort-arrow').innerText = ' ↓'; // Down arrow
            } else {
                th.classList.add('asc');
                th.querySelector('.sort-arrow').innerText = ' ↑'; // Up arrow
            }

            Array.from(tbody.querySelectorAll('tr'))
                .sort(comparer(column, !currentIsAsc))
                .forEach(tr => tbody.appendChild(tr));
        }));
    });

    <?php if($_SESSION['permissao'] == 'Administrador'): ?>
    // --- Lógica do Modal de Pesquisa de Itens ---
    const pesquisarItensBtn = document.getElementById('pesquisarItensBtn');
    const modal = document.getElementById('pesquisarItensModal');
    const closeBtn = modal.querySelector('.close-button');
    const searchForm = document.getElementById('searchForm');
    const searchTermInput = document.getElementById('searchTerm');
    const itensEncontradosContainer = document.getElementById('itensEncontradosContainer');
    const itensList = document.getElementById('itensList');
    const movimentacaoSection = document.getElementById('movimentacaoSection');

    const searchLocalInput = document.getElementById('searchLocal');
    const novoLocalIdInput = document.getElementById('novoLocalId');
    const localSuggestions = document.getElementById('localSuggestions');

    const searchResponsavelInput = document.getElementById('searchResponsavel');
    const novoResponsavelIdInput = document.getElementById('novoResponsavelId');
    const responsavelSuggestions = document.getElementById('responsavelSuggestions');

    const movimentarForm = document.getElementById('movimentarForm');

    // --- Abrir/Fechar Modal ---
    pesquisarItensBtn.addEventListener('click', function() {
        modal.style.display = 'flex';
    });

    const closeModal = () => {
        modal.style.display = 'none';
        searchForm.reset();
        itensEncontradosContainer.style.display = 'none';
        movimentacaoSection.style.display = 'none';
        itensList.innerHTML = '';
        movimentarForm.reset();
        localSuggestions.innerHTML = '';
        responsavelSuggestions.innerHTML = '';
        novoLocalIdInput.value = '';
        novoResponsavelIdInput.value = '';
    };

    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            closeModal();
        }
    });

    // --- Pesquisar Itens ---
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const searchTerm = searchTermInput.value.trim();
        
        if (searchTerm.length < 1) {
            alert('Por favor, digite pelo menos 1 caractere para pesquisar.');
            return;
        }
        
        // Mostrar indicador de carregamento
        itensList.innerHTML = '<p>Buscando itens...</p>';
        itensEncontradosContainer.style.display = 'block';
        
        // Fazer requisição para buscar itens
        fetch(`api/search_items.php?term=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Erro ao buscar itens: ' + data.error);
                    itensList.innerHTML = '<p>Erro ao buscar itens.</p>';
                    return;
                }
                
                // Exibir itens encontrados
                itensList.innerHTML = '';
                
                if (data.length > 0) {
                    let itensConfirmadosCount = 0;
                    
                    data.forEach(item => {
                        // Verificar se o item está confirmado
                        if (item.status_confirmacao !== 'Confirmado') {
                            return; // Pular itens não confirmados
                        }
                        
                        itensConfirmadosCount++;
                        
                        const div = document.createElement('div');
                        div.className = 'item-option';
                        div.innerHTML = `
                            <input type="checkbox" id="item_${item.id}" name="selected_items[]" value="${item.id}" class="item-checkbox">
                            <label for="item_${item.id}">
                                <strong>${item.nome}</strong> 
                                (ID: ${item.id}, 
                                 Patrimônio: ${item.patrimonio_novo || 'N/A'}, 
                                 Local: ${item.local || 'N/A'}, 
                                 Responsável: ${item.responsavel || 'N/A'})
                            </label>
                        `;
                        itensList.appendChild(div);
                    });
                    
                    if (itensConfirmadosCount > 0) {
                        // Adicionar evento para mostrar seção de movimentação quando um item for selecionado
                        const checkboxes = itensList.querySelectorAll('.item-checkbox');
                        checkboxes.forEach(checkbox => {
                            checkbox.addEventListener('change', function() {
                                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                                movimentacaoSection.style.display = anyChecked ? 'block' : 'none';
                            });
                        });
                        
                        itensEncontradosContainer.style.display = 'block';
                    } else {
                        itensList.innerHTML = '<p>Nenhum item confirmado encontrado com os critérios de busca.</p>';
                        itensEncontradosContainer.style.display = 'block';
                    }
                } else {
                    itensList.innerHTML = '<p>Nenhum item encontrado com os critérios de busca.</p>';
                    itensEncontradosContainer.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                itensList.innerHTML = '<p>Ocorreu um erro ao buscar os itens.</p>';
                itensEncontradosContainer.style.display = 'block';
            });
    });

    // --- Lógica de Autocomplete ---
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

    // --- Submeter Formulário de Movimentação ---
    movimentarForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Obter itens selecionados
        const selectedCheckboxes = document.querySelectorAll('#itensList .item-checkbox:checked');
        const selectedItemIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        
        if (selectedItemIds.length === 0) {
            alert('Por favor, selecione pelo menos um item para movimentar.');
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
    <?php endif; ?>
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>