<?php
echo "<pre>DEBUG: Script movimentacao_add.php iniciado.</pre>";
/**
 * Página para registrar a movimentação de um ou mais itens de inventário.
 * Permite que um administrador mova itens de um local de origem para um local de destino,
 * atribuindo-os a um novo responsável. Gera uma notificação para o novo responsável.
 */

// Bloco de inicialização: Garante que a sessão PHP esteja ativa.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclusão de arquivos essenciais: o cabeçalho da página e a configuração do banco de dados.
require_once 'includes/header.php';
require_once 'config/db.php';

// --- VERIFICAÇÃO DE PERMISSÃO ---
// Apenas usuários com perfil 'Administrador' podem acessar esta página.
// Se o usuário não for um administrador, exibe uma mensagem de erro e encerra o script.
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

// --- PROCESSAMENTO DO FORMULÁRIO (quando enviado via POST) ---
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Coleta dos dados enviados pelo formulário.
    $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : []; // Array de IDs dos itens a serem movidos.
    $local_destino_id = $_POST['local_destino_id']; // ID do local de destino.
    $novo_responsavel_id = $_POST['novo_responsavel_id']; // ID do novo responsável pelos itens.
    $usuario_id = $_SESSION['id']; // ID do administrador que está realizando a operação.

    // Validação para garantir que todos os campos necessários foram preenchidos.
    if (empty($selected_items) || empty($local_destino_id) || empty($novo_responsavel_id)) {
        echo "<div class='alert alert-danger'>Por favor, selecione pelo menos um item, um local de destino e um novo responsável.</div>";
    } else {
        // --- TRANSAÇÃO DE BANCO DE DADOS ---
        // Inicia uma transação para garantir que todas as operações de banco de dados
        // sejam executadas com sucesso. Se qualquer uma falhar, todas são revertidas (rollback).
        mysqli_begin_transaction($link);
        try {
            // Itera sobre cada item selecionado para a movimentação.
            foreach ($selected_items as $item_id) {
                // 1. Busca o local de origem e o responsável anterior do item.
                $sql_origem = "SELECT local_id, responsavel_id FROM itens WHERE id = ?";
                $stmt_origem = mysqli_prepare($link, $sql_origem);
                mysqli_stmt_bind_param($stmt_origem, "i", $item_id);
                mysqli_stmt_execute($stmt_origem);
                $result_origem = mysqli_stmt_get_result($stmt_origem);
                $item = mysqli_fetch_assoc($result_origem);
                $local_origem_id = $item['local_id'];
                $usuario_anterior_id = $item['responsavel_id'];
                mysqli_stmt_close($stmt_origem);

                // 2. Insere um registro na tabela `movimentacoes` para o histórico.
                $sql_mov = "INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id) VALUES (?, ?, ?, ?, ?)";
                $stmt_mov = mysqli_prepare($link, $sql_mov);
                mysqli_stmt_bind_param($stmt_mov, "iiiii", $item_id, $local_origem_id, $local_destino_id, $usuario_id, $usuario_anterior_id);
                mysqli_stmt_execute($stmt_mov);
                mysqli_stmt_close($stmt_mov);

                // 3. Atualiza o registro do item na tabela `itens` com o novo local e responsável.
                // O status de confirmação é definido como 'Pendente' para que o novo responsável confirme o recebimento.
                $sql_update = "UPDATE itens SET local_id = ?, responsavel_id = ?, status_confirmacao = 'Pendente' WHERE id = ?";
                $stmt_update = mysqli_prepare($link, $sql_update);
                mysqli_stmt_bind_param($stmt_update, "iii", $local_destino_id, $novo_responsavel_id, $item_id);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);

                // --- DEBUG TEMPORÁRIO: Verificar se a movimentação foi inserida --- //
                echo "<pre>DEBUG: Item ID processado: " . htmlspecialchars($item_id) . "</pre>";
                $check_mov_sql = "SELECT id FROM movimentacoes WHERE item_id = ? ORDER BY id DESC LIMIT 1";
                $stmt_check_mov = mysqli_prepare($link, $check_mov_sql);
                mysqli_stmt_bind_param($stmt_check_mov, "i", $item_id);
                mysqli_stmt_execute($stmt_check_mov);
                $result_check_mov = mysqli_stmt_get_result($stmt_check_mov);
                $debug_mov_id = mysqli_fetch_assoc($result_check_mov)['id'] ?? 'N/A';
                echo "<pre>DEBUG: Último Movimentacao ID para item " . htmlspecialchars($item_id) . ": " . htmlspecialchars($debug_mov_id) . "</pre>";
                mysqli_stmt_close($stmt_check_mov);
                // --- FIM DEBUG TEMPORÁRIO --- //
            }

            // --- LÓGICA DE NOTIFICAÇÃO (NOVA IMPLEMENTAÇÃO) ---
            // Para cada item movimentado, cria uma notificação individual na nova tabela notificacoes_movimentacao.
            foreach ($selected_items as $item_id) {
                // Obtém o ID da movimentação recém-inserida para este item.
                // A forma mais robusta é buscar o ID da movimentação para o item_id atual.
                $sql_get_mov_id = "SELECT id FROM movimentacoes WHERE item_id = ? ORDER BY data_movimentacao DESC LIMIT 1";
                $stmt_get_mov_id = mysqli_prepare($link, $sql_get_mov_id);
                mysqli_stmt_bind_param($stmt_get_mov_id, "i", $item_id);
                mysqli_stmt_execute($stmt_get_mov_id);
                $result_get_mov_id = mysqli_stmt_get_result($stmt_get_mov_id);
                $movimentacao_data = mysqli_fetch_assoc($result_get_mov_id);
                $movimentacao_id = $movimentacao_data['id'] ?? null;
                mysqli_stmt_close($stmt_get_mov_id);

                echo "<pre>DEBUG: Movimentacao ID para notificação (item " . htmlspecialchars($item_id) . "): " . htmlspecialchars($movimentacao_id) . "</pre>";

                if ($movimentacao_id) {
                    // Insere a notificação individual para o item na tabela notificacoes_movimentacao.
                    $sql_insert_notificacao_item = "INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao) VALUES (?, ?, ?, 'Pendente')";
                    $stmt_insert_notificacao_item = mysqli_prepare($link, $sql_insert_notificacao_item);
                    mysqli_stmt_bind_param($stmt_insert_notificacao_item, "iii", $movimentacao_id, $item_id, $novo_responsavel_id);
                    $notif_exec_success = mysqli_stmt_execute($stmt_insert_notificacao_item);
                    echo "<pre>DEBUG: Notificação para item " . htmlspecialchars($item_id) . " inserida? " . ($notif_exec_success ? 'Sim' : 'Não') . ". Erro: " . htmlspecialchars(mysqli_error($link)) . "</pre>";
                    mysqli_stmt_close($stmt_insert_notificacao_item);
                } else {
                    echo "<pre>DEBUG: ERRO: Movimentacao ID não encontrado para item " . htmlspecialchars($item_id) . ". Notificação não inserida.</pre>";
                }
            }

            // Se todas as operações foram bem-sucedidas, confirma a transação.
            mysqli_commit($link);
            // Redireciona para a página de histórico de movimentações.
            header("location: movimentacoes.php");
            exit();
        } catch (Exception $e) {
            // Se ocorreu qualquer erro, reverte todas as operações no banco de dados.
            mysqli_rollback($link);
            echo "<div class='alert alert-danger'>Oops! Algo deu errado. " . $e->getMessage() . "</div>";
        }
    }
}

