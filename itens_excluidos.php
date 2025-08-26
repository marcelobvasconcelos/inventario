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

// Consulta para contagem total de itens excluídos
$sql_count = "SELECT COUNT(*) FROM itens i JOIN locais l ON i.local_id = l.id WHERE i.estado = 'Excluido' AND i.responsavel_id = ?";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute([$lixeira_id]);
$total_itens = $stmt_count->fetchColumn();

$total_paginas = ceil($total_itens / $itens_por_pagina);

// Consulta para os itens excluídos da página atual
$sql = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.nome AS local, i.estado 
        FROM itens i 
        JOIN locais l ON i.local_id = l.id 
        WHERE i.estado = 'Excluido' AND i.responsavel_id = ? 
        ORDER BY i.id DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $lixeira_id, PDO::PARAM_INT);
$stmt->bindValue(2, $itens_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Itens Excluídos (Lixeira)</h2>

<?php if (count($result) > 0): ?>
    <table class="table-striped table-hover">
        <thead>
            <tr>
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
        <?php if ($pagina_atual > 1): ?>
            <a href="?pagina=<?php echo $pagina_atual - 1; ?>">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?php echo $i; ?>" class="<?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($pagina_atual < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina_atual + 1; ?>">Próxima</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Modal de Restauração -->
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

<?php else: ?>
    <div class="alert alert-info">Nenhum item excluído encontrado.</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal de restauração
    const modal = document.getElementById('restaurarModal');
    const closeBtn = modal.querySelector('.close-button');
    const restaurarForm = document.getElementById('restaurarForm');
    
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

    // Abrir modal para restaurar item
    document.querySelectorAll('.restaurar-item').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            document.getElementById('itemId').value = itemId;
            modal.style.display = 'flex';
        });
    });

    // Fechar modal
    const closeModal = () => {
        modal.style.display = 'none';
        restaurarForm.reset();
        localSuggestions.innerHTML = '';
        responsavelSuggestions.innerHTML = '';
    };

    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            closeModal();
        }
    });

    // Submeter formulário de restauração
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
});
</script>

<?php
require_once 'includes/footer.php';
?>