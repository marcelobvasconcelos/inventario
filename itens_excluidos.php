<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem acessar esta página
if($_SESSION["permissao"] != 'Administrador'){
    echo "Acesso negado.";
    exit;
}

// Obter o ID do usuário "Lixeira"
try {
    $stmt_lixeira = $pdo->prepare("SELECT id FROM usuarios WHERE nome = 'Lixeira'");
    $stmt_lixeira->execute();
    $lixeira = $stmt_lixeira->fetch(PDO::FETCH_ASSOC);
    
    if (!$lixeira) {
        echo "<div class='alert alert-danger'>Usuário 'Lixeira' não encontrado. Execute o script de atualização do banco de dados.</div>";
        exit;
    }
    
    $lixeira_id = $lixeira['id'];
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erro ao localizar usuário 'Lixeira': " . $e->getMessage() . "</div>";
    exit;
}

// Configurações de paginação
$itens_por_pagina = 60;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Variáveis de pesquisa
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// Parâmetros para as consultas
$count_params = [$lixeira_id];

// Consulta para contagem total de itens excluídos
$sql_count = "SELECT COUNT(*) FROM itens i JOIN locais l ON i.local_id = l.id WHERE i.estado = 'Excluido' AND i.responsavel_id = ?";

// Adicionar condição de pesquisa, se houver
if (!empty($search_query)) {
    $sql_count .= " AND (l.nome LIKE ? OR i.nome LIKE ? OR i.patrimonio_novo LIKE ? OR i.patrimonio_secundario LIKE ?)";
    $count_params[] = '%' . $search_query . '%';
    $count_params[] = '%' . $search_query . '%';
    $count_params[] = '%' . $search_query . '%';
    $count_params[] = '%' . $search_query . '%';
}

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($count_params);
$total_itens = $stmt_count->fetchColumn();

$total_paginas = ceil($total_itens / $itens_por_pagina);

// Consulta para os itens excluídos da página atual
$sql = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.nome AS local, i.estado 
        FROM itens i 
        JOIN locais l ON i.local_id = l.id 
        WHERE i.estado = 'Excluido' AND i.responsavel_id = ?";

