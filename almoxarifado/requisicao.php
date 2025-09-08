<?php
require_once '../includes/header.php';
require_once '../config/db.php'; // Garante que $pdo seja inicializado

// Verificar permissões - apenas usuários logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Verificar se o usuário tem permissão de administrador, almoxarife, visualizador ou gestor
if (!in_array($_SESSION["permissao"], ['Administrador', 'Almoxarife', 'Visualizador', 'Gestor'])) {
    header("location: index.php");
    exit;
}

// Buscar todos os locais (usando PDO)
$sql_locais = "SELECT id, nome FROM locais ORDER BY nome";
$stmt_locais = $pdo->prepare($sql_locais);
$stmt_locais->execute();
$locais = $stmt_locais->fetchAll(PDO::FETCH_ASSOC);

// Variáveis para mensagens
$message = "";
$message_type = "";

// Processar o formulário quando for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['local_id'])) {
    $local_id = isset($_POST['local_id']) ? (int)$_POST['local_id'] : 0;
    $justificativa = isset($_POST['justificativa']) ? trim($_POST['justificativa']) : '';
    $materiais_selecionados = isset($_POST['material_id']) ? $_POST['material_id'] : [];
    $quantidades = isset($_POST['quantidade']) ? $_POST['quantidade'] : [];

    $erros_validacao = [];
    if (empty($local_id)) {
        $erros_validacao[] = "O campo 'Local de Destino' é obrigatório.";
    }
    if (empty($justificativa)) {
        $erros_validacao[] = "O campo 'Justificativa' é obrigatório.";
    }
    if (empty($materiais_selecionados) || empty($materiais_selecionados[0])) {
        $erros_validacao[] = "É necessário adicionar pelo menos um material à requisição.";
    }

    if (!empty($erros_validacao)) {
        $message = implode("<br>", $erros_validacao);
        $message_type = "danger";
    } else {
        // Validar materiais e quantidades
        $itens_validos = [];
        $erros = [];

        for ($i = 0; $i < count($materiais_selecionados); $i++) {
            $material_id = (int)$materiais_selecionados[$i];
            $quantidade = isset($quantidades[$i]) ? (int)$quantidades[$i] : 0;

            if ($material_id > 0 && $quantidade > 0) {
                // Verificar estoque na tabela correta (almoxarifado_materiais)
                $sql_estoque = "SELECT nome, estoque_atual FROM almoxarifado_materiais WHERE id = ?";
                $stmt_estoque = $pdo->prepare($sql_estoque);
                $stmt_estoque->execute([$material_id]);
                $material = $stmt_estoque->fetch(PDO::FETCH_ASSOC);

                if ($material && $quantidade <= $material['estoque_atual']) {
                    $itens_validos[] = [
                        'material_id' => $material_id,
                        'quantidade' => $quantidade,
                        'nome' => $material['nome']
                    ];
                } else {
                    $erros[] = "Quantidade solicitada para " . ($material ? htmlspecialchars($material['nome']) : "material desconhecido") . " excede o estoque disponível.";
                }
            }
        }

        if (!empty($erros)) {
            $message = "Erros encontrados:<br>" . implode("<br>", $erros);
            $message_type = "danger";
        } elseif (empty($itens_validos)) {
            $message = "Nenhum item válido foi adicionado à requisição.";
            $message_type = "danger";
        } else {
            // Iniciar transação
            $pdo->beginTransaction();

            try {
                // Criar requisição
                $sql_requisicao = "INSERT INTO almoxarifado_requisicoes (usuario_id, local_id, data_requisicao, justificativa) 
                                   VALUES (?, ?, NOW(), ?)";
                $stmt_requisicao = $pdo->prepare($sql_requisicao);
                $stmt_requisicao->execute([$_SESSION['id'], $local_id, $justificativa]);
                $requisicao_id = $pdo->lastInsertId();

                // Adicionar itens à requisição
                foreach ($itens_validos as $item) {
                    $sql_item = "INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, material_id, quantidade_solicitada) 
                                 VALUES (?, ?, ?)";
                    $stmt_item = $pdo->prepare($sql_item);
                    $stmt_item->execute([$requisicao_id, $item['material_id'], $item['quantidade']]);
                }

                // Buscar administradores e almoxarifes para notificar
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

                $pdo->commit();
                $message = "Requisição criada com sucesso! Aguarde a aprovação.";
                $message_type = "success";
                $_POST = array(); // Limpar formulário
            } catch (Exception $e) {
                $pdo->rollback();
                $message = "Erro ao criar requisição: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}
?>

<div class="almoxarifado-header">
    <h2>Nova Requisição</h2>
    <?php
    $is_privileged_user = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);
    require_once 'menu_almoxarifado.php';
    ?>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
            <textarea name="justificativa" id="justificativa" class="form-control" rows="2"><?php echo isset($_POST['justificativa']) ? htmlspecialchars($_POST['justificativa']) : ''; ?></textarea>
        </div>
    </div>

    <div class="almoxarifado-form-section">
        <h3>Itens da Requisição</h3>
        <div id="itens-requisicao">
            <!-- O primeiro item é adicionado aqui para o caso de não haver itens pré-selecionados -->
            <div class="item-requisicao">
                <div class="requisicao-grid">
                    <div class="form-group">
                        <label>Material</label>
                        <div class="autocomplete-container">
                            <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material...">
                            <input type="hidden" name="material_id[]" class="material-id">
                            <div class="autocomplete-suggestions material-suggestions"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Quantidade</label>
                        <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1">
                        <small class="estoque-info" style="display: none;">Estoque: <span class="estoque-valor"></span></small>
                    </div>
                </div>
                <button type="button" class="remover-item" style="display: none;">Remover</button>
            </div>
        </div>
        <button type="button" class="btn btn-secondary" id="adicionar-item">Adicionar Item</button>
    </div>

    <div class="form-group">
        <input type="submit" class="btn-custom" value="Enviar Requisição">
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função genérica para criar um campo de autocomplete
    function createAutocomplete(input, suggestionsContainer, apiUrl, onSelectCallback) {
        let timeout;
        input.addEventListener('input', function() {
            const term = this.value.trim();
            clearTimeout(timeout);
            if (term.length < 2) {
                suggestionsContainer.style.display = 'none';
                return;
            }
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
                                let details = item.categoria ? `Categoria: ${item.categoria}` : '';
                                if (item.estoque_atual !== undefined) {
                                    details += ` | Estoque: ${item.estoque_atual}`;
                                }
                                suggestion.innerHTML = `<div class="suggestion-title">${item.nome}</div><div class="suggestion-subtitle">${details}</div>`;
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
            }, 300);
        });
        document.addEventListener('click', e => {
            if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    }

    // Inicializar autocomplete para locais
    const localInput = document.getElementById('local_autocomplete');
    const localSuggestions = document.getElementById('local_suggestions');
    const localHiddenInput = document.getElementById('local_id');
    if (localInput) {
        createAutocomplete(localInput, localSuggestions, 'search_locais.php', item => {
            localHiddenInput.value = item.id;
        });
    }

    // Função para configurar o autocomplete de um item de material
    function setupMaterialAutocomplete(itemElement) {
        const materialInput = itemElement.querySelector('.material-autocomplete');
        const materialSuggestions = itemElement.querySelector('.material-suggestions');
        const materialHiddenInput = itemElement.querySelector('.material-id');
        const estoqueInfo = itemElement.querySelector('.estoque-info');
        const estoqueValor = itemElement.querySelector('.estoque-valor');
        const quantidadeInput = itemElement.querySelector('.quantidade-input');

        createAutocomplete(materialInput, materialSuggestions, 'almoxarifado_search_materiais.php', item => {
            materialHiddenInput.value = item.id;
            estoqueValor.textContent = item.estoque_atual;
            estoqueInfo.style.display = 'block';
            quantidadeInput.max = item.estoque_atual;
        });

        quantidadeInput.addEventListener('input', function() {
            const quantidade = parseInt(this.value) || 0;
            const estoque = parseInt(this.max);
            if (quantidade > estoque) {
                alert(`A quantidade não pode ser maior que o estoque (${estoque}).`);
                this.value = estoque;
            }
        });
    }

    // Configurar o primeiro item
    setupMaterialAutocomplete(document.querySelector('.item-requisicao'));

    // Adicionar novo item
    document.getElementById('adicionar-item').addEventListener('click', function() {
        const itensContainer = document.getElementById('itens-requisicao');
        const novoItem = document.createElement('div');
        novoItem.className = 'item-requisicao';
        novoItem.innerHTML = `
            <hr>
            <div class="requisicao-grid">
                <div class="form-group">
                    <label>Material</label>
                    <div class="autocomplete-container">
                        <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material...">
                        <input type="hidden" name="material_id[]" class="material-id">
                        <div class="autocomplete-suggestions material-suggestions"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1">
                    <small class="estoque-info" style="display: none;">Estoque: <span class="estoque-valor"></span></small>
                </div>
            </div>
            <button type="button" class="remover-item">Remover</button>
        `;
        itensContainer.appendChild(novoItem);
        setupMaterialAutocomplete(novoItem);
        
        const btnRemover = novoItem.querySelector('.remover-item');
        btnRemover.style.display = 'inline-block';
        btnRemover.addEventListener('click', function() {
            novoItem.remove();
        });
    });

    // Configurar botão de remover para o primeiro item (se houver mais de um)
    const primeiroRemover = document.querySelector('.remover-item');
    if (primeiroRemover) {
        primeiroRemover.addEventListener('click', function() {
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
require_once '../includes/footer.php';
?>