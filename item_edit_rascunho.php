<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem acessar esta página
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Obter o ID do rascunho a ser editado
$rascunho_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rascunho_id <= 0) {
    $error = "ID do rascunho inválido.";
}

// Buscar os dados do rascunho
$rascunho = null;
if (!$error) {
    $sql = "SELECT * FROM rascunhos_itens WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rascunho_id]);
    $rascunho = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rascunho) {
        $error = "Rascunho não encontrado.";
    }
}

// Processar o formulário de edição
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$error) {
    try {
        $pdo->beginTransaction();
        
        // Obter dados do formulário
        $nome = $_POST['nome'] ?? '';
        $descricao_detalhada = $_POST['descricao_detalhada'] ?? '';
        $numero_serie = $_POST['numero_serie'] ?? '';
        $quantidade = (int)($_POST['quantidade'] ?? 1);
        $valor = $_POST['valor'] ?? null;
        $nota_fiscal_documento = $_POST['nota_fiscal_documento'] ?? '';
        $data_entrada_aceitacao = $_POST['data_entrada_aceitacao'] ?? null;
        $estado = $_POST['estado'] ?? 'Em uso';
        $local_id = !empty($_POST['local_id']) ? (int)$_POST['local_id'] : null;
        $responsavel_id = !empty($_POST['responsavel_id']) ? (int)$_POST['responsavel_id'] : null;
        $observacao = $_POST['observacao'] ?? '';
        $patrimonio_novo = $_POST['patrimonio_novo'] ?? null;
        
        // Dados de aquisição (empenho)
        $empenho_id = !empty($_POST['empenho_id']) ? (int)$_POST['empenho_id'] : null;
        $empenho = $_POST['empenho'] ?? '';
        $data_emissao_empenho = $_POST['data_emissao_empenho'] ?? null;
        $fornecedor = $_POST['fornecedor'] ?? '';
        $cnpj_fornecedor = $_POST['cnpj_fornecedor'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        
        // Atualizar o rascunho
        $sql = "UPDATE rascunhos_itens SET 
            nome = ?, patrimonio_novo = ?, local_id = ?, responsavel_id = ?, estado = ?, observacao = ?,
            descricao_detalhada = ?, numero_serie = ?, quantidade = ?, valor = ?, nota_fiscal_documento = ?,
            data_entrada_aceitacao = ?, empenho_id = ?, empenho = ?, data_emissao_empenho = ?,
            fornecedor = ?, cnpj_fornecedor = ?, categoria = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome, $patrimonio_novo, $local_id, $responsavel_id, $estado, $observacao,
            $descricao_detalhada, $numero_serie, $quantidade, $valor, $nota_fiscal_documento,
            $data_entrada_aceitacao, $empenho_id, $empenho, $data_emissao_empenho,
            $fornecedor, $cnpj_fornecedor, $categoria, $rascunho_id
        ]);
        
        $pdo->commit();
        $message = "Rascunho atualizado com sucesso!";
        
        // Atualizar os dados do rascunho na variável para exibição
        $rascunho = array_merge($rascunho, $_POST);
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = "Erro ao atualizar o rascunho: " . $e->getMessage();
    }
}

// Buscar empenhos abertos para o select
$empenhos_abertos = [];
$sql_empenhos = "SELECT e.id, e.numero_empenho, e.data_emissao, e.nome_fornecedor, e.cnpj_fornecedor, c.numero as categoria_numero, c.descricao as categoria_descricao 
                 FROM empenhos e 
                 JOIN categorias c ON e.categoria_id = c.id 
                 WHERE e.status = 'Aberto' 
                 ORDER BY e.numero_empenho ASC";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$empenhos_abertos = $stmt_empenhos->fetchAll(PDO::FETCH_ASSOC);

// Buscar locais e usuários para os dropdowns
$locais_result = $pdo->query("SELECT id, nome FROM locais ORDER BY nome ASC");
$locais = $locais_result->fetchAll(PDO::FETCH_ASSOC);

