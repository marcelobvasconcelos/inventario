<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem adicionar movimentações
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
    $local_destino_id = $_POST['local_destino_id'];
    $novo_responsavel_id = $_POST['novo_responsavel_id'];
    $usuario_id = $_SESSION['id'];
    $local_origem_id_form = $_POST['local_origem_id']; // Local de origem selecionado no formulário

    if (empty($selected_items)) {
        echo "<div class='alert alert-danger'>Por favor, selecione pelo menos um item para movimentar.</div>";
    } else {
        mysqli_begin_transaction($link);
        try {
            foreach ($selected_items as $item_id) {
                // Obter local de origem e responsável atual do item
                $sql_origem = "SELECT local_id, responsavel_id FROM itens WHERE id = ?";
                if($stmt_origem = mysqli_prepare($link, $sql_origem)){
                    mysqli_stmt_bind_param($stmt_origem, "i", $item_id);
                    mysqli_stmt_execute($stmt_origem);
                    $result_origem = mysqli_stmt_get_result($stmt_origem);
                    $item = mysqli_fetch_assoc($result_origem);
                    $local_origem_id = $item['local_id'];
                    $usuario_anterior_id = $item['responsavel_id'];
                    mysqli_stmt_close($stmt_origem);
                } else {
                    throw new Exception("Erro ao preparar consulta de origem: " . mysqli_error($link));
                }

                // Inserir movimentação
                $sql_mov = "INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id) VALUES (?, ?, ?, ?, ?)";
                if($stmt_mov = mysqli_prepare($link, $sql_mov)){
                    mysqli_stmt_bind_param($stmt_mov, "iiiii", $item_id, $local_origem_id, $local_destino_id, $usuario_id, $usuario_anterior_id);
                    if(!mysqli_stmt_execute($stmt_mov)){
                        throw new Exception("Erro ao inserir movimentação para o item " . $item_id . ": " . mysqli_error($link));
                    }
                    mysqli_stmt_close($stmt_mov);
                } else {
                    throw new Exception("Erro ao preparar inserção de movimentação: " . mysqli_error($link));
                }

                // Atualizar o local e o responsável do item
                $sql_update = "UPDATE itens SET local_id = ?, responsavel_id = ? WHERE id = ?";
                if($stmt_update = mysqli_prepare($link, $sql_update)){
                    mysqli_stmt_bind_param($stmt_update, "iii", $local_destino_id, $novo_responsavel_id, $item_id);
                    if(!mysqli_stmt_execute($stmt_update)){
                        throw new Exception("Erro ao atualizar item " . $item_id . ": " . mysqli_error($link));
                    }
                    mysqli_stmt_close($stmt_update);
                } else {
                    throw new Exception("Erro ao preparar atualização do item: " . mysqli_error($link));
                }
            }

            mysqli_commit($link);
            header("location: movimentacoes.php");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($link);
            echo "<div class='alert alert-danger'>Oops! Algo deu errado. Por favor, tente novamente mais tarde. Erro: " . $e->getMessage() . "</div>";
        }
    }
}

$locais = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
$usuarios_ativos = mysqli_query($link, "SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC");

?>

<h2>Registrar Nova Movimentação</h2>

<form action="" method="post">
    <div>
        <label for="local_origem_id">Local de Origem</label>
        <select name="local_origem_id" id="local_origem_id" required>
            <option value="">Selecione um local</option>
            <?php while($local = mysqli_fetch_assoc($locais)): ?>
                <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div id="itens_do_local_container" style="margin-top: 20px;">
        <div class="form-group">
            <label for="item_filter_search">Filtrar Itens:</label>
            <input type="text" id="item_filter_search" placeholder="Digite para filtrar itens...">
        </div>
        <!-- Itens do local serão carregados aqui via JavaScript -->
    </div>

    <div>
        <label for="local_destino_id">Local de Destino</label>
        <select name="local_destino_id" id="local_destino_id" required>
            <option value="">Selecione um local</option>
            <?php 
            // Resetar o ponteiro do resultado para reutilizar $locais
            mysqli_data_seek($locais, 0);
            while($local = mysqli_fetch_assoc($locais)): 
            ?>
                <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div>
        <label for="novo_responsavel_id">Novo Responsável</label>
        <select name="novo_responsavel_id" id="novo_responsavel_id">
            <?php while($usuario = mysqli_fetch_assoc($usuarios_ativos)): ?>
                <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div>
        <input type="submit" value="Registrar Movimentação">
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const localOrigemSelect = document.getElementById('local_origem_id');
    const itensDoLocalContainer = document.getElementById('itens_do_local_container');
    const itemFilterSearchInput = document.getElementById('item_filter_search'); // Novo
    const form = document.querySelector('form');

    let debounceTimeout; // Para o debounce do filtro

    function fetchItemsForLocation(locationId, searchTerm = '') {
        if (!locationId) {
            itensDoLocalContainer.innerHTML = '';
            return;
        }

        const url = `api/get_items_by_location.php?location_id=${locationId}&search_term=${searchTerm}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                let html = '<h3>Itens neste Local:</h3>';
                if (data.length > 0) {
                    html += '<div style="margin-bottom: 10px;"><input type="checkbox" id="select_all_items"> <label for="select_all_items">Selecionar Todos</label></div>';
                    html += '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">';
                    data.forEach(item => {
                        html += `
                            <div style="margin-bottom: 5px;">
                                <input type="checkbox" name="selected_items[]" value="${item.id}" id="item_${item.id}">
                                <label for="item_${item.id}">${item.nome} (${item.patrimonio_novo})</label>
                            </div>
                        `;
                    });
                    html += '</div>';
                } else {
                    html += '<p>Nenhum item encontrado neste local.</p>';
                }
                itensDoLocalContainer.innerHTML = html;

                // Add event listener for select all checkbox
                const selectAllCheckbox = document.getElementById('select_all_items');
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function() {
                        const checkboxes = itensDoLocalContainer.querySelectorAll('input[type="checkbox"][name="selected_items[]"]');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao buscar itens:', error);
                itensDoLocalContainer.innerHTML = '<p>Erro ao carregar itens.</p>';
            });
    }

    localOrigemSelect.addEventListener('change', function() {
        const locationId = this.value;
        itemFilterSearchInput.value = ''; // Limpa o filtro ao mudar o local
        fetchItemsForLocation(locationId);
    });

    itemFilterSearchInput.addEventListener('input', function() { // Novo event listener para o filtro
        clearTimeout(debounceTimeout);
        const locationId = localOrigemSelect.value;
        const searchTerm = this.value;
        debounceTimeout = setTimeout(() => {
            fetchItemsForLocation(locationId, searchTerm);
        }, 300); // Debounce para evitar muitas requisições
    });

    // Initial load if a location is already selected (e.g., after form submission error)
    if (localOrigemSelect.value) {
        fetchItemsForLocation(localOrigemSelect.value);
    }

    form.addEventListener('submit', function(event) {
        const selectedItems = itensDoLocalContainer.querySelectorAll('input[type="checkbox"][name="selected_items[]"]:checked');
        if (selectedItems.length === 0) {
            alert('Por favor, selecione pelo menos um item para movimentar.');
            event.preventDefault(); // Prevent form submission
        }
    });
});
</script>