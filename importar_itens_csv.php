<?php
/**
 * Página para importar itens via arquivo CSV para movimentação.
 * Permite que um administrador faça upload de um arquivo CSV contendo
 * informações sobre os itens a serem movimentados, e então registrar
 * essas movimentações no sistema.
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
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file'])){
    // Verifica se o arquivo foi enviado sem erros
    if ($_FILES['csv_file']['error'] == 0) {
        // Verifica se o arquivo é realmente um CSV
        $fileType = mime_content_type($_FILES['csv_file']['tmp_name']);
        if ($fileType == 'text/plain' || $fileType == 'text/csv' || $fileType == 'application/vnd.ms-excel') {
            // Processa o arquivo CSV
            $csvFile = fopen($_FILES['csv_file']['tmp_name'], 'r');
            
            // Verifica se foi possível abrir o arquivo
            if ($csvFile) {
                // Lê o cabeçalho do CSV
                $header = fgetcsv($csvFile, 1000, ',');
                
                // Verifica se o cabeçalho está correto
                if ($header && count($header) >= 1) {
                    // Array para armazenar os dados dos itens
                    $itemsData = [];
                    
                    // Lê os dados do CSV
                    while (($data = fgetcsv($csvFile, 1000, ',')) !== FALSE) {
                        // Verifica se a linha tem o número correto de colunas
                        if (count($data) >= 1) {
                            $itemsData[] = [
                                'item_id' => trim($data[0])
                            ];
                        }
                    }
                    
                    fclose($csvFile);
                    
                    // Verifica se há dados para processar
                    if (count($itemsData) > 0) {
                        // Coleta os dados do formulário
                        $local_destino_id = $_POST['local_destino_id'];
                        $novo_responsavel_id = $_POST['novo_responsavel_id'];
                        $usuario_id = $_SESSION['id'];
                        
                        // Validação para garantir que todos os campos necessários foram preenchidos
                        if (empty($local_destino_id) || empty($novo_responsavel_id)) {
                            echo "<div class='alert alert-danger'>Por favor, selecione um local de destino e um novo responsável.</div>";
                        } else {
                            // Extrai apenas os IDs dos itens
                            $selected_items = array_column($itemsData, 'item_id');
                            
                            // --- VERIFICAÇÕES DE SEGURANÇA ---
                            // Verificar se os itens podem ser movimentados
                            $canMove = true;
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
                                    echo "<div class='alert alert-danger'>Item com ID {$item_id} não encontrado.</div>";
                                    $canMove = false;
                                    break;
                                }
                                
                                if ($item_status['status_confirmacao'] !== 'Confirmado') {
                                    echo "<div class='alert alert-danger'>O item com ID {$item_id} não pode ser movimentado porque seu status não é 'Confirmado'. Status atual: '{$item_status['status_confirmacao']}'</div>";
                                    $canMove = false;
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
                                    echo "<div class='alert alert-danger'>O item com ID {$item_id} não pode ser movimentado porque já se encontra pendente de confirmação de outro usuário.</div>";
                                    $canMove = false;
                                    break;
                                }
                            }
                            
                            if ($canMove) {
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
                                    // Exibe mensagem de sucesso
                                    echo "<div class='alert alert-success'>Movimentação de " . count($selected_items) . " itens registrada com sucesso!</div>";
                                } catch (Exception $e) {
                                    // Se ocorreu qualquer erro, reverte todas as operações no banco de dados.
                                    mysqli_rollback($link);
                                    echo "<div class='alert alert-danger'>Oops! Algo deu errado. " . $e->getMessage() . "</div>";
                                }
                            }
                        }
                    } else {
                        echo "<div class='alert alert-danger'>Nenhum dado válido encontrado no arquivo CSV.</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>Formato de cabeçalho inválido no arquivo CSV.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Erro ao abrir o arquivo CSV.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Por favor, envie um arquivo CSV válido.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Erro no envio do arquivo. Código de erro: " . $_FILES['csv_file']['error'] . "</div>";
    }
}

// Busca a lista de todos os locais para preencher os menus suspensos (dropdowns).
$locais = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
?>

<!-- Título da Página -->
<h2>Importar Itens via CSV para Movimentação</h2>

<p>Esta funcionalidade permite importar uma lista de itens para movimentação através de um arquivo CSV.</p>

<!-- Instruções para o formato do CSV -->
<div class="alert alert-info">
    <strong>Instruções:</strong>
    <ul>
        <li>O arquivo CSV deve conter apenas uma coluna com o ID dos itens a serem movimentados.</li>
        <li>A primeira linha do arquivo (cabeçalho) deve conter o texto "item_id".</li>
        <li>Exemplo de conteúdo do arquivo CSV:
            <pre>item_id
1
2
3</pre>
        </li>
    </ul>
</div>

<!-- Formulário de Importação -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
    <!-- Upload do Arquivo CSV -->
    <div>
        <label for="csv_file">Arquivo CSV:</label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
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
        <input type="submit" value="Importar e Registrar Movimentação">
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
});
</script>

<?php require_once 'includes/footer.php'; ?>