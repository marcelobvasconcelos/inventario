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
    header('Content-Type: application/json');
    // Inicializa a resposta AJAX
    $response = [
        'success' => false, 
        'message' => '', 
        'new_item_status' => '', 
        'new_notif_status' => '',
        'justificativa' => '',
        'data_justificativa' => ''
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
            // Verifica se a notificação pertence ao usuário logado e ao item correto
            $sql_check = "SELECT id FROM notificacoes_movimentacao WHERE id = ? AND item_id = ? AND usuario_notificado_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$notificacao_id, $item_id, $usuario_logado_id]);
            
            if ($stmt_check->fetchColumn() === false) {
                throw new Exception("Notificação inválida, não pertence a você ou o item não corresponde.");
            }

            $new_item_status = '';
            $data_atualizacao = date('Y-m-d H:i:s');

            if ($action == 'confirmar_item') {
                $new_item_status = 'Confirmado';
                $sql_update = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, justificativa_usuario = NULL, resposta_admin = NULL, data_atualizacao = ? WHERE id = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$new_item_status, $data_atualizacao, $notificacao_id]);
                
                $response['message'] = "Item confirmado com sucesso!";

            } elseif ($action == 'nao_confirmar_item') {
                if (empty($justificativa)) {
                    throw new Exception("Por favor, forneça uma justificativa para não confirmar.");
                }
                // O status muda para "Em Disputa" para o admin avaliar
                $new_item_status = 'Em Disputa'; 
                $sql_update = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, justificativa_usuario = ?, data_atualizacao = ? WHERE id = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$new_item_status, $justificativa, $data_atualizacao, $notificacao_id]);

                $response['message'] = "Sua justificativa foi registrada e enviada para análise.";
                $response['justificativa'] = htmlspecialchars($justificativa);
                $response['data_justificativa'] = date('d/m/Y H:i', strtotime($data_atualizacao));

            } else {
                throw new Exception("Ação inválida.");
            }
            
            // --- Lógica para determinar o status geral da notificação ---
            // 1. Encontrar o movimentacao_id desta notificação
            $stmt_mov_id = $pdo->prepare("SELECT movimentacao_id FROM notificacoes_movimentacao WHERE id = ?");
            $stmt_mov_id->execute([$notificacao_id]);
            $movimentacao_id = $stmt_mov_id->fetchColumn();

            $new_notif_status = 'Pendente'; // Status padrão
            if($movimentacao_id) {
                // 2. Obter todos os status para essa movimentação
                $stmt_statuses = $pdo->prepare("SELECT status_confirmacao FROM notificacoes_movimentacao WHERE movimentacao_id = ?");
                $stmt_statuses->execute([$movimentacao_id]);
                $statuses = $stmt_statuses->fetchAll(PDO::FETCH_COLUMN);

                // 3. Calcular o status geral
                $unique_statuses = array_unique($statuses);
                if (in_array('Em Disputa', $unique_statuses)) {
                    $new_notif_status = 'Em Disputa';
                } elseif (in_array('Pendente', $unique_statuses)) {
                    $new_notif_status = 'Pendente';
                } elseif (count($unique_statuses) == 1 && $unique_statuses[0] == 'Confirmado') {
                    $new_notif_status = 'Confirmado';
                } else {
                    // Se houver uma mistura de "Confirmado" e outros status resolvidos (ex: Movimento Desfeito),
                    // mas sem pendentes ou disputas, consideramos "Parcialmente Confirmado" ou similar.
                    // Por simplicidade, vamos manter "Confirmado" se não houver pendências ativas.
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

$sql .= " ORDER BY nm.data_notificacao DESC";

$stmt = $pdo->prepare($sql);

if ($notificacao_unica_id > 0) {
    $stmt->execute([$usuario_logado_id, $notificacao_unica_id]);
} else {
    $stmt->execute([$usuario_logado_id]);
}

$notificacoes_movimentacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notificacoes_para_exibir = [];
foreach ($notificacoes_movimentacao as $nm) {
    $notificacoes_para_exibir[] = [
        'id' => $nm['id'],
        'tipo' => 'transferencia',
        'mensagem' => "Movimentação do item: " . htmlspecialchars($nm['item_nome']) . " (Patrimônio: " . htmlspecialchars($nm['patrimonio_novo']) . "). Status: " . htmlspecialchars(ucfirst($nm['status_confirmacao'])),
        'status' => ucfirst($nm['status_confirmacao']),
        'data_envio' => $nm['data_notificacao'],
        'administrador_nome' => $nm['admin_nome'],
        'assunto_titulo' => 'Movimentação de Item',
        'assunto_resumo' => "Item: " . htmlspecialchars($nm['item_nome']) . " - Status: " . htmlspecialchars(ucfirst($nm['status_confirmacao'])),
        'detalhes_itens' => [
            [
                'id' => $nm['item_id'],
                'status_confirmacao' => ucfirst($nm['status_confirmacao']),
                'justificativa_usuario' => $nm['justificativa_usuario'],
                'admin_reply' => $nm['resposta_admin'],
                'nome' => $nm['item_nome'],
                'patrimonio_novo' => $nm['patrimonio_novo'],
                'patrimonio_secundario' => $nm['patrimonio_secundario'],
                'estado' => $nm['estado'],
                'observacao' => $nm['observacao'],
                'local_nome' => $nm['local_nome'],
                'responsavel_nome' => $nm['responsavel_nome'],
                'data_justificativa' => $nm['data_atualizacao'],
                'data_admin_reply' => $nm['data_atualizacao'],
            ]
        ],
        'data_item_statuses' => ucfirst($nm['status_confirmacao'])
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
        <?php endif; ?>

        <div class="notification-inbox">
            <?php foreach ($notificacoes as $notificacao): ?>
                <div class="notification-item card mb-2" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-statuses="<?php echo $notificacao['data_item_statuses']; ?>">
                    <div class="card-header notification-summary">
                        <a href="notificacoes_usuario.php?notif_id=<?php echo $notificacao['id']; ?>" class="d-flex justify-content-between align-items-center w-100 text-decoration-none text-dark">
                            <div>
                                <i class="fas <?php 
                                    if ($notificacao['status'] == 'Pendente' || $notificacao['status'] == 'Em Disputa') echo 'fa-envelope';
                                    else if ($notificacao['status'] == 'Confirmado' || $notificacao['status'] == 'Movimento Desfeito') echo 'fa-envelope-open';
                                    else echo 'fa-envelope';
                                ?>"></i>
                                <strong><?php echo htmlspecialchars($notificacao['administrador_nome']); ?></strong>
                                <strong class="ml-2"><?php echo $notificacao['assunto_titulo']; ?>:</strong>
                                <span class="assunto-resumo"><?php echo htmlspecialchars($notificacao['assunto_resumo']); ?></span>
                            </div>
                            <div>
                                <div class="item-status-badges">
                                    <?php
                                    $unique_statuses = [];
                                    if (!empty($notificacao['detalhes_itens'])) {
                                        foreach ($notificacao['detalhes_itens'] as $item) {
                                            $unique_statuses[$item['status_confirmacao']] = true;
                                        }
                                    }
                                    if (empty($unique_statuses)) {
                                        $status = $notificacao['status'];
                                        $badge_class = 'badge-secondary';
                                        if($status == 'Pendente') $badge_class = 'badge-warning';
                                        else if($status == 'Confirmado') $badge_class = 'badge-success';
                                        else if($status == 'Em Disputa') $badge_class = 'badge-danger';
                                        echo "<span class=\"badge {$badge_class} mr-1\">" . htmlspecialchars($status) . "</span>";
                                    } else {
                                        foreach (array_keys($unique_statuses) as $status) {
                                            $badge_class = 'badge-secondary';
                                            if ($status == 'Pendente') $badge_class = 'badge-warning';
                                            elseif ($status == 'Confirmado') $badge_class = 'badge-success';
                                            elseif ($status == 'Nao Confirmado') $badge_class = 'badge-danger';
                                            echo "<span class=\"badge {$badge_class} mr-1\">" . htmlspecialchars($status) . "</span>";
                                        }
                                    }
                                    ?>
                                </div>
                                <small class="text-muted ml-2"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_envio'])); ?></small>
                            </div>
                        </a>
                    </div>
                    <div class="card-body notification-details" <?php echo ($notificacao_unica_id > 0) ? '' : 'style="display: none;"'; ?>>
                        <p><strong>Mensagem Geral:</strong> <?php echo nl2br(htmlspecialchars($notificacao['mensagem'])); ?></p>
                        <h6 class="mt-4">Detalhes dos Itens:</h6>
                        <?php if (!empty($notificacao['detalhes_itens'])): ?>
                            <?php foreach ($notificacao['detalhes_itens'] as $item): ?>
                                <div class="item-detail-card card mb-2" data-item-id="<?php echo $item['id']; ?>">
                                    <div class="card-body">
                                        <h7 class="card-title">Item: <?php echo htmlspecialchars($item['nome']); ?> (Patrimônio: <?php echo htmlspecialchars($item['patrimonio_novo']); ?>)</h7>
                                        <ul>
                                            <li><strong>ID:</strong> <?php echo htmlspecialchars($item['id']); ?></li>
                                            <li><strong>Patrimônio Secundário:</strong> <?php echo htmlspecialchars($item['patrimonio_secundario']); ?></li>
                                            <li><strong>Local:</strong> <?php echo htmlspecialchars($item['local_nome']); ?></li>
                                            <li><strong>Responsável Atual:</strong> <?php echo htmlspecialchars($item['responsavel_nome']); ?></li>
                                            <li><strong>Estado:</strong> <?php echo htmlspecialchars($item['estado']); ?></li>
                                            <li><strong>Observação:</strong> <?php echo nl2br(htmlspecialchars($item['observacao'])); ?></li>
                                            <li><strong>Status Confirmação:</strong> 
                                                <span class="badge item-status-badge badge-<?php 
                                                    if($item['status_confirmacao'] == 'Pendente') echo 'warning';
                                                    else if($item['status_confirmacao'] == 'Confirmado') echo 'success';
                                                    else if($item['status_confirmacao'] == 'Nao Confirmado') echo 'danger';
                                                    else if($item['status_confirmacao'] == 'Em Disputa') echo 'danger';
                                                    else if($item['status_confirmacao'] == 'Movimento Desfeito') echo 'info';
                                                    else echo 'secondary';
                                                ?>">
                                                    <?php echo htmlspecialchars($item['status_confirmacao']); ?>
                                                </span>
                                            </li>
                                            <?php if ($item['status_confirmacao'] == 'Nao Confirmado' && !empty($item['justificativa_usuario'])): ?>
                                                <li>
                                                    <strong>Sua Justificativa:</strong> <?php echo nl2br(htmlspecialchars($item['justificativa_usuario'])); ?><br>
                                                    <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($item['data_justificativa'])); ?></small>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ($item['status_confirmacao'] == 'Em Disputa' && !empty($item['admin_reply'])): ?>
                                                <li>
                                                    <strong>Resposta do Administrador:</strong> <?php echo nl2br(htmlspecialchars($item['admin_reply'])); ?><br>
                                                    <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($item['data_admin_reply'])); ?></small>
                                                </li>
                                            <?php endif; ?>
                                        </ul>

                                        <?php if ($item['status_confirmacao'] == 'Pendente' || $item['status_confirmacao'] == 'Em Disputa'): ?>
                                            <div class="item-actions-container">
                                                <form class="mt-2 item-action-form" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="action" value="confirmar_item">
                                                    <button type="submit" class="btn btn-success btn-sm">Confirmar Item</button>
                                                </form>
                                                <button type="button" class="btn btn-danger btn-sm mt-2" onclick="showItemJustificativaForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Não Confirmar Item</button>
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
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Nenhum item associado a esta notificação.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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
                feedbackMessage.textContent = data.message;
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
                        const actionContainer = itemCard.querySelector('.item-actions-container');
                        if (actionContainer) {
                            actionContainer.style.display = 'none';
                        }
                        hideItemJustificativaForm(notifId, itemId);
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
                if (filter === 'Todos' || (itemStatuses && itemStatuses.includes(filter))) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>