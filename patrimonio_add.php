<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem acessar esta página
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$search_term = '';
$itens = [];
$message = '';
$error = '';

// Variáveis para persistir os dados de aquisição (inicializadas no topo)
$empenho = isset($_POST['empenho']) ? $_POST['empenho'] : '';
$data_emissao_empenho = isset($_POST['data_emissao_empenho']) ? $_POST['data_emissao_empenho'] : '';
$fornecedor = isset($_POST['fornecedor']) ? $_POST['fornecedor'] : '';
$cnpj_fornecedor = isset($_POST['cnpj_fornecedor']) ? $_POST['cnpj_fornecedor'] : '';
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : '';
$valor_nf = isset($_POST['valor_nf']) ? $_POST['valor_nf'] : '';
$nd_nota_despesa = isset($_POST['nd_nota_despesa']) ? $_POST['nd_nota_despesa'] : '';
$unidade_medida = isset($_POST['unidade_medida']) ? $_POST['unidade_medida'] : '';
$valor = isset($_POST['valor']) ? $_POST['valor'] : '';

// Variável para persistir o ID do empenho
$empenho_id = isset($_POST['empenho_id']) ? $_POST['empenho_id'] : '';

// Variáveis para a pesquisa avançada
$search_empenho = isset($_POST['search_empenho']) ? $_POST['search_empenho'] : '';
$search_valor_nf = isset($_POST['search_valor_nf']) ? $_POST['search_valor_nf'] : '';
$advanced_search_results = [];

// Determina a aba ativa
$active_tab = 'update';
if (isset($_POST['create_bulk'])) {
    $active_tab = 'create';
} elseif (isset($_GET['advanced_search'])) {
    $active_tab = 'advanced_search';
} elseif (isset($_GET['categorias'])) {
    $active_tab = 'categorias';
} elseif (isset($_GET['empenhos'])) {
    $active_tab = 'empenhos';
}

