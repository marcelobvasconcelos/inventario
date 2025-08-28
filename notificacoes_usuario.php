<?php
// Inicia a sessão PHP e inclui a conexão com o banco de dados
require_once 'config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Inicia a sessão apenas se não houver uma ativa
} // Garante que a sessão está iniciada

// Redireciona para a página de login se o usuário não estiver logado
if (!isset($_SESSION["id"])) {
    header("location: login.php");
    exit;
}

$usuario_logado_id = $_SESSION['id']; // ID do usuário atualmente logado

// --- Processamento de Ações do Formulário via AJAX ---

if (isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    ob_start();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    $response = [
        'success' => false, 
        'message' => '', 
        'new_item_status' => '', 
        'new_notif_status' => '',
        'justificativa' => '',
    // 'data_justificativa' removido pois não existe na tabela
    ];

    // Validação robusta dos dados recebidos
    if (isset($_POST['notificacao_id'], $_POST['item_id'], $_POST['action'])) {
        $notificacao_id = filter_var($_POST['notificacao_id'], FILTER_VALIDATE_INT);
        $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);
        $action = $_POST['action'];
        $justificativa = isset($_POST['justificativa']) ? trim($_POST['justificativa']) : '';

        if (!$notificacao_id || !$item_id) {
            $response['message'] = 'ID de notificação ou item inválido.';
            echo json_encode($response);
            exit;
        }

        $pdo->beginTransaction();
        try {
            // Verifica se a notificação pertence ao usuário logado e ao item correto e obtém movimentacao_id
            $sql_check = "SELECT id, movimentacao_id FROM notificacoes_movimentacao WHERE id = ? AND item_id = ? AND usuario_notificado_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$notificacao_id, $item_id, $usuario_logado_id]);
            $row_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
            if (!$row_check) {
                throw new Exception("Notificação inválida, não pertence a você ou o item não corresponde.");
            }
            $movimentacao_id = $row_check['movimentacao_id'];
            $data_atualizacao = date('Y-m-d H:i:s');

            // Atualiza o status do item conforme ação
            if ($action === 'confirmar_item') {
                $sql_update_item = "UPDATE itens SET status_confirmacao = 'Confirmado' WHERE id = ?";
                $pdo->prepare($sql_update_item)->execute([$item_id]);
                // Atualiza a notificação desta movimentação para não ficar pendente
                $sql_update_nm = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, data_atualizacao = ? WHERE id = ?";
                $pdo->prepare($sql_update_nm)->execute(['Confirmado', $data_atualizacao, $notificacao_id]);
                $new_item_status = 'Confirmado';
            } elseif ($action === 'nao_confirmar_item') {
                if (empty($justificativa)) throw new Exception('Justificativa obrigatória para não confirmar.');
                // Salva justificativa do usuário no histórico de conversa
                $sql_insert_history = "INSERT INTO notificacoes_respostas_historico (notificacao_movimentacao_id, remetente_id, tipo_remetente, conteudo_resposta, data_resposta) VALUES (?, ?, ?, ?, ?)";
                $pdo->prepare($sql_insert_history)->execute([$notificacao_id, $usuario_logado_id, 'usuario', $justificativa, $data_atualizacao]);
                // Atualiza o status do item na tabela principal
                $sql_update_item = "UPDATE itens SET status_confirmacao = 'Nao Confirmado' WHERE id = ?";
                $pdo->prepare($sql_update_item)->execute([$item_id]);
                // Atualiza a notificação desta movimentação para refletir a ação do usuário e registrar justificativa
                $sql_update_nm = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, justificativa_usuario = ?, data_atualizacao = ? WHERE id = ?";
                $pdo->prepare($sql_update_nm)->execute(['Nao Confirmado', $justificativa, $data_atualizacao, $notificacao_id]);
                $new_item_status = 'Não Confirmado';
            } else {
                throw new Exception('Ação inválida.');
            }

            // Atualiza status geral da notificação (movimentação)
            $new_notif_status = 'Pendente';
            if($movimentacao_id) {
                $stmt_statuses = $pdo->prepare("SELECT status_confirmacao FROM notificacoes_movimentacao WHERE movimentacao_id = ?");
                $stmt_statuses->execute([$movimentacao_id]);
                $statuses = $stmt_statuses->fetchAll(PDO::FETCH_COLUMN);
                $unique_statuses = array_unique($statuses);
                if (in_array('Em Disputa', $unique_statuses)) {
                    $new_notif_status = 'Em Disputa';
                } elseif (
                    in_array('Nao Confirmado', $unique_statuses) ||
                    in_array('nao_confirmado', $unique_statuses) ||
                    in_array('Não Confirmado', $unique_statuses)
                ) {
                    $new_notif_status = 'Não Confirmado';
                } elseif (in_array('Pendente', $unique_statuses)) {
                    $new_notif_status = 'Pendente';
                } elseif (count($unique_statuses) == 1 && $unique_statuses[0] == 'Confirmado') {
                    $new_notif_status = 'Confirmado';
                } else {
                    $new_notif_status = 'Confirmado';
                }
            }

            $pdo->commit();
            $response['success'] = true;
            $response['new_item_status'] = $new_item_status;
            $response['new_notif_status'] = $new_notif_status;

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = "Erro: " . $e->getMessage();
            error_log("Erro na ação de notificação: " . $e->getMessage());
        }
    } else {
        $response['message'] = 'Dados incompletos na requisição.';
    }
    // Limpa qualquer saída inesperada antes do JSON
    $buffer = ob_get_clean();
    if (strlen($buffer) > 0) {
        $response['message'] .= ' [Aviso de saída inesperada: ' . strip_tags($buffer) . ']';
    }
    echo json_encode($response);
    exit;
}

