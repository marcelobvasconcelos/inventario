<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Verificar permissões - apenas usuários logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login.php");
    exit;
}

// Verificar se o usuário tem permissão de administrador, almoxarife, visualizador ou gestor
if ($_SESSION["permissao"] != 'Administrador' && $_SESSION["permissao"] != 'Almoxarife' && $_SESSION["permissao"] != 'Visualizador' && $_SESSION["permissao"] != 'Gestor') {
    header("location: index.php");
    exit;
}

// Verificar se itens foram enviados via POST (requisição individual ou em massa)
$itens_selecionados = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['item_ids'])) {
    $itens_selecionados = $_POST['item_ids'];
}

// Buscar todos os materiais disponíveis
$sql_materiais = "SELECT m.id, m.nome, m.qtd as estoque_atual, c.descricao as categoria 
                  FROM materiais m 
                  LEFT JOIN categorias c ON m.categoria_id = c.id 
                  WHERE m.qtd > 0 
                  ORDER BY m.nome";
$stmt_materiais = $pdo->prepare($sql_materiais);
$stmt_materiais->execute();
$materiais = $stmt_materiais->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os locais
$sql_locais = "SELECT id, nome FROM locais ORDER BY nome";
$stmt_locais = $pdo->prepare($sql_locais);
$stmt_locais->execute();
$locais = $stmt_locais->fetchAll(PDO::FETCH_ASSOC);

// Variáveis para mensagens
$message = "";
$message_type = "";

