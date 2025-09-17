<?php
// almoxarifado/admin_notificacoes.php - Interface do administrador para gerenciar notificações
// Garante que não haja saída antes do processamento AJAX
ob_start();

// Definir o diretório base para facilitar os includes
$base_path = dirname(__DIR__);

require_once $base_path . '/includes/header.php';
require_once $base_path . '/config/db.php';
require_once 'config.php';

// Verificar conexão com o banco
if (!isset($pdo) || !$pdo) {
    if (isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']);
        exit;
    } else {
        die('Erro de conexão com o banco de dados.');
    }
}

// Verificar permissões - apenas administradores
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["permissao"] != 'Administrador') {
    header("location: ../login.php");
    exit;
}

$admin_id = $_SESSION['id'];

// Obter o status de filtro da URL ou definir um padrão
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pendente'; // Padrão: apenas pendentes

// Processar ações via AJAX
if (isset($_POST['is_ajax']) && $_POST['is_ajax'] === 'true' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpa qualquer saída anterior completamente
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Configura headers
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        if (isset($_POST['requisicao_id'], $_POST['acao'])) {
            $requisicao_id = filter_var($_POST['requisicao_id'], FILTER_VALIDATE_INT);
            $acao = $_POST['acao'] ?? '';
            
            if (!$requisicao_id || !is_numeric($requisicao_id)) {
                echo json_encode(['success' => false, 'message' => 'ID de requisição inválido.']);
                exit;
            }
            
            if (empty($acao)) {
                echo json_encode(['success' => false, 'message' => 'Ação não especificada.']);
                exit;
            }
            
            $sql_check = "SELECT id, usuario_id, status_notificacao FROM almoxarifado_requisicoes WHERE id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$requisicao_id]);
            
            if ($row_check = $stmt_check->fetch(PDO::FETCH_ASSOC)) {
                $usuario_requisitante_id = $row_check['usuario_id'];
                
                if ($acao == 'aprovar') {
                    $pdo->beginTransaction();
                    try {
                        // Captura quantidades aprovadas enviadas (se houver)
                        $quantidades_aprovadas = [];
                        if (isset($_POST['quantidades']) && is_array($_POST['quantidades'])) {
                            foreach ($_POST['quantidades'] as $prod_id => $qtd) {
                                $prod_id = filter_var($prod_id, FILTER_VALIDATE_INT);
                                $qtd = filter_var($qtd, FILTER_VALIDATE_INT);
                                if ($prod_id !== false && $qtd !== false) {
                                    $quantidades_aprovadas[$prod_id] = $qtd;
                                }
                            }
                        }

                        // Busca itens da requisição e aplica as quantidades aprovadas (limitadas ao solicitado)
                        $sql_itens_req = "SELECT produto_id, quantidade_solicitada FROM almoxarifado_requisicoes_itens WHERE requisicao_id = ?";
                        $stmt_itens_req = $pdo->prepare($sql_itens_req);
                        $stmt_itens_req->execute([$requisicao_id]);
                        $itens_req = $stmt_itens_req->fetchAll(PDO::FETCH_ASSOC);

                        if ($itens_req) {
                            $stmt_upd_item = $pdo->prepare("UPDATE almoxarifado_requisicoes_itens SET quantidade_entregue = ? WHERE requisicao_id = ? AND produto_id = ?");
                            $stmt_get_stock = $pdo->prepare("SELECT estoque_atual FROM almoxarifado_materiais WHERE id = ?");
                            $stmt_upd_stock = $pdo->prepare("UPDATE almoxarifado_materiais SET estoque_atual = estoque_atual - ? WHERE id = ?");

                            // Preparar dados para Saída e Movimentação
                            $stmt_req_local = $pdo->prepare("SELECT l.nome FROM almoxarifado_requisicoes ar LEFT JOIN locais l ON ar.local_id = l.id WHERE ar.id = ?");
                            $stmt_req_local->execute([$requisicao_id]);
                            $setor_destino_nome = $stmt_req_local->fetchColumn() ?: null;

                            $stmt_user_nome = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
                            $stmt_user_nome->execute([$usuario_requisitante_id]);
                            $responsavel_saida_nome = $stmt_user_nome->fetchColumn() ?: null;

                            $stmt_saida = $pdo->prepare("INSERT INTO almoxarifado_saidas (material_id, quantidade, setor_destino, responsavel_saida, data_saida, data_cadastro, usuario_id) VALUES (?, ?, ?, ?, CURDATE(), NOW(), ?)");
                            $stmt_mov = $pdo->prepare("INSERT INTO almoxarifado_movimentacoes (material_id, tipo, quantidade, saldo_anterior, saldo_atual, data_movimentacao, usuario_id, referencia_id) VALUES (?, 'saida', ?, ?, ?, NOW(), ?, ?)");
                            foreach ($itens_req as $it) {
                                $prod_id = (int)$it['produto_id'];
                                $solicitada = (int)$it['quantidade_solicitada'];
                                $aprovada = isset($quantidades_aprovadas[$prod_id]) ? (int)$quantidades_aprovadas[$prod_id] : $solicitada;
                                if ($aprovada < 0) { $aprovada = 0; }
                                if ($aprovada > $solicitada) { $aprovada = $solicitada; }

                                // Respeita o estoque disponível
                                $stmt_get_stock->execute([$prod_id]);
                                $estoque_atual = (float)$stmt_get_stock->fetchColumn();
                                $entregar = min((float)$aprovada, max(0.0, $estoque_atual));

                                // Atualiza item com a quantidade efetivamente entregue
                                $stmt_upd_item->execute([$entregar, $requisicao_id, $prod_id]);

                                if ($entregar > 0) {
                                    // Registrar Saída
                                    $stmt_saida->execute([$prod_id, $entregar, $setor_destino_nome, $responsavel_saida_nome, $admin_id]);
                                    $saida_id = (int)$pdo->lastInsertId();

                                    // Registrar Movimentação
                                    $saldo_anterior = $estoque_atual;
                                    $saldo_atual = $estoque_atual - $entregar;
                                    $stmt_mov->execute([$prod_id, $entregar, $saldo_anterior, $saldo_atual, $admin_id, $saida_id]);

                                    // Subtrai do estoque
                                    $stmt_upd_stock->execute([$entregar, $prod_id]);
                                }
                            }
                        }

                        // Atualiza o status da requisição para aprovada
                        $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'aprovada' WHERE id = ?";
                        $stmt_update_req = $pdo->prepare($sql_update_req);
                        $stmt_update_req->execute([$requisicao_id]);

                        // Cria notificação para o requisitante
                        $mensagem_notificacao = "Sua requisição #" . $requisicao_id . " foi aprovada. Agora você pode agendar a entrega.";
                        $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) VALUES (?, ?, ?, 'aprovada', ?, 'pendente')";
                        $stmt_notificacao = $pdo->prepare($sql_notificacao);
                        $stmt_notificacao->execute([$requisicao_id, $admin_id, $usuario_requisitante_id, $mensagem_notificacao]);

                        $pdo->commit();
                        echo json_encode(['success' => true, 'message' => 'Requisição aprovada com sucesso!']);
                        exit;
                    } catch (Exception $e) {
                        if (isset($pdo) && $pdo->inTransaction()) {
                            $pdo->rollback();
                        }
                        echo json_encode(['success' => false, 'message' => 'Erro ao aprovar requisição: ' . $e->getMessage()]);
                        exit;
                    }
                } elseif ($acao == 'rejeitar' && isset($_POST['justificativa'])) {
                    $justificativa = trim($_POST['justificativa']);
                    if (!empty($justificativa)) {
                        $pdo->beginTransaction();
                        try {
                            $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'rejeitada' WHERE id = ?";
                            $stmt_update_req = $pdo->prepare($sql_update_req);
                            $stmt_update_req->execute([$requisicao_id]);
                            
                            $mensagem_notificacao = "Sua requisição #" . $requisicao_id . " foi rejeitada. Justificativa: " . $justificativa;
                            $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) VALUES (?, ?, ?, 'rejeitada', ?, 'pendente')";
                            $stmt_notificacao = $pdo->prepare($sql_notificacao);
                            $stmt_notificacao->execute([$requisicao_id, $admin_id, $usuario_requisitante_id, $mensagem_notificacao]);
                            
                            $pdo->commit();
                            echo json_encode(['success' => true, 'message' => 'Requisição rejeitada com sucesso!']);
                            exit;
                        } catch (Exception $e) {
                            if (isset($pdo) && $pdo->inTransaction()) {
                                $pdo->rollback();
                            }
                            echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar requisição: ' . $e->getMessage()]);
                            exit;
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Por favor, informe a justificativa da rejeição.']);
                        exit;
                    }
                } elseif ($acao == 'solicitar_informacoes' && isset($_POST['mensagem'])) {
                    $mensagem = trim($_POST['mensagem']);
                    if (!empty($mensagem)) {
                        $pdo->beginTransaction();
                        try {
                            $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'em_discussao' WHERE id = ?";
                            $stmt_update_req = $pdo->prepare($sql_update_req);
                            $stmt_update_req->execute([$requisicao_id]);
                            
                            $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) VALUES (?, ?, ?, 'resposta_admin', ?, 'pendente')";
                            $stmt_notificacao = $pdo->prepare($sql_notificacao);
                            $stmt_notificacao->execute([$requisicao_id, $admin_id, $usuario_requisitante_id, $mensagem]);
                            $notificacao_id = $pdo->lastInsertId();

                            $sql_conversa = "INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES (?, ?, ?, 'administrador')";
                            $stmt_conversa = $pdo->prepare($sql_conversa);
                            $stmt_conversa->execute([$notificacao_id, $admin_id, $mensagem]);
                            
                            $pdo->commit();
                            echo json_encode(['success' => true, 'message' => 'Solicitação de informações enviada com sucesso!']);
                            exit;
                        } catch (Exception $e) {
                            if (isset($pdo) && $pdo->inTransaction()) {
                                $pdo->rollback();
                            }
                            echo json_encode(['success' => false, 'message' => 'Erro ao solicitar informações: ' . $e->getMessage()]);
                            exit;
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Por favor, informe a mensagem solicitando informações.']);
                        exit;
                    }
                } elseif ($acao == 'ajustar_agendamento' && isset($_POST['data_inicio'], $_POST['data_fim'])) {
                    $data_inicio_raw = trim($_POST['data_inicio']);
                    $data_fim_raw = trim($_POST['data_fim']);
                    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

                    $normalize_dt = function($s) {
                        if ($s === '' || $s === null) return null;
                        $s = str_replace('T', ' ', $s);
                        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $s)) { $s .= ':00'; }
                        $ts = strtotime($s);
                        return $ts ? date('Y-m-d H:i:s', $ts) : null;
                    };

                    $data_inicio = $normalize_dt($data_inicio_raw);
                    $data_fim = $normalize_dt($data_fim_raw);

                    if ($data_inicio && $data_fim && strtotime($data_inicio) <= strtotime($data_fim)) {
                        try {
                            $pdo->beginTransaction();

                            // Define status como agendada
                            $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'agendada' WHERE id = ?";
                            $stmt_update_req = $pdo->prepare($sql_update_req);
                            $stmt_update_req->execute([$requisicao_id]);

                            // Atualiza ou insere agendamento
                            $stmt_find = $pdo->prepare("SELECT id FROM almoxarifado_agendamentos WHERE requisicao_id = ? ORDER BY id DESC LIMIT 1");
                            $stmt_find->execute([$requisicao_id]);
                            $periodo_texto = 'Período ajustado pelo administrador: ' . date('d/m/Y H:i', strtotime($data_inicio)) . ' a ' . date('d/m/Y H:i', strtotime($data_fim));
                            $observacoes_final = trim($periodo_texto . (strlen($observacoes) ? '. Observações: ' . $observacoes : ''));
                            if ($row_ag = $stmt_find->fetch(PDO::FETCH_ASSOC)) {
                                $stmt_upd = $pdo->prepare("UPDATE almoxarifado_agendamentos SET data_agendamento = ?, observacoes = ? WHERE id = ?");
                                $stmt_upd->execute([$data_inicio, $observacoes_final, $row_ag['id']]);
                            } else {
                                $stmt_ins = $pdo->prepare("INSERT INTO almoxarifado_agendamentos (requisicao_id, data_agendamento, observacoes) VALUES (?, ?, ?)");
                                $stmt_ins->execute([$requisicao_id, $data_inicio, $observacoes_final]);
                            }

                            // Registra mensagem na conversa
                            $mensagem_conversa = "Administrador ajustou o período de entrega para " . date('d/m/Y H:i', strtotime($data_inicio)) . " a " . date('d/m/Y H:i', strtotime($data_fim));
                            if (!empty($observacoes)) { $mensagem_conversa .= ". Observações: " . $observacoes; }
                            $stmt_conv = $pdo->prepare("INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES ((SELECT id FROM almoxarifado_requisicoes_notificacoes WHERE requisicao_id = ? ORDER BY id DESC LIMIT 1), ?, ?, 'administrador')");
                            $stmt_conv->execute([$requisicao_id, $admin_id, $mensagem_conversa]);

                            // Notifica o requisitante
                            $stmt_notif = $pdo->prepare("INSERT INTO almoxarifado_requisicoes_notificacoes (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) VALUES (?, ?, ?, 'agendamento', ?, 'pendente')");
                            $stmt_notif->execute([$requisicao_id, $admin_id, $usuario_requisitante_id, $mensagem_conversa]);

                            $pdo->commit();
                            echo json_encode(['success' => true, 'message' => 'Período de entrega ajustado com sucesso.']);
                            exit;
                        } catch (Exception $e) {
                            if ($pdo->inTransaction()) { $pdo->rollback(); }
                            echo json_encode(['success' => false, 'message' => 'Erro ao ajustar agendamento: ' . $e->getMessage()]);
                            exit;
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Informe um período válido (data inicial e final).']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ação inválida ou dados incompletos.']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Requisição não encontrada.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
            exit;
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
        exit;
    }
}