// Busca a lista de todos os locais para preencher os menus suspensos (dropdowns).
$locais = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
?>

<!-- Título da Página -->
<h2>Registrar Nova Movimentação</h2>

<!-- Formulário de Movimentação -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
    <!-- Seleção do Local de Origem -->
    <div>
        <label for="local_origem_id">Local de Origem</label>
        <select name="local_origem_id" id="local_origem_id" required>
            <option value="">Selecione um local</option>
            <?php while($local = mysqli_fetch_assoc($locais)): ?>
                <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Container para os Itens do Local -->
    <!-- Este container será preenchido dinamicamente via JavaScript -->
    <div id="itens_do_local_container" style="margin-top: 20px;">
    </div>

    <!-- Seleção do Local de Destino -->
    <div>
        <label for="local_destino_id">Local de Destino</label>
        <select name="local_destino_id" id="local_destino_id" required>
            <option value="">Selecione um local</option>
            <?php 
            // Reseta o ponteiro do resultado da query de locais para reutilizá-lo.
            mysqli_data_seek($locais, 0);
            while($local = mysqli_fetch_assoc($locais)): 
            ?>
                <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Campo de Busca para o Novo Responsável -->
    <div>
        <label for="search_usuario_input">Novo Responsável</label>
        <input type="text" id="search_usuario_input" placeholder="Digite para buscar um responsável..." autocomplete="off" required>
        <input type="hidden" name="novo_responsavel_id" id="novo_responsavel_id"> <!-- Campo oculto para guardar o ID do usuário selecionado -->
        <div id="usuario_search_results" class="search-results"></div> <!-- Container para os resultados da busca de usuário -->
    </div>

    <!-- Botão de Submissão -->
    <div>
        <input type="submit" value="Registrar Movimentação">
    </div>
</form>