// --- Lógica para buscar notificações para exibição da página (apenas se não for AJAX) ---
$notificacoes_movimentacao = [];
$notificacao_unica_id = isset($_GET['notif_id']) ? (int)$_GET['notif_id'] : 0;

$sql = "
    SELECT
        nm.id, nm.status_confirmacao, nm.justificativa_usuario, nm.resposta_admin,
        nm.data_notificacao, nm.data_atualizacao,
        i.id as item_id, i.nome as item_nome, i.patrimonio_novo, i.patrimonio_secundario, i.estado, i.observacao,
        l.nome as local_nome,
        resp.nome as responsavel_nome,
        mov.usuario_id as admin_id,
        admin_user.nome as admin_nome,
        mov.data_movimentacao
    FROM notificacoes_movimentacao nm
    JOIN itens i ON nm.item_id = i.id
    JOIN movimentacoes mov ON nm.movimentacao_id = mov.id
    JOIN usuarios admin_user ON mov.usuario_id = admin_user.id
    LEFT JOIN locais l ON i.local_id = l.id
    LEFT JOIN usuarios resp ON i.responsavel_id = resp.id
    WHERE nm.usuario_notificado_id = ?
";

if ($notificacao_unica_id > 0) {
    $sql .= " AND nm.id = ?";
}

$sql .= " ORDER BY nm.data_notificacao DESC, nm.id DESC";

$stmt = $pdo->prepare($sql);

if ($notificacao_unica_id > 0) {
    $stmt->execute([$usuario_logado_id, $notificacao_unica_id]);
} else {
    $stmt->execute([$usuario_logado_id]);
}

