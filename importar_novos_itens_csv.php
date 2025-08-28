<?php
/**
 * Página para importar novos itens via arquivo CSV.
 * Permite que um administrador faça upload de um arquivo CSV contendo
 * patrimônio e descrição dos itens a serem cadastrados, e então registrar
 * esses itens no sistema com notificação automática.
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
    $local_id = $_POST['local_id'] ?? '';
    $responsavel_id = $_POST['responsavel_id'] ?? '';
    $usuario_id = $_SESSION['id']; // ID do administrador que está realizando a operação.
    
    // Validação para garantir que todos os campos necessários foram preenchidos.
    if (empty($local_id) || empty($responsavel_id)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios (local e responsável).";
        $tipo_mensagem = "danger";
    } else if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $mensagem = "Por favor, selecione um arquivo CSV válido para upload. Erro do arquivo: " . $_FILES['csv_file']['error'];
        $tipo_mensagem = "danger";
    } else {
        // Verificar se o arquivo é realmente um CSV
        $file_type = mime_content_type($_FILES['csv_file']['tmp_name']);
        // Adicionar verificação adicional para arquivos CSV com diferentes tipos MIME
        $allowed_types = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'];
        if (!in_array($file_type, $allowed_types)) {
            $mensagem = "O arquivo enviado não é um CSV válido. Tipo detectado: " . $file_type . ". Tipos permitidos: " . implode(', ', $allowed_types);
            $tipo_mensagem = "danger";
        } else {
            // Processar o arquivo CSV
            $csv_file = $_FILES['csv_file']['tmp_name'];
            // Abrir o arquivo com a codificação UTF-8 para lidar com caracteres especiais
            $handle = fopen($csv_file, 'r');
            
            if ($handle) {
                // Tentar detectar a codificação do arquivo
                $sample = fread($handle, 1024);
                fclose($handle);
                
                // Reabrir o arquivo
                $handle = fopen($csv_file, 'r');
                
                // Ler o cabeçalho
                $header = fgetcsv($handle, 1000, ',');
                
                // Verificar se o cabeçalho contém as colunas esperadas
                // Tratar possíveis problemas de codificação
                if ($header) {
                    // Mostrar os cabeçalhos para depuração
                    // error_log("Cabeçalhos do CSV: " . print_r($header, true));
                    
                    // Tentar corrigir a codificação dos cabeçalhos se necessário
                    $header = array_map(function($item) {
                        // Se o item não estiver em UTF-8, tentar converter
                        if (!mb_check_encoding($item, 'UTF-8')) {
                            return mb_convert_encoding($item, 'UTF-8', 'auto');
                        }
                        return $item;
                    }, $header);
                    
                    // Normalizar os cabeçalhos para comparação
                    $normalized_header = array_map('trim', $header);
                    $normalized_header = array_map('mb_strtoupper', $normalized_header);
                    
                    // Verificar se contém as colunas esperadas (com tratamento de maiúsculas)
                    $has_patrimonio = false;
                    $has_descricao = false;
                    
                    foreach ($normalized_header as $col) {
                        if (strpos($col, 'PATRIMÔNIO') !== false && strpos($col, 'NOVO') !== false) {
                            $has_patrimonio = true;
                        }
                        if (strpos($col, 'DESCRIÇÃO') !== false || strpos($col, 'DESCRICAO') !== false) {
                            $has_descricao = true;
                        }
                    }
                }
                
                // Adicionar verificação adicional para mostrar os cabeçalhos normalizados
                // error_log("Cabeçalhos normalizados: " . print_r($normalized_header, true));
                // error_log("Tem patrimônio: " . ($has_patrimonio ? 'Sim' : 'Não'));
                // error_log("Tem descrição: " . ($has_descricao ? 'Sim' : 'Não'));
                
                if ($header && count($header) >= 2 && $has_patrimonio && $has_descricao) {
                    
                    // Encontrar os índices corretos das colunas
                    $patrimonio_index = null;
                    $descricao_index = null;
                    $patrimonio_secundario_index = null;
                    
                    // Normalizar cabeçalhos para comparação
                    $normalized_header = array_map('trim', $header);
                    $normalized_header = array_map('mb_strtoupper', $normalized_header);
                    
                    foreach ($header as $index => $column) {
                        $normalized_column = mb_strtoupper(trim($column));
                        if (strpos($normalized_column, 'PATRIMÔNIO') !== false && strpos($normalized_column, 'NOVO') !== false) {
                            $patrimonio_index = $index;
                        }
                        if (strpos($normalized_column, 'DESCRIÇÃO') !== false || strpos($normalized_column, 'DESCRICAO') !== false) {
                            $descricao_index = $index;
                        }
                        if (strpos($normalized_column, 'PATRIMÔNIO') !== false && strpos($normalized_column, 'ANTIGO') !== false) {
                            $patrimonio_secundario_index = $index;
                        }
                    }
                    $novos_itens = [];
                    
                    // Verificar se os índices foram encontrados
                    if ($patrimonio_index === null || $descricao_index === null) {
                        $mensagem = "Não foi possível encontrar as colunas necessárias no arquivo CSV. Verifique se as colunas estão nomeadas corretamente.";
                        $tipo_mensagem = "danger";
                        fclose($handle);
                        return;
                    }
                    
                    // Ler as linhas do CSV
                    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                        // Verificar se a linha tem o número correto de colunas
                        if (count($data) < 2) {
                            continue; // Pular linhas inválidas
                        }
                        
                        if (isset($data[$patrimonio_index]) && isset($data[$descricao_index])) {
                            $patrimonio = trim($data[$patrimonio_index]);
                            $descricao = trim($data[$descricao_index]);
                            // Obter patrimônio secundário se a coluna existir
                            $patrimonio_secundario = '';
                            if ($patrimonio_secundario_index !== null && isset($data[$patrimonio_secundario_index])) {
                                $patrimonio_secundario = trim($data[$patrimonio_secundario_index]);
                            }
                            
                            // Verificar se os campos obrigatórios não estão vazios
                    if (!empty($patrimonio) && !empty($descricao)) {
                        // Verificar se o patrimônio já existe
                        $sql_check = "SELECT id FROM itens WHERE patrimonio_novo = ?";
                        $stmt_check = mysqli_prepare($link, $sql_check);
                        mysqli_stmt_bind_param($stmt_check, "s", $patrimonio);
                        mysqli_stmt_execute($stmt_check);
                        $result_check = mysqli_stmt_get_result($stmt_check);
                        
                        if (mysqli_fetch_assoc($result_check)) {
                            $mensagem .= "Item com patrimônio '$patrimonio' já existe e foi ignorado.<br>";
                            $tipo_mensagem = "warning";
                        } else {
                            $novos_itens[] = [
                                'patrimonio' => $patrimonio,
                                'descricao' => $descricao,
                                'patrimonio_secundario' => $patrimonio_secundario
                            ];
                        }
                        mysqli_stmt_close($stmt_check);
                    } else {
                        // Adicionar mensagem de depuração para campos vazios
                        if (empty($patrimonio)) {
                            $mensagem .= "Patrimônio vazio encontrado na linha.<br>";
                        }
                        if (empty($descricao)) {
                            $mensagem .= "Descrição vazia encontrada na linha.<br>";
                        }
                        $tipo_mensagem = "warning";
                    }
                        }
                    }
                    
                    fclose($handle);
                    
                    // Se encontrou itens novos, processar a inserção
                    if (!empty($novos_itens)) {
                        // Mostrar informações de depuração
                        // error_log("Número de itens a importar: " . count($novos_itens));
                        // error_log("Primeiro item: " . print_r($novos_itens[0], true));
                        
                        // --- TRANSAÇÃO DE BANCO DE DADOS ---
                        mysqli_begin_transaction($link);
                        try {
                            $itens_inseridos = 0;
                            
                            // Itera sobre cada item para inserção
                            foreach ($novos_itens as $item) {
                                $patrimonio = $item['patrimonio'];
                                $descricao = $item['descricao'];
                                $patrimonio_secundario = $item['patrimonio_secundario'];
                                $estado = 'Em uso'; // Padrão
                                $observacao = ''; // Padrão vazio
                                $status_confirmacao = ((int)$responsavel_id === $usuario_id) ? 'Confirmado' : 'Pendente';
                                
                                // 1. Inserir o item na tabela `itens`
                                $sql_insert = "INSERT INTO itens (nome, patrimonio_novo, patrimonio_secundario, local_id, responsavel_id, estado, observacao, data_cadastro, status_confirmacao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
                                $stmt_insert = mysqli_prepare($link, $sql_insert);
                                mysqli_stmt_bind_param($stmt_insert, "sssiisss", $descricao, $patrimonio, $patrimonio_secundario, $local_id, $responsavel_id, $estado, $observacao, $status_confirmacao);
                                
                                if (mysqli_stmt_execute($stmt_insert)) {
                                    $novo_item_id = mysqli_insert_id($link);
                                    $itens_inseridos++;
                                    
                                    // 2. Notificação de novo item via notificacoes_movimentacao (pendente para confirmação)
                                    if ($status_confirmacao === 'Pendente') {
                                        // a) Criar uma movimentação inicial (cadastro) com origem = destino = local atual
                                        $sql_mov = "INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id, usuario_destino_id) VALUES (?, ?, ?, ?, NULL, ?)";
                                        $stmt_mov = mysqli_prepare($link, $sql_mov);
                                        if ($stmt_mov) {
                                            mysqli_stmt_bind_param($stmt_mov, "iiiii", $novo_item_id, $local_id, $local_id, $usuario_id, $responsavel_id);
                                            mysqli_stmt_execute($stmt_mov);
                                            $movimentacao_id = mysqli_insert_id($link);
                                            mysqli_stmt_close($stmt_mov);
                                            
                                            // b) Criar a notificação pendente atrelada à movimentação
                                            $sql_nm = "INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao) VALUES (?, ?, ?, 'Pendente')";
                                            $stmt_nm = mysqli_prepare($link, $sql_nm);
                                            if ($stmt_nm) {
                                                mysqli_stmt_bind_param($stmt_nm, "iii", $movimentacao_id, $novo_item_id, $responsavel_id);
                                                mysqli_stmt_execute($stmt_nm);
                                                mysqli_stmt_close($stmt_nm);
                                            }
                                        }
                                    }
                                }
                                mysqli_stmt_close($stmt_insert);
                            }
                            
                            // Se todas as operações foram bem-sucedidas, confirma a transação.
                            mysqli_commit($link);
                            $mensagem = "Importação realizada com sucesso! $itens_inseridos itens foram cadastrados.";
                            $tipo_mensagem = "success";
                        } catch (Exception $e) {
                            // Se ocorreu qualquer erro, reverte todas as operações no banco de dados.
                            mysqli_rollback($link);
                            $mensagem = "Erro ao processar a importação: " . $e->getMessage();
                            $tipo_mensagem = "danger";
                        }
                    } else {
                        if (empty($mensagem)) {
                            $mensagem = "Nenhum item novo encontrado no arquivo CSV. Verifique se os dados estão preenchidos corretamente e se o arquivo contém as colunas esperadas.";
                            $tipo_mensagem = "warning";
                        }
                    }
                } else {
                    $mensagem = "Formato de CSV inválido. O arquivo deve conter colunas chamadas 'PATRIMÔNIO NOVO', 'PATRIMÔNIO ANTIGO' (opcional) e 'DESCRIÇÃO'. Cabeçalhos encontrados: " . implode(', ', $header) . ". Verifique se os nomes das colunas estão exatamente como especificado.";
                    $tipo_mensagem = "danger";
                }
            } else {
                $mensagem = "Erro ao ler o arquivo CSV. Verifique se o arquivo está corrompido ou protegido.";
                $tipo_mensagem = "danger";
            }
        }
    }
}

// Busca a lista de todos os locais e usuários para preencher os menus suspensos (dropdowns).
$locais = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
$usuarios = mysqli_query($link, "SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC");
?>

<!-- Título da Página -->
<h2>Importar Novos Itens via CSV</h2>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem; ?>">
        <?php echo $mensagem; ?>
    </div>
<?php endif; ?>

<!-- Instruções -->
<div class="alert alert-info">
    <strong>Instruções:</strong>
    <ol>
        <li>Selecione o <strong>Local</strong> para os itens.</li>
        <li>Selecione o <strong>Responsável</strong> pelos itens.</li>
        <li>Faça o upload de um arquivo CSV contendo patrimônio e descrição dos itens a serem cadastrados.</li>
        <li>Clique em "Importar Itens".</li>
    </ol>
    <p><strong>Formato do CSV:</strong> O arquivo deve conter colunas chamadas <code>"PATRIMÔNIO NOVO"</code>, <code>"PATRIMÔNIO ANTIGO"</code> e <code>DESCRIÇÃO</code>.</p>
    <p>Exemplo de conteúdo do CSV:</p>
    <pre>"PATRIMÔNIO NOVO","PATRIMÔNIO ANTIGO",DESCRIÇÃO
123456,ABC123,Notebook Dell Inspiron
789012,XYZ789,Monitor LCD 24 polegadas
345678,DEF456,Teclado sem fio</pre>
</div>

<!-- Formulário de Importação -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
    <!-- Seleção do Local -->
    <div>
        <label for="local_id">Local</label>
        <select name="local_id" id="local_id" required>
            <option value="">Selecione um local</option>
            <?php 
            while($local = mysqli_fetch_assoc($locais)): 
            ?>
                <option value="<?php echo $local['id']; ?>" <?php echo (isset($_POST['local_id']) && $_POST['local_id'] == $local['id']) ? 'selected' : ''; ?>>
                    <?php echo $local['nome']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Campo de Busca para o Responsável -->
    <div>
        <label for="search_usuario_input">Responsável</label>
        <input type="text" id="search_usuario_input" placeholder="Digite para buscar um responsável..." autocomplete="off" required>
        <input type="hidden" name="responsavel_id" id="responsavel_id" value="<?php echo isset($_POST['responsavel_id']) ? $_POST['responsavel_id'] : ''; ?>"> <!-- Campo oculto para guardar o ID do usuário selecionado -->
        <div id="usuario_search_results" class="search-results"></div> <!-- Container para os resultados da busca de usuário -->
    </div>

    <!-- Upload do Arquivo CSV -->
    <div>
        <label for="csv_file">Arquivo CSV</label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required>
    </div>

    <!-- Botão de Submissão -->
    <div>
        <input type="submit" value="Importar Itens">
        <a href="itens.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<!-- Bloco de JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- LÓGICA PARA BUSCA DINÂMICA DE USUÁRIOS ---
    const searchUsuarioInput = document.getElementById('search_usuario_input');
    const usuarioSearchResults = document.getElementById('usuario_search_results');
    const responsavelIdInput = document.getElementById('responsavel_id');
    let debounceUserSearchTimeout; // Variável para controlar o debounce.

    // Listener para o evento 'input' no campo de busca de usuário.
    searchUsuarioInput.addEventListener('input', function() {
        clearTimeout(debounceUserSearchTimeout);
        const searchTerm = this.value;
        usuarioSearchResults.innerHTML = ''; // Limpa resultados anteriores.
        responsavelIdInput.value = ''; // Limpa o ID do responsável oculto.

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
                            responsavelIdInput.value = user.id; // Preenche o campo oculto com o ID.
                            usuarioSearchResults.innerHTML = ''; // Limpa os resultados.
                        });
                        usuarioSearchResults.appendChild(div);
                    });
                })
                .catch(error => console.error('Erro ao buscar usuários:', error));
        }, 300);
    });

    // Se já houver um responsável selecionado, buscar o nome
    if (responsavelIdInput.value) {
        fetch(`api/search_usuarios.php?term=${encodeURIComponent(responsavelIdInput.value)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const usuario = data.find(u => u.id == responsavelIdInput.value);
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