$sql = "
    SELECT 
        ar.id as requisicao_id,
        ar.usuario_id,
        ar.local_id,
        ar.data_requisicao,
        ar.status_notificacao,
        ar.justificativa,
        u.nome as usuario_nome,
        l.nome as local_nome,
        (SELECT c.mensagem FROM almoxarifado_requisicoes_conversas c JOIN almoxarifado_requisicoes_notificacoes n ON c.notificacao_id = n.id WHERE n.requisicao_id = ar.id ORDER BY c.data_mensagem DESC LIMIT 1) as ultima_mensagem
    FROM almoxarifado_requisicoes ar
    JOIN usuarios u ON ar.usuario_id = u.id
    LEFT JOIN locais l ON ar.local_id = l.id
";

$where_clauses = [];
$params = [];

if ($filter_status != 'todas') {
    if ($filter_status === 'agendada') {
        // Considera requisições marcadas como agendada OU com agendamento registrado, mas exclui concluídas
        $where_clauses[] = "(ar.status_notificacao = 'agendada' OR (EXISTS (SELECT 1 FROM almoxarifado_agendamentos aa WHERE aa.requisicao_id = ar.id) AND ar.status_notificacao <> 'concluida'))";
    } else {
        $where_clauses[] = "ar.status_notificacao = ?";
        $params[] = $filter_status;
    }
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY ar.data_requisicao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requisicoes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sql_itens = "SELECT ari.quantidade_solicitada, ari.quantidade_entregue, ari.produto_id as produto_id, m.nome as material_nome
                  FROM almoxarifado_requisicoes_itens ari
                  JOIN almoxarifado_materiais m ON ari.produto_id = m.id
                  WHERE ari.requisicao_id = ?";
    $stmt_itens = $pdo->prepare($sql_itens);
    $stmt_itens->execute([$row['requisicao_id']]);
    $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
    $row['itens'] = $itens;

    $sql_conversa = "SELECT c.mensagem, c.data_mensagem, u.nome as autor_nome, c.tipo_usuario FROM almoxarifado_requisicoes_conversas c JOIN almoxarifado_requisicoes_notificacoes n ON c.notificacao_id = n.id JOIN usuarios u ON c.usuario_id = u.id WHERE n.requisicao_id = ? ORDER BY c.data_mensagem ASC";
    $stmt_conversa = $pdo->prepare($sql_conversa);
    $stmt_conversa->execute([$row['requisicao_id']]);
    $conversa = $stmt_conversa->fetchAll(PDO::FETCH_ASSOC);
    $row['conversa'] = $conversa;

    // Buscar último agendamento (se houver)
    $sql_agendamento = "SELECT data_agendamento, observacoes FROM almoxarifado_agendamentos WHERE requisicao_id = ? ORDER BY id DESC LIMIT 1";
    $stmt_agendamento = $pdo->prepare($sql_agendamento);
    $stmt_agendamento->execute([$row['requisicao_id']]);
    $agendamento = $stmt_agendamento->fetch(PDO::FETCH_ASSOC);
    $row['agendamento'] = $agendamento ?: null;

    $requisicoes[] = $row;
}
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Gerenciar Requisições de Materiais</h2>
        <?php
        $is_privileged_user = true; // Página de admin, sempre privilegiado
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <div id="feedback-message" class="alert" style="display:none;"></div>
    
    <div class="filter-section mb-3">
        <label for="statusFilter">Filtrar por Status:</label>
        <select id="statusFilter" class="form-control w-auto d-inline-block">
            <option value="pendente" <?php echo ($filter_status == 'pendente') ? 'selected' : ''; ?>>Pendentes</option>
            <option value="em_discussao" <?php echo ($filter_status == 'em_discussao') ? 'selected' : ''; ?>>Em Discussão</option>
            <option value="aprovada" <?php echo ($filter_status == 'aprovada') ? 'selected' : ''; ?>>Aprovadas</option>
            <option value="rejeitada" <?php echo ($filter_status == 'rejeitada') ? 'selected' : ''; ?>>Rejeitadas</option>
            <option value="agendada" <?php echo ($filter_status == 'agendada') ? 'selected' : ''; ?>>Agendadas</option>
            <option value="todas" <?php echo ($filter_status == 'todas') ? 'selected' : ''; ?>>Todas</option>
        </select>
    </div>
    
    <?php if (empty($requisicoes)): ?>
        <div class="alert alert-info">
            Não há requisições para o filtro selecionado.
        </div>
    <?php else: ?>
        <div class="requisicoes-list">
            <?php foreach ($requisicoes as $requisicao): ?>
                <div class="requisicao-item card mb-2" data-requisicao-id="<?php echo $requisicao['requisicao_id']; ?>">
                    <div class="card-header requisicao-summary d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Requisição #<?php echo $requisicao['requisicao_id']; ?></strong> - 
                            <span class="badge badge-<?php 
                                switch($requisicao['status_notificacao']) {
                                    case 'pendente': echo 'warning'; break;
                                    case 'em_discussao': echo 'info'; break;
                                    case 'aprovada': echo 'success'; break;
                                    case 'rejeitada': echo 'danger'; break;
                                    case 'agendada': echo 'primary'; break;
                                    default: echo 'secondary';
                                }
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $requisicao['status_notificacao'])); ?>
                            </span>
                            <span class="text-muted ml-3">Última mensagem: <?php echo htmlspecialchars($requisicao['ultima_mensagem'] ?? 'N/A'); ?></span>
                        </div>
                        <small class="text-muted">
                            <?php echo date('d/m/Y H:i', strtotime($requisicao['data_requisicao'])); ?>
                        </small>
                    </div>
                    <div class="card-body requisicao-details" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Requisitante:</strong> <?php echo htmlspecialchars($requisicao['usuario_nome']); ?></p>
                                <p><strong>Local:</strong> <?php echo htmlspecialchars($requisicao['local_nome']); ?></p>
                                <p><strong>Justificativa:</strong> <?php echo htmlspecialchars($requisicao['justificativa']); ?></p>
                                <?php if (!empty($requisicao['agendamento'])): ?>
                                    <p><strong>Agendamento Início:</strong> <?php echo date('d/m/Y H:i', strtotime($requisicao['agendamento']['data_agendamento'])); ?></p>
                                    <?php if (!empty($requisicao['agendamento']['observacoes'])): ?>
                                        <p><strong>Observações do Agendamento:</strong><br><?php echo nl2br(htmlspecialchars($requisicao['agendamento']['observacoes'])); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Itens solicitados:</strong></p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Material</th>
                                                <th class="text-center">Solicitada</th>
                                                <th class="text-center">Aprovar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($requisicao['itens'] as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['material_nome']); ?></td>
                                                    <td class="text-center"><?php echo (int)$item['quantidade_solicitada']; ?></td>
                                                    <td style="width: 160px;">
                                                        <input type="number" class="form-control form-control-sm qtd-aprovar" data-produto-id="<?php echo (int)$item['produto_id']; ?>" min="0" max="<?php echo (int)$item['quantidade_solicitada']; ?>" value="<?php echo in_array($requisicao['status_notificacao'], ['pendente','em_discussao']) ? (int)$item['quantidade_solicitada'] : ((isset($item['quantidade_entregue']) && $item['quantidade_entregue'] !== null) ? (int)$item['quantidade_entregue'] : (int)$item['quantidade_solicitada']); ?>"<?php echo in_array($requisicao['status_notificacao'], ['aprovada','rejeitada','agendada','concluida']) ? ' disabled' : ''; ?>>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($requisicao['conversa'])): ?>
                        <hr>
                        <div class="conversa-historico">
                            <h6>Histórico da Conversa:</h6>
                            <?php foreach($requisicao['conversa'] as $msg): ?>
                                <div class="chat-message-wrapper <?php echo ($msg['tipo_usuario'] == 'administrador') ? 'align-right' : 'align-left'; ?>">
                                    <div class="mensagem-chat <?php echo ($msg['tipo_usuario'] == 'administrador') ? 'mensagem-admin' : 'mensagem-requisitante'; ?>">
                                        <strong><?php echo htmlspecialchars($msg['autor_nome']); ?>:</strong>
                                        <p><?php echo nl2br(htmlspecialchars($msg['mensagem'])); ?></p>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($msg['data_mensagem'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($requisicao['status_notificacao'] == 'pendente' || $requisicao['status_notificacao'] == 'em_discussao'): ?>
                            <hr>
                            <div class="acao-buttons">
                                <form class="acao-form d-inline" data-requisicao-id="<?php echo $requisicao['requisicao_id']; ?>" data-acao="aprovar">
                                    <button type="submit" class="btn btn-success">Aprovar Requisição</button>
                                </form>
                                
                                <button type="button" class="btn btn-info" onclick="showSolicitarInfoForm(<?php echo $requisicao['requisicao_id']; ?>)">
                                    Solicitar Mais Informações
                                </button>
                                
                                <button type="button" class="btn btn-danger" onclick="showRejeitarForm(<?php echo $requisicao['requisicao_id']; ?>)">
                                    Rejeitar Requisição
                                </button>
                            </div>
                            
                            <div id="solicitar-info-form-<?php echo $requisicao['requisicao_id']; ?>" class="mt-3" style="display:none;">
                                <form class="solicitar-info-form" data-requisicao-id="<?php echo $requisicao['requisicao_id']; ?>">
                                    <div class="form-group">
                                        <label>Mensagem para o requisitante:</label>
                                        <textarea class="form-control" name="mensagem" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
                                    <button type="button" class="btn btn-secondary" onclick="hideSolicitarInfoForm(<?php echo $requisicao['requisicao_id']; ?>)">Cancelar</button>
                                </form>
                            </div>
                            
                            <div id="rejeitar-form-<?php echo $requisicao['requisicao_id']; ?>" class="mt-3" style="display:none;">
                                <form class="rejeitar-form" data-requisicao-id="<?php echo $requisicao['requisicao_id']; ?>">
                                    <div class="form-group">
                                        <label>Justificativa da rejeição:</label>
                                        <textarea class="form-control" name="justificativa" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger">Rejeitar Requisição</button>
                                    <button type="button" class="btn btn-secondary" onclick="hideRejeitarForm(<?php echo $requisicao['requisicao_id']; ?>)">Cancelar</button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if ($requisicao['status_notificacao'] == 'agendada' && !empty($requisicao['agendamento'])): ?>
                            <hr>
                            <div class="agendamento-admin">
                                <button type="button" class="btn btn-primary" onclick="showAjusteAgendamentoForm(<?php echo $requisicao['requisicao_id']; ?>)">Ajustar Período de Entrega</button>
                                <div id="ajuste-agendamento-form-<?php echo $requisicao['requisicao_id']; ?>" class="mt-3" style="display:none;">
                                    <form class="ajustar-agendamento-form" data-requisicao-id="<?php echo $requisicao['requisicao_id']; ?>">
                                        <div class="form-group">
                                            <label>Início:</label>
                                            <input type="datetime-local" class="form-control" name="data_inicio" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Fim:</label>
                                            <input type="datetime-local" class="form-control" name="data_fim" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Observações:</label>
                                            <textarea class="form-control" name="observacoes" rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Salvar Ajuste</button>
                                        <button type="button" class="btn btn-secondary" onclick="hideAjusteAgendamentoForm(<?php echo $requisicao['requisicao_id']; ?>)">Cancelar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function showSolicitarInfoForm(requisicaoId) {
    document.getElementById('solicitar-info-form-' + requisicaoId).style.display = 'block';
    document.getElementById('rejeitar-form-' + requisicaoId).style.display = 'none';
}

function hideSolicitarInfoForm(requisicaoId) {
    document.getElementById('solicitar-info-form-' + requisicaoId).style.display = 'none';
}

function showRejeitarForm(requisicaoId) {
    document.getElementById('rejeitar-form-' + requisicaoId).style.display = 'block';
    document.getElementById('solicitar-info-form-' + requisicaoId).style.display = 'none';
}

function hideRejeitarForm(requisicaoId) {
    document.getElementById('rejeitar-form-' + requisicaoId).style.display = 'none';
}

function showAjusteAgendamentoForm(requisicaoId) {
    document.getElementById('ajuste-agendamento-form-' + requisicaoId).style.display = 'block';
}

function hideAjusteAgendamentoForm(requisicaoId) {
    document.getElementById('ajuste-agendamento-form-' + requisicaoId).style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    // Lógica para o filtro de status
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            window.location.href = 'admin_notificacoes.php?status=' + this.value;
        });
    }

    // Lógica para expandir/colapsar detalhes da requisição
    document.querySelectorAll('.requisicao-summary').forEach(summary => {
        summary.addEventListener('click', function() {
            const details = this.nextElementSibling; // O próximo elemento é o .requisicao-details
            if (details.style.display === 'none') {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        });
    });

    document.querySelectorAll('.acao-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const requisicaoId = this.dataset.requisicaoId;
            const acao = this.dataset.acao;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('requisicao_id', requisicaoId);
            formData.append('acao', acao);
            // Coleta as quantidades aprovadas por item desta requisição
            const requisicaoItem = this.closest('.requisicao-item');
            if (requisicaoItem) {
                requisicaoItem.querySelectorAll('.qtd-aprovar').forEach(inp => {
                    const pid = inp.getAttribute('data-produto-id');
                    const val = inp.value;
                    if (pid !== null && val !== null && val !== '') {
                        formData.append(`quantidades[${pid}]`, val);
                    }
                });
            }
            fetch('admin_notificacoes.php', { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Resposta não é JSON válido');
                }
                return response.json();
            })
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;
                feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                if (data.success) { setTimeout(() => { location.reload(); }, 1000); }
            })
            .catch(error => {
                console.error('Erro de rede:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });
    
    document.querySelectorAll('.solicitar-info-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const requisicaoId = this.dataset.requisicaoId;
            const mensagem = this.querySelector('textarea[name="mensagem"]').value;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('requisicao_id', requisicaoId);
            formData.append('acao', 'solicitar_informacoes');
            formData.append('mensagem', mensagem);
            fetch('admin_notificacoes.php', { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Resposta não é JSON válido');
                }
                return response.json();
            })
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;
                feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                if (data.success) {
                    this.querySelector('textarea[name="mensagem"]').value = '';
                    document.getElementById('solicitar-info-form-' + requisicaoId).style.display = 'none';
                    setTimeout(() => { location.reload(); }, 1000);
                }
            })
            .catch(error => {
                console.error('Erro de rede:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });
    
    document.querySelectorAll('.rejeitar-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const requisicaoId = this.dataset.requisicaoId;
            const justificativa = this.querySelector('textarea[name="justificativa"]').value;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('requisicao_id', requisicaoId);
            formData.append('acao', 'rejeitar');
            formData.append('justificativa', justificativa);
            fetch('admin_notificacoes.php', { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Resposta não é JSON válido');
                }
                return response.json();
            })
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;
                feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                if (data.success) {
                    this.querySelector('textarea[name="justificativa"]').value = '';
                    document.getElementById('rejeitar-form-' + requisicaoId).style.display = 'none';
                    setTimeout(() => { location.reload(); }, 1000);
                }
            })
            .catch(error => {
                console.error('Erro de rede:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });

    // Ajuste de agendamento pelo administrador
    document.querySelectorAll('.ajustar-agendamento-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const requisicaoId = this.dataset.requisicaoId;
            const dataInicio = this.querySelector('input[name="data_inicio"]').value;
            const dataFim = this.querySelector('input[name="data_fim"]').value;
            const observacoes = this.querySelector('textarea[name="observacoes"]').value;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('requisicao_id', requisicaoId);
            formData.append('acao', 'ajustar_agendamento');
            formData.append('data_inicio', dataInicio);
            formData.append('data_fim', dataFim);
            formData.append('observacoes', observacoes);
            fetch('admin_notificacoes.php', { method: 'POST', body: formData })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro HTTP: ' + response.status);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Resposta não é JSON válido');
                }
                return response.json();
            })
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;
                feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                if (data.success) {
                    document.getElementById('ajuste-agendamento-form-' + requisicaoId).style.display = 'none';
                    setTimeout(() => { location.reload(); }, 1000);
                }
            })
            .catch(error => {
                console.error('Erro de rede:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>