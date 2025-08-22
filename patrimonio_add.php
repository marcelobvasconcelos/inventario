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
$valor = isset($_POST['valor']) ? $_POST['valor'] : '';

// Variável para persistir o ID do empenho
$empenho_id = isset($_POST['empenho_id']) ? $_POST['empenho_id'] : '';

// Variáveis para a pesquisa avançada
$advanced_search_results = [];

// Determina a aba ativa
$active_tab = 'update';
if (isset($_POST['create_bulk'])) {
    $active_tab = 'create';
} elseif (isset($_POST['create_draft_bulk'])) {
    $active_tab = 'draft';
} elseif (isset($_GET['advanced_search'])) {
    $active_tab = 'advanced_search';
} elseif (isset($_GET['categorias'])) {
    $active_tab = 'categorias';
} elseif (isset($_GET['empenhos'])) {
    $active_tab = 'empenhos';
}

// Lógica principal de processamento POST
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Lógica de busca de itens para atualização (pesquisa automática)
    if(isset($_POST['search_action']) || (isset($_POST['search']) && strlen($_POST['search']) >= 3)){
        $search_term = isset($_POST['search']) ? mysqli_real_escape_string($link, $_POST['search']) : '';
        $search_by = isset($_POST['search_by']) ? $_POST['search_by'] : 'patrimonio_novo';
        
        if(strlen($search_term) >= 3){
            if ($search_by == 'id') {
                $sql_search = "SELECT id, nome, patrimonio_novo FROM itens WHERE id LIKE '%$search_term%' ORDER BY id ASC";
            } elseif ($search_by == 'nome') {
                $sql_search = "SELECT id, nome, patrimonio_novo FROM itens WHERE nome LIKE '%$search_term%' ORDER BY nome ASC";
            } elseif ($search_by == 'local') {
                $sql_search = "SELECT i.id, i.nome, i.patrimonio_novo 
                              FROM itens i 
                              JOIN locais l ON i.local_id = l.id 
                              WHERE l.nome LIKE '%$search_term%' 
                              ORDER BY l.nome ASC";
            } elseif ($search_by == 'responsavel') {
                $sql_search = "SELECT i.id, i.nome, i.patrimonio_novo 
                              FROM itens i 
                              JOIN usuarios u ON i.responsavel_id = u.id 
                              WHERE u.nome LIKE '%$search_term%' 
                              ORDER BY u.nome ASC";
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
        }
    }

    mysqli_begin_transaction($link);
    try {
        // Ação: Criar rascunhos em lote
        if(isset($_POST['create_draft_bulk'])){
            $quantidade = (int)$_POST['quantidade'];
            
            if($quantidade <= 0){
                throw new Exception("A quantidade deve ser um número positivo.");
            }
            
            // Se um empenho foi selecionado, obter os dados do empenho
            $empenho_id = isset($_POST['empenho_id']) ? (int)$_POST['empenho_id'] : null;
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
            
            // Gerar patrimônios temporários sequenciais
            // Primeiro, encontrar o próximo número sequencial para patrimônios temporários
            $sql_next_temp = "SELECT MAX(CAST(SUBSTRING(patrimonio_novo, 5) AS UNSIGNED)) as max_temp FROM rascunhos_itens WHERE patrimonio_novo LIKE 'temp%'";
            $result_next_temp = mysqli_query($link, $sql_next_temp);
            $next_temp_number = 1;
            if($result_next_temp){
                $row_temp = mysqli_fetch_assoc($result_next_temp);
                $next_temp_number = $row_temp['max_temp'] ? $row_temp['max_temp'] + 1 : 1;
            }
            
            // Inserir os rascunhos
            $sql_insert = "INSERT INTO rascunhos_itens (
                nome, patrimonio_novo, local_id, responsavel_id, estado, observacao, 
                descricao_detalhada, empenho_id, empenho, data_emissao_empenho, 
                fornecedor, cnpj_fornecedor, categoria, data_criacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt_insert = mysqli_prepare($link, $sql_insert);
            
            for($i = 0; $i < $quantidade; $i++){
                // Gerar patrimônio temporário
                $patrimonio_temp = 'temp' . ($next_temp_number + $i);
                
                mysqli_stmt_bind_param($stmt_insert, "sssssssssssss",
                    $_POST['nome'], $patrimonio_temp, $_POST['local_id'], $_POST['responsavel_id'],
                    $_POST['estado'], $_POST['observacao'], $_POST['descricao_detalhada'], $empenho_id, $empenho_numero,
                    $data_emissao_empenho, $fornecedor, $cnpj_fornecedor, $categoria
                );
                if(!mysqli_stmt_execute($stmt_insert)){
                    throw new Exception("Erro ao inserir rascunho com patrimônio " . $patrimonio_temp . ".");
                }
            }
            mysqli_stmt_close($stmt_insert);
            
            $message = $quantidade . " rascunho(s) criado(s) com sucesso!";
        }
        
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
            
            $sql_update = "UPDATE itens SET 
                processo_documento = IFNULL(?, processo_documento),
                nome = IFNULL(?, nome),
                descricao_detalhada = IFNULL(?, descricao_detalhada),
                numero_serie = IFNULL(?, numero_serie),
                quantidade = IFNULL(?, quantidade),
                valor = IFNULL(?, valor),
                nota_fiscal_documento = IFNULL(?, nota_fiscal_documento),
                data_entrada_aceitacao = IFNULL(?, data_entrada_aceitacao),
                estado = IFNULL(?, estado),
                local_id = IFNULL(?, local_id),
                responsavel_id = IFNULL(?, responsavel_id),
                observacao = IFNULL(?, observacao),
                empenho_id = IFNULL(?, empenho_id),
                empenho = IFNULL(?, empenho),
                data_emissao_empenho = IFNULL(?, data_emissao_empenho),
                fornecedor = IFNULL(?, fornecedor),
                cnpj_fornecedor = IFNULL(?, cnpj_fornecedor),
                categoria = IFNULL(?, categoria)
            WHERE id = ?";
            $stmt_update = mysqli_prepare($link, $sql_update);

            foreach($item_ids as $item_id){
                $processo_documento = isset($_POST['processo_documento']) ? $_POST['processo_documento'] : '';
                $nome_item = isset($_POST['nome']) ? $_POST['nome'] : '';
                $descricao_detalhada = isset($_POST['descricao_detalhada']) ? $_POST['descricao_detalhada'] : '';
                $numero_serie = isset($_POST['numero_serie']) ? $_POST['numero_serie'] : '';
                $quantidade = isset($_POST['quantidade']) ? $_POST['quantidade'] : '';
                $valor_unitario = isset($_POST['valor']) ? $_POST['valor'] : '';
                $nota_fiscal_documento = isset($_POST['nota_fiscal_documento']) ? $_POST['nota_fiscal_documento'] : '';
                $data_entrada_aceitacao = isset($_POST['data_entrada_aceitacao']) ? $_POST['data_entrada_aceitacao'] : '';
                $estado_item = isset($_POST['estado']) ? $_POST['estado'] : '';
                $local_id_post = isset($_POST['local_id']) ? $_POST['local_id'] : '';
                $responsavel_id_post = isset($_POST['responsavel_id']) ? $_POST['responsavel_id'] : '';
                $observacao_post = isset($_POST['observacao']) ? $_POST['observacao'] : '';
                // converter campos vazios em NULL para nao sobrescrever valores existentes
                $processo_documento = ($processo_documento === '' ? null : $processo_documento);
                $nome_item = ($nome_item === '' ? null : $nome_item);
                $descricao_detalhada = ($descricao_detalhada === '' ? null : $descricao_detalhada);
                $numero_serie = ($numero_serie === '' ? null : $numero_serie);
                $quantidade = ($quantidade === '' ? null : $quantidade);
                $valor_unitario = ($valor_unitario === '' ? null : $valor_unitario);
                $nota_fiscal_documento = ($nota_fiscal_documento === '' ? null : $nota_fiscal_documento);
                $data_entrada_aceitacao = ($data_entrada_aceitacao === '' ? null : $data_entrada_aceitacao);
                $estado_item = ($estado_item === '' ? null : $estado_item);
                $local_id_post = ($local_id_post === '' ? null : $local_id_post);
                $responsavel_id_post = ($responsavel_id_post === '' ? null : $responsavel_id_post);
                $observacao_post = ($observacao_post === '' ? null : $observacao_post);
                $empenho_id = ($empenho_id === 0 ? null : $empenho_id);
                $empenho_numero = ($empenho_numero === '' ? null : $empenho_numero);
                $data_emissao_empenho = ($data_emissao_empenho === '' ? null : $data_emissao_empenho);
                $fornecedor = ($fornecedor === '' ? null : $fornecedor);
                $cnpj_fornecedor = ($cnpj_fornecedor === '' ? null : $cnpj_fornecedor);
                $categoria = ($categoria === '' ? null : $categoria);
                mysqli_stmt_bind_param(
                    $stmt_update,
                    "sssssssssssssssssss",
                    $processo_documento,
                    $nome_item,
                    $descricao_detalhada,
                    $numero_serie,
                    $quantidade,
                    $valor_unitario,
                    $nota_fiscal_documento,
                    $data_entrada_aceitacao,
                    $estado_item,
                    $local_id_post,
                    $responsavel_id_post,
                    $observacao_post,
                    $empenho_id,
                    $empenho_numero,
                    $data_emissao_empenho,
                    $fornecedor,
                    $cnpj_fornecedor,
                    $categoria,
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

            // Determinar o status de confirmação com base no responsável
            $usuario_logado_id = $_SESSION['id'];
            $responsavel_id = (int)$_POST['responsavel_id'];
            $status_confirmacao = ($responsavel_id === $usuario_logado_id) ? 'Confirmado' : 'Pendente';
            
            // Inserir os itens
            $sql_insert = "INSERT INTO itens (nome, patrimonio_novo, local_id, responsavel_id, estado, observacao, descricao_detalhada, empenho_id, empenho, data_emissao_empenho, fornecedor, cnpj_fornecedor, categoria, data_cadastro, status_confirmacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
            $stmt_insert = mysqli_prepare($link, $sql_insert);

            $itens_criados = [];
            for($i = 0; $i < $quantidade; $i++){
                $patrimonio_atual = $patrimonio_inicial + $i;
                mysqli_stmt_bind_param($stmt_insert, "ssiiisssisssss",
                    $_POST['nome'], $patrimonio_atual, $_POST['local_id'], $responsavel_id,
                    $_POST['estado'], $_POST['observacao'], $_POST['descricao_detalhada'], $empenho_id, $empenho_numero,
                    $data_emissao_empenho, $fornecedor, $cnpj_fornecedor, $categoria, $status_confirmacao
                );
                if(!mysqli_stmt_execute($stmt_insert)){
                    throw new Exception("Erro ao inserir item com patrimônio " . $patrimonio_atual . ". O patrimônio já existe?");
                }
                // Armazenar os IDs dos itens criados para notificação
                $itens_criados[] = mysqli_insert_id($link);
            }
            mysqli_stmt_close($stmt_insert);
            
            // Se o status é pendente, criar movimentações e notificações
            if ($status_confirmacao === 'Pendente') {
                $ok = true;
                $local_id = (int)$_POST['local_id'];
                
                foreach($itens_criados as $novo_item_id) {
                    // 1) Criar uma movimentação inicial (cadastro) com origem = destino = local atual
                    $sql_mov = "INSERT INTO movimentacoes (item_id, local_origem_id, local_destino_id, usuario_id, usuario_anterior_id, usuario_destino_id) VALUES (?, ?, ?, ?, NULL, ?)";
                    $stmt_mov = mysqli_prepare($link, $sql_mov);
                    if ($stmt_mov) {
                        mysqli_stmt_bind_param($stmt_mov, "iiiii", $novo_item_id, $local_id, $local_id, $usuario_logado_id, $responsavel_id);
                        if (!mysqli_stmt_execute($stmt_mov)) { 
                            $ok = false; 
                            $error = "Erro ao criar movimentação para o item ID " . $novo_item_id . ": " . mysqli_stmt_error($stmt_mov);
                        }
                        $movimentacao_id = mysqli_insert_id($link);
                        mysqli_stmt_close($stmt_mov);
                    } else { 
                        $ok = false; 
                        $error = "Erro ao preparar consulta de movimentação: " . mysqli_error($link);
                    }

                    // 2) Criar a notificação pendente atrelada à movimentação
                    if ($ok) {
                        $sql_nm = "INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao) VALUES (?, ?, ?, 'Pendente')";
                        $stmt_nm = mysqli_prepare($link, $sql_nm);
                        if ($stmt_nm) {
                            mysqli_stmt_bind_param($stmt_nm, "iii", $movimentacao_id, $novo_item_id, $responsavel_id);
                            if (!mysqli_stmt_execute($stmt_nm)) { 
                                $ok = false; 
                                $error = "Erro ao criar notificação para o item ID " . $novo_item_id . ": " . mysqli_stmt_error($stmt_nm);
                            }
                            mysqli_stmt_close($stmt_nm);
                        } else { 
                            $ok = false; 
                            $error = "Erro ao preparar consulta de notificação: " . mysqli_error($link);
                        }
                    }
                    
                    if (!$ok) {
                        throw new Exception($error);
                    }
                }
            }
            
            $message = $quantidade . " item(s) criado(s) com sucesso!";
        }

        // Ação: Criar rascunhos em lote
        if(isset($_POST['create_draft_bulk'])){
            $quantidade = (int)$_POST['quantidade'];
            
            if($quantidade <= 0){
                throw new Exception("A quantidade deve ser um número positivo.");
            }
            
            // Se um empenho foi selecionado, obter os dados do empenho
            $empenho_id = isset($_POST['empenho_id']) ? (int)$_POST['empenho_id'] : null;
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
            
            // Gerar um número de sequência para os patrimônios temporários
            // Para garantir unicidade, vamos usar o timestamp + um número sequencial
            $timestamp = time();
            $sequencia_inicial = rand(1000, 9999); // Número aleatório para evitar conflitos
            
            // Inserir os rascunhos
            $sql_insert = "INSERT INTO rascunhos_itens (nome, patrimonio_novo, local_id, responsavel_id, estado, observacao, descricao_detalhada, empenho_id, empenho, data_emissao_empenho, fornecedor, cnpj_fornecedor, categoria, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt_insert = mysqli_prepare($link, $sql_insert);
            
            for($i = 0; $i < $quantidade; $i++){
                $patrimonio_temp = "temp" . ($timestamp + $i) . $sequencia_inicial;
                mysqli_stmt_bind_param($stmt_insert, "ssiiisssissss",
                    $_POST['nome'], $patrimonio_temp, $_POST['local_id'], $_POST['responsavel_id'],
                    $_POST['estado'], $_POST['observacao'], $_POST['descricao_detalhada'], $empenho_id, $empenho_numero,
                    $data_emissao_empenho, $fornecedor, $cnpj_fornecedor, $categoria
                );
                if(!mysqli_stmt_execute($stmt_insert)){
                    throw new Exception("Erro ao inserir rascunho com patrimônio " . $patrimonio_temp . ".");
                }
            }
            mysqli_stmt_close($stmt_insert);
            
            $message = $quantidade . " rascunho(s) criado(s) com sucesso!";
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

// Empenhos para a aba de atualização (listar todos para preservar o empenho atual do item)
$empenhos_update = [];
$sql_empenhos_update = "SELECT e.id, e.numero_empenho, e.data_emissao, e.nome_fornecedor, e.cnpj_fornecedor, c.numero as categoria_numero, c.descricao as categoria_descricao 
                        FROM empenhos e 
                        JOIN categorias c ON e.categoria_id = c.id 
                        ORDER BY e.numero_empenho ASC";
$result_empenhos_update = mysqli_query($link, $sql_empenhos_update);
if($result_empenhos_update){
    while($row = mysqli_fetch_assoc($result_empenhos_update)){
        $empenhos_update[] = $row;
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
    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(280px, 1fr));
        gap: 20px;
        align-items: start;
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
    <button class="tab-button <?php if($active_tab == 'rascunhos') echo 'active'; ?>" onclick="showTab(event, 'rascunhos')">Rascunhos</button>
    <button class="tab-button <?php if($active_tab == 'advanced_search') echo 'active'; ?>" onclick="showTab(event, 'advanced_search')">Pesquisa Avançada</button>
</div>

<!-- Formulário de Atualização -->
<div id="update" class="tab-content <?php if($active_tab == 'update') echo 'active'; ?>">
    <form action="patrimonio_add.php" method="post" class="form-inline" style="margin-bottom:15px;">
        <h3>1. Buscar Item</h3>
        <label for="search_by">Pesquisar por:</label>
        <select name="search_by" id="search_by">
            <option value="patrimonio_novo" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'patrimonio_novo') ? 'selected' : ''; ?>>Patrimônio</option>
            <option value="id" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'id') ? 'selected' : ''; ?>>ID</option>
            <option value="nome" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'nome') ? 'selected' : ''; ?>>Nome do Item</option>
            <option value="local" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'local') ? 'selected' : ''; ?>>Local</option>
            <option value="responsavel" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'responsavel') ? 'selected' : ''; ?>>Responsável</option>
        </select>
        <input type="text" name="search" id="search_input" placeholder="Digite o termo de busca (a pesquisa é automática)" value="<?php echo htmlspecialchars($search_term); ?>">
        <!-- Removido o botão de busca -->
    </form>
    <form action="patrimonio_add.php" method="post">
        <h3>2. Selecione o Item para Atualizar</h3>
        <?php if(!empty($itens)): ?>
            <div class="item-list">
                <table>
                    <thead><tr><th></th><th>Nome</th><th>Patrimônio</th></tr></thead>
                    <tbody>
                    <?php foreach($itens as $item): ?>
                        <tr>
                            <td><input type="checkbox" name="item_ids[]" value="<?php echo $item['id']; ?>" class="select-item-checkbox" onchange="handleItemSelection()"></td>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td><?php echo htmlspecialchars($item['patrimonio_novo']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif(isset($_POST['search_action']) && empty($itens) && empty($error)): ?>
            <p>Nenhum item encontrado com o termo de busca.</p>
        <?php endif; ?>
        <h3 style="margin-top: 20px;">3. Preencha as Informações do Item</h3>
        <div class="form-grid-3">
            <!-- Coluna 1: Empenho + Detalhes da Aquisição -->
            <div class="form-section">
                <div id="edit_empenho_select"><label>Empenho:</label>
                <select name="empenho_id_update" id="empenho_id_update" onchange="preencherDadosEmpenhoUpdate()">
                    <option value="">Selecione um empenho (opcional)</option>
                    <?php foreach($empenhos_update as $empenho_item): ?>
                        <option value="<?php echo $empenho_item['id']; ?>" 
                                data-categoria="<?php echo htmlspecialchars($empenho_item['categoria_numero'] . ' - ' . $empenho_item['categoria_descricao']); ?>"
                                data-data-emissao="<?php echo $empenho_item['data_emissao']; ?>"
                                data-fornecedor="<?php echo htmlspecialchars($empenho_item['nome_fornecedor']); ?>"
                                data-cnpj="<?php echo $empenho_item['cnpj_fornecedor']; ?>">
                            <?php echo htmlspecialchars($empenho_item['numero_empenho'] . ' | ' . date('d/m/Y', strtotime($empenho_item['data_emissao']))); ?>
                        </option>
                    <?php endforeach; ?>
                </select></div>
                <h4 style="margin-top:10px;">Detalhes da Aquisição</h4>
                <div id="edit_acq_categoria"><label>Categoria:</label><input type="text" name="categoria" id="categoria_update"></div>
                <div id="edit_acq_empenho"><label>Número do Empenho:</label><input type="text" name="empenho" id="empenho_update"></div>
                <div id="edit_acq_data_emissao"><label>Data Emissão Empenho:</label><input type="date" name="data_emissao_empenho" id="data_emissao_empenho_update"></div>
                <div id="edit_acq_fornecedor"><label>Fornecedor:</label><input type="text" name="fornecedor" id="fornecedor_update"></div>
                <div id="edit_acq_cnpj"><label>CNPJ Fornecedor:</label><input type="text" name="cnpj_fornecedor" id="cnpj_fornecedor_update"></div>
            </div>

            <!-- Coluna 2: Dados atuais do item (somente leitura) -->
            <div class="form-section">
                <h4>Dados atuais do item</h4>
                <div><label>Patrimônio:</label><input type="text" id="current_patrimonio" readonly></div>
                <div><label>Nome do Item:</label><input type="text" id="current_nome" readonly></div>
                <div><label>Descrição Detalhada:</label><textarea id="current_descricao_detalhada" readonly></textarea></div>
                <div><label>Número de Série:</label><input type="text" id="current_numero_serie" readonly></div>
                <div><label>Quantidade:</label><input type="text" id="current_quantidade" readonly></div>
                <div><label>Valor Unitário:</label><input type="text" id="current_valor" readonly></div>
                <div><label>Nota Fiscal/Documento:</label><input type="text" id="current_nota_fiscal_documento" readonly></div>
                <div><label>Data Entrada/Aceitação:</label><input type="text" id="current_data_entrada_aceitacao" readonly></div>
                <div><label>Estado:</label><input type="text" id="current_estado" readonly></div>
                <div><label>Local:</label><input type="text" id="current_local" readonly></div>
                <div><label>Responsável:</label><input type="text" id="current_responsavel" readonly></div>
                <div><label>Observação:</label><textarea id="current_observacao" readonly></textarea></div>
            </div>

            <!-- Coluna 3: Campos para atualização -->
            <div class="form-section">
                <h4>Atualizar dados do item</h4>
                <div id="edit_processo_documento"><label>Processo/Documento:</label><input type="text" name="processo_documento" value="<?php echo isset($_POST['processo_documento']) ? htmlspecialchars($_POST['processo_documento']) : ''; ?>"></div>
                <div id="edit_nome"><label>Nome do Item:</label><input type="text" name="nome" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"></div>
                <div id="edit_descricao_detalhada"><label>Descrição Detalhada:</label><textarea name="descricao_detalhada" maxlength="200" placeholder="Máximo 200 caracteres"><?php echo isset($_POST['descricao_detalhada']) ? htmlspecialchars($_POST['descricao_detalhada']) : ''; ?></textarea></div>
                <div id="edit_numero_serie"><label>Número de Série:</label><input type="text" name="numero_serie" value="<?php echo isset($_POST['numero_serie']) ? htmlspecialchars($_POST['numero_serie']) : ''; ?>"></div>
                <div id="edit_quantidade"><label>Quantidade:</label><input type="number" name="quantidade" min="1" value="<?php echo isset($_POST['quantidade']) ? htmlspecialchars($_POST['quantidade']) : '1'; ?>"></div>
                <div id="edit_valor"><label>Valor Unitário:</label><input type="number" step="0.01" name="valor" value="<?php echo htmlspecialchars($valor); ?>"></div>
                <div id="edit_nota_fiscal_documento"><label>Nota Fiscal/Documento:</label><input type="text" name="nota_fiscal_documento" value="<?php echo isset($_POST['nota_fiscal_documento']) ? htmlspecialchars($_POST['nota_fiscal_documento']) : ''; ?>"></div>
                <div id="edit_data_entrada_aceitacao"><label>Data de Entrada/Aceitação:</label><input type="date" name="data_entrada_aceitacao" value="<?php echo isset($_POST['data_entrada_aceitacao']) ? htmlspecialchars($_POST['data_entrada_aceitacao']) : ''; ?>"></div>
                <div id="edit_estado"><label>Estado:</label>
                    <select name="estado">
                        <option value="Em uso">Em uso</option>
                        <option value="Ocioso">Ocioso</option>
                        <option value="Recuperável">Recuperável</option>
                        <option value="Inservível">Inservível</option>
                    </select>
                </div>
                <div id="edit_local"><label>Local:</label>
                    <div class="autocomplete-container">
                        <input type="text" id="search_local_update" name="search_local_update" placeholder="Digite para buscar um local..." autocomplete="off">
                        <input type="hidden" name="local_id" id="local_id_update">
                        <div id="local_suggestions_update" class="suggestions-list"></div>
                    </div>
                </div>
                <div id="edit_responsavel"><label>Responsável:</label>
                    <div class="autocomplete-container">
                        <input type="text" id="search_responsavel_update" name="search_responsavel_update" placeholder="Digite para buscar um responsável..." autocomplete="off">
                        <input type="hidden" name="responsavel_id" id="responsavel_id_update">
                        <div id="responsavel_suggestions_update" class="suggestions-list"></div>
                    </div>
                </div>
                <div><label>Observação:</label><textarea name="observacao"><?php echo isset($_POST['observacao']) ? htmlspecialchars($_POST['observacao']) : ''; ?></textarea></div>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <input type="submit" name="update_existing" id="btn_update_single" value="Atualizar Itens Selecionados" class="btn-custom" disabled>
        </div>
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
                                <?php echo htmlspecialchars($empenho_item['numero_empenho'] . ' | ' . date('d/m/Y', strtotime($empenho_item['data_emissao']))); ?>
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

<!-- Formulário de Rascunhos -->
 <div id="rascunhos" class="tab-content <?php if($active_tab == 'rascunhos') echo 'active'; ?>">
    <h3>Gestão de Rascunhos</h3>
    
    <!-- Formulário de Criação de Rascunho -->
    <form action="patrimonio_add.php" method="post">
        <h4>Dados do Rascunho</h4>
        <div class="form-grid">
            <div class="form-section">
                <div><label>Nome do Item:</label><input type="text" name="nome" required></div>
                <div><label>Descrição Detalhada:</label><textarea name="descricao_detalhada" maxlength="200"></textarea></div>
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
                        <input type="text" id="search_local_rascunho" name="search_local_rascunho" placeholder="Digite para buscar um local..." autocomplete="off">
                        <input type="hidden" name="local_id" id="local_id_rascunho" required>
                        <div id="local_suggestions_rascunho" class="suggestions-list"></div>
                    </div>
                </div>
                <div><label>Responsável:</label>
                    <div class="autocomplete-container">
                        <input type="text" id="search_responsavel_rascunho" name="search_responsavel_rascunho" placeholder="Digite para buscar um responsável..." autocomplete="off">
                        <input type="hidden" name="responsavel_id" id="responsavel_id_rascunho" required>
                        <div id="responsavel_suggestions_rascunho" class="suggestions-list"></div>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div><label>Empenho:</label>
                    <select name="empenho_id" id="empenho_id_rascunho">
                        <option value="">Selecione um empenho (opcional)</option>
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
                <div><label>Processo/Documento:</label><input type="text" name="processo_documento"></div>
                <div><label>Número de Série:</label><input type="text" name="numero_serie"></div>
                <div><label>Quantidade:</label><input type="number" name="quantidade" min="1" value="1"></div>
                <div><label>Valor Unitário:</label><input type="number" step="0.01" name="valor"></div>
                <div><label>Nota Fiscal/Documento:</label><input type="text" name="nota_fiscal_documento"></div>
                <div><label>Data de Entrada/Aceitação:</label><input type="date" name="data_entrada_aceitacao"></div>
                <div><label>Observação:</label><textarea name="observacao"></textarea></div>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <input type="submit" name="create_draft" value="Salvar Rascunho" class="btn-custom">
        </div>
    </form>

    <!-- Lista de Rascunhos -->
    <div class="item-list" style="margin-top: 20px;">
        <h4>Rascunhos Cadastrados</h4>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Patrimônio Temporário</th>
                    <th>Local</th>
                    <th>Responsável</th>
                    <th>Empenho</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($drafts as $draft): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($draft['id']); ?></td>
                        <td><?php echo htmlspecialchars($draft['nome']); ?></td>
                        <td><?php echo htmlspecialchars($draft['patrimonio_novo']); ?></td>
                        <td><?php echo htmlspecialchars($draft['local_nome']); ?></td>
                        <td><?php echo htmlspecialchars($draft['responsavel_nome']); ?></td>
                        <td><?php echo htmlspecialchars($draft['empenho']); ?></td>
                        <td>
                            <a href="item_draft_details.php?id=<?php echo $draft['id']; ?>" title="Ver Detalhes"><i class="fas fa-eye"></i></a>
                            <a href="item_draft_edit.php?id=<?php echo $draft['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                            <a href="patrimonio_add.php?finalize_draft=<?php echo $draft['id']; ?>" title="Finalizar Rascunho" onclick="return confirm('Tem certeza que deseja finalizar este rascunho?')"><i class="fas fa-check"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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
// Habilita botão de atualizar ao selecionar um ou mais itens
    const btnUpdateSingle = document.getElementById('btn_update_single');
    document.addEventListener('change', function(e){
        if (e.target && e.target.classList.contains('select-item-checkbox')){
            const checkboxes = document.querySelectorAll('.select-item-checkbox:checked');
            if (btnUpdateSingle) btnUpdateSingle.disabled = checkboxes.length === 0;
        }
    });

    function toggleEditField(id, show){
        const el = document.getElementById(id);
        if (!el) return;
        el.style.display = show ? '' : 'none';
        // remove required de todos inputs internos quando esconder
        if (!show){
            el.querySelectorAll('input, select, textarea').forEach(inp => inp.required = false);
        }
    }

    function updateEditVisibility(data){
        // Campos: mostrar somente os que estiverem vazios no item atual
        // Permitir sempre a alteração de todos os campos
        toggleEditField('edit_processo_documento', true);
        toggleEditField('edit_nome', true);
        toggleEditField('edit_descricao_detalhada', true);
        toggleEditField('edit_numero_serie', true);
        toggleEditField('edit_quantidade', true);
        toggleEditField('edit_valor', true);
        toggleEditField('edit_nota_fiscal_documento', true);
        toggleEditField('edit_data_entrada_aceitacao', true);
        toggleEditField('edit_estado', true);
        toggleEditField('edit_local', true);
        toggleEditField('edit_responsavel', true);
        toggleEditField('edit_observacao', true);
        // Empenho e detalhes da aquisição também sempre visíveis
        toggleEditField('edit_empenho_select', true);
        toggleEditField('edit_acq_categoria', true);
        toggleEditField('edit_acq_empenho', true);
        toggleEditField('edit_acq_data_emissao', true);
        toggleEditField('edit_acq_fornecedor', true);
        toggleEditField('edit_acq_cnpj', true);
    }

    // Carrega detalhes do item selecionado e preenche o formulário
    window.loadItemDetails = function(itemId){
        fetch('api/get_item_details.php?id=' + itemId)
            .then(r => r.json())
            .then(data => {
                if (!data || data.error){
                    console.error(data && data.error ? data.error : 'Erro ao carregar detalhes do item');
                    return;
                }
                // Campos básicos
                const setVal = (name, val) => {
                    const el = document.querySelector(`[name="${name}"]`);
                    if (el) el.value = val !== null && val !== undefined ? val : '';
                };
                const setCurrent = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = (val !== null && val !== undefined) ? val : '';
                };
                setVal('processo_documento', data.processo_documento);
                setVal('nome', data.nome);
                setVal('descricao_detalhada', data.descricao_detalhada);
                setVal('numero_serie', data.numero_serie);
                setVal('quantidade', data.quantidade);
                setVal('valor', data.valor);
                setVal('nota_fiscal_documento', data.nota_fiscal_documento);
                setVal('data_entrada_aceitacao', data.data_entrada_aceitacao);
                setVal('observacao', data.observacao);

                // Patrimônio (somente exibição)
                const patr = document.getElementById('patrimonio_display_update');
                if (patr) patr.value = data.patrimonio_novo || '';
                setCurrent('current_patrimonio', data.patrimonio_novo);

                // Estado
                const estadoSel = document.querySelector('select[name="estado"]');
                if (estadoSel && data.estado){ estadoSel.value = data.estado; }

                // Local e Responsável (autocomplete + hidden id)
                const locInput = document.getElementById('search_local_update');
                const locHidden = document.getElementById('local_id_update');
                if (locInput) locInput.value = data.local_nome || '';
                if (locHidden) locHidden.value = data.local_id || '';
                setCurrent('current_local', data.local_nome);
                const respInput = document.getElementById('search_responsavel_update');
                const respHidden = document.getElementById('responsavel_id_update');
                if (respInput) respInput.value = data.responsavel_nome || '';
                if (respHidden) respHidden.value = data.responsavel_id || '';
                setCurrent('current_responsavel', data.responsavel_nome);

                // Empenho: selecionar o registro atual, se houver
                const empSelect = document.getElementById('empenho_id_update');
                if (empSelect) {
                    empSelect.value = data.empenho_id || '';
                    // Permitir sempre a alteração do empenho
                    empSelect.disabled = false;
                }
                // Preencher campos de empenho com os dados atuais do item
                setVal('empenho', data.empenho);
                setVal('data_emissao_empenho', data.data_emissao_empenho);
                setVal('fornecedor', data.fornecedor);
                setVal('cnpj_fornecedor', data.cnpj_fornecedor);
                setVal('categoria', data.categoria);
                // Preencher "Dados atuais" (somente leitura)
                setCurrent('current_nome', data.nome);
                setCurrent('current_descricao_detalhada', data.descricao_detalhada);
                setCurrent('current_numero_serie', data.numero_serie);
                setCurrent('current_quantidade', data.quantidade);
                setCurrent('current_valor', data.valor);
                setCurrent('current_nota_fiscal_documento', data.nota_fiscal_documento);
                setCurrent('current_data_entrada_aceitacao', data.data_entrada_aceitacao);
                setCurrent('current_estado', data.estado);
                setCurrent('current_observacao', data.observacao);

                updateEditVisibility(data);
                if (btnUpdateSingle) btnUpdateSingle.disabled = false;
            })
            .catch(err => console.error('Erro ao carregar detalhes do item:', err));
    };
    
    // Função para lidar com a seleção de múltiplos itens
    window.handleItemSelection = function() {
        const checkboxes = document.querySelectorAll('.select-item-checkbox:checked');
        if (btnUpdateSingle) btnUpdateSingle.disabled = checkboxes.length === 0;
        
        // Se apenas um item estiver selecionado, carregar seus detalhes
        if (checkboxes.length === 1) {
            const itemId = checkboxes[0].value;
            loadItemDetails(itemId);
        } else {
            // Se nenhum ou múltiplos itens estiverem selecionados, limpar os campos
            clearItemDetails();
        }
    };

    // Função para limpar os campos de detalhes do item
    window.clearItemDetails = function() {
        // Limpar campos de entrada
        const inputs = document.querySelectorAll('.form-section input, .form-section textarea, .form-section select');
        inputs.forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') return;
            if (input.id.startsWith('current_')) return; // Não limpar campos "Dados atuais"
            input.value = '';
        });
        
        // Resetar selects
        const selects = document.querySelectorAll('.form-section select');
        selects.forEach(select => {
            select.selectedIndex = 0;
        });
        
        // Limpar campos "Dados atuais"
        const currentFields = document.querySelectorAll('[id^="current_"]');
        currentFields.forEach(field => {
            field.value = '';
        });
    };
    
    // Função para realizar pesquisa automática
    function autoSearch() {
        const searchInput = document.getElementById('search_input');
        const searchBy = document.getElementById('search_by');
        const form = document.querySelector('#update form');
        
        if (searchInput && searchBy && form) {
            let debounceTimer;
            
            // Evento de input no campo de pesquisa
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                
                // Cancelar o timer anterior se ainda estiver ativo
                clearTimeout(debounceTimer);
                
                // Se tiver 3 ou mais caracteres, agendar a pesquisa
                if (searchTerm.length >= 3) {
                    debounceTimer = setTimeout(function() {
                        // Criar um objeto FormData com os dados do formulário
                        const formData = new FormData(form);
                        formData.append('search_action', '1');
                        
                        // Enviar requisição via fetch
                        fetch('patrimonio_add.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(html => {
                            // Criar um elemento temporário para parsear o HTML retornado
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            
                            // Extrair os resultados da pesquisa
                            const newItemList = doc.querySelector('#update .item-list');
                            const existingItemList = document.querySelector('#update .item-list');
                            
                            // Atualizar a lista de itens
                            if (newItemList && existingItemList) {
                                existingItemList.innerHTML = newItemList.innerHTML;
                            }
                            
                            // Reativar os checkboxes
                            const checkboxes = document.querySelectorAll('.select-item-checkbox');
                            checkboxes.forEach(checkbox => {
                                checkbox.addEventListener('change', window.handleItemSelection);
                            });
                        })
                        .catch(error => {
                            console.error('Erro na pesquisa automática:', error);
                        });
                    }, 500); // Aguardar 500ms antes de enviar
                } else if (searchTerm.length === 0) {
                    // Se o campo estiver vazio, limpar os resultados
                    const itemList = document.querySelector('#update .item-list');
                    if (itemList) {
                        itemList.innerHTML = '';
                    }
                }
            });
        }
    }
    
    // Iniciar a funcionalidade de pesquisa automática quando a página carregar
    autoSearch();
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>