$usuarios_result = $pdo->query("SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC");
$usuarios = $usuarios_result->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    .form-section {
        border: 1px solid #eee;
        padding: 15px;
        border-radius: 5px;
    }
    .autocomplete-container {
        position: relative;
    }
    .suggestions-list {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }
    .suggestions-list div {
        padding: 8px;
        cursor: pointer;
    }
    .suggestions-list div:hover {
        background-color: #f0f0f0;
    }
    .empenho-info {
        background-color: #f0f8ff;
        border: 1px solid #add8e6;
        border-radius: 5px;
        padding: 10px;
        margin: 10px 0;
        display: none;
    }
</style>

<div class="form-container">
    <h2>Editar Rascunho de Item</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($rascunho): ?>
        <form action="item_edit_rascunho.php?id=<?php echo $rascunho_id; ?>" method="post">
            <div class="form-grid">
                <div class="form-section">
                    <h3>Dados Básicos</h3>
                    <div>
                        <label>Nome do Item: *</label>
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($rascunho['nome'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label>Descrição Detalhada:</label>
                        <textarea name="descricao_detalhada" maxlength="200" placeholder="Máximo 200 caracteres"><?php echo htmlspecialchars($rascunho['descricao_detalhada'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label>Número de Série:</label>
                        <input type="text" name="numero_serie" value="<?php echo htmlspecialchars($rascunho['numero_serie'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Quantidade:</label>
                        <input type="number" name="quantidade" min="1" value="<?php echo htmlspecialchars($rascunho['quantidade'] ?? '1'); ?>">
                    </div>
                    <div>
                        <label>Patrimônio (opcional):</label>
                        <input type="text" name="patrimonio_novo" value="<?php echo htmlspecialchars($rascunho['patrimonio_novo'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Detalhes da Aquisição</h3>
                    <div>
                        <label>Empenho:</label>
                        <select name="empenho_id" id="empenho_id" onchange="preencherDadosEmpenho()">
                            <option value="">Selecione um empenho</option>
                            <?php foreach($empenhos_abertos as $empenho_item): ?>
                                <option value="<?php echo $empenho_item['id']; ?>" 
                                        data-categoria="<?php echo htmlspecialchars($empenho_item['categoria_numero'] . ' - ' . $empenho_item['categoria_descricao']); ?>"
                                        data-data-emissao="<?php echo $empenho_item['data_emissao']; ?>"
                                        data-fornecedor="<?php echo htmlspecialchars($empenho_item['nome_fornecedor']); ?>"
                                        data-cnpj="<?php echo $empenho_item['cnpj_fornecedor']; ?>"
                                        <?php echo (isset($rascunho['empenho_id']) && $rascunho['empenho_id'] == $empenho_item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($empenho_item['numero_empenho'] . ' | ' . date('d/m/Y', strtotime($empenho_item['data_emissao']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="empenho_info" class="empenho-info" style="<?php echo !empty($rascunho['empenho_id']) ? 'display: block;' : 'display: none;'; ?>">
                        <h4>Informações do Empenho</h4>
                        <div><strong>Categoria:</strong> <span id="info_categoria"><?php echo htmlspecialchars($rascunho['categoria'] ?? ''); ?></span></div>
                        <div><strong>Data de Emissão:</strong> <span id="info_data_emissao"><?php echo htmlspecialchars($rascunho['data_emissao_empenho'] ?? ''); ?></span></div>
                        <div><strong>Fornecedor:</strong> <span id="info_fornecedor"><?php echo htmlspecialchars($rascunho['fornecedor'] ?? ''); ?></span></div>
                        <div><strong>CNPJ:</strong> <span id="info_cnpj"><?php echo htmlspecialchars($rascunho['cnpj_fornecedor'] ?? ''); ?></span></div>
                    </div>
                    
                    <div>
                        <label>Valor Unitário:</label>
                        <input type="number" step="0.01" name="valor" value="<?php echo htmlspecialchars($rascunho['valor'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Nota Fiscal/Documento:</label>
                        <input type="text" name="nota_fiscal_documento" value="<?php echo htmlspecialchars($rascunho['nota_fiscal_documento'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Data de Entrada/Aceitação:</label>
                        <input type="date" name="data_entrada_aceitacao" value="<?php echo htmlspecialchars($rascunho['data_entrada_aceitacao'] ?? ''); ?>">
                    </div>
                    
                    <!-- Campos ocultos que serão preenchidos automaticamente -->
                    <input type="hidden" name="empenho" id="empenho_hidden" value="<?php echo htmlspecialchars($rascunho['empenho'] ?? ''); ?>">
                    <input type="hidden" name="data_emissao_empenho" id="data_emissao_empenho_hidden" value="<?php echo htmlspecialchars($rascunho['data_emissao_empenho'] ?? ''); ?>">
                    <input type="hidden" name="fornecedor" id="fornecedor_hidden" value="<?php echo htmlspecialchars($rascunho['fornecedor'] ?? ''); ?>">
                    <input type="hidden" name="cnpj_fornecedor" id="cnpj_fornecedor_hidden" value="<?php echo htmlspecialchars($rascunho['cnpj_fornecedor'] ?? ''); ?>">
                    <input type="hidden" name="categoria" id="categoria_hidden" value="<?php echo htmlspecialchars($rascunho['categoria'] ?? ''); ?>">
                </div>
                
                <div class="form-section">
                    <h3>Localização e Responsabilidade</h3>
                    <div>
                        <label>Estado:</label>
                        <select name="estado">
                            <option value="Em uso" <?php echo (isset($rascunho['estado']) && $rascunho['estado'] == 'Em uso') ? 'selected' : ''; ?>>Em uso</option>
                            <option value="Ocioso" <?php echo (isset($rascunho['estado']) && $rascunho['estado'] == 'Ocioso') ? 'selected' : ''; ?>>Ocioso</option>
                            <option value="Recuperável" <?php echo (isset($rascunho['estado']) && $rascunho['estado'] == 'Recuperável') ? 'selected' : ''; ?>>Recuperável</option>
                            <option value="Inservível" <?php echo (isset($rascunho['estado']) && $rascunho['estado'] == 'Inservível') ? 'selected' : ''; ?>>Inservível</option>
                        </select>
                    </div>
                    <div>
                        <label>Local:</label>
                        <div class="autocomplete-container">
                            <input type="text" id="search_local" name="search_local" placeholder="Digite para buscar um local..." autocomplete="off" 
                                   value="<?php echo htmlspecialchars($rascunho['local_nome'] ?? ($_POST['search_local'] ?? '')); ?>">
                            <input type="hidden" name="local_id" id="local_id" value="<?php echo htmlspecialchars($rascunho['local_id'] ?? ($_POST['local_id'] ?? '')); ?>">
                            <div id="local_suggestions" class="suggestions-list"></div>
                        </div>
                    </div>
                    <div>
                        <label>Responsável:</label>
                        <div class="autocomplete-container">
                            <input type="text" id="search_responsavel" name="search_responsavel" placeholder="Digite para buscar um responsável..." autocomplete="off"
                                   value="<?php echo htmlspecialchars($rascunho['responsavel_nome'] ?? ($_POST['search_responsavel'] ?? '')); ?>">
                            <input type="hidden" name="responsavel_id" id="responsavel_id" value="<?php echo htmlspecialchars($rascunho['responsavel_id'] ?? ($_POST['responsavel_id'] ?? '')); ?>">
                            <div id="responsavel_suggestions" class="suggestions-list"></div>
                        </div>
                    </div>
                    <div>
                        <label>Observação:</label>
                        <textarea name="observacao"><?php echo htmlspecialchars($rascunho['observacao'] ?? ($_POST['observacao'] ?? '')); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <input type="submit" value="Atualizar Rascunho" class="btn-custom">
                <a href="rascunhos_itens.php" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
// Função para preencher os dados do empenho selecionado
function preencherDadosEmpenho() {
    const selectEmpenho = document.getElementById('empenho_id');
    const empenhoInfo = document.getElementById('empenho_info');
    const selectedOption = selectEmpenho.options[selectEmpenho.selectedIndex];
    
    if (selectedOption.value === "") {
        // Ocultar informações do empenho e limpar campos ocultos
        empenhoInfo.style.display = 'none';
        document.getElementById('empenho_hidden').value = '';
        document.getElementById('data_emissao_empenho_hidden').value = '';
        document.getElementById('fornecedor_hidden').value = '';
        document.getElementById('cnpj_fornecedor_hidden').value = '';
        document.getElementById('categoria_hidden').value = '';
        return;
    }
    
    // Obter dados do empenho selecionado
    const categoria = selectedOption.getAttribute('data-categoria');
    const dataEmissao = selectedOption.getAttribute('data-data-emissao');
    const fornecedor = selectedOption.getAttribute('data-fornecedor');
    const cnpj = selectedOption.getAttribute('data-cnpj');
    const numeroEmpenho = selectedOption.textContent.split(' | ')[0];
    
    // Preencher campos ocultos
    document.getElementById('empenho_hidden').value = numeroEmpenho;
    document.getElementById('data_emissao_empenho_hidden').value = dataEmissao;
    document.getElementById('fornecedor_hidden').value = fornecedor;
    document.getElementById('cnpj_fornecedor_hidden').value = cnpj;
    document.getElementById('categoria_hidden').value = categoria;
    
    // Preencher informações do empenho
    document.getElementById('info_categoria').textContent = categoria;
    document.getElementById('info_data_emissao').textContent = dataEmissao;
    document.getElementById('info_fornecedor').textContent = fornecedor;
    document.getElementById('info_cnpj').textContent = cnpj;
    
    // Mostrar informações do empenho
    empenhoInfo.style.display = 'block';
}

// Função genérica para busca com autocomplete
function setupAutocomplete(inputEl, suggestionsEl, hiddenIdEl, searchUrl) {
    let debounceTimeout;
    
    inputEl.addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        const searchTerm = this.value;
        suggestionsEl.innerHTML = '';
        hiddenIdEl.value = '';
        
        if (searchTerm.length < 2) {
            suggestionsEl.style.display = 'none';
            return;
        }
        
        // Debounce: Atraso de 300ms para evitar chamadas excessivas à API
        debounceTimeout = setTimeout(() => {
            fetch(`${searchUrl}?term=${searchTerm}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.textContent = item.nome;
                            div.dataset.id = item.id;
                            div.addEventListener('click', function() {
                                inputEl.value = this.textContent;
                                hiddenIdEl.value = this.dataset.id;
                                suggestionsEl.innerHTML = '';
                                suggestionsEl.style.display = 'none';
                            });
                            suggestionsEl.appendChild(div);
                        });
                        suggestionsEl.style.display = 'block';
                    } else {
                        suggestionsEl.innerHTML = '<div class="search-result-item">Nenhum resultado encontrado</div>';
                        suggestionsEl.style.display = 'block';
                    }
                })
                .catch(error => console.error('Erro no autocomplete:', error));
        }, 300);
    });
    
    // Esconder sugestões se clicar fora
    document.addEventListener('click', function(e) {
        if (e.target !== inputEl) {
            suggestionsEl.style.display = 'none';
        }
    });
}

// Configurar autocomplete para locais e responsáveis
document.addEventListener('DOMContentLoaded', function() {
    const searchLocal = document.getElementById('search_local');
    const localSuggestions = document.getElementById('local_suggestions');
    const localId = document.getElementById('local_id');
    
    const searchResponsavel = document.getElementById('search_responsavel');
    const responsavelSuggestions = document.getElementById('responsavel_suggestions');
    const responsavelId = document.getElementById('responsavel_id');
    
    if (searchLocal && localSuggestions && localId) {
        setupAutocomplete(searchLocal, localSuggestions, localId, 'api/search_locais.php');
    }
    
    if (searchResponsavel && responsavelSuggestions && responsavelId) {
        setupAutocomplete(searchResponsavel, responsavelSuggestions, responsavelId, 'api/search_usuarios.php');
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>