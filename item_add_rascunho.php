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

// Processar o formulário de criação
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();
        
        // Obter dados do formulário
        $processo_documento = $_POST['processo_documento'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $descricao_detalhada = $_POST['descricao_detalhada'] ?? '';
        $numero_serie = $_POST['numero_serie'] ?? '';
        $quantidade = (int)($_POST['quantidade'] ?? 1);
        $patrimonio_novo = $_POST['patrimonio_novo'] ?? '';
        $patrimonio_secundario = $_POST['patrimonio_secundario'] ?? '';
        $local_id = !empty($_POST['local_id']) ? (int)$_POST['local_id'] : null;
        $responsavel_id = !empty($_POST['responsavel_id']) ? (int)$_POST['responsavel_id'] : null;
        $estado = $_POST['estado'] ?? 'Bom';
        $observacao = $_POST['observacao'] ?? '';
        $usuario_anterior_id = !empty($_POST['usuario_anterior_id']) ? (int)$_POST['usuario_anterior_id'] : null;
        $empenho_id = !empty($_POST['empenho_id']) ? (int)$_POST['empenho_id'] : null;
        $empenho = $_POST['empenho'] ?? '';
        $data_emissao_empenho = $_POST['data_emissao_empenho'] ?? null;
        $fornecedor = $_POST['fornecedor'] ?? '';
        $cnpj_cpf_fornecedor = $_POST['cnpj_cpf_fornecedor'] ?? '';
        $cnpj_fornecedor = $_POST['cnpj_fornecedor'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $valor_nf = $_POST['valor_nf'] ?? null;
        $nd_nota_despesa = $_POST['nd_nota_despesa'] ?? '';
        $unidade_medida = $_POST['unidade_medida'] ?? '';
        $valor = $_POST['valor'] ?? null;
        $tipo_aquisicao = $_POST['tipo_aquisicao'] ?? 'compra';
        $tipo_aquisicao_descricao = $_POST['tipo_aquisicao_descricao'] ?? '';
        $numero_documento = $_POST['numero_documento'] ?? '';
        $nota_fiscal_documento = $_POST['nota_fiscal_documento'] ?? '';
        $data_entrada_aceitacao = $_POST['data_entrada_aceitacao'] ?? null;
        $status_confirmacao = $_POST['status_confirmacao'] ?? 'Pendente';
        
        // Inserir o rascunho
        $sql = "INSERT INTO rascunhos_itens (
            processo_documento, nome, descricao_detalhada, numero_serie, quantidade,
            patrimonio_novo, patrimonio_secundario, local_id, responsavel_id, estado,
            observacao, usuario_anterior_id, empenho_id, empenho, data_emissao_empenho,
            fornecedor, cnpj_cpf_fornecedor, cnpj_fornecedor, categoria, valor_nf,
            nd_nota_despesa, unidade_medida, valor, tipo_aquisicao,
            tipo_aquisicao_descricao, numero_documento, nota_fiscal_documento,
            data_entrada_aceitacao, status_confirmacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $processo_documento, $nome, $descricao_detalhada, $numero_serie, $quantidade,
            $patrimonio_novo, $patrimonio_secundario, $local_id, $responsavel_id, $estado,
            $observacao, $usuario_anterior_id, $empenho_id, $empenho, $data_emissao_empenho,
            $fornecedor, $cnpj_cpf_fornecedor, $cnpj_fornecedor, $categoria, $valor_nf,
            $nd_nota_despesa, $unidade_medida, $valor, $tipo_aquisicao,
            $tipo_aquisicao_descricao, $numero_documento, $nota_fiscal_documento,
            $data_entrada_aceitacao, $status_confirmacao
        ]);
        
        $pdo->commit();
        $message = "Rascunho criado com sucesso!";
        
        // Limpar os campos após a criação
        $_POST = [];
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = "Erro ao criar o rascunho: " . $e->getMessage();
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
    .form-section h3 {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
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
    /* Estilos para campos de formulário */
    input[type="text"], input[type="number"], input[type="date"], select, textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }
    input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus, select:focus, textarea:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
    }
    label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #555;
    }
    .help-block {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 5px;
    }
    div {
        margin-bottom: 15px;
    }