// Adicionar condição de pesquisa, se houver
$params = [$lixeira_id];
if (!empty($search_query)) {
    $sql .= " AND (l.nome LIKE ? OR i.nome LIKE ? OR i.patrimonio_novo LIKE ? OR i.patrimonio_secundario LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

$sql .= " ORDER BY i.id DESC LIMIT " . $itens_por_pagina . " OFFSET " . $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Itens Excluídos (Lixeira)</h2>

<div class="controls-container">
    <div class="main-actions">
        <a href="itens.php" class="btn-custom">← Voltar para Itens</a>
    </div>
    
    <div class="search-form">
        <form action="" method="GET">
            <div class="search-input">
                <input type="text" name="search_query" placeholder="Pesquisar por nome do item, patrimônio, setor..." value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
            </div>
        </form>
    </div>
</div>

<?php if (count($result) > 0): ?>
    <div class="bulk-actions" id="bulkActions" style="display: none; margin-bottom: 20px;">
        <button id="restaurarSelecionadosBtn" class="btn btn-primary">
            <i class="fas fa-undo"></i> Restaurar Selecionados
        </button>
    </div>
    
    <table class="table-striped table-hover">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>ID</th>
                <th>Nome</th>
                <th>Patrimônio</th>
                <th>Patrimônio Secundário</th>
                <th>Local</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($result as $row): ?>
            <tr>
                <td><input type="checkbox" class="item-checkbox" data-item-id="<?php echo $row['id']; ?>"></td>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['nome']; ?></td>
                <td><?php echo $row['patrimonio_novo']; ?></td>
                <td><?php echo $row['patrimonio_secundario']; ?></td>
                <td><?php echo $row['local']; ?></td>
                <td><?php echo $row['estado']; ?></td>
                <td>
                    <button class="btn btn-primary btn-sm restaurar-item" 
                            data-item-id="<?php echo $row['id']; ?>">
                        <i class="fas fa-undo"></i> Restaurar
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Paginação -->
<?php if ($total_paginas > 1): ?>
<div class="pagination">
    <?php 
    // Constrói os parâmetros para manter a pesquisa na paginação
    $query_params = [];
    if (!empty($search_query)) {
        $query_params['search_query'] = $search_query;
    }
    
    $base_url = '?' . http_build_query($query_params);
    ?>
    
    <?php if ($pagina_atual > 1): ?>
        <a href="<?php echo $base_url . '&pagina=' . ($pagina_atual - 1); ?>">Anterior</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <a href="<?php echo $base_url . '&pagina=' . $i; ?>" class="<?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>

    <?php if ($pagina_atual < $total_paginas): ?>
        <a href="<?php echo $base_url . '&pagina=' . ($pagina_atual + 1); ?>">Próxima</a>
    <?php endif; ?>
</div>
<?php endif; ?>

    <!-- Modal de Restauração Individual -->
    <div id="restaurarModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Restaurar Item</h3>
            <p>Selecione o novo local e responsável para o item:</p>
            <form id="restaurarForm">
                <input type="hidden" id="itemId" name="item_id">
                
                <div class="form-group autocomplete-container">
                    <label for="searchLocal">Novo Local</label>
                    <input type="text" id="searchLocal" name="search_local" class="form-control" placeholder="Digite para pesquisar..." required>
                    <input type="hidden" id="novoLocalId" name="novo_local_id">
                    <div id="localSuggestions" class="suggestions-list"></div>
                </div>
                
                <div class="form-group autocomplete-container">
                    <label for="searchResponsavel">Novo Responsável</label>
                    <input type="text" id="searchResponsavel" name="search_responsavel" class="form-control" placeholder="Digite para pesquisar..." required>
                    <input type="hidden" id="novoResponsavelId" name="novo_responsavel_id">
                    <div id="responsavelSuggestions" class="suggestions-list"></div>
                </div>
                
                <button type="submit" class="btn btn-primary">Confirmar Restauração</button>
            </form>
        </div>
    </div>
    
    <!-- Modal de Restauração em Massa -->
    <div id="restaurarMassaModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Restaurar Itens Selecionados</h3>
            <p>Selecione o novo local e responsável para os itens selecionados:</p>
            <form id="restaurarMassaForm">
                <div class="form-group autocomplete-container">
                    <label for="searchLocalMassa">Novo Local</label>
                    <input type="text" id="searchLocalMassa" name="search_local" class="form-control" placeholder="Digite para pesquisar..." required>
                    <input type="hidden" id="novoLocalIdMassa" name="novo_local_id">
                    <div id="localSuggestionsMassa" class="suggestions-list"></div>
                </div>
                
                <div class="form-group autocomplete-container">
                    <label for="searchResponsavelMassa">Novo Responsável</label>
                    <input type="text" id="searchResponsavelMassa" name="search_responsavel" class="form-control" placeholder="Digite para pesquisar..." required>
                    <input type="hidden" id="novoResponsavelIdMassa" name="novo_responsavel_id">
                    <div id="responsavelSuggestionsMassa" class="suggestions-list"></div>
                </div>
                
                <div id="itensSelecionadosList" style="margin-top: 15px;"></div>
                
                <button type="submit" class="btn btn-primary">Confirmar Restauração em Massa</button>
            </form>
        </div>
    </div>

<?php else: ?>
    <div class="alert alert-info">Nenhum item excluído encontrado.</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Implementação da pesquisa automática
    const searchInput = document.querySelector('input[name="search_query"]');
    if (searchInput) {
        let timeout = null;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const searchTerm = this.value;
            
            // Aguarda 300ms após o usuário parar de digitar antes de enviar a requisição
            timeout = setTimeout(function() {
                if (searchTerm.length >= 3) {
                    // Aqui você poderia implementar uma pesquisa em tempo real via AJAX
                    // Por enquanto, vamos apenas submeter o formulário automaticamente
                    searchInput.form.submit();
                } else if (searchTerm.length === 0) {
                    // Se o campo estiver vazio, submete o formulário para limpar a pesquisa
                    searchInput.form.submit();
                }
            }, 300);
        });
    }
    
    // Modal de restauração individual
    const modal = document.getElementById('restaurarModal');
    const closeBtn = modal.querySelector('.close-button');
    const restaurarForm = document.getElementById('restaurarForm');
    
    // Modal de restauração em massa
    const modalMassa = document.getElementById('restaurarMassaModal');
    const closeBtnMassa = modalMassa.querySelector('.close-button');
    const restaurarMassaForm = document.getElementById('restaurarMassaForm');
    const itensSelecionadosList = document.getElementById('itensSelecionadosList');
    
    const searchLocalInput = document.getElementById('searchLocal');
    const novoLocalIdInput = document.getElementById('novoLocalId');
    const localSuggestions = document.getElementById('localSuggestions');
    
    const searchResponsavelInput = document.getElementById('searchResponsavel');
    const novoResponsavelIdInput = document.getElementById('novoResponsavelId');
    const responsavelSuggestions = document.getElementById('responsavelSuggestions');
    
    // Elementos para restauração em massa
    const searchLocalMassaInput = document.getElementById('searchLocalMassa');
    const novoLocalIdMassaInput = document.getElementById('novoLocalIdMassa');
    const localSuggestionsMassa = document.getElementById('localSuggestionsMassa');
    
    const searchResponsavelMassaInput = document.getElementById('searchResponsavelMassa');
    const novoResponsavelIdMassaInput = document.getElementById('novoResponsavelIdMassa');
    const responsavelSuggestionsMassa = document.getElementById('responsavelSuggestionsMassa');
    
    // Botões de ação
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const restaurarSelecionadosBtn = document.getElementById('restaurarSelecionadosBtn');
    
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
    setupAutocomplete(searchLocalMassaInput, localSuggestionsMassa, novoLocalIdMassaInput, 'api/search_locais.php');
    setupAutocomplete(searchResponsavelMassaInput, responsavelSuggestionsMassa, novoResponsavelIdMassaInput, 'api/search_usuarios.php');

    // Função para atualizar a visibilidade dos botões de ação em massa
    function toggleBulkActions() {
        const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
        bulkActions.style.display = anyChecked ? 'block' : 'none';
    }

    // Selecionar todos os checkboxes
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = this.checked);
            toggleBulkActions();
        });
    }

    // Atualizar botões de ação quando um checkbox é clicado
    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', toggleBulkActions);
    });

    // Abrir modal para restaurar item individual
    document.querySelectorAll('.restaurar-item').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            document.getElementById('itemId').value = itemId;
            modal.style.display = 'flex';
        });
    });

    // Abrir modal para restaurar itens selecionados
    if (restaurarSelecionadosBtn) {
        restaurarSelecionadosBtn.addEventListener('click', function() {
            const selectedItems = Array.from(document.querySelectorAll('.item-checkbox')).filter(cb => cb.checked);
            
            if (selectedItems.length === 0) {
                alert('Por favor, selecione pelo menos um item para restaurar.');
                return;
            }
            
            // Atualizar a lista de itens selecionados no modal
            itensSelecionadosList.innerHTML = '<h4>Itens selecionados:</h4><ul>';
            selectedItems.forEach(item => {
                const itemId = item.getAttribute('data-item-id');
                const row = item.closest('tr');
                const itemName = row.cells[2].textContent; // Nome do item
                const itemPatrimonio = row.cells[3].textContent; // Patrimônio
                itensSelecionadosList.innerHTML += `<li>${itemName} (Patrimônio: ${itemPatrimonio})</li>`;
            });
            itensSelecionadosList.innerHTML += '</ul>';
            
            modalMassa.style.display = 'flex';
        });
    }

    // Fechar modals
    const closeModal = () => {
        modal.style.display = 'none';
        restaurarForm.reset();
        localSuggestions.innerHTML = '';
        responsavelSuggestions.innerHTML = '';
    };
    
    const closeModalMassa = () => {
        modalMassa.style.display = 'none';
        restaurarMassaForm.reset();
        localSuggestionsMassa.innerHTML = '';
        responsavelSuggestionsMassa.innerHTML = '';
        itensSelecionadosList.innerHTML = '';
    };

    closeBtn.addEventListener('click', closeModal);
    closeBtnMassa.addEventListener('click', closeModalMassa);
    
    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            closeModal();
        }
        if (event.target == modalMassa) {
            closeModalMassa();
        }
    });

    // Submeter formulário de restauração individual
    restaurarForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const itemId = document.getElementById('itemId').value;
        const novoLocalId = novoLocalIdInput.value;
        const novoResponsavelId = novoResponsavelIdInput.value;
        
        if (!itemId || !novoLocalId || !novoResponsavelId) {
            alert('Por favor, preencha todos os campos.');
            return;
        }
        
        const data = {
            item_id: itemId,
            novo_local_id: novoLocalId,
            novo_responsavel_id: novoResponsavelId
        };
        
        fetch('api/restaurar_item.php', {
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
            alert('Ocorreu um erro ao tentar restaurar o item.');
        });
    });
    
    // Submeter formulário de restauração em massa
    restaurarMassaForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedItems = Array.from(document.querySelectorAll('.item-checkbox')).filter(cb => cb.checked);
        
        if (selectedItems.length === 0) {
            alert('Nenhum item selecionado.');
            return;
        }
        
        const novoLocalId = novoLocalIdMassaInput.value;
        const novoResponsavelId = novoResponsavelIdMassaInput.value;
        
        if (!novoLocalId || !novoResponsavelId) {
            alert('Por favor, selecione um novo local e um novo responsável.');
            return;
        }
        
        // Confirmar a restauração em massa
        if (!confirm(`Tem certeza que deseja restaurar ${selectedItems.length} item(s) para o mesmo local e responsável?`)) {
            return;
        }
        
        // Obter os IDs dos itens selecionados
        const itemIds = selectedItems.map(item => parseInt(item.getAttribute('data-item-id')));
        
        const data = {
            item_ids: itemIds,
            novo_local_id: parseInt(novoLocalId),
            novo_responsavel_id: parseInt(novoResponsavelId)
        };
        
        fetch('api/restaurar_itens_em_massa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                closeModalMassa();
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao tentar restaurar os itens.');
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>