$notificacoes_movimentacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notificacoes_para_exibir = [];
foreach ($notificacoes_movimentacao as $nm) {
    // Buscar status do item diretamente da tabela itens
    $sql_item_status = "SELECT status_confirmacao FROM itens WHERE id = ?";
    $stmt_item_status = $pdo->prepare($sql_item_status);
    $stmt_item_status->execute([$nm['item_id']]);
    $item_status_row = $stmt_item_status->fetch(PDO::FETCH_ASSOC);
    $item_status = $item_status_row ? $item_status_row['status_confirmacao'] : '';
    if (empty($item_status)) {
        $status_fmt = 'Pendente';
    } elseif ($item_status === 'Nao Confirmado') {
        $status_fmt = 'Não Confirmado';
    } elseif ($item_status === 'Confirmado') {
        $status_fmt = 'Confirmado';
    } elseif ($item_status === 'Movimento Desfeito') {
        $status_fmt = 'Movimento Desfeito';
    } elseif ($item_status === 'Em Disputa') {
        $status_fmt = 'Em Disputa';
    } else {
        $status_fmt = ucfirst($item_status);
    }
    $notificacoes_para_exibir[] = [
        'id' => $nm['id'],
        'tipo' => 'transferencia',
        'mensagem' => "Movimentação do item: " . htmlspecialchars($nm['item_nome']) . " (Patrimônio: " . htmlspecialchars($nm['patrimonio_novo']) . "). Status: " . htmlspecialchars($status_fmt),
        'status' => $status_fmt,
        'data_envio' => $nm['data_notificacao'],
        'administrador_nome' => $nm['admin_nome'],
        'assunto_titulo' => 'Movimentação de Item',
        'assunto_resumo' => "Item: " . htmlspecialchars($nm['item_nome']) . " - Status: " . htmlspecialchars($status_fmt),
        'detalhes_itens' => [
            [
                'id' => $nm['item_id'],
                'status_confirmacao' => $item_status, // valor do item
                'justificativa_usuario' => $nm['justificativa_usuario'],
                'admin_reply' => $nm['resposta_admin'],
                'nome' => $nm['item_nome'],
                'patrimonio_novo' => $nm['patrimonio_novo'],
                'patrimonio_secundario' => $nm['patrimonio_secundario'],
                'estado' => $nm['estado'],
                'observacao' => $nm['observacao'],
                'local_nome' => $nm['local_nome'],
                'responsavel_nome' => $nm['responsavel_nome'],
                'data_admin_reply' => $nm['data_atualizacao'],
            ]
        ],
        'data_item_statuses' => $status_fmt
    ];
}
$notificacoes = $notificacoes_para_exibir;

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <?php if ($notificacao_unica_id > 0): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Detalhes da Notificação</h2>
            <a href="notificacoes_usuario.php" class="btn btn-primary btn-sm">Voltar para Notificações</a>
        </div>
    <?php else: ?>
        <h2>Minhas Notificações de Inventário</h2>
        <p>Aqui você pode visualizar e confirmar as movimentações de itens atribuídos a você.</p>
    <?php endif; ?>

    <div id="feedback-message" class="alert" style="display:none;"></div>

    <?php if (empty($notificacoes)): ?>
        <div class="alert alert-info">Você não possui notificações no momento.</div>
    <?php else: ?>
        <?php if (!$notificacao_unica_id): ?>
        <div class="notification-filter-controls mb-3">
            <span>Filtrar por status do item:</span>
            <button class="btn btn-sm btn-primary filter-btn active" data-filter="Todos">Todos</button>
            <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="Pendente">Pendentes</button>
            <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="Confirmado">Confirmados</button>
            <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="Nao Confirmado">Não Confirmados</button>
        </div>
        
        <!-- Botões de confirmação em massa -->
        <div class="bulk-action-buttons mb-3" style="display: none;">
            <button id="bulkConfirmBtn" class="btn btn-success btn-sm">
                <i class="fas fa-check"></i> Confirmar Selecionados
            </button>
            <button id="bulkRejectBtn" class="btn btn-danger btn-sm">
                <i class="fas fa-times"></i> Não Confirmar Selecionados
            </button>
        </div>
        
        <!-- Formulário de justificativa em massa -->
        <div id="bulkJustificativaForm" class="card mt-3" style="display: none;">
            <div class="card-header">
                <h5>Justificativa para Itens Não Confirmados</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="bulkJustificativaText">Justificativa:</label>
                    <textarea id="bulkJustificativaText" class="form-control" rows="3" placeholder="Informe a justificativa para não confirmar os itens selecionados..."></textarea>
                </div>
                <button id="submitBulkJustificativa" class="btn btn-primary">Enviar Justificativa</button>
                <button id="cancelBulkJustificativa" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="notification-inbox">
        <?php foreach ($notificacoes as $notificacao): ?>
                <?php
                // O status da notificação deve refletir o status da notificação na tabela notificacoes_movimentacao
                // e não apenas o status do item
                // Para notificações de movimentação, o status está em 'status'
                // Para notificações gerais, precisamos verificar de outra forma
                $notificacao_status = $notificacao['status'] ?? 'Pendente';
                if ($notificacao_status == 'Pendente' || $notificacao_status == 'Em Disputa') {
                    $notif_card_class = 'notif-pendente';
                } else {
                    $notif_card_class = 'notif-nao-pendente';
                }
                ?>
                <div class="notification-item card mb-1 <?php echo $notif_card_class; ?>" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-statuses="<?php echo htmlspecialchars($notificacao['data_item_statuses']); ?>" style="padding: 0.5rem 0.7rem; border-radius: 8px;">
                    <div class="card-header notification-summary">
                        <a href="notificacoes_usuario.php?notif_id=<?php echo $notificacao['id']; ?>" class="d-flex justify-content-between align-items-center w-100 text-decoration-none text-dark">
                            <div>
                                <i class="fas <?php 
                                    if ($notificacao_status == 'Pendente' || $notificacao_status == 'Em Disputa') echo 'fa-envelope';
                                    else if ($notificacao_status == 'Confirmado' || $notificacao_status == 'Movimento Desfeito') echo 'fa-envelope-open';
                                    else echo 'fa-envelope';
                                ?>"></i>
                                <strong><?php echo htmlspecialchars($notificacao['administrador_nome']); ?></strong>
                                <strong class="ml-2"><?php echo $notificacao['assunto_titulo']; ?>:</strong>
                                <span class="assunto-resumo"></span>
                                <div class="item-status-list mt-1" style="font-size:0.97em;">
                                    <strong><?php echo htmlspecialchars($notificacao['detalhes_itens'][0]['nome']); ?></strong> |
                                    Patrimônio: <?php echo htmlspecialchars($notificacao['detalhes_itens'][0]['patrimonio_novo']); ?>
                                    <?php
                                    // Badge do status do item principal
                                    $item_status = $notificacao['detalhes_itens'][0]['status_confirmacao'] ?? '';
                                    if (empty($item_status) || $item_status === 'Pendente') {
                                        $badge_text = 'Pendente';
                                    } elseif ($item_status === 'Nao Confirmado') {
                                        $badge_text = 'Não Confirmado';
                                    } elseif ($item_status === 'Confirmado') {
                                        $badge_text = 'Confirmado';
                                    } elseif ($item_status === 'Movimento Desfeito') {
                                        $badge_text = 'Movimento Desfeito';
                                    } elseif ($item_status === 'Em Disputa') {
                                        $badge_text = 'Em Disputa';
                                    } else {
                                        $badge_text = ucfirst($item_status);
                                    }
                                    $badge_style = '';
                                    if($badge_text == 'Pendente') {
                                        $badge_style = 'background: linear-gradient(90deg,#ffecb3,#ffc107); color:#7a5700; font-weight:bold; border:1px solid #ffc107;';
                                    } else if($badge_text == 'Confirmado') {
                                        $badge_style = 'background: linear-gradient(90deg,#b2f7c1,#28a745); color:#155724; font-weight:bold; border:1px solid #28a745;';
                                    } else if($badge_text == 'Não Confirmado') {
                                        $badge_style = 'background: linear-gradient(90deg,#ffb3b3,#dc3545); color:#721c24; font-weight:bold; border:1px solid #dc3545;';
                                    } else if($badge_text == 'Em Disputa') {
                                        $badge_style = 'background: linear-gradient(90deg,#ffe0e0,#ff5252); color:#a71d2a; font-weight:bold; border:1px solid #ff5252;';
                                    } else if($badge_text == 'Movimento Desfeito') {
                                        $badge_style = 'background: linear-gradient(90deg,#b3e0ff,#17a2b8); color:#0c5460; font-weight:bold; border:1px solid #17a2b8;';
                                    } else {
                                        $badge_style = 'background:#e2e3e5;color:#383d41;';
                                    }
                                    echo " <span class=\"badge ml-2\" style=\"font-size:1em;vertical-align:middle;{$badge_style}\">" . htmlspecialchars($badge_text, ENT_QUOTES, 'UTF-8') . "</span>";
                                    // Buscar a última mensagem do histórico
                                    $sql_last_msg = "SELECT h.*, u.nome as usuario_nome FROM notificacoes_respostas_historico h LEFT JOIN usuarios u ON h.remetente_id = u.id WHERE h.notificacao_movimentacao_id = ? ORDER BY h.data_resposta DESC LIMIT 1";
                                    $stmt_last_msg = $pdo->prepare($sql_last_msg);
                                    $stmt_last_msg->execute([$notificacao['id']]);
                                    $last_msg = $stmt_last_msg->fetch(PDO::FETCH_ASSOC);
                                    if ($last_msg) {
                                        $remetente = $last_msg['tipo_remetente'] === 'admin' ? 'Administrador' : ($last_msg['usuario_nome'] ?? 'Usuário');
                                        echo '<br><span style="font-size:0.93em;color:#666;">';
                                        echo '<strong>' . htmlspecialchars($remetente) . ':</strong> ' . htmlspecialchars(mb_strimwidth($last_msg['conteudo_resposta'], 0, 60, '...'));
                                        echo '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div>
                                <small class="text-muted ml-2"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_envio'])); ?></small>
                            </div>
                        </a>
                    </div>
                    <div class="card-body notification-details" <?php echo ($notificacao_unica_id > 0) ? '' : 'style="display: none;"'; ?>>
                        <p><strong>Mensagem Geral:</strong> <?php echo nl2br(htmlspecialchars($notificacao['mensagem'])); ?></p>

                        <!-- Histórico de Conversa -->
                        <?php
                        // Buscar histórico de respostas para esta notificação
                        $sql_historico = "SELECT h.*, u.nome as usuario_nome FROM notificacoes_respostas_historico h LEFT JOIN usuarios u ON h.remetente_id = u.id WHERE h.notificacao_movimentacao_id = ? ORDER BY h.data_resposta ASC";
                        $stmt_historico = $pdo->prepare($sql_historico);
                        $stmt_historico->execute([$notificacao['id']]);
                        $historico_respostas = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="card mt-3 mb-3">
                            <div class="card-header"><strong>Histórico da Conversa</strong></div>
                            <div class="card-body" style="max-height:350px; overflow-y:auto; background:#f8f9fa;">
                                <style>
                                .chat-bubble { max-width: 70%; padding: 10px 15px; border-radius: 15px; margin-bottom: 8px; position: relative; }
                                .chat-admin { background: #e3f0fa; color: #124a80; align-self: flex-end; margin-left:auto; }
                                .chat-user { background: #e9ecef; color: #333; align-self: flex-start; margin-right:auto; }
                                .chat-meta { font-size: 0.85em; color: #888; margin-bottom: 2px; }
                                .chat-container { display: flex; flex-direction: column; }
                                </style>
                                <div class="chat-container">
                                <?php if (empty($historico_respostas)): ?>
                                    <div class="text-muted">Nenhuma mensagem registrada ainda.</div>
                                <?php else: ?>
                                    <?php foreach ($historico_respostas as $msg): ?>
                                        <div class="chat-bubble chat-<?php echo $msg['tipo_remetente'] === 'admin' ? 'admin' : 'user'; ?>">
                                            <div class="chat-meta">
                                                <strong>
                                                    <?php
                                                    if ($msg['tipo_remetente'] === 'admin') {
                                                        echo 'Administrador';
                                                    } else {
                                                        echo htmlspecialchars($msg['usuario_nome'] ?? 'Usuário');
                                                    }
                                                    ?>
                                                </strong>
                                                &bull; <?php echo date('d/m/Y H:i', strtotime($msg['data_resposta'])); ?>
                                            </div>
                                            <div><?php echo nl2br(htmlspecialchars($msg['conteudo_resposta'])); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <h6 class="mt-4">Detalhes dos Itens:</h6>
                        <?php if (!empty($notificacao['detalhes_itens'])): ?>
                            <?php foreach ($notificacao['detalhes_itens'] as $item): ?>
                                <?php
                                if (empty($item['status_confirmacao'])) {
                                    $status_item = 'Pendente';
                                } else {
                                    $normalized_item_status = strtolower(str_replace(['ã', 'á', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'ú', 'ç'], ['a','a','a','e','e','i','o','o','u','c'], $item['status_confirmacao']));
                                    if (strpos($normalized_item_status, 'nao confirmado') !== false) {
                                        $status_item = 'Não Confirmado';
                                    } else {
                                        $status_item = $item['status_confirmacao'];
                                    }
                                }
                                $item_class = $status_item == 'Pendente' ? 'item-pendente' : 'item-nao-pendente';
                                ?>
                                <div class="item-detail-card card mb-1 <?php echo $item_class; ?>" data-item-id="<?php echo $item['id']; ?>" style="border-radius:6px;">
                                    <div class="card-body d-flex align-items-center" style="padding: 0.5rem 0.7rem; min-height: 40px;">
                                        <?php if (!$notificacao_unica_id && (empty($item['status_confirmacao']) || $item['status_confirmacao'] == 'Pendente')): ?>
                                            <input type="checkbox" class="item-checkbox mr-3" name="itens_confirmar[]" value="<?php echo $item['id']; ?>" data-notif-id="<?php echo $notificacao['id']; ?>" style="width:18px; height:18px; margin-right:12px;">
                                        <?php endif; ?>
                                        <div style="flex:1; min-width:0; font-size:0.97em;">
                                            <h7 class="card-title">Item: <?php echo htmlspecialchars($item['nome']); ?> (Patrimônio: <?php echo htmlspecialchars($item['patrimonio_novo']); ?>)</h7>
                                            <ul style="margin-bottom:0.2rem;">
                                                <li><strong>ID:</strong> <?php echo htmlspecialchars($item['id']); ?></li>
                                                <li><strong>Patrimônio Secundário:</strong> <?php echo htmlspecialchars($item['patrimonio_secundario']); ?></li>
                                                <li><strong>Local:</strong> <?php echo htmlspecialchars($item['local_nome']); ?></li>
                                                <li><strong>Responsável Atual:</strong> <?php echo htmlspecialchars($item['responsavel_nome']); ?></li>
                                                <li><strong>Estado:</strong> <?php echo htmlspecialchars($item['estado']); ?></li>
                                                <li><strong>Observação:</strong> <?php echo nl2br(htmlspecialchars($item['observacao'])); ?></li>
                                                <li><strong>Patrimônio:</strong> <?php echo htmlspecialchars($item['patrimonio_novo']); ?></li>
                                                <?php if ($status_item == 'Não Confirmado' && !empty($item['justificativa_usuario'])): ?>
                                                    <li>
                                                        <strong>Sua Justificativa:</strong> <?php echo nl2br(htmlspecialchars($item['justificativa_usuario'])); ?><br>
                                                        <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($item['data_atualizacao'])); ?></small>
                                                    </li>
                                                <?php endif; ?>
                                                <?php if ($status_item == 'Pendente' && !empty($item['admin_reply'])): ?>
                                                    <li>
                                                        <strong>Resposta do Administrador:</strong> <?php echo nl2br(htmlspecialchars($item['admin_reply'])); ?><br>
                                                        <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($item['data_admin_reply'])); ?></small>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                            <?php if (empty($item['status_confirmacao']) || $item['status_confirmacao'] == 'Pendente'): ?>
                                                <div class="mt-2 item-actions-container" id="item_actions_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>">
                                                    <form class="item-action-form d-inline" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="action" value="confirmar_item">
                                                        <button type="submit" class="btn btn-success btn-sm">Confirmar</button>
                                                </form>
                                                <style>
                                                .notification-item.card { margin-bottom: 0.5rem !important; }
                                                .item-detail-card.card { margin-bottom: 0.3rem !important; }
                                                .notif-nao-pendente { opacity: 0.55; background: #f8f9fa !important; }
                                                .notif-pendente { box-shadow: 0 0 0 2px #ffc10755; border: 1.5px solid #ffc107 !important; background: #fffbe7 !important; }
                                                .item-nao-pendente { opacity: 0.6; background: #f4f4f4 !important; }
                                                .item-pendente { background: #fffde7 !important; border-left: 3px solid #ffc107; }
                                                .notification-summary { padding: 0.4rem 0.7rem !important; }
                                                .card-body { padding: 0.5rem 0.7rem !important; }
                                                .item-detail-card .card-body ul { margin-bottom: 0.1rem; }
                                                </style>
                                                    <button type="button" class="btn btn-danger btn-sm ml-2" onclick="showItemJustificativaForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Não Confirmar</button>
                                                    <div id="item_justificativa_form_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>" style="display:none; margin-top: 10px;">
                                                        <form class="item-action-form" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                            <input type="hidden" name="action" value="nao_confirmar_item">
                                                            <div class="form-group">
                                                                <label for="item_justificativa_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>">Justificativa para este item:</label>
                                                                <textarea name="justificativa" id="item_justificativa_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>" class="form-control" rows="2" required></textarea>
                                                            </div>
                                                            <button type="submit" class="btn btn-primary btn-sm">Enviar Justificativa</button>
                                                            <button type="button" class="btn btn-secondary btn-sm" onclick="hideItemJustificativaForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Cancelar</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Nenhum item associado a esta notificação.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php if (!$notificacao_unica_id): ?>
            </form>
        <?php endif; ?>
    <!-- JS de confirmação em massa removido -->
    <?php endif; ?>
</div>

<script>
// Funções para exibir/esconder o formulário de justificativa para itens individuais
function showItemJustificativaForm(notifId, itemId) {
    const form = document.getElementById(`item_justificativa_form_${notifId}_${itemId}`);
    if (form) form.style.display = 'block';
}

function hideItemJustificativaForm(notifId, itemId) {
    const form = document.getElementById(`item_justificativa_form_${notifId}_${itemId}`);
    if (form) form.style.display = 'none';
}

// Lógica para submissão de formulários via AJAX
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-action-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('is_ajax', 'true');
            formData.append('notificacao_id', this.dataset.notifId);
            formData.append('item_id', this.dataset.itemId);

            const itemId = this.dataset.itemId;
            const notifId = this.dataset.notifId;

            fetch('notificacoes_usuario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                let msg = data.message && data.message.trim() !== '' ? data.message : (data.success ? 'Ação realizada com sucesso!' : 'Ocorreu um erro ao processar sua solicitação.');
                feedbackMessage.textContent = msg;
                feedbackMessage.className = `alert ${data.success ? 'alert-success' : 'alert-danger'}`;

                if (data.success) {
                    // Atualiza o status do item na UI
                    const itemCard = document.querySelector(`.item-detail-card[data-item-id="${itemId}"]`);
                    if (itemCard) {
                        const statusBadge = itemCard.querySelector('.item-status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = data.new_item_status;
                            updateBadgeClass(statusBadge, data.new_item_status);
                        }
                        // Esconde os botões de ação após confirmação
                        const actionContainer = document.getElementById(`item_actions_${notifId}_${itemId}`);
                        if (actionContainer) {
                            actionContainer.style.display = 'none';
                        }
                        hideItemJustificativaForm(notifId, itemId);

                        // Adiciona/atualiza o bloco de justificativa do usuário
                        let justificativaBlock = itemCard.querySelector('.item-justificativa-block');
                        if (!justificativaBlock) {
                            justificativaBlock = document.createElement('li');
                            justificativaBlock.className = 'item-justificativa-block';
                            // Insere no <ul> de detalhes do item
                            const ul = itemCard.querySelector('ul');
                            if (ul) ul.appendChild(justificativaBlock);
                        }
                        justificativaBlock.innerHTML = `<strong>Sua Justificativa:</strong> ${data.justificativa ? data.justificativa.replace(/\n/g, '<br>') : ''}<br><small>Respondido agora</small>`;
                    }

                    // Atualiza o status geral da notificação na UI
                    const notifItem = document.querySelector(`.notification-item[data-notif-id="${notifId}"]`);
                    if (notifItem) {
                        const notifStatusBadge = notifItem.querySelector('.item-status-badges .badge');
                        const notifIcon = notifItem.querySelector('.fas');

                        if (notifStatusBadge) {
                            notifStatusBadge.textContent = data.new_notif_status;
                            updateBadgeClass(notifStatusBadge, data.new_notif_status);
                        }
                        if (notifIcon) {
                            updateNotifIcon(notifIcon, data.new_notif_status);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erro na requisição Fetch:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.className = 'alert alert-danger';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação.';
            });
        });
    });

    // Função auxiliar para atualizar a classe do badge
    function updateBadgeClass(badge, status) {
        badge.className = 'badge item-status-badge ';
        if (status === 'Pendente') badge.classList.add('badge-warning');
        else if (status === 'Confirmado') badge.classList.add('badge-success');
        else if (status === 'Nao Confirmado' || status === 'Em Disputa') badge.classList.add('badge-danger');
        else if (status === 'Movimento Desfeito') badge.classList.add('badge-info');
        else badge.classList.add('badge-secondary');
    }

    // Função auxiliar para atualizar o ícone da notificação
    function updateNotifIcon(icon, status) {
        icon.className = 'fas';
        if (status === 'Pendente' || status === 'Em Disputa') icon.classList.add('fa-envelope');
        else if (status === 'Confirmado' || status === 'Movimento Desfeito') icon.classList.add('fa-envelope-open');
        else icon.classList.add('fa-envelope');
    }

    // --- Lógica para o Filtro de Status ---
    const filterButtons = document.querySelectorAll('.filter-btn');
    const notificationItems = document.querySelectorAll('.notification-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => {
                btn.classList.remove('btn-primary', 'active');
                btn.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary', 'active');

            const filter = this.dataset.filter;

            notificationItems.forEach(item => {
                const itemStatuses = item.dataset.itemStatuses;
                let shouldDisplay = false;
                
                if (filter === 'Todos') {
                    shouldDisplay = true;
                } else if (itemStatuses) {
                    // Normalizar os status para comparação
                    const normalizedStatuses = itemStatuses.split(',').map(status => {
                        return status.trim().toLowerCase().replace('ã', 'a').replace('ç', 'c');
                    });
                    
                    // Verificar se o filtro corresponde a algum status
                    if (filter === 'Nao Confirmado') {
                        shouldDisplay = normalizedStatuses.some(status => 
                            status.includes('nao confirmado') || status.includes('não confirmado') || status.includes('nao confirmado')
                        );
                    } else {
                        const normalizedFilter = filter.toLowerCase().replace('ã', 'a').replace('ç', 'c');
                        shouldDisplay = normalizedStatuses.some(status => 
                            status.includes(normalizedFilter)
                        );
                    }
                }
                
                item.style.display = shouldDisplay ? 'block' : 'none';
            });
        });
    });
    
    // --- Lógica para seleção em massa e ações em massa ---
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActionButtons = document.querySelector('.bulk-action-buttons');
    const bulkConfirmBtn = document.getElementById('bulkConfirmBtn');
    const bulkRejectBtn = document.getElementById('bulkRejectBtn');
    const bulkJustificativaForm = document.getElementById('bulkJustificativaForm');
    const bulkJustificativaText = document.getElementById('bulkJustificativaText');
    const submitBulkJustificativa = document.getElementById('submitBulkJustificativa');
    const cancelBulkJustificativa = document.getElementById('cancelBulkJustificativa');
    
    // Mostrar/ocultar botões de ação em massa quando checkboxes são selecionados
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            bulkActionButtons.style.display = anyChecked ? 'block' : 'none';
        });
    });
    
    // Ação de confirmação em massa
    bulkConfirmBtn.addEventListener('click', function() {
        const selectedItems = Array.from(checkboxes).filter(cb => cb.checked);
        if (selectedItems.length === 0) {
            alert('Por favor, selecione pelo menos um item.');
            return;
        }
        
        // Confirmar todos os itens selecionados
        const promises = selectedItems.map(checkbox => {
            const notifId = checkbox.dataset.notifId;
            const itemId = checkbox.value;
            
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('notificacao_id', notifId);
            formData.append('item_id', itemId);
            formData.append('action', 'confirmar_item');
            
            return fetch('notificacoes_usuario.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json());
        });
        
        Promise.all(promises)
            .then(results => {
                // Verificar se todas as operações foram bem-sucedidas
                const allSuccess = results.every(result => result.success);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.className = `alert ${allSuccess ? 'alert-success' : 'alert-danger'}`;
                feedbackMessage.textContent = allSuccess ? 
                    `Todos os ${selectedItems.length} itens foram confirmados com sucesso!` : 
                    'Alguns itens não puderam ser confirmados. Verifique os detalhes.';
                
                // Atualizar a interface para os itens confirmados
                selectedItems.forEach(checkbox => {
                    const notifId = checkbox.dataset.notifId;
                    const itemId = checkbox.value;
                    const itemCard = document.querySelector(`.item-detail-card[data-item-id="${itemId}"]`);
                    if (itemCard) {
                        // Esconder os botões de ação
                        const actionContainer = document.getElementById(`item_actions_${notifId}_${itemId}`);
                        if (actionContainer) {
                            actionContainer.style.display = 'none';
                        }
                        // Marcar o checkbox como não selecionável
                        checkbox.disabled = true;
                        checkbox.checked = false;
                    }
                });
                
                // Ocultar botões de ação em massa
                bulkActionButtons.style.display = 'none';
            })
            .catch(error => {
                console.error('Erro na confirmação em massa:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.className = 'alert alert-danger';
                feedbackMessage.textContent = 'Ocorreu um erro ao confirmar os itens em massa.';
            });
    });
    
    // Ação de rejeição em massa - mostrar formulário de justificativa
    bulkRejectBtn.addEventListener('click', function() {
        const selectedItems = Array.from(checkboxes).filter(cb => cb.checked);
        if (selectedItems.length === 0) {
            alert('Por favor, selecione pelo menos um item.');
            return;
        }
        
        // Mostrar formulário de justificativa em massa
        bulkJustificativaForm.style.display = 'block';
    });
    
    // Submeter justificativa em massa
    submitBulkJustificativa.addEventListener('click', function() {
        const justificativa = bulkJustificativaText.value.trim();
        if (!justificativa) {
            alert('Por favor, informe uma justificativa.');
            return;
        }
        
        const selectedItems = Array.from(checkboxes).filter(cb => cb.checked);
        if (selectedItems.length === 0) {
            alert('Nenhum item selecionado.');
            return;
        }
        
        // Rejeitar todos os itens selecionados com a mesma justificativa
        const promises = selectedItems.map(checkbox => {
            const notifId = checkbox.dataset.notifId;
            const itemId = checkbox.value;
            
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('notificacao_id', notifId);
            formData.append('item_id', itemId);
            formData.append('action', 'nao_confirmar_item');
            formData.append('justificativa', justificativa);
            
            return fetch('notificacoes_usuario.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json());
        });
        
        Promise.all(promises)
            .then(results => {
                // Verificar se todas as operações foram bem-sucedidas
                const allSuccess = results.every(result => result.success);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.className = `alert ${allSuccess ? 'alert-success' : 'alert-danger'}`;
                feedbackMessage.textContent = allSuccess ? 
                    `Todos os ${selectedItems.length} itens foram rejeitados com sucesso!` : 
                    'Alguns itens não puderam ser rejeitados. Verifique os detalhes.';
                
                // Atualizar a interface para os itens rejeitados
                selectedItems.forEach(checkbox => {
                    const notifId = checkbox.dataset.notifId;
                    const itemId = checkbox.value;
                    const itemCard = document.querySelector(`.item-detail-card[data-item-id="${itemId}"]`);
                    if (itemCard) {
                        // Esconder os botões de ação
                        const actionContainer = document.getElementById(`item_actions_${notifId}_${itemId}`);
                        if (actionContainer) {
                            actionContainer.style.display = 'none';
                        }
                        // Marcar o checkbox como não selecionável
                        checkbox.disabled = true;
                        checkbox.checked = false;
                    }
                });
                
                // Ocultar formulário de justificativa e botões de ação em massa
                bulkJustificativaForm.style.display = 'none';
                bulkJustificativaText.value = '';
                bulkActionButtons.style.display = 'none';
            })
            .catch(error => {
                console.error('Erro na rejeição em massa:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.className = 'alert alert-danger';
                feedbackMessage.textContent = 'Ocorreu um erro ao rejeitar os itens em massa.';
            });
    });
    
    // Cancelar justificativa em massa
    cancelBulkJustificativa.addEventListener('click', function() {
        bulkJustificativaForm.style.display = 'none';
        bulkJustificativaText.value = '';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>