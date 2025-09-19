<?php
// Inclusão de arquivos essenciais e inicialização de sessão
require_once '../includes/header.php';
require_once '../config/db.php'; // Garante que a conexão PDO $pdo seja inicializada

// --- CONTROLE DE ACESSO ---
// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Verifica se o usuário tem uma das permissões necessárias para acessar a página.
// Permissões permitidas: Administrador, Almoxarife, Visualizador, Gestor.
if (!in_array($_SESSION["permissao"], ['Administrador', 'Almoxarife', 'Visualizador', 'Gestor'])) {
    // Se não tiver a permissão, redireciona para a página inicial do almoxarifado.
    header("location: index.php");
    exit;
}

// --- PREPARAÇÃO DE DADOS ---
// Busca todos os locais cadastrados para preencher o campo de destino da requisição.
$sql_locais = "SELECT id, nome FROM locais ORDER BY nome";
$stmt_locais = $pdo->prepare($sql_locais);
$stmt_locais->execute();
$locais = $stmt_locais->fetchAll(PDO::FETCH_ASSOC);

// Inicialização de variáveis para mensagens de feedback ao usuário.
$message = "";
$message_type = "";

// Verificar se há itens pré-selecionados vindos do index (via GET ou POST)
$itens_pre_selecionados = [];

// Via GET (nova implementação)
if (isset($_GET['material_id']) && is_array($_GET['material_id'])) {
    foreach ($_GET['material_id'] as $index => $material_id) {
        $material_nome = $_GET['material_nome'][$index] ?? '';
        if (!empty($material_id) && !empty($material_nome)) {
            $itens_pre_selecionados[] = [
                'id' => (int)$material_id,
                'nome' => $material_nome
            ];
        }
    }
}
// Via POST (compatibilidade)
elseif (!empty($_POST['produto_id']) && !empty($_POST['material_nome']) && empty($_POST['local_id'])) {
    for ($i = 0; $i < count($_POST['produto_id']); $i++) {
        $itens_pre_selecionados[] = [
            'id' => $_POST['produto_id'][$i],
            'nome' => $_POST['material_nome'][$i]
        ];
    }
    $_POST = [];
}