// Lógica principal de processamento POST
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Lógica de busca de itens para atualização
    if(isset($_POST['search_action'])){
        $search_term = mysqli_real_escape_string($link, $_POST['search']);
        $search_by = isset($_POST['search_by']) ? $_POST['search_by'] : 'patrimonio_novo';
        if(strlen($search_term) >= 3){
            if ($search_by == 'id') {
                $sql_search = "SELECT id, nome, patrimonio_novo FROM itens WHERE id LIKE '%$search_term%' ORDER BY id ASC";
            } elseif ($search_by == 'nome') {
                $sql_search = "SELECT id, nome, patrimonio_novo FROM itens WHERE nome LIKE '%$search_term%' ORDER BY nome ASC";
            } else {
                $sql_search = "SELECT id, nome, patrimonio_novo FROM itens WHERE patrimonio_novo LIKE '$search_term%' ORDER BY patrimonio_novo ASC";
            }
            $result_search = mysqli_query($link, $sql_search);
            if($result_search){
                while($row = mysqli_fetch_assoc($result_search)){
                    $itens[] = $row;
                }
                if(empty($itens)){
                    $message = "Nenhum item encontrado com o termo '$search_term'.";
                }
            } else {
                $error = "Erro ao buscar itens: " . mysqli_error($link);
            }
        } else {
            $error = "Por favor, insira pelo menos 3 caracteres para buscar.";
        }
    }

    mysqli_begin_transaction($link);
    try {
        // Ação: Atualizar itens existentes
        if(isset($_POST['update_existing'])){
            if(empty($_POST['item_ids'])){
                throw new Exception("Nenhum item foi selecionado para atualização.");
            }
            $item_ids = $_POST['item_ids'];
            
            // Verificar se foi selecionado um empenho para atualização
            $empenho_id = isset($_POST['empenho_id_update']) ? (int)$_POST['empenho_id_update'] : null;
            
            // Se um empenho foi selecionado, obter os dados do empenho
            $empenho_numero = isset($_POST['empenho']) ? $_POST['empenho'] : '';
            $data_emissao_empenho = isset($_POST['data_emissao_empenho']) ? $_POST['data_emissao_empenho'] : '';
            $fornecedor = isset($_POST['fornecedor']) ? $_POST['fornecedor'] : '';
            $cnpj_fornecedor = isset($_POST['cnpj_fornecedor']) ? $_POST['cnpj_fornecedor'] : '';
            $categoria = isset($_POST['categoria']) ? $_POST['categoria'] : '';
            
            if($empenho_id) {
                $sql_empenho = "SELECT e.numero_empenho, e.data_emissao, e.nome_fornecedor, e.cnpj_fornecedor, c.numero as categoria_numero, c.descricao as categoria_descricao 
                               FROM empenhos e 
                               JOIN categorias c ON e.categoria_id = c.id 
                               WHERE e.id = ?";
                $stmt_empenho = mysqli_prepare($link, $sql_empenho);
                mysqli_stmt_bind_param($stmt_empenho, "i", $empenho_id);
                if(mysqli_stmt_execute($stmt_empenho)){
                    $result_empenho = mysqli_stmt_get_result($stmt_empenho);
                    if($empenho_data = mysqli_fetch_assoc($result_empenho)){
                        $empenho_numero = $empenho_data['numero_empenho'];
                        $data_emissao_empenho = $empenho_data['data_emissao'];
                        $fornecedor = $empenho_data['nome_fornecedor'];
                        $cnpj_fornecedor = $empenho_data['cnpj_fornecedor'];
                        $categoria = $empenho_data['categoria_numero'] . ' - ' . $empenho_data['categoria_descricao'];
                    }
                }
                mysqli_stmt_close($stmt_empenho);
            }
            
            $sql_update = "UPDATE itens SET empenho_id=?, empenho=?, data_emissao_empenho=?, fornecedor=?, cnpj_fornecedor=?, categoria=?, valor_nf=?, nd_nota_despesa=?, unidade_medida=?, valor=? WHERE id=?";
            $stmt_update = mysqli_prepare($link, $sql_update);

            foreach($item_ids as $item_id){
                mysqli_stmt_bind_param($stmt_update, "isssssddssi", 
                    $empenho_id, $empenho_numero, $data_emissao_empenho, $fornecedor, 
                    $cnpj_fornecedor, $categoria, $_POST['valor_nf'], 
                    $_POST['nd_nota_despesa'], $_POST['unidade_medida'], $_POST['valor'],
                    $item_id
                );
                if(!mysqli_stmt_execute($stmt_update)){
                    throw new Exception("Erro ao atualizar o item ID " . $item_id . ": " . mysqli_stmt_error($stmt_update));
                }
            }
            mysqli_stmt_close($stmt_update);
            $message = count($item_ids) . " item(s) atualizado(s) com sucesso!";
        }

        // Ação: Criar itens em lote
        if(isset($_POST['create_bulk'])){
            $quantidade = (int)$_POST['quantidade'];
            $patrimonio_inicial = (int)$_POST['patrimonio_inicial'];
            $empenho_id = isset($_POST['empenho_id']) ? (int)$_POST['empenho_id'] : null;

            if($quantidade <= 0 || $patrimonio_inicial <= 0){
                throw new Exception("A quantidade e o patrimônio inicial devem ser números positivos.");
            }

            // Se um empenho foi selecionado, obter os dados do empenho
            $empenho_numero = '';
            $data_emissao_empenho = '';
            $fornecedor = '';
            $cnpj_fornecedor = '';
            $categoria = '';
            
            if($empenho_id) {
                $sql_empenho = "SELECT e.numero_empenho, e.data_emissao, e.nome_fornecedor, e.cnpj_fornecedor, c.numero as categoria_numero, c.descricao as categoria_descricao 
                               FROM empenhos e 
                               JOIN categorias c ON e.categoria_id = c.id 
                               WHERE e.id = ?";
                $stmt_empenho = mysqli_prepare($link, $sql_empenho);
                mysqli_stmt_bind_param($stmt_empenho, "i", $empenho_id);
                if(mysqli_stmt_execute($stmt_empenho)){
                    $result_empenho = mysqli_stmt_get_result($stmt_empenho);
                    if($empenho_data = mysqli_fetch_assoc($result_empenho)){
                        $empenho_numero = $empenho_data['numero_empenho'];
                        $data_emissao_empenho = $empenho_data['data_emissao'];
                        $fornecedor = $empenho_data['nome_fornecedor'];
                        $cnpj_fornecedor = $empenho_data['cnpj_fornecedor'];
                        $categoria = $empenho_data['categoria_numero'] . ' - ' . $empenho_data['categoria_descricao'];
                    }
                }
                mysqli_stmt_close($stmt_empenho);
            }

            $sql_insert = "INSERT INTO itens (nome, patrimonio_novo, local_id, responsavel_id, estado, observacao, descricao_detalhada, empenho_id, empenho, data_emissao_empenho, fornecedor, cnpj_fornecedor, categoria, valor_nf, nd_nota_despesa, unidade_medida, valor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($link, $sql_insert);

            for($i = 0; $i < $quantidade; $i++){
                $patrimonio_atual = $patrimonio_inicial + $i;
                mysqli_stmt_bind_param($stmt_insert, "ssiiisssisssdsss",
                    $_POST['nome'], $patrimonio_atual, $_POST['local_id'], $_POST['responsavel_id'],
                    $_POST['estado'], $_POST['observacao'], $_POST['descricao_detalhada'], $empenho_id, $empenho_numero,
                    $data_emissao_empenho, $fornecedor, $cnpj_fornecedor, $categoria, $_POST['valor_nf_bulk'],
                    $_POST['nd_nota_despesa_bulk'], $_POST['unidade_medida_bulk'], $_POST['valor_bulk']
                );
                if(!mysqli_stmt_execute($stmt_insert)){
                    throw new Exception("Erro ao inserir item com patrimônio " . $patrimonio_atual . ". O patrimônio já existe?");
                }
            }
            mysqli_stmt_close($stmt_insert);
            $message = $quantidade . " item(s) criado(s) com sucesso!";
        }

        mysqli_commit($link);
    } catch (Exception $e) {
        mysqli_rollback($link);
        $error = "Erro na transação: " . $e->getMessage();
    }
}

