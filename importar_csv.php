<?php
/**
 * Página para importar movimentações de itens via arquivo CSV.
 * Permite que um administrador mova vários itens de um local de origem para um local de destino,
 * atribuindo-os a um novo responsável, com base nos dados de um arquivo CSV.
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
$mensagem = '';
$tipo_mensagem = '';

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Coleta dos dados enviados pelo formulário.
    $local_origem_id = $_POST['local_origem_id'] ?? '';
    $local_destino_id = $_POST['local_destino_id'] ?? '';
    $novo_responsavel_id = $_POST['novo_responsavel_id'] ?? '';
    $usuario_id = $_SESSION['id']; // ID do administrador que está realizando a operação.
    
    // Validação para garantir que todos os campos necessários foram preenchidos.
    if (empty($local_origem_id) || empty($local_destino_id) || empty($novo_responsavel_id)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios (local de origem, local de destino e novo responsável).";
        $tipo_mensagem = "danger";
    } else if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $mensagem = "Por favor, selecione um arquivo CSV válido para upload.";
        $tipo_mensagem = "danger";
    } else {
        // Verificar se o arquivo é realmente um CSV
        $file_type = mime_content_type($_FILES['csv_file']['tmp_name']);
        if ($file_type !== 'text/plain' && $file_type !== 'text/csv' && $file_type !== 'application/csv') {
            $mensagem = "O arquivo enviado não é um CSV válido.";
            $tipo_mensagem = "danger";
        } else {
            // Processar o arquivo CSV
            $csv_file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($csv_file, 'r');
            
            if ($handle) {
                // Ler o cabeçalho
                $header = fgetcsv($handle, 1000, ',');
                
                // Verificar se o cabeçalho contém as colunas esperadas
                if ($header && count($header) >= 1 && in_array('patrimonio', $header)) {
                    $patrimonio_index = array_search('patrimonio', $header);
                    $selected_items = [];
                    
                    // Ler as linhas do CSV
                    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                        if (isset($data[$patrimonio_index])) {
                            $patrimonio = trim($data[$patrimonio_index]);
                            if (!empty($patrimonio)) {
                                // Buscar o ID do item pelo patrimônio
                                $sql = "SELECT id FROM itens WHERE patrimonio_novo = ? AND local_id = ?";
                                $stmt = mysqli_prepare($link, $sql);
                                mysqli_stmt_bind_param($stmt, "si", $patrimonio, $local_origem_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                
                                if ($item = mysqli_fetch_assoc($result)) {
                                    $selected_items[] = $item['id'];
                                } else {
                                    $mensagem .= "Item com patrimônio '$patrimonio' não encontrado no local de origem.<br>";
                                    $tipo_mensagem = "warning";
                                }
                                mysqli_stmt_close($stmt);
                            }
                        }
                    }
                    
                    fclose($handle);
                    
                    // Se encontrou itens, processar a movimentação
                    if (!empty($selected_items)) {
                        // --- VERIFICAÇÕES DE SEGURANÇA ---
                        $itens_validos = true;
                        foreach ($selected_items as $item_id) {
                            // 1. Verificar se o item está com status "Confirmado"
                            $sql_check_status = "SELECT status_confirmacao FROM itens WHERE id = ?";
                            $stmt_check_status = mysqli_prepare($link, $sql_check_status);
                            mysqli_stmt_bind_param($stmt_check_status, "i", $item_id);
                            mysqli_stmt_execute($stmt_check_status);
                            $result_check_status = mysqli_stmt_get_result($stmt_check_status);
                            $item_status = mysqli_fetch_assoc($result_check_status);
                            mysqli_stmt_close($stmt_check_status);
                            
                            if (!$item_status) {
                                $mensagem = "Item com ID {$item_id} não encontrado.";
                                $tipo_mensagem = "danger";
                                $itens_validos = false;
                                break;
                            }
                            
                            if ($item_status['status_confirmacao'] !== 'Confirmado') {
                                $mensagem = "O item com ID {$item_id} não pode ser movimentado porque seu status não é 'Confirmado'. Status atual: '{$item_status['status_confirmacao']}'";
                                $tipo_mensagem = "danger";
                                $itens_validos = false;
                                break;
                            }
                            
                            // 2. Verificar se o item já possui uma solicitação de movimentação pendente
                            $sql_check_pending = "SELECT COUNT(*) FROM notificacoes_movimentacao WHERE item_id = ? AND status_confirmacao = 'Pendente'";
                            $stmt_check_pending = mysqli_prepare($link, $sql_check_pending);
                            mysqli_stmt_bind_param($stmt_check_pending, "i", $item_id);
                            mysqli_stmt_execute($stmt_check_pending);
                            $result_check_pending = mysqli_stmt_get_result($stmt_check_pending);
                            $pending_count = mysqli_fetch_row($result_check_pending)[0];
                            mysqli_stmt_close($stmt_check_pending);
                            
                            if ($pending_count > 0) {
                                $mensagem = "O item com ID {$item_id} não pode ser movimentado porque já se encontra pendente de confirmação de outro usuário.";
                                $tipo_mensagem = "danger";
                                $itens_validos = false;
                                break;
                            }
                        }
                        
                        if ($itens_validos) {
                            // --- TRANSAÇÃO DE BANCO DE DADOS ---
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

                                    if ($movimentacao_id) {
                                        // Insere a notificação individual para o item na tabela notificacoes_movimentacao.
                                        $sql_insert_notificacao_item = "INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao) VALUES (?, ?, ?, 'Pendente')";
                                        $stmt_insert_notificacao_item = mysqli_prepare($link, $sql_insert_notificacao_item);
                                        mysqli_stmt_bind_param($stmt_insert_notificacao_item, "iii", $movimentacao_id, $item_id, $novo_responsavel_id);
                                        $notif_exec_success = mysqli_stmt_execute($stmt_insert_notificacao_item);
                                        mysqli_stmt_close($stmt_insert_notificacao_item);
                                    }
                                }

                                // Se todas as operações foram bem-sucedidas, confirma a transação.
                                mysqli_commit($link);
                                $mensagem = "Movimentação de " . count($selected_items) . " itens realizada com sucesso!";
                                $tipo_mensagem = "success";
                            } catch (Exception $e) {
                                // Se ocorreu qualquer erro, reverte todas as operações no banco de dados.
                                mysqli_rollback($link);
                                $mensagem = "Erro ao processar a movimentação: " . $e->getMessage();
                                $tipo_mensagem = "danger";
                            }
                        }
                    } else {
                        if (empty($mensagem)) {
                            $mensagem = "Nenhum item válido encontrado no arquivo CSV.";
                            $tipo_mensagem = "warning";
                        }
                    }
                } else {
                    $mensagem = "Formato de CSV inválido. O arquivo deve conter uma coluna chamada 'patrimonio'.";
                    $tipo_mensagem = "danger";
                }
            } else {
                $mensagem = "Erro ao ler o arquivo CSV.";
                $tipo_mensagem = "danger";
            }
        }
    }
}

// Busca a lista de todos os locais para preencher os menus suspensos (dropdowns).
$locais = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
?>

<!-- Título da Página -->
<h2>Importar Movimentações via CSV</h2>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem; ?>">
        <?php echo $mensagem; ?>
    </div>
<?php endif; ?>

<!-- Instruções -->
<div class="alert alert-info">
    <strong>Instruções:</strong>
    <ol>
        <li>Selecione o <strong>Local de Origem</strong> dos itens.</li>
        <li>Selecione o <strong>Local de Destino</strong> para onde os itens serão movidos.</li>
        <li>Selecione o <strong>Novo Responsável</strong> pelos itens.</li>
        <li>Faça o upload de um arquivo CSV contendo os patrimônios dos itens a serem movimentados.</li>
        <li>Clique em "Importar Movimentações".</li>
    </ol>
    <p><strong>Formato do CSV:</strong> O arquivo deve conter uma coluna chamada <code>patrimonio</code>.</p>
    <p>Exemplo de conteúdo do CSV:</p>
    <pre>patrimonio
123456
789012
345678</pre>
</div>

<!-- Formulário de Importação -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
    <!-- Seleção do Local de Origem -->
    <div>
        <label for="local_origem_id">Local de Origem</label>
        <select name="local_origem_id" id="local_origem_id" required>
            <option value="">Selecione um local</option>
            <?php 
            $locais_origem = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
            while($local = mysqli_fetch_assoc($locais_origem)): 
            ?>
                <option value="<?php echo $local['id']; ?>" <?php echo (isset($_POST['local_origem_id']) && $_POST['local_origem_id'] == $local['id']) ? 'selected' : ''; ?>>
                    <?php echo $local['nome']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Seleção do Local de Destino -->
    <div>
        <label for="local_destino_id">Local de Destino</label>
        <select name="local_destino_id" id="local_destino_id" required>
            <option value="">Selecione um local</option>
            <?php 
            mysqli_data_seek($locais, 0);
            while($local = mysqli_fetch_assoc($locais)): 
            ?>
                <option value="<?php echo $local['id']; ?>" <?php echo (isset($_POST['local_destino_id']) && $_POST['local_destino_id'] == $local['id']) ? 'selected' : ''; ?>>
                    <?php echo $local['nome']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Campo de Busca para o Novo Responsável -->
    <div>
        <label for="search_usuario_input">Novo Responsável</label>
        <input type="text" id="search_usuario_input" placeholder="Digite para buscar um responsável..." autocomplete="off" required>
        <input type="hidden" name="novo_responsavel_id" id="novo_responsavel_id" value="<?php echo isset($_POST['novo_responsavel_id']) ? $_POST['novo_responsavel_id'] : ''; ?>"> <!-- Campo oculto para guardar o ID do usuário selecionado -->
        <div id="usuario_search_results" class="search-results"></div> <!-- Container para os resultados da busca de usuário -->
    </div>

    <!-- Upload do Arquivo CSV -->
    <div>
        <label for="csv_file">Arquivo CSV</label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required>
    </div>

    <!-- Botão de Submissão -->
    <div>
        <input type="submit" value="Importar Movimentações">
        <a href="movimentacoes.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<!-- Bloco de JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
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

    // Se já houver um responsável selecionado, buscar o nome
    if (novoResponsavelIdInput.value) {
        fetch(`api/search_usuarios.php?term=${encodeURIComponent(novoResponsavelIdInput.value)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const usuario = data.find(u => u.id == novoResponsavelIdInput.value);
                    if (usuario) {
                        searchUsuarioInput.value = usuario.nome;
                    }
                }
            })
            .catch(error => console.error('Erro ao buscar nome do responsável:', error));
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>