<!-- Bloco de JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Seleciona os elementos do DOM necessários para a interatividade.
    const localOrigemSelect = document.getElementById('local_origem_id');
    const itensDoLocalContainer = document.getElementById('itens_do_local_container');
    const form = document.querySelector('form');

    /**
     * Função para buscar e exibir os itens de um determinado local.
     * @param {string} locationId - O ID do local selecionado.
     */
    function fetchItemsForLocation(locationId) {
        // Se nenhum local for selecionado, limpa o container de itens.
        if (!locationId) {
            itensDoLocalContainer.innerHTML = '';
            return;
        }
        // Faz uma chamada AJAX para a API que retorna os itens do local.
        fetch(`api/get_items_by_location.php?location_id=${locationId}`)
            .then(response => response.json())
            .then(data => {
                let html = '<h3>Itens neste Local:</h3>';
                if (data.length > 0) {
                    // Adiciona um checkbox "Selecionar Todos" para facilitar a seleção em massa.
                    html += '<div style="margin-bottom: 10px;"><input type="checkbox" id="select_all_items"> <label for="select_all_items">Selecionar Todos</label></div>';
                    html += '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">';
                    // Cria um checkbox para cada item retornado.
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
                // Insere o HTML gerado no container.
                itensDoLocalContainer.innerHTML = html;

                // Adiciona a funcionalidade ao checkbox "Selecionar Todos".
                const selectAllCheckbox = document.getElementById('select_all_items');
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function() {
                        const checkboxes = itensDoLocalContainer.querySelectorAll('input[type="checkbox"][name="selected_items[]"]');
                        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao buscar itens:', error);
                itensDoLocalContainer.innerHTML = '<p>Erro ao carregar itens.</p>';
            });
    }

    // Adiciona um listener para o evento 'change' no seletor de local de origem.
    localOrigemSelect.addEventListener('change', function() {
        fetchItemsForLocation(this.value);
    });

    // Se um local de origem já estiver selecionado ao carregar a página, busca os itens.
    if (localOrigemSelect.value) {
        fetchItemsForLocation(localOrigemSelect.value);
    }

    // --- LÓGICA PARA BUSCA DINÂMICA DE USUÁRIOS ---
    const searchUsuarioInput = document.getElementById('search_usuario_input');
    const usuarioSearchResults = document.getElementById('usuario_search_results');
    const novoResponsavelIdInput = document.getElementById('novo_responsavel_id');
    let debounceUserSearchTimeout; // Variável para controlar o debounce.

    // Listener para o evento 'input' no campo de busca de usuário.
    searchUsuarioInput.addEventListener('input', function() {
        clearTimeout(debounceUserSearchTimeout);
        const searchTerm = this.value;
        usuarioSearchResults.innerHTML = ''; // Limpa resultados anteriores.
        novoResponsavelIdInput.value = ''; // Limpa o ID do responsável oculto.

        // Não busca se o termo for muito curto.
        if (searchTerm.length < 2) return;

        // Debounce: Atraso de 300ms para evitar chamadas excessivas à API enquanto o usuário digita.
        debounceUserSearchTimeout = setTimeout(() => {
            fetch(`api/search_usuarios.php?term=${searchTerm}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        usuarioSearchResults.innerHTML = '<div class="search-result-item">Nenhum usuário encontrado</div>';
                        return;
                    }
                    // Cria um elemento `div` para cada usuário encontrado.
                    data.forEach(user => {
                        const div = document.createElement('div');
                        div.textContent = user.nome;
                        div.classList.add('search-result-item');
                        // Adiciona um listener de clique para selecionar o usuário.
                        div.addEventListener('click', function() {
                            searchUsuarioInput.value = user.nome; // Preenche o campo de busca com o nome.
                            novoResponsavelIdInput.value = user.id; // Preenche o campo oculto com o ID.
                            usuarioSearchResults.innerHTML = ''; // Limpa os resultados.
                        });
                        usuarioSearchResults.appendChild(div);
                    });
                })
                .catch(error => console.error('Erro ao buscar usuários:', error));
        }, 300);
    });

    // --- VALIDAÇÃO ANTES DO ENVIO DO FORMULÁRIO ---
    form.addEventListener('submit', function(event) {
        // Verifica se pelo menos um item foi selecionado.
        const selectedItems = itensDoLocalContainer.querySelectorAll('input[type="checkbox"][name="selected_items[]"]:checked');
        if (selectedItems.length === 0) {
            alert('Por favor, selecione pelo menos um item para movimentar.');
            event.preventDefault(); // Impede o envio do formulário.
        }
        // Verifica se um novo responsável foi selecionado da busca.
        if (!novoResponsavelIdInput.value) {
            alert('Por favor, selecione um novo responsável da lista de busca.');
            event.preventDefault(); // Impede o envio do formulário.
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>