// Lógica da Pesquisa Avançada
if(isset($_GET['advanced_search'])) {
    $search_by_advanced = isset($_GET['advanced_search_by']) ? $_GET['advanced_search_by'] : '';
    $search_query_advanced = isset($_GET['advanced_search_query']) ? mysqli_real_escape_string($link, $_GET['advanced_search_query']) : '';

    $sql_advanced_search = "SELECT i.*, l.nome as local_nome, u.nome as responsavel_nome FROM itens i 
                            JOIN locais l ON i.local_id = l.id 
                            JOIN usuarios u ON i.responsavel_id = u.id";
    
    $where_clause_advanced = "";
    $params_advanced = [];
    $types_advanced = '';

    if (!empty($search_query_advanced)) {
        $search_term_like = '%' . $search_query_advanced . '%';
        switch ($search_by_advanced) {
            case 'id':
                $where_clause_advanced .= " WHERE i.id LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'nome':
                $where_clause_advanced .= " WHERE i.nome LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'patrimonio_novo':
                $where_clause_advanced .= " WHERE i.patrimonio_novo LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'patrimonio_secundario':
                $where_clause_advanced .= " WHERE i.patrimonio_secundario LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'local':
                $where_clause_advanced .= " WHERE l.nome LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'responsavel':
                $where_clause_advanced .= " WHERE u.nome LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'empenho':
                $where_clause_advanced .= " WHERE i.empenho LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'fornecedor':
                $where_clause_advanced .= " WHERE i.fornecedor LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'cnpj_fornecedor':
                $where_clause_advanced .= " WHERE i.cnpj_fornecedor LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            case 'valor_nf': // Renomeado para 'Número NF' no frontend, mas o campo no DB é 'valor_nf'
                $where_clause_advanced .= " WHERE i.valor_nf LIKE ?";
                $params_advanced[] = $search_term_like;
                $types_advanced .= "s";
                break;
            default:
                // Se nenhum critério válido for selecionado, não adiciona WHERE clause
                break;
        }
    }

    $sql_advanced_search .= $where_clause_advanced . " ORDER BY i.id DESC";

    $stmt_advanced = mysqli_prepare($link, $sql_advanced_search);
    
    if ($stmt_advanced) {
        if (!empty($params_advanced)) {
            $refs_advanced = [];
            foreach($params_advanced as $key => $value) {
                $refs_advanced[$key] = &$params_advanced[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_advanced, $types_advanced], $refs_advanced));
        }
        
        if(mysqli_stmt_execute($stmt_advanced)){
            $result_advanced = mysqli_stmt_get_result($stmt_advanced);
            while($row = mysqli_fetch_assoc($result_advanced)){
                $advanced_search_results[] = $row;
            }
            if(empty($advanced_search_results) && !empty($search_query_advanced)){
                    $message = "Nenhum item encontrado com os critérios fornecidos.";
                } elseif (empty($search_query_advanced)) {
                    $message = "Por favor, digite um termo para pesquisa.";
                }
        } else {
                $error = "Erro ao realizar a pesquisa avançada: " . mysqli_stmt_error($stmt_advanced);
            }
            mysqli_stmt_close($stmt_advanced);
        } else {
             $error = "Erro ao preparar a consulta de pesquisa avançada: " . mysqli_error($link);
        }
}

// Persistir o termo de busca no campo de input
if(isset($_POST['search_action']) && isset($_POST['search'])) {
    $search_term = htmlspecialchars($_POST['search']);
} else {
    $search_term = '';
}

// Buscar locais e usuários para os dropdowns
$locais_result = mysqli_query($link, "SELECT id, nome FROM locais ORDER BY nome ASC");
$usuarios_result = mysqli_query($link, "SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC");

// Buscar empenhos abertos para o select
$empenhos_abertos = [];
$sql_empenhos = "SELECT e.id, e.numero_empenho, e.data_emissao, e.nome_fornecedor, e.cnpj_fornecedor, c.numero as categoria_numero, c.descricao as categoria_descricao 
                 FROM empenhos e 
                 JOIN categorias c ON e.categoria_id = c.id 
                 WHERE e.status = 'Aberto' 
                 ORDER BY e.numero_empenho ASC";
$result_empenhos = mysqli_query($link, $sql_empenhos);
if($result_empenhos){
    while($row = mysqli_fetch_assoc($result_empenhos)){
        $empenhos_abertos[] = $row;
    }
}

?>