// Processar o formulário quando for enviado
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['local_id'])){
    $local_id = isset($_POST['local_id']) ? (int)$_POST['local_id'] : 0;
    $justificativa = isset($_POST['justificativa']) ? trim($_POST['justificativa']) : '';
    $materiais_selecionados = isset($_POST['material_id']) ? $_POST['material_id'] : [];
    $quantidades = isset($_POST['quantidade']) ? $_POST['quantidade'] : [];
    
    // Validar dados
    if(empty($local_id) || empty($justificativa) || empty($materiais_selecionados)){
        $message = "Por favor, preencha todos os campos obrigatórios.";
        $message_type = "danger";
    } else {
        // Validar materiais e quantidades
        $itens_validos = [];
        $erros = [];
        
        for($i = 0; $i < count($materiais_selecionados); $i++){
            $material_id = (int)$materiais_selecionados[$i];
            $quantidade = isset($quantidades[$i]) ? (int)$quantidades[$i] : 0;
            
            if($material_id > 0 && $quantidade > 0){
                // Verificar estoque
                $sql_estoque = "SELECT nome, qtd FROM materiais WHERE id = ?";
                $stmt_estoque = $pdo->prepare($sql_estoque);
                $stmt_estoque->execute([$material_id]);
                $material = $stmt_estoque->fetch(PDO::FETCH_ASSOC);
                
                if($material && $quantidade <= $material['qtd']){
                    $itens_validos[] = [
                        'material_id' => $material_id,
                        'quantidade' => $quantidade,
                        'nome' => $material['nome']
                    ];
                } else {
                    $erros[] = "Quantidade solicitada para " . ($material ? $material['nome'] : "material") . " excede o estoque disponível.";
                }
            }
        }
        
        if(!empty($erros)){
            $message = "Erros encontrados:<br>" . implode("<br>", $erros);
            $message_type = "danger";
        } elseif(empty($itens_validos)){
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
                foreach($itens_validos as $item){
                    $sql_item = "INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada) 
                                 VALUES (?, ?, ?)";
                    $stmt_item = $pdo->prepare($sql_item);
                    $stmt_item->execute([$requisicao_id, $item['material_id'], $item['quantidade']]);
                }
                
                // Buscar administradores para notificar
                $sql_admins = "SELECT id FROM usuarios WHERE permissao_id = 1";
                $stmt_admins = $pdo->prepare($sql_admins);
                $stmt_admins->execute();
                $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
                
                if(!empty($admins)){
                    // Criar uma notificação para cada administrador
                    foreach($admins as $admin){
                        $admin_id = $admin['id'];
                        
                        // Mensagem da notificação
                        $mensagem_notificacao = "Nova requisição de materiais #{$requisicao_id} criada por {$_SESSION['nome']}.";
                        
                        // Inserir notificação
                        $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes 
                            (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) 
                            VALUES (?, ?, ?, 'nova_requisicao', ?, 'pendente')";
                        
                        $stmt_notificacao = $pdo->prepare($sql_notificacao);
                        $stmt_notificacao->execute([$requisicao_id, $_SESSION['id'], $admin_id, $mensagem_notificacao]);
                    }
                }
                
                // Commit da transação
                $pdo->commit();
                
                $message = "Requisição criada com sucesso! Aguarde a aprovação do administrador.";
                $message_type = "success";
                
                // Limpar os dados do formulário
                $_POST = array();
            } catch(Exception $e) {
                // Rollback da transação em caso de erro
                $pdo->rollback();
                $message = "Erro ao criar requisição: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
}
?>

<div class="requisicao-container">
    <div class="requisicao-header">
        <h2>Nova Requisição de Materiais</h2>
    </div>
    
    <?php if(!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="requisicao-card">
            <div class="requisicao-card-header">
                <h3>Dados da Requisição</h3>
            </div>
            <div class="requisicao-card-body">
                <div class="requisicao-grid">
                    <div class="form-group">
                        <label for="local_id">Local de Destino</label>
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
            </div>
        </div>
        
        <div class="requisicao-card">
            <div class="requisicao-card-header">
                <h3>Itens da Requisição</h3>
            </div>
            <div class="requisicao-card-body">
                <div id="itens-requisicao">
                    <?php if (!empty($itens_selecionados)): ?>
                        <?php
                        // Buscar os detalhes dos materiais selecionados
                        if (!empty($itens_selecionados)) {
                            $placeholders = implode(',', array_fill(0, count($itens_selecionados), '?'));
                            $sql_itens = "SELECT m.id, m.nome, m.qtd as estoque_atual FROM materiais m WHERE m.id IN ($placeholders)";
                            $stmt_itens = $pdo->prepare($sql_itens);
                            $stmt_itens->execute($itens_selecionados);
                            $materiais_pre_selecionados = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
                        } else {
                            $materiais_pre_selecionados = [];
                        }
                        ?>

                        <?php foreach ($materiais_pre_selecionados as $material): ?>
                            <div class="item-requisicao">
                                <div class="requisicao-grid">
                                    <div class="form-group">
                                        <label>Material</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($material['nome']); ?>" readonly>
                                        <input type="hidden" name="material_id[]" value="<?php echo $material['id']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="quantidade[]">Quantidade</label>
                                        <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1" max="<?php echo $material['estoque_atual']; ?>" required>
                                        <small class="estoque-info">Estoque disponível: <span class="estoque-valor"><?php echo $material['estoque_atual']; ?></span></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <!-- Lógica original para adicionar itens dinamicamente -->
                        <div class="item-requisicao">
                            <div class="requisicao-grid">
                                <div class="form-group">
                                    <label for="material_id[]">Material</label>
                                    <div class="autocomplete-container">
                                        <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material...">
                                        <input type="hidden" name="material_id[]" class="material-id">
                                        <div class="autocomplete-suggestions material-suggestions"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="quantidade[]">Quantidade</label>
                                    <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1" value="<?php echo isset($_POST['quantidade'][0]) ? (int)$_POST['quantidade'][0] : ''; ?>">
                                    <small class="estoque-info" style="display: none;">Estoque disponível: <span class="estoque-valor"></span></small>
                                </div>
                            </div>
                            <button type="button" class="remover-item" style="display: none;">Remover Item</button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($itens_selecionados)): ?>
                    <button type="button" class="btn btn-secondary" id="adicionar-item">Adicionar Mais Itens</button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="botoes-acao">
            <input type="submit" class="btn btn-primary" value="Enviar Requisição">
            <a href="../index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // A lógica de pré-preenchimento conflitante via JS foi removida.
    // A renderização agora é feita diretamente via PHP.
    
    // Função para criar autocomplete
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
                const fullApiUrl = `/inventario/almoxarifado/empenhos/api/${apiUrl}?term=${encodeURIComponent(term)}`;
                
                fetch(fullApiUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        suggestionsContainer.innerHTML = '';
                        
                        if (data.length > 0) {
                            suggestionsContainer.style.display = 'block';
                            
                            data.forEach(item => {
                                const suggestion = document.createElement('div');
                                suggestion.className = 'autocomplete-suggestion';
                                
                                if (item.categoria) {
                                    suggestion.innerHTML = `
                                        <div class="suggestion-title">${item.nome}</div>
                                        <div class="suggestion-subtitle">Categoria: ${item.categoria} | Estoque: ${item.estoque_atual}</div>
                                    `;
                                } else {
                                    suggestion.innerHTML = `
                                        <div class="suggestion-title">${item.nome}</div>
                                    `;
                                }
                                
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
                    .catch(error => {
                        console.error('Erro ao buscar sugestões:', error);
                        suggestionsContainer.style.display = 'none';
                    });
            }, 300);
        });
        
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    }
    
    // Inicializar autocomplete para locais
    const localInput = document.getElementById('local_autocomplete');
    const localSuggestions = document.getElementById('local_suggestions');
    const localHiddenInput = document.getElementById('local_id');
    
    if (localInput && localSuggestions) {
        createAutocomplete(localInput, localSuggestions, 'search_locais.php', function(item) {
            localHiddenInput.value = item.id;
        });
    }
    
    // Função para adicionar novo item
    const adicionarItemBtn = document.getElementById('adicionar-item');
    if (adicionarItemBtn) {
        adicionarItemBtn.addEventListener('click', function() {
            var itensContainer = document.getElementById('itens-requisicao');
            var novoItem = document.createElement('div');
            novoItem.className = 'item-requisicao';
            novoItem.innerHTML = `
                <hr>
                <div class="requisicao-grid">
                    <div class="form-group">
                        <label for="material_id[]">Material</label>
                        <div class="autocomplete-container">
                            <input type="text" class="autocomplete-input material-autocomplete" placeholder="Digite o nome do material...">
                            <input type="hidden" name="material_id[]" class="material-id">
                            <div class="autocomplete-suggestions material-suggestions"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantidade[]">Quantidade</label>
                        <input type="number" name="quantidade[]" class="form-control quantidade-input" min="1">
                        <small class="estoque-info" style="display: none;">Estoque disponível: <span class="estoque-valor"></span></small>
                    </div>
                </div>
                
                <button type="button" class="remover-item">Remover Item</button>
            `;
            itensContainer.appendChild(novoItem);
            
            const materialInput = novoItem.querySelector('.material-autocomplete');
            const materialSuggestions = novoItem.querySelector('.material-suggestions');
            const materialHiddenInput = novoItem.querySelector('.material-id');
            const estoqueInfo = novoItem.querySelector('.estoque-info');
            const estoqueValor = novoItem.querySelector('.estoque-valor');
            const quantidadeInput = novoItem.querySelector('.quantidade-input');
            
            if (materialInput && materialSuggestions) {
                createAutocomplete(materialInput, materialSuggestions, 'search_materiais.php', function(item) {
                    materialHiddenInput.value = item.id;
                    estoqueValor.textContent = item.estoque_atual;
                    estoqueInfo.style.display = 'block';
                    
                    quantidadeInput.addEventListener('input', function() {
                        const quantidade = parseInt(this.value) || 0;
                        const estoque = parseInt(item.estoque_atual);
                        
                        if (quantidade > estoque) {
                            alert('A quantidade solicitada não pode ser maior que o estoque disponível (' + estoque + ').');
                            this.value = estoque;
                        }
                    });
                });
            }
            
            var botaoRemover = novoItem.querySelector('.remover-item');
            botaoRemover.addEventListener('click', function() {
                removerItem(this);
            });
            botaoRemover.style.display = 'inline-block';
        });
    }
    
    // Função para remover item
    function removerItem(botao) {
        var item = botao.closest('.item-requisicao');
        item.remove();
    }
    
    // Inicializar autocomplete para o primeiro campo de material (se não foi pré-preenchido)
    const firstMaterialInput = document.querySelector('.material-autocomplete');
    if (firstMaterialInput) {
        const firstMaterialSuggestions = firstMaterialInput.closest('.autocomplete-container').querySelector('.material-suggestions');
        const firstMaterialHiddenInput = firstMaterialInput.closest('.autocomplete-container').querySelector('.material-id');
        const itemRequisicao = firstMaterialInput.closest('.item-requisicao');
        const firstEstoqueInfo = itemRequisicao.querySelector('.estoque-info');
        const firstEstoqueValor = itemRequisicao.querySelector('.estoque-valor');
        const firstQuantidadeInput = itemRequisicao.querySelector('.quantidade-input');

        if (firstMaterialSuggestions && !firstMaterialHiddenInput.value) {
            createAutocomplete(firstMaterialInput, firstMaterialSuggestions, 'search_materiais.php', function(item) {
                firstMaterialHiddenInput.value = item.id;
                firstEstoqueValor.textContent = item.estoque_atual;
                firstEstoqueInfo.style.display = 'block';
                
                firstQuantidadeInput.addEventListener('input', function() {
                    const quantidade = parseInt(this.value) || 0;
                    const estoque = parseInt(item.estoque_atual);
                    
                    if (quantidade > estoque) {
                        alert('A quantidade solicitada não pode ser maior que o estoque disponível (' + estoque + ').');
                        this.value = estoque;
                    }
                });
            });
        }
    }
    
    document.querySelectorAll('.remover-item').forEach(function(botao) {
        botao.addEventListener('click', function() {
            removerItem(this);
        });
        botao.style.display = 'inline-block';
    });
});
</script>

<?php
require_once '../../includes/footer.php';
?>
