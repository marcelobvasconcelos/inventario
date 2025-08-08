<?php
// Inicia a sessão PHP para gerenciar o estado do usuário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o cabeçalho HTML padrão e a conexão com o banco de dados
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem adicionar movimentações
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Processa o formulário quando ele é submetido (método POST)
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Obtém os itens selecionados, local de destino e novo responsável
    $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
    $local_destino_id = $_POST['local_destino_id'];
    $novo_responsavel_id = $_POST['novo_responsavel_id'];
    $usuario_id = $_SESSION['id']; // ID do administrador que está realizando a movimentação
    $local_origem_id_form = $_POST['local_origem_id']; // Local de origem selecionado no formulário

    // Verifica se pelo menos um item foi selecionado
    if (empty($selected_items)) {
        echo "<div class='alert alert-danger'>Por favor, selecione pelo menos um item para movimentar.</div>";
    } else {
        // Inicia uma transação para garantir a atomicidade das operações no banco de dados
        mysqli_begin_transaction($link);
        try {
            // Loop através de cada item selecionado para movimentação
            foreach ($selected_items as $item_id) {
                // Obter local de origem e responsável atual do item antes da atualização
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

                // Inserir registro da movimentação na tabela 'movimentacoes'
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

                // Atualizar o local, o responsável e o status de confirmação do item na tabela 'itens'
                // O status_confirmacao é definido como 'Pendente' para que o novo responsável confirme
                $sql_update = "UPDATE itens SET local_id = ?, responsavel_id = ?, status_confirmacao = 'Pendente' WHERE id = ?";
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

            // --- Lógica de Notificação para Movimentação em Massa --- //
            // Após todas as atualizações de itens, criar uma única notificação para a movimentação em massa
            $admin_id = $_SESSION['id']; // O administrador que realizou a movimentação
            $itens_ids_notificacao = implode(',', $selected_items); // IDs dos itens envolvidos, separados por vírgula
            
            // Obter nomes dos itens para incluir na mensagem da notificação
            $item_names = [];
            // Prepara placeholders para a cláusula IN (segurança contra SQL Injection)
            $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
            $sql_get_item_names = "SELECT nome FROM itens WHERE id IN ($placeholders)";
            $stmt_get_item_names = $pdo->prepare($sql_get_item_names);
            // Vincula os parâmetros dinamicamente
            foreach ($selected_items as $k => $id) {
                $stmt_get_item_names->bindValue(($k+1), $id, PDO::PARAM_INT);
            }
            $stmt_get_item_names->execute();
            // Coleta os nomes dos itens
            while ($row = $stmt_get_item_names->fetch(PDO::FETCH_ASSOC)) {
                $item_names[] = $row['nome'];
            }
            $nomes_itens_str = implode(', ', $item_names); // Converte o array de nomes em uma string

            // Constrói a mensagem da notificação
            $mensagem_notificacao = "Uma movimentação de inventário foi registrada para os seguintes itens: " . htmlspecialchars($nomes_itens_str) . ". Eles foram atribuídos a você. Por favor, confirme o recebimento.";
            
            // Insere a notificação na tabela 'notificacoes' usando PDO
            $sql_notificacao = "INSERT INTO notificacoes (usuario_id, administrador_id, tipo, itens_ids, mensagem, status) VALUES (?, ?, ?, ?, ?, 'Pendente')";
            $stmt_notificacao = $pdo->prepare($sql_notificacao);
            $stmt_notificacao->execute([$novo_responsavel_id, $admin_id, 'transferencia', $itens_ids_notificacao, $mensagem_notificacao]);
            // --- Fim Lógica de Notificação --- //

            // Confirma a transação se todas as operações foram bem-sucedidas
            mysqli_commit($link);
            // Redireciona para a página de movimentações após o sucesso
            header("location: movimentacoes.php");
            exit();
        } catch (Exception $e) {
            // Em caso de erro, reverte a transação
            mysqli_rollback($link);
            echo "<div class='alert alert-danger'>Oops! Algo deu errado. Por favor, tente novamente mais tarde. Erro: " . $e->getMessage() . "</div>";
        }
    }
}

// Obtém a lista de locais para os dropdowns
$locais = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
// Obtém a lista de usuários ativos para o dropdown de responsável
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