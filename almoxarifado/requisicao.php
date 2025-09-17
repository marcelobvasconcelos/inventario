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

    // Se houver erros de validação, exibe-os.
    if (!empty($erros_validacao)) {
        $message = implode("<br>", $erros_validacao);
        $message_type = "danger";
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
                foreach ($itens_validos as $item) {
                    $sql_item = "INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada) 
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
                $_POST = array(); // Limpa os dados do formulário para evitar reenvio.

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
                <input type="text" id="local_autocomplete" class="autocomplete-input" placeholder="Digite o nome do local...">
                <input type="hidden" name="local_id" id="local_id">
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
                            <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material...">
                            <input type="hidden" name="produto_id[]" class="produto-id">
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
                <button type="button" class="remover-item" style="display: none;">Remover</button>
            </div>
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

    // Configura o autocomplete para o primeiro item que já está na página.
    setupMaterialAutocomplete(document.querySelector('.item-requisicao'));

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

    // Configura o botão de remover para o primeiro item.
    const primeiroRemover = document.querySelector('.remover-item');
    if (primeiroRemover) {
        primeiroRemover.addEventListener('click', function() {
            // Só permite remover se houver mais de um item na lista.
            if (document.querySelectorAll('.item-requisicao').length > 1) {
                this.closest('.item-requisicao').remove();
            } else {
                alert("Você não pode remover o último item.");
            }
        });
    }
});
</script>

<?php
// Inclusão do rodapé da página.
require_once '../includes/footer.php';
?>