</style>

<div class="form-container">
    <h2>Criar Rascunho de Item</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="item_add_rascunho.php" method="post">
        <div class="form-grid">
            <div class="form-section">
                <h3>Dados Básicos</h3>
                <div>
                    <label>Processo/Documento:</label>
                    <input type="text" name="processo_documento" value="<?php echo htmlspecialchars($_POST['processo_documento'] ?? ''); ?>">
                </div>
                <div>
                    <label>Nome do Item: *</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                </div>
                <div>
                    <label>Descrição Detalhada:</label>
                    <textarea name="descricao_detalhada" maxlength="200" placeholder="Máximo 200 caracteres"><?php echo htmlspecialchars($_POST['descricao_detalhada'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label>Número de Série:</label>
                    <input type="text" name="numero_serie" value="<?php echo htmlspecialchars($_POST['numero_serie'] ?? ''); ?>">
                </div>
                <div>
                    <label>Quantidade:</label>
                    <input type="number" name="quantidade" min="1" value="<?php echo htmlspecialchars($_POST['quantidade'] ?? '1'); ?>">
                </div>
                <div>
                    <label>Patrimônio Principal:</label>
                    <input type="text" name="patrimonio_novo" value="<?php echo htmlspecialchars($_POST['patrimonio_novo'] ?? ''); ?>">
                </div>
                <div>
                    <label>Patrimônio Secundário:</label>
                    <input type="text" name="patrimonio_secundario" value="<?php echo htmlspecialchars($_POST['patrimonio_secundario'] ?? ''); ?>">
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
                                    data-cnpj="<?php echo $empenho_item['cnpj_fornecedor']; ?>">
                                <?php echo htmlspecialchars($empenho_item['numero_empenho'] . ' | ' . date('d/m/Y', strtotime($empenho_item['data_emissao']))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="empenho_info" class="empenho-info">
                    <h4>Informações do Empenho</h4>
                    <div><strong>Categoria:</strong> <span id="info_categoria"></span></div>
                    <div><strong>Data de Emissão:</strong> <span id="info_data_emissao"></span></div>
                    <div><strong>Fornecedor:</strong> <span id="info_fornecedor"></span></div>
                    <div><strong>CNPJ:</strong> <span id="info_cnpj"></span></div>
                </div>
                
                <div>
                    <label>Valor NF:</label>
                    <input type="number" step="0.01" name="valor_nf" value="<?php echo htmlspecialchars($_POST['valor_nf'] ?? ''); ?>">
                </div>
                <div>
                    <label>ND Nota Despesa:</label>
                    <input type="text" name="nd_nota_despesa" value="<?php echo htmlspecialchars($_POST['nd_nota_despesa'] ?? ''); ?>">
                </div>
                <div>
                    <label>Unidade Medida:</label>
                    <input type="text" name="unidade_medida" value="<?php echo htmlspecialchars($_POST['unidade_medida'] ?? ''); ?>">
                </div>
                <div>
                    <label>Valor:</label>
                    <input type="number" step="0.01" name="valor" value="<?php echo htmlspecialchars($_POST['valor'] ?? ''); ?>">
                </div>
                <div>
                    <label>Tipo Aquisição:</label>
                    <select name="tipo_aquisicao">
                        <option value="compra" <?php echo (isset($_POST['tipo_aquisicao']) && $_POST['tipo_aquisicao'] == 'compra') ? 'selected' : ''; ?>>Compra</option>
                        <option value="outra" <?php echo (isset($_POST['tipo_aquisicao']) && $_POST['tipo_aquisicao'] == 'outra') ? 'selected' : ''; ?>>Outra</option>
                    </select>
                </div>
                <div>
                    <label>Tipo Aquisição Descrição:</label>
                    <input type="text" name="tipo_aquisicao_descricao" value="<?php echo htmlspecialchars($_POST['tipo_aquisicao_descricao'] ?? ''); ?>">
                </div>
                <div>
                    <label>Número Documento:</label>
                    <input type="text" name="numero_documento" value="<?php echo htmlspecialchars($_POST['numero_documento'] ?? ''); ?>">
                </div>
                <div>
                    <label>Nota Fiscal/Documento:</label>
                    <input type="text" name="nota_fiscal_documento" value="<?php echo htmlspecialchars($_POST['nota_fiscal_documento'] ?? ''); ?>">
                </div>
                <div>
                    <label>Data de Entrada/Aceitação:</label>
                    <input type="date" name="data_entrada_aceitacao" value="<?php echo htmlspecialchars($_POST['data_entrada_aceitacao'] ?? ''); ?>">
                </div>
                <div>
                    <label>Status Confirmação:</label>
                    <select name="status_confirmacao">
                        <option value="Pendente" <?php echo (isset($_POST['status_confirmacao']) && $_POST['status_confirmacao'] == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                        <option value="Confirmado" <?php echo (isset($_POST['status_confirmacao']) && $_POST['status_confirmacao'] == 'Confirmado') ? 'selected' : ''; ?>>Confirmado</option>
                        <option value="Nao Confirmado" <?php echo (isset($_POST['status_confirmacao']) && $_POST['status_confirmacao'] == 'Nao Confirmado') ? 'selected' : ''; ?>>Não Confirmado</option>
                        <option value="Movimento Desfeito" <?php echo (isset($_POST['status_confirmacao']) && $_POST['status_confirmacao'] == 'Movimento Desfeito') ? 'selected' : ''; ?>>Movimento Desfeito</option>
                    </select>
                </div>
                
                <!-- Campos ocultos que serão preenchidos automaticamente -->
                <input type="hidden" name="empenho" id="empenho_hidden">
                <input type="hidden" name="data_emissao_empenho" id="data_emissao_empenho_hidden">
                <input type="hidden" name="fornecedor" id="fornecedor_hidden">
                <input type="hidden" name="cnpj_fornecedor" id="cnpj_fornecedor_hidden">
                <input type="hidden" name="categoria" id="categoria_hidden">
            </div>
            
            <div class="form-section">
                <h3>Localização e Responsabilidade</h3>
                <div>
                    <label>Estado:</label>
                    <select name="estado">
                        <option value="Bom" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Bom') ? 'selected' : ''; ?>>Bom</option>
                        <option value="Razoável" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Razoável') ? 'selected' : ''; ?>>Razoável</option>
                        <option value="Inservível" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Inservível') ? 'selected' : ''; ?>>Inservível</option>
                    </select>
                </div>
                <div>
                    <label>Local:</label>
                    <div class="autocomplete-container">
                        <input type="text" id="search_local" name="search_local" placeholder="Digite para buscar um local..." autocomplete="off"
                               value="<?php echo htmlspecialchars($_POST['search_local'] ?? ''); ?>">
                        <input type="hidden" name="local_id" id="local_id" value="<?php echo htmlspecialchars($_POST['local_id'] ?? ''); ?>">
                        <div id="local_suggestions" class="suggestions-list"></div>
                    </div>
                </div>
                <div>
                    <label>Responsável:</label>
                    <div class="autocomplete-container">
                        <input type="text" id="search_responsavel" name="search_responsavel" placeholder="Digite para buscar um responsável..." autocomplete="off"
                               value="<?php echo htmlspecialchars($_POST['search_responsavel'] ?? ''); ?>">
                        <input type="hidden" name="responsavel_id" id="responsavel_id" value="<?php echo htmlspecialchars($_POST['responsavel_id'] ?? ''); ?>">
                        <div id="responsavel_suggestions" class="suggestions-list"></div>
                    </div>
                </div>
                <div>
                    <label>Usuário Anterior:</label>
                    <input type="number" name="usuario_anterior_id" value="<?php echo htmlspecialchars($_POST['usuario_anterior_id'] ?? ''); ?>">
                </div>
                <div>
                    <label>Observação:</label>
                    <textarea name="observacao"><?php echo htmlspecialchars($_POST['observacao'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <div>
            <input type="submit" value="Salvar Rascunho" class="btn-custom">
            <a href="rascunhos_itens.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
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