// --- PROCESSAMENTO DO FORMULÁRIO (MÉTODO POST) ---
// Verifica se o formulário foi enviado.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['local_id'])) {
    // Coleta e sanitiza os dados do formulário.
    $local_id = isset($_POST['local_id']) ? (int)$_POST['local_id'] : 0;
    $justificativa = isset($_POST['justificativa']) ? trim($_POST['justificativa']) : '';
    $produtos_selecionados = isset($_POST['produto_id']) ? $_POST['produto_id'] : [];
    $quantidades = isset($_POST['quantidade']) ? $_POST['quantidade'] : [];

    // --- VERIFICAÇÃO DE JUSTIFICATIVA ---
    // Verifica se algum item solicitado ultrapassa a quantidade máxima permitida por requisição.
    $exige_justificativa = false;
    $itens_com_excesso = [];
    
    for ($i = 0; $i < count($produtos_selecionados); $i++) {
        $produto_id = (int)$produtos_selecionados[$i];
        $quantidade = isset($quantidades[$i]) ? (int)$quantidades[$i] : 0;

        if ($produto_id > 0 && $quantidade > 0) {
            // Consulta a quantidade máxima para o material específico.
            $sql_max = "SELECT nome, quantidade_maxima_requisicao FROM almoxarifado_materiais WHERE id = ?";
            $stmt_max = $pdo->prepare($sql_max);
            $stmt_max->execute([$produto_id]);
            $material_info = $stmt_max->fetch(PDO::FETCH_ASSOC);
            
            // Se a quantidade solicitada for maior que a máxima permitida, marca a necessidade de justificativa.
            if ($material_info && $material_info['quantidade_maxima_requisicao'] !== null && $quantidade > $material_info['quantidade_maxima_requisicao']) {
                $exige_justificativa = true;
                $itens_com_excesso[] = $material_info['nome'];
            }
        }
    }

    // --- VALIDAÇÃO DOS DADOS DE ENTRADA ---
    $erros_validacao = [];
    if (empty($local_id)) {
        $erros_validacao[] = "O campo 'Local de Destino' é obrigatório.";
    }
    // A justificativa só é obrigatória se a flag $exige_justificativa for verdadeira.
    if ($exige_justificativa && empty($justificativa)) {
        $erros_validacao[] = "O campo 'Justificativa' é obrigatório, pois alguns itens ultrapassam a quantidade máxima permitida.";
    }
    if (empty($produtos_selecionados) || empty($produtos_selecionados[0])) {
        $erros_validacao[] = "É necessário adicionar pelo menos um material à requisição.";
    }

    // Se houver erros de validação
    if (!empty($erros_validacao)) {
        // Para erro de justificativa, apenas definir flag para popup
        if ($exige_justificativa && empty($justificativa)) {
            $show_justificativa_popup = true;
        } else {
            $message = implode("<br>", $erros_validacao);
            $message_type = "danger";
        }
    } else {
        // --- VALIDAÇÃO DE ESTOQUE E ITENS ---
        $itens_validos = [];
        $erros = [];

        for ($i = 0; $i < count($produtos_selecionados); $i++) {
            $produto_id = (int)$produtos_selecionados[$i];
            $quantidade = isset($quantidades[$i]) ? (int)$quantidades[$i] : 0;

            if ($produto_id > 0 && $quantidade > 0) {
                // Verifica o estoque atual do material.
                $sql_estoque = "SELECT nome, estoque_atual, quantidade_maxima_requisicao FROM almoxarifado_materiais WHERE id = ?";
                $stmt_estoque = $pdo->prepare($sql_estoque);
                $stmt_estoque->execute([$produto_id]);
                $material = $stmt_estoque->fetch(PDO::FETCH_ASSOC);

                // Se a quantidade solicitada for menor ou igual ao estoque, o item é considerado válido.
                if ($material && $quantidade <= $material['estoque_atual']) {
                    $itens_validos[] = [
                        'produto_id' => $produto_id,
                        'quantidade' => $quantidade,
                        'nome' => $material['nome'],
                        'quantidade_maxima_requisicao' => $material['quantidade_maxima_requisicao']
                    ];
                } else {
                    // Caso contrário, adiciona um erro.
                    $erros[] = "Quantidade solicitada para " . ($material ? htmlspecialchars($material['nome']) : "material desconhecido") . " excede o estoque disponível.";
                }
            }
        }

        // Se houver erros de estoque, exibe-os.
        if (!empty($erros)) {
            $message = "Erros encontrados:<br>" . implode("<br>", $erros);
            $message_type = "danger";
        } elseif (empty($itens_validos)) {
            // Se nenhum item válido foi adicionado.
            $message = "Nenhum item válido foi adicionado à requisição.";
            $message_type = "danger";
        } else {
            // --- CRIAÇÃO DA REQUISIÇÃO E NOTIFICAÇÕES (TRANSAÇÃO) ---
            $pdo->beginTransaction(); // Inicia uma transação para garantir a integridade dos dados.

            try {
                // 1. Insere a requisição na tabela principal.
                $sql_requisicao = "INSERT INTO almoxarifado_requisicoes (usuario_id, local_id, data_requisicao, justificativa) 
                                   VALUES (?, ?, NOW(), ?)";
                $stmt_requisicao = $pdo->prepare($sql_requisicao);
                $stmt_requisicao->execute([$_SESSION['id'], $local_id, $justificativa]);
                $requisicao_id = $pdo->lastInsertId(); // Obtém o ID da requisição recém-criada.

                // 2. Insere os itens da requisição na tabela de itens.
                // Detectar automaticamente o nome da coluna
                $sql_check_column = "SHOW COLUMNS FROM almoxarifado_requisicoes_itens";
                $stmt_check = $pdo->prepare($sql_check_column);
                $stmt_check->execute();
                $columns = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
                
                $column_name = 'produto_id'; // padrão
                foreach ($columns as $col) {
                    if ($col['Field'] == 'material_id') {
                        $column_name = 'material_id';
                        break;
                    } elseif ($col['Field'] == 'produto_id') {
                        $column_name = 'produto_id';
                        break;
                    }
                }
                
                foreach ($itens_validos as $item) {
                    $sql_item = "INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, $column_name, quantidade_solicitada) 
                                 VALUES (?, ?, ?)";
                    $stmt_item = $pdo->prepare($sql_item);
                    $stmt_item->execute([$requisicao_id, $item['produto_id'], $item['quantidade']]);
                }

                // 3. Cria notificações para administradores e almoxarifes.
                $sql_users = "SELECT u.id FROM usuarios u JOIN perfis p ON u.permissao_id = p.id WHERE p.nome IN ('Administrador', 'Almoxarife')";
                $stmt_users = $pdo->prepare($sql_users);
                $stmt_users->execute();
                $users_to_notify = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($users_to_notify)) {
                    foreach ($users_to_notify as $user) {
                        $user_id = $user['id'];
                        $mensagem_notificacao = "Nova requisição de materiais #{$requisicao_id} criada por {$_SESSION['nome']}.";
                        $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes 
                            (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) 
                            VALUES (?, ?, ?, 'nova_requisicao', ?, 'pendente')";
                        $stmt_notificacao = $pdo->prepare($sql_notificacao);
                        $stmt_notificacao->execute([$requisicao_id, $_SESSION['id'], $user_id, $mensagem_notificacao]);
                    }
                }

                // Se todas as operações foram bem-sucedidas, confirma a transação.
                $pdo->commit();
                $message = "Requisição criada com sucesso! Aguarde a aprovação.";
                $message_type = "success";
                // Limpar dados apenas em caso de sucesso
                $local_id = 0;
                $justificativa = '';
                $produtos_selecionados = [];
                $quantidades = [];

            } catch (Exception $e) {
                // Se ocorrer qualquer erro, reverte a transação.
                $pdo->rollback();
                $message = "Erro ao criar requisição: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}
?>

<!-- --- SEÇÃO DE CABEÇALHO E MENU --- -->
<div class="almoxarifado-header">
    <h2>Nova Requisição</h2>
    <?php
    // Verifica se o usuário tem privilégios para ver certas opções do menu.
    $is_privileged_user = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);
    require_once 'menu_almoxarifado.php'; // Inclui o menu de navegação do almoxarifado.
    ?>
</div>

<!-- Exibe mensagens de feedback (sucesso ou erro) -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- --- FORMULÁRIO DE NOVA REQUISIÇÃO --- -->
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <!-- Seção de Dados da Requisição (Destino e Justificativa) -->
    <div class="almoxarifado-form-section">
        <h3>Dados da Requisição</h3>
        <div class="form-group">
            <label for="local_autocomplete">Local de Destino</label>
            <div class="autocomplete-container">
                <input type="text" id="local_autocomplete" class="autocomplete-input" placeholder="Digite o nome do local..." value="<?php echo isset($_POST['local_nome']) ? htmlspecialchars($_POST['local_nome']) : ''; ?>">
                <input type="hidden" name="local_id" id="local_id" value="<?php echo isset($_POST['local_id']) ? htmlspecialchars($_POST['local_id']) : ''; ?>">
                <input type="hidden" name="local_nome" id="local_nome" value="<?php echo isset($_POST['local_nome']) ? htmlspecialchars($_POST['local_nome']) : ''; ?>">
                <div class="autocomplete-suggestions" id="local_suggestions"></div>
            </div>
        </div>
        <div class="form-group">
            <label for="justificativa">Justificativa</label>
            <textarea name="justificativa" id="justificativa" class="form-control" rows="2" placeholder="obrigatório se quantidade além do máximo permitido."><?php echo isset($_POST['justificativa']) ? htmlspecialchars($_POST['justificativa']) : ''; ?></textarea>
            <small class="form-text text-muted">A justificativa só é obrigatória se algum item ultrapassar a quantidade máxima permitida.</small>
        </div>
    </div>

    <!-- Seção de Itens da Requisição (Materiais e Quantidades) -->
    <div class="almoxarifado-form-section">
        <h3>Itens da Requisição</h3>
        <div id="itens-requisicao">
            <!-- Um item inicial é adicionado por padrão -->
            <div class="item-requisicao">
                <div class="requisicao-grid">
                    <div class="form-group">
                        <label>Material</label>
                        <div class="autocomplete-container">
                            <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material..." value="<?php echo isset($_POST['material_nome'][0]) ? htmlspecialchars($_POST['material_nome'][0]) : ''; ?>">
                            <input type="hidden" name="produto_id[]" class="produto-id" value="<?php echo isset($_POST['produto_id'][0]) ? htmlspecialchars($_POST['produto_id'][0]) : ''; ?>">
                            <input type="hidden" name="material_nome[]" class="material-nome" value="<?php echo isset($_POST['material_nome'][0]) ? htmlspecialchars($_POST['material_nome'][0]) : ''; ?>">
                            <div class="autocomplete-suggestions material-suggestions"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Quantidade</label>
                        <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1" value="<?php echo isset($_POST['quantidade'][0]) ? htmlspecialchars($_POST['quantidade'][0]) : ''; ?>">
                        <small class="estoque-info" style="display: none;">Estoque: <span class="estoque-valor"></span></small>
                        <small class="quantidade-maxima-info" style="display: none;">Máximo por requisição: <span class="quantidade-maxima-valor"></span></small>
                    </div>
                </div>
                <button type="button" class="remover-item" style="display: none;">Remover</button>
            </div>
            
            <?php if (!empty($_POST['produto_id']) && count($_POST['produto_id']) > 1): ?>
                <?php for ($i = 1; $i < count($_POST['produto_id']); $i++): ?>
                <div class="item-requisicao">
                    <hr>
                    <div class="requisicao-grid">
                        <div class="form-group">
                            <label>Material</label>
                            <div class="autocomplete-container">
                                <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material..." value="<?php echo isset($_POST['material_nome'][$i]) ? htmlspecialchars($_POST['material_nome'][$i]) : ''; ?>">
                                <input type="hidden" name="produto_id[]" class="produto-id" value="<?php echo htmlspecialchars($_POST['produto_id'][$i]); ?>">
                                <input type="hidden" name="material_nome[]" class="material-nome" value="<?php echo isset($_POST['material_nome'][$i]) ? htmlspecialchars($_POST['material_nome'][$i]) : ''; ?>">
                                <div class="autocomplete-suggestions material-suggestions"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Quantidade</label>
                            <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1" value="<?php echo htmlspecialchars($_POST['quantidade'][$i]); ?>">
                            <small class="estoque-info" style="display: none;">Estoque: <span class="estoque-valor"></span></small>
                            <small class="quantidade-maxima-info" style="display: none;">Máximo por requisição: <span class="quantidade-maxima-valor"></span></small>
                        </div>
                    </div>
                    <button type="button" class="remover-item">Remover</button>
                </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-secondary" id="adicionar-item">Adicionar Item</button>
    </div>

    <!-- Botões de Ação -->
    <div class="form-group">
        <input type="submit" class="btn-custom" value="Enviar Requisição">
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<!-- --- SCRIPT JAVASCRIPT PARA FUNCIONALIDADES DINÂMICAS --- -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Função genérica para criar um campo de autocomplete.
     * @param {HTMLInputElement} input - O campo de input de texto.
     * @param {HTMLElement} suggestionsContainer - O contêiner para exibir as sugestões.
     * @param {string} apiUrl - O endpoint da API para buscar os dados.
     * @param {function} onSelectCallback - Função a ser chamada quando um item é selecionado.
     */
    function createAutocomplete(input, suggestionsContainer, apiUrl, onSelectCallback) {
        let timeout;
        input.addEventListener('input', function() {
            const term = this.value.trim();
            clearTimeout(timeout);
            if (term.length < 2) { // Só busca a partir de 2 caracteres
                suggestionsContainer.style.display = 'none';
                return;
            }
            // Usa um timeout para evitar requisições excessivas enquanto o usuário digita.
            timeout = setTimeout(() => {
                const fullApiUrl = `/inventario/api/${apiUrl}?term=${encodeURIComponent(term)}`;
                fetch(fullApiUrl)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsContainer.innerHTML = '';
                        if (data.length > 0) {
                            suggestionsContainer.style.display = 'block';
                            data.forEach(item => {
                                const suggestion = document.createElement('div');
                                suggestion.className = 'autocomplete-suggestion';
                                // Constrói os detalhes a serem exibidos na sugestão.
                                let details = item.categoria ? `Categoria: ${item.categoria}` : '';
                                if (item.estoque_atual !== undefined) {
                                    details += ` | Estoque: ${item.estoque_atual}`;
                                }
                                if (item.quantidade_maxima_requisicao !== null) {
                                    details += ` | Máximo por requisição: ${item.quantidade_maxima_requisicao}`;
                                }
                                suggestion.innerHTML = `<div class="suggestion-title">${item.nome}</div><div class="suggestion-subtitle">${details}</div>`;
                                // Evento de clique para selecionar uma sugestão.
                                suggestion.addEventListener('click', () => {
                                    input.value = item.nome;
                                    suggestionsContainer.style.display = 'none';
                                    if (onSelectCallback) onSelectCallback(item);
                                });
                                suggestionsContainer.appendChild(suggestion);
                            });
                        } else {
                            suggestionsContainer.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Erro ao buscar sugestões:', error));
            }, 300); // Delay de 300ms
        });
        // Esconde as sugestões se o usuário clicar fora do campo.
        document.addEventListener('click', e => {
            if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    }

    // Inicializa o autocomplete para o campo de "Local de Destino".
    const localInput = document.getElementById('local_autocomplete');
    const localSuggestions = document.getElementById('local_suggestions');
    const localHiddenInput = document.getElementById('local_id');
    if (localInput) {
        createAutocomplete(localInput, localSuggestions, 'search_locais.php', item => {
            localHiddenInput.value = item.id; // Armazena o ID do local no campo oculto.
            document.getElementById('local_nome').value = item.nome; // Armazena o nome do local.
        });
    }

    /**
     * Configura o autocomplete para um campo de material.
     * @param {HTMLElement} itemElement - O elemento que contém os campos do item de requisição.
     */
    function setupMaterialAutocomplete(itemElement) {
        const materialInput = itemElement.querySelector('.material-autocomplete');
        const materialSuggestions = itemElement.querySelector('.material-suggestions');
        const produtoHiddenInput = itemElement.querySelector('.produto-id');
        const estoqueInfo = itemElement.querySelector('.estoque-info');
        const estoqueValor = itemElement.querySelector('.estoque-valor');
        const quantidadeMaximaInfo = itemElement.querySelector('.quantidade-maxima-info');
        const quantidadeMaximaValor = itemElement.querySelector('.quantidade-maxima-valor');
        const quantidadeInput = itemElement.querySelector('.quantidade-input');

        createAutocomplete(materialInput, materialSuggestions, 'almoxarifado_search_materiais.php', item => {
            produtoHiddenInput.value = item.id; // Armazena o ID do material.
            // Adicionar campo hidden para o nome do material
            let nomeInput = itemElement.querySelector('.material-nome');
            if (!nomeInput) {
                nomeInput = document.createElement('input');
                nomeInput.type = 'hidden';
                nomeInput.name = 'material_nome[]';
                nomeInput.className = 'material-nome';
                itemElement.appendChild(nomeInput);
            }
            nomeInput.value = item.nome;
            
            estoqueValor.textContent = item.estoque_atual;
            estoqueInfo.style.display = 'block'; // Exibe o estoque atual.
            quantidadeInput.max = item.estoque_atual; // Define o máximo do input de quantidade.
            
            // Exibe a quantidade máxima por requisição, se houver.
            if (item.quantidade_maxima_requisicao !== null) {
                quantidadeMaximaValor.textContent = item.quantidade_maxima_requisicao;
                quantidadeMaximaInfo.style.display = 'block';
            } else {
                quantidadeMaximaInfo.style.display = 'none';
            }
        });

        // Validação para não permitir quantidade maior que o estoque.
        quantidadeInput.addEventListener('input', function() {
            const quantidade = parseInt(this.value) || 0;
            const estoque = parseInt(this.max);
            if (quantidade > estoque) {
                alert(`A quantidade não pode ser maior que o estoque (${estoque}).`);
                this.value = estoque;
            }
        });
    }

    // Primeiro, configurar autocomplete para o item inicial
    const itensContainer = document.getElementById('itens-requisicao');
    const primeiroItem = itensContainer.querySelector('.item-requisicao');
    setupMaterialAutocomplete(primeiroItem);
    
    // Preencher itens pré-selecionados
    <?php if (!empty($itens_pre_selecionados)): ?>
    // Preencher primeiro item
    <?php if (isset($itens_pre_selecionados[0])): ?>
    primeiroItem.querySelector('.material-autocomplete').value = '<?php echo addslashes($itens_pre_selecionados[0]['nome']); ?>';
    primeiroItem.querySelector('.produto-id').value = '<?php echo $itens_pre_selecionados[0]['id']; ?>';
    primeiroItem.querySelector('.material-nome').value = '<?php echo addslashes($itens_pre_selecionados[0]['nome']); ?>';
    <?php endif; ?>
    
    // Adicionar itens adicionais
    <?php for ($i = 1; $i < count($itens_pre_selecionados); $i++): ?>
    const novoItem<?php echo $i; ?> = document.createElement('div');
    novoItem<?php echo $i; ?>.className = 'item-requisicao';
    novoItem<?php echo $i; ?>.innerHTML = `
        <hr>
        <div class="requisicao-grid">
            <div class="form-group">
                <label>Material</label>
                <div class="autocomplete-container">
                    <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material..." value="<?php echo addslashes($itens_pre_selecionados[$i]['nome']); ?>">
                    <input type="hidden" name="produto_id[]" class="produto-id" value="<?php echo $itens_pre_selecionados[$i]['id']; ?>">
                    <input type="hidden" name="material_nome[]" class="material-nome" value="<?php echo addslashes($itens_pre_selecionados[$i]['nome']); ?>">
                    <div class="autocomplete-suggestions material-suggestions"></div>
                </div>
            </div>
            <div class="form-group">
                <label>Quantidade</label>
                <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1">
                <small class="estoque-info" style="display: none;">Estoque: <span class="estoque-valor"></span></small>
                <small class="quantidade-maxima-info" style="display: none;">Máximo por requisição: <span class="quantidade-maxima-valor"></span></small>
            </div>
        </div>
        <button type="button" class="remover-item">Remover</button>
    `;
    itensContainer.appendChild(novoItem<?php echo $i; ?>);
    setupMaterialAutocomplete(novoItem<?php echo $i; ?>);
    <?php endfor; ?>
    <?php else: ?>
    // Se não há itens pré-selecionados, configurar apenas o primeiro item
    setupMaterialAutocomplete(primeiroItem);
    <?php endif; ?>
    
    // Configurar eventos de remover para itens existentes
    document.querySelectorAll('.remover-item').forEach(btn => {
        btn.addEventListener('click', function() {
            if (document.querySelectorAll('.item-requisicao').length > 1) {
                this.closest('.item-requisicao').remove();
            } else {
                alert('Você não pode remover o último item.');
            }
        });
    });
    
    // Mostrar popup se houver erro de justificativa
    <?php if (isset($show_justificativa_popup)): ?>
    setTimeout(() => {
        alert('ATENÇÃO: Alguns itens ultrapassam a quantidade máxima permitida por requisição.\n\nPor favor, preencha o campo JUSTIFICATIVA e tente novamente.');
        document.getElementById('justificativa').focus();
    }, 100);
    <?php endif; ?>

    // Evento para o botão "Adicionar Item".
    document.getElementById('adicionar-item').addEventListener('click', function() {
        const itensContainer = document.getElementById('itens-requisicao');
        const novoItem = document.createElement('div');
        novoItem.className = 'item-requisicao';
        // HTML para o novo item de requisição.
        novoItem.innerHTML = `
            <hr>
            <div class="requisicao-grid">
                <div class="form-group">
                    <label>Material</label>
                    <div class="autocomplete-container">
                        <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material...">
                        <input type="hidden" name="produto_id[]" class="produto-id">
                        <input type="hidden" name="material_nome[]" class="material-nome">
                        <div class="autocomplete-suggestions material-suggestions"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1">
                    <small class="estoque-info" style="display: none;">Estoque: <span class="estoque-valor"></span></small>
                    <small class="quantidade-maxima-info" style="display: none;">Máximo por requisição: <span class="quantidade-maxima-valor"></span></small>
                </div>
            </div>
            <button type="button" class="remover-item">Remover</button>
        `;
        itensContainer.appendChild(novoItem);
        setupMaterialAutocomplete(novoItem); // Configura o autocomplete para o novo item.
        
        // Adiciona a funcionalidade de remover o item.
        const btnRemover = novoItem.querySelector('.remover-item');
        btnRemover.style.display = 'inline-block';
        btnRemover.addEventListener('click', function() {
            novoItem.remove();
        });
    });


});
</script>

<?php
// Inclusão do rodapé da página.
require_once '../includes/footer.php';
?>