<style>
    .tab-container {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid #ccc;
    }
    .tab-button {
        padding: 10px 20px;
        cursor: pointer;
        border: none;
        background-color: #f1f1f1;
        border-bottom: 1px solid #ccc;
        margin-bottom: -1px;
    }
    .tab-button.active {
        background-color: #fff;
        border: 1px solid #ccc;
        border-bottom: 1px solid #fff;
    }
    .tab-content {
        display: none;
        padding: 20px;
        border: 1px solid #ccc;
        border-top: none;
    }
    .tab-content.active {
        display: block;
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
    .item-list {
        max-height: 400px; /* Aumentado para melhor visualização */
        overflow-y: auto;
        border: 1px solid #ccc;
        padding: 10px;
        margin-top: 10px;
    }
    .item-list table {
        width: 100%;
        border-collapse: collapse;
    }
    .item-list th, .item-list td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
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

<h2>Gestão de Patrimônio</h2>

<?php if($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="tab-container">
    <button class="tab-button <?php if($active_tab == 'update') echo 'active'; ?>" onclick="showTab(event, 'update')">Atualizar Itens Existentes</button>
    <button class="tab-button <?php if($active_tab == 'create') echo 'active'; ?>" onclick="showTab(event, 'create')">Criar Novos Itens em Lote</button>
    <button class="tab-button <?php if($active_tab == 'categorias') echo 'active'; ?>" onclick="showTab(event, 'categorias')">Categorias</button>
    <button class="tab-button <?php if($active_tab == 'empenhos') echo 'active'; ?>" onclick="showTab(event, 'empenhos')">Empenhos</button>
    <button class="tab-button <?php if($active_tab == 'advanced_search') echo 'active'; ?>" onclick="showTab(event, 'advanced_search')">Pesquisa Avançada</button>
</div>

<!-- Formulário de Atualização -->
<div id="update" class="tab-content <?php if($active_tab == 'update') echo 'active'; ?>">
    <form action="patrimonio_add.php" method="post">
        <h3>1. Preencha as Informações do Item</h3>
        <div class="form-grid">
            <!-- Campo de seleção de empenho -->
            <div>
                <label>Empenho:</label>
                <select name="empenho_id_update" id="empenho_id_update" onchange="preencherDadosEmpenhoUpdate()">
                    <option value="">Selecione um empenho (opcional)</option>
                    <?php foreach($empenhos_abertos as $empenho_item): ?>
                        <option value="<?php echo $empenho_item['id']; ?>" 
                                data-categoria="<?php echo htmlspecialchars($empenho_item['categoria_numero'] . ' - ' . $empenho_item['categoria_descricao']); ?>"
                                data-data-emissao="<?php echo $empenho_item['data_emissao']; ?>"
                                data-fornecedor="<?php echo htmlspecialchars($empenho_item['nome_fornecedor']); ?>"
                                data-cnpj="<?php echo $empenho_item['cnpj_fornecedor']; ?>">
                            <?php echo htmlspecialchars($empenho_item['numero_empenho']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div><label>Processo/Documento:</label><input type="text" name="processo_documento" value="<?php echo isset($_POST['processo_documento']) ? htmlspecialchars($_POST['processo_documento']) : ''; ?>"></div>
            <div><label>Nome do Item:</label><input type="text" name="nome" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" required></div>
            <div><label>Descrição Detalhada:</label><textarea name="descricao_detalhada" maxlength="200" placeholder="Máximo 200 caracteres"><?php echo isset($_POST['descricao_detalhada']) ? htmlspecialchars($_POST['descricao_detalhada']) : ''; ?></textarea></div>
            <div><label>Número de Série:</label><input type="text" name="numero_serie" value="<?php echo isset($_POST['numero_serie']) ? htmlspecialchars($_POST['numero_serie']) : ''; ?>"></div>
            <div><label>Quantidade:</label><input type="number" name="quantidade" min="1" value="<?php echo isset($_POST['quantidade']) ? htmlspecialchars($_POST['quantidade']) : '1'; ?>" required></div>
            <div><label>Valor Unitário:</label><input type="number" step="0.01" name="valor" value="<?php echo htmlspecialchars($valor); ?>"></div>
            <div><label>Nota Fiscal/Documento:</label><input type="text" name="nota_fiscal_documento" value="<?php echo isset($_POST['nota_fiscal_documento']) ? htmlspecialchars($_POST['nota_fiscal_documento']) : ''; ?>"></div>
            <div><label>Data de Entrada/Aceitação:</label><input type="date" name="data_entrada_aceitacao" value="<?php echo isset($_POST['data_entrada_aceitacao']) ? htmlspecialchars($_POST['data_entrada_aceitacao']) : ''; ?>"></div>
            <div><label>Patrimônio Inicial:</label><input type="number" name="patrimonio_inicial" value="<?php echo isset($_POST['patrimonio_inicial']) ? htmlspecialchars($_POST['patrimonio_inicial']) : ''; ?>" required></div>
            <div><label>Estado:</label>
                <select name="estado" required>
                    <option value="Em uso">Em uso</option>
                    <option value="Ocioso">Ocioso</option>
                    <option value="Recuperável">Recuperável</option>
                    <option value="Inservível">Inservível</option>
                </select>
            </div>
            <div><label>Local:</label>
                <div class="autocomplete-container">
                    <input type="text" id="search_local_update" name="search_local_update" placeholder="Digite para buscar um local..." autocomplete="off">
                    <input type="hidden" name="local_id" id="local_id_update" required>
                    <div id="local_suggestions_update" class="suggestions-list"></div>
                </div>
            </div>
            <div><label>Responsável:</label>
                <div class="autocomplete-container">
                    <input type="text" id="search_responsavel_update" name="search_responsavel_update" placeholder="Digite para buscar um responsável..." autocomplete="off">
                    <input type="hidden" name="responsavel_id" id="responsavel_id_update" required>
                    <div id="responsavel_suggestions_update" class="suggestions-list"></div>
                </div>
            </div>
            <div><label>Observação:</label><textarea name="observacao"><?php echo isset($_POST['observacao']) ? htmlspecialchars($_POST['observacao']) : ''; ?></textarea></div>
            
            <!-- Campos ocultos para os dados do empenho -->
            <input type="hidden" name="empenho" id="empenho_update">
            <input type="hidden" name="data_emissao_empenho" id="data_emissao_empenho_update">
            <input type="hidden" name="fornecedor" id="fornecedor_update">
            <input type="hidden" name="cnpj_fornecedor" id="cnpj_fornecedor_update">
            <input type="hidden" name="categoria" id="categoria_update">
        </div>
        
        <h3 style="margin-top: 20px;">2. Selecione os Itens para Atualizar</h3>
        <div class="form-inline">
            <label for="search_by">Pesquisar por:</label>
            <select name="search_by" id="search_by">
                <option value="patrimonio_novo" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'patrimonio_novo') ? 'selected' : ''; ?>>Patrimônio</option>
                <option value="id" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'id') ? 'selected' : ''; ?>>ID</option>
                <option value="nome" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'nome') ? 'selected' : ''; ?>>Nome do Item</option>
            </select>
             <input type="text" name="search" placeholder="Digite o termo de busca" value="<?php echo htmlspecialchars($search_term); ?>">
             <button type="submit" name="search_action" class="btn-custom">Buscar</button>
        </div>

        <?php if(!empty($itens)): ?>
            <div class="item-list">
                <table>
                    <thead><tr><th></th><th>Nome</th><th>Patrimônio</th></tr></thead>
                    <tbody>
                    <?php foreach($itens as $item): ?>
                        <tr>
                            <td><input type="checkbox" name="item_ids[]" value="<?php echo $item['id']; ?>"></td>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td><?php echo htmlspecialchars($item['patrimonio_novo']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px;">
                <input type="submit" name="update_existing" value="Atualizar Itens Selecionados" class="btn-custom">
            </div>
        <?php elseif(isset($_POST['search_action']) && empty($itens) && empty($error)): ?>
            <p>Nenhum item encontrado com o patrimônio inicial "<?php echo htmlspecialchars($search_term); ?>".</p>
        <?php endif; ?>
    </form>
</div>

<!-- Formulário de Criação em Lote -->
<div id="create" class="tab-content <?php if($active_tab == 'create') echo 'active'; ?>">
    <form action="patrimonio_add.php" method="post">
        <h3>Informações dos Novos Itens</h3>
        <div class="form-grid">
            <div class="form-section">
                <h4>Dados do Processo</h4>
                <!-- Campo de seleção de empenho -->
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
                                <?php echo htmlspecialchars($empenho_item['numero_empenho']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Informações do empenho selecionado -->
                <div id="empenho_info" class="empenho-info" style="display: none;">
                    <h4>Informações do Empenho</h4>
                    <div><strong>Categoria:</strong> <span id="info_categoria"></span></div>
                    <div><strong>Data de Emissão:</strong> <span id="info_data_emissao"></span></div>
                    <div><strong>Fornecedor:</strong> <span id="info_fornecedor"></span></div>
                    <div><strong>CNPJ:</strong> <span id="info_cnpj"></span></div>
                </div>
                
                <div><label>Processo/Documento:</label><input type="text" name="processo_documento"></div>
                <div><label>Nome do Item:</label><input type="text" name="nome" required></div>
                <div><label>Descrição Detalhada:</label><textarea name="descricao_detalhada" maxlength="200" placeholder="Máximo 200 caracteres"></textarea></div>
                <div><label>Número de Série:</label><input type="text" name="numero_serie"></div>
                <div><label>Quantidade:</label><input type="number" name="quantidade" min="1" value="1" required></div>
                <div><label>Valor Unitário:</label><input type="number" step="0.01" name="valor"></div>
                <div><label>Nota Fiscal/Documento:</label><input type="text" name="nota_fiscal_documento"></div>
                <div><label>Data de Entrada/Aceitação:</label><input type="date" name="data_entrada_aceitacao"></div>
                <div><label>Patrimônio Inicial:</label><input type="number" name="patrimonio_inicial" required></div>
                <div><label>Estado:</label>
                    <select name="estado" required>
                        <option value="Em uso">Em uso</option>
                        <option value="Ocioso">Ocioso</option>
                        <option value="Recuperável">Recuperável</option>
                        <option value="Inservível">Inservível</option>
                    </select>
                </div>
                <div><label>Local:</label>
                    <div class="autocomplete-container">
                        <input type="text" id="search_local_create" name="search_local_create" placeholder="Digite para buscar um local..." autocomplete="off">
                        <input type="hidden" name="local_id" id="local_id_create" required>
                        <div id="local_suggestions_create" class="suggestions-list"></div>
                    </div>
                </div>
                <div><label>Responsável:</label>
                    <div class="autocomplete-container">
                        <input type="text" id="search_responsavel_create" name="search_responsavel_create" placeholder="Digite para buscar um responsável..." autocomplete="off">
                        <input type="hidden" name="responsavel_id" id="responsavel_id_create" required>
                        <div id="responsavel_suggestions_create" class="suggestions-list"></div>
                    </div>
                </div>
                <div><label>Observação:</label><textarea name="observacao"></textarea></div>
            </div>
            <div class="form-section">
                <h4>Detalhes da Aquisição (Preenchidos automaticamente pelo empenho)</h4>
                <!-- Campos que serão preenchidos automaticamente -->
                <div><label>Categoria:</label><input type="text" name="categoria_bulk" id="categoria_bulk" readonly></div>
                <div><label>Empenho:</label><input type="text" name="empenho_bulk" id="empenho_bulk" readonly></div>
                <div><label>Data Emissão Empenho:</label><input type="date" name="data_emissao_empenho_bulk" id="data_emissao_empenho_bulk" readonly></div>
                <div><label>Fornecedor:</label><input type="text" name="fornecedor_bulk" id="fornecedor_bulk" readonly></div>
                <div><label>CNPJ Fornecedor:</label><input type="text" name="cnpj_fornecedor_bulk" id="cnpj_fornecedor_bulk" readonly></div>
                <div><label>Número NF:</label><input type="number" step="0.01" name="valor_nf_bulk"></div>
                <div><label>ND-Nota de Despesa:</label><input type="text" name="nd_nota_despesa_bulk"></div>
                <div><label>Unidade de Medida:</label><input type="text" name="unidade_medida_bulk"></div>
                <div><label>Valor Unitário:</label><input type="number" step="0.01" name="valor_bulk"></div>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <input type="submit" name="create_bulk" value="Criar Itens em Lote" class="btn-custom">
        </div>
    </form>
</div>

<!-- Formulário de Categorias -->
<div id="categorias" class="tab-content <?php if($active_tab == 'categorias') echo 'active'; ?>">
    <h3>Cadastro de Categorias</h3>
    <p>Para cadastrar uma nova categoria, <a href="categoria_add.php" target="_blank">clique aqui</a>.</p>
    
    <?php
    // Buscar todas as categorias cadastradas
    $sql_categorias = "SELECT * FROM categorias ORDER BY numero ASC";
    $result_categorias = mysqli_query($link, $sql_categorias);
    $categorias = [];
    if($result_categorias){
        while($row = mysqli_fetch_assoc($result_categorias)){
            $categorias[] = $row;
        }
    }
    ?>
    
    <?php if(!empty($categorias)): ?>
        <h4 style="margin-top: 20px;">Categorias Cadastradas</h4>
        <div class="item-list">
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Descrição</th>
                        <th>Data de Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categorias as $categoria): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($categoria['numero']); ?></td>
                            <td><?php echo htmlspecialchars($categoria['descricao']); ?></td>
                            <td><?php echo isset($categoria['data_cadastro']) ? date('d/m/Y H:i', strtotime($categoria['data_cadastro'])) : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Nenhuma categoria cadastrada.</p>
    <?php endif; ?>
</div>

<!-- Formulário de Empenhos -->
<div id="empenhos" class="tab-content <?php if($active_tab == 'empenhos') echo 'active'; ?>">
    <h3>Cadastro de Empenhos</h3>
    <p>Para cadastrar um novo empenho, <a href="empenho_add.php" target="_blank">clique aqui</a>.</p>
    
    <?php
    // Buscar todos os empenhos cadastrados com suas categorias
    $sql_todos_empenhos = "SELECT e.*, c.numero as categoria_numero, c.descricao as categoria_descricao 
                           FROM empenhos e 
                           JOIN categorias c ON e.categoria_id = c.id 
                           ORDER BY e.data_cadastro DESC";
    $result_todos_empenhos = mysqli_query($link, $sql_todos_empenhos);
    $todos_empenhos = [];
    if($result_todos_empenhos){
        while($row = mysqli_fetch_assoc($result_todos_empenhos)){
            $todos_empenhos[] = $row;
        }
    }
    ?>
    
    <?php if(!empty($todos_empenhos)): ?>
        <h4 style="margin-top: 20px;">Empenhos Cadastrados</h4>
        <div class="item-list">
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Data de Emissão</th>
                        <th>Fornecedor</th>
                        <th>CNPJ</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Data de Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($todos_empenhos as $empenho_item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($empenho_item['numero_empenho']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($empenho_item['data_emissao'])); ?></td>
                            <td><?php echo htmlspecialchars($empenho_item['nome_fornecedor']); ?></td>
                            <td><?php echo htmlspecialchars($empenho_item['cnpj_fornecedor']); ?></td>
                            <td><?php echo htmlspecialchars($empenho_item['categoria_numero'] . ' - ' . $empenho_item['categoria_descricao']); ?></td>
                            <td><?php echo htmlspecialchars($empenho_item['status']); ?></td>
                            <td><?php echo isset($empenho_item['data_cadastro']) ? date('d/m/Y H:i', strtotime($empenho_item['data_cadastro'])) : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Nenhum empenho cadastrado.</p>
    <?php endif; ?>
</div>

<!-- Formulário de Pesquisa Avançada -->
<div id="advanced_search" class="tab-content <?php if($active_tab == 'advanced_search') echo 'active'; ?>">
    <h3>Pesquisa Avançada de Itens</h3>
    <form action="patrimonio_add.php" method="get">
        <div class="form-inline">
            <label for="advanced_search_by">Pesquisar por:</label>
            <select name="advanced_search_by" id="advanced_search_by">
                <option value="nome" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'nome') ? 'selected' : ''; ?>>Nome</option>
                <option value="id" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'id') ? 'selected' : ''; ?>>ID</option>
                <option value="patrimonio_novo" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'patrimonio_novo') ? 'selected' : ''; ?>>Patrimônio</option>
                <option value="patrimonio_secundario" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'patrimonio_secundario') ? 'selected' : ''; ?>>Patrimônio Secundário</option>
                <option value="local" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'local') ? 'selected' : ''; ?>>Local</option>
                <option value="responsavel" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'responsavel') ? 'selected' : ''; ?>>Responsável</option>
                <option value="empenho" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'empenho') ? 'selected' : ''; ?>>Empenho</option>
                <option value="fornecedor" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'fornecedor') ? 'selected' : ''; ?>>Fornecedor</option>
                <option value="cnpj_fornecedor" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'cnpj_fornecedor') ? 'selected' : ''; ?>>CNPJ Fornecedor</option>
                <option value="valor_nf" <?php echo (isset($_GET['advanced_search_by']) && $_GET['advanced_search_by'] == 'valor_nf') ? 'selected' : ''; ?>>Número NF</option>
            </select>
            <input type="text" name="advanced_search_query" placeholder="Digite o termo de pesquisa" value="<?php echo isset($_GET['advanced_search_query']) ? htmlspecialchars($_GET['advanced_search_query']) : ''; ?>">
            <input type="submit" name="advanced_search" value="Pesquisar" class="btn-custom">
        </div>

    <?php if(!empty($advanced_search_results)): ?>
        <h3 style="margin-top: 20px;">Resultados da Pesquisa</h3>
        <div class="item-list">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Patrimônio</th>
                        <th>Local</th>
                        <th>Responsável</th>
                        <th>Empenho</th>
                        <th>Número NF</th>
                        <th>Fornecedor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($advanced_search_results as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                        <td><?php echo htmlspecialchars($item['patrimonio_novo']); ?></td>
                        <td><?php echo htmlspecialchars($item['local_nome']); ?></td>
                        <td><?php echo htmlspecialchars($item['responsavel_nome']); ?></td>
                        <td><?php echo htmlspecialchars($item['empenho']); ?></td>
                        <td><?php echo htmlspecialchars($item['valor_nf']); ?></td>
                        <td><?php echo htmlspecialchars($item['fornecedor']); ?></td>
                        <td>
                            <a href="item_details.php?id=<?php echo $item['id']; ?>" title="Ver Detalhes"><i class="fas fa-eye"></i></a>
                            <a href="item_edit.php?id=<?php echo $item['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif(isset($_POST['advanced_search'])): ?>
        <p style="margin-top: 15px;">Nenhum item encontrado para os critérios de pesquisa.</p>
    <?php endif; ?>
</div>


<script>
function showTab(event, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-button");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    document.getElementById(tabName).classList.add("active");
    event.currentTarget.className += " active";
}

// Função para preencher os dados do empenho selecionado
function preencherDadosEmpenho() {
    const selectEmpenho = document.getElementById('empenho_id');
    const empenhoInfo = document.getElementById('empenho_info');
    const selectedOption = selectEmpenho.options[selectEmpenho.selectedIndex];
    
    if (selectedOption.value === "") {
        // Ocultar informações do empenho e limpar campos
        empenhoInfo.style.display = 'none';
        document.getElementById('categoria_bulk').value = '';
        document.getElementById('empenho_bulk').value = '';
        document.getElementById('data_emissao_empenho_bulk').value = '';
        document.getElementById('fornecedor_bulk').value = '';
        document.getElementById('cnpj_fornecedor_bulk').value = '';
        return;
    }
    
    // Obter dados do empenho selecionado
    const categoria = selectedOption.getAttribute('data-categoria');
    const dataEmissao = selectedOption.getAttribute('data-data-emissao');
    const fornecedor = selectedOption.getAttribute('data-fornecedor');
    const cnpj = selectedOption.getAttribute('data-cnpj');
    const numeroEmpenho = selectedOption.textContent;
    
    // Preencher campos
    document.getElementById('categoria_bulk').value = categoria;
    document.getElementById('empenho_bulk').value = numeroEmpenho;
    document.getElementById('data_emissao_empenho_bulk').value = dataEmissao;
    document.getElementById('fornecedor_bulk').value = fornecedor;
    document.getElementById('cnpj_fornecedor_bulk').value = cnpj;
    
    // Preencher informações do empenho
    document.getElementById('info_categoria').textContent = categoria;
    document.getElementById('info_data_emissao').textContent = dataEmissao;
    document.getElementById('info_fornecedor').textContent = fornecedor;
    document.getElementById('info_cnpj').textContent = cnpj;
    
    // Mostrar informações do empenho
    empenhoInfo.style.display = 'block';
}

// Função para preencher os dados do empenho na seção de atualização
function preencherDadosEmpenhoUpdate() {
    const selectEmpenho = document.getElementById('empenho_id_update');
    const selectedOption = selectEmpenho.options[selectEmpenho.selectedIndex];
    
    if (selectedOption.value === "") {
        // Limpar campos ocultos
        document.getElementById('empenho_update').value = '';
        document.getElementById('data_emissao_empenho_update').value = '';
        document.getElementById('fornecedor_update').value = '';
        document.getElementById('cnpj_fornecedor_update').value = '';
        document.getElementById('categoria_update').value = '';
        return;
    }
    
    // Obter dados do empenho selecionado
    const categoria = selectedOption.getAttribute('data-categoria');
    const dataEmissao = selectedOption.getAttribute('data-data-emissao');
    const fornecedor = selectedOption.getAttribute('data-fornecedor');
    const cnpj = selectedOption.getAttribute('data-cnpj');
    const numeroEmpenho = selectedOption.textContent;
    
    // Preencher campos ocultos
    document.getElementById('empenho_update').value = numeroEmpenho;
    document.getElementById('data_emissao_empenho_update').value = dataEmissao;
    document.getElementById('fornecedor_update').value = fornecedor;
    document.getElementById('cnpj_fornecedor_update').value = cnpj;
    document.getElementById('categoria_update').value = categoria;
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

// Configurar autocomplete para ambas as abas
document.addEventListener('DOMContentLoaded', function() {
    // Aba de Atualização
    const searchLocalUpdate = document.getElementById('search_local_update');
    const localSuggestionsUpdate = document.getElementById('local_suggestions_update');
    const localIdUpdate = document.getElementById('local_id_update');
    
    const searchResponsavelUpdate = document.getElementById('search_responsavel_update');
    const responsavelSuggestionsUpdate = document.getElementById('responsavel_suggestions_update');
    const responsavelIdUpdate = document.getElementById('responsavel_id_update');
    
    if (searchLocalUpdate && localSuggestionsUpdate && localIdUpdate) {
        setupAutocomplete(searchLocalUpdate, localSuggestionsUpdate, localIdUpdate, 'api/search_locais.php');
    }
    
    if (searchResponsavelUpdate && responsavelSuggestionsUpdate && responsavelIdUpdate) {
        setupAutocomplete(searchResponsavelUpdate, responsavelSuggestionsUpdate, responsavelIdUpdate, 'api/search_usuarios.php');
    }
    
    // Aba de Criação
    const searchLocalCreate = document.getElementById('search_local_create');
    const localSuggestionsCreate = document.getElementById('local_suggestions_create');
    const localIdCreate = document.getElementById('local_id_create');
    
    const searchResponsavelCreate = document.getElementById('search_responsavel_create');
    const responsavelSuggestionsCreate = document.getElementById('responsavel_suggestions_create');
    const responsavelIdCreate = document.getElementById('responsavel_id_create');
    
    if (searchLocalCreate && localSuggestionsCreate && localIdCreate) {
        setupAutocomplete(searchLocalCreate, localSuggestionsCreate, localIdCreate, 'api/search_locais.php');
    }
    
    if (searchResponsavelCreate && responsavelSuggestionsCreate && responsavelIdCreate) {
        setupAutocomplete(searchResponsavelCreate, responsavelSuggestionsCreate, responsavelIdCreate, 'api/search_usuarios.php');
    }
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>