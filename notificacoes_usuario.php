<?php
// Inicia a sessão PHP e inclui a conexão com o banco de dados
require_once 'config/db.php';

// Redireciona para a página de login se o usuário não estiver logado
if(!isset($_SESSION["id"])){
    header("location: login.php");
    exit;
}

$usuario_logado_id = $_SESSION['id']; // ID do usuário atualmente logado

// Função para atualizar o status geral da notificação com base nos itens vinculados
function atualizarStatusNotificacao($pdo, $notificacao_id, $itens_ids_array) {
    $todos_confirmados = true;
    $algum_nao_confirmado = false;

    if (empty($itens_ids_array)) {
        $novo_status_notificacao = 'Confirmado';
    } else {
        $placeholders = implode(',', array_fill(0, count($itens_ids_array), '?'));
        $sql_itens_status = "SELECT status_confirmacao FROM itens WHERE id IN ($placeholders)";
        $stmt_itens_status = $pdo->prepare($sql_itens_status);
        foreach ($itens_ids_array as $k => $id) {
            $stmt_itens_status->bindValue(($k+1), $id, PDO::PARAM_INT);
        }
        $stmt_itens_status->execute();
        $itens_status = $stmt_itens_status->fetchAll(PDO::FETCH_COLUMN);

        foreach ($itens_status as $status) {
            if ($status == 'Pendente') {
                $todos_confirmados = false;
            }
            if ($status == 'Nao Confirmado') {
                $algum_nao_confirmado = true;
                $todos_confirmados = false; // If any is 'Nao Confirmado', not all are 'Confirmado'
            }
        }

        // Verifica o status atual da notificação para manter 'Em Disputa' se já estiver
        $sql_current_notif_status = "SELECT status FROM notificacoes WHERE id = ?";
        $stmt_current_notif_status = $pdo->prepare($sql_current_notif_status);
        $stmt_current_notif_status->execute([$notificacao_id]);
        $current_notif_status = $stmt_current_notif_status->fetchColumn();

        if ($algum_nao_confirmado) {
            $novo_status_notificacao = 'Em Disputa'; // Se algum item não foi confirmado, a notificação entra em disputa
        } elseif ($todos_confirmados) {
            $novo_status_notificacao = 'Confirmado';
        } else {
            // Se não está totalmente confirmado e não está em disputa, mantém pendente
            $novo_status_notificacao = 'Pendente';
        }

        // Se a notificação já estava em disputa e agora todos os itens foram confirmados, muda para Confirmado
        if ($current_notif_status == 'Em Disputa' && $todos_confirmados) {
            $novo_status_notificacao = 'Confirmado';
        }
    }

    // Atualiza o status da notificação na tabela notificacoes
    $sql_update_notif_status = "UPDATE notificacoes SET status = ? WHERE id = ?";
    $stmt_update_notif_status = $pdo->prepare($sql_update_notif_status);
    $stmt_update_notif_status->execute([$novo_status_notificacao, $notificacao_id]);
    return $novo_status_notificacao; // Retorna o novo status da notificação
}

// --- Processamento de Ações do Formulário via AJAX ---
if(isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'new_notif_status' => '', 'new_item_status' => ''];

    if(isset($_POST['notificacao_id']) && isset($_POST['item_id']) && isset($_POST['action'])){
        $notificacao_id = $_POST['notificacao_id'];
        $item_id = $_POST['item_id'];
        $action = $_POST['action'];
        $justificativa = isset($_POST['justificativa']) ? trim($_POST['justificativa']) : '';

        $pdo->beginTransaction();
        try {
            $sql_check_item_notif = "SELECT n.usuario_id, n.itens_ids FROM notificacoes n WHERE n.id = ? AND n.usuario_id = ?";
            $stmt_check_item_notif = $pdo->prepare($sql_check_item_notif);
            $stmt_check_item_notif->execute([$notificacao_id, $usuario_logado_id]);
            $notificacao_info = $stmt_check_item_notif->fetch(PDO::FETCH_ASSOC);

            if (!$notificacao_info || !in_array($item_id, explode(',', $notificacao_info['itens_ids']))) {
                throw new Exception("Item ou notificação inválida.");
            }

            if ($action == 'confirmar_item') {
                $sql_update_item = "UPDATE itens SET status_confirmacao = 'Confirmado' WHERE id = ?";
                $stmt_update_item = $pdo->prepare($sql_update_item);
                $stmt_update_item->execute([$item_id]);
                $response['message'] = "Item #{$item_id} confirmado com sucesso!";
                $response['new_item_status'] = 'Confirmado';

                $sql_clear_just = "UPDATE notificacoes SET justificativa = NULL, data_resposta = NULL, admin_reply = NULL, admin_reply_date = NULL WHERE id = ?";
                $stmt_clear_just = $pdo->prepare($sql_clear_just);
                $stmt_clear_just->execute([$notificacao_id]);

            } elseif ($action == 'nao_confirmar_item') {
                if (empty($justificativa)) {
                    throw new Exception("Por favor, forneça uma justificativa para não confirmar o item.");
                }
                $sql_update_item = "UPDATE itens SET status_confirmacao = 'Nao Confirmado' WHERE id = ?";
                $stmt_update_item = $pdo->prepare($sql_update_item);
                $stmt_update_item->execute([$item_id]);

                $sql_update_notif_just = "UPDATE notificacoes SET justificativa = ?, data_resposta = NOW(), status = 'Em Disputa' WHERE id = ?";
                $stmt_update_notif_just = $pdo->prepare($sql_update_notif_just);
                $stmt_update_notif_just->execute([$justificativa, $notificacao_id]);

                $response['message'] = "Item #{$item_id} não confirmado. Sua justificativa foi registrada.";
                $response['new_item_status'] = 'Nao Confirmado';
            }

            $itens_ids_array = explode(',', $notificacao_info['itens_ids']);
            $response['new_notif_status'] = atualizarStatusNotificacao($pdo, $notificacao_id, $itens_ids_array);

            $pdo->commit();
            $response['success'] = true;
        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = "Erro ao processar ação: " . $e->getMessage();
            error_log("Erro na confirmação de notificação: " . $e->getMessage()); // Log do erro para depuração
        }
    }
    echo json_encode($response);
    exit; // Importante para parar a execução após a resposta AJAX
}

// --- Lógica para buscar notificações para exibição da página ---

// Se não for uma requisição AJAX, busca os dados para exibir na página
$notificacoes = [];
$notificacao_unica_id = isset($_GET['notif_id']) ? (int)$_GET['notif_id'] : 0;

// Busca as notificações do usuário logado
// Modifique a query para buscar também o nome do administrador e um resumo do assunto

$sql = "
    SELECT 
        n.id, n.tipo, n.mensagem, n.status, n.data_envio, n.itens_ids, n.justificativa, n.data_resposta, n.admin_reply, n.admin_reply_date,
        u.nome as administrador_nome,
        n.tipo as assunto_titulo,
        n.mensagem as assunto_resumo
    FROM notificacoes n
    JOIN usuarios u ON n.administrador_id = u.id
    WHERE n.usuario_id = ?
";


if ($notificacao_unica_id > 0) {
    $sql .= " AND n.id = ?";
}

$sql .= " ORDER BY n.data_envio DESC";

$stmt = $pdo->prepare($sql);

if ($notificacao_unica_id > 0) {
    $stmt->execute([$usuario_logado_id, $notificacao_unica_id]);
} else {
    $stmt->execute([$usuario_logado_id]);
}

$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para cada notificação, busca os detalhes dos itens associados
foreach ($notificacoes as $key => $notificacao) {
    $itens_ids = explode(',', $notificacao['itens_ids']);
    if (!empty($itens_ids) && !empty($itens_ids[0])) {
        $placeholders = implode(',', array_fill(0, count($itens_ids), '?'));
        
        $sql_itens = "
            SELECT 
                i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, i.estado, i.observacao, i.status_confirmacao,
                l.nome as local_nome,
                u.nome as responsavel_nome
            FROM itens i
            LEFT JOIN locais l ON i.local_id = l.id
            LEFT JOIN usuarios u ON i.responsavel_id = u.id
            WHERE i.id IN ($placeholders)
        ";
        
        $stmt_itens = $pdo->prepare($sql_itens);
        foreach ($itens_ids as $k => $id) {
            $stmt_itens->bindValue(($k + 1), $id, PDO::PARAM_INT);
        }
        $stmt_itens->execute();
        $notificacoes[$key]['detalhes_itens'] = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Inclui o cabeçalho da página
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
        <div class="notification-inbox">
            <?php foreach ($notificacoes as $notificacao): ?>
                <div class="notification-item card mb-2" data-notif-id="<?php echo $notificacao['id']; ?>">
                    <div class="card-header notification-summary" onclick="window.location.href='notificacoes_usuario.php?notif_id=<?php echo $notificacao['id']; ?>';">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas <?php 
                                    if ($notificacao['status'] == 'Pendente' || $notificacao['status'] == 'Em Disputa') echo 'fa-envelope';
                                    else if ($notificacao['status'] == 'Confirmado' || $notificacao['status'] == 'Movimento Desfeito') echo 'fa-envelope-open';
                                    else echo 'fa-envelope'; // Fallback
                                ?>"></i>
                                <strong><?php echo htmlspecialchars($notificacao['administrador_nome']); ?></strong>
                                <strong class="ml-2"><?php echo $notificacao['assunto_titulo']; ?>:</strong>
                                <span class="assunto-resumo"><?php echo htmlspecialchars($notificacao['assunto_resumo']); ?></span>
                            </div>
                            <div>
                                <span class="badge badge-<?php 
                                    if($notificacao['status'] == 'Pendente') echo 'warning';
                                    else if($notificacao['status'] == 'Confirmado') echo 'success';
                                    else if($notificacao['status'] == 'Em Disputa') echo 'danger'; // Disputa em vermelho
                                    else if($notificacao['status'] == 'Movimento Desfeito') echo 'info'; // Movimento desfeito em azul
                                    else echo 'secondary'; // Fallback
                                ?>">
                                    <?php echo htmlspecialchars($notificacao['status']); ?>
                                </span>
                                <small class="text-muted ml-2"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_envio'])); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body notification-details" <?php echo ($notificacao_unica_id > 0) ? '' : 'style="display: none;"'; ?>>
                        <p><strong>Mensagem Geral:</strong> <?php echo nl2br(htmlspecialchars($notificacao['mensagem'])); ?></p>
                        <?php if ($notificacao['status'] == 'Nao Confirmado' && !empty($notificacao['justificativa'])): ?>
                            <div class="alert alert-warning mt-2">
                                <strong>Sua Justificativa:</strong> <?php echo nl2br(htmlspecialchars($notificacao['justificativa'])); ?><br>
                                <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($notificacao['data_resposta'])); ?></small>
                            </div>
                        <?php endif; ?>

                        <?php if ($notificacao['status'] == 'Em Disputa' && !empty($notificacao['admin_reply'])): ?>
                            <div class="alert alert-info mt-2">
                                <strong>Resposta do Administrador:</strong> <?php echo nl2br(htmlspecialchars($notificacao['admin_reply'])); ?><br>
                                <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($notificacao['admin_reply_date'])); ?></small>
                            </div>
                        <?php endif; ?>

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
                                                    else echo 'danger';
                                                ?>">
                                                    <?php echo htmlspecialchars($item['status_confirmacao']); ?>
                                                </span>
                                            </li>
                                        </ul>

                                        <?php if ($item['status_confirmacao'] == 'Pendente' || $notificacao['status'] == 'Em Disputa'): ?>
                                            <form class="mt-2 item-action-form" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="action" value="confirmar_item">
                                                <button type="submit" class="btn btn-success btn-sm">Confirmar Item</button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="showItemJustificativaForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Não Confirmar Item</button>
                                            </form>
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
    document.getElementById('item_justificativa_form_' + notifId + '_' + itemId).style.display = 'block';
}

function hideItemJustificativaForm(notifId, itemId) {
    document.getElementById('item_justificativa_form_' + notifId + '_' + itemId).style.display = 'none';
}

// Lógica para submissão de formulários via AJAX
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-action-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Previne o recarregamento da página

            const formData = new FormData(this);
            formData.append('is_ajax', 'true'); // Indica que é uma requisição AJAX
            formData.append('notificacao_id', this.dataset.notifId);
            formData.append('item_id', this.dataset.itemId);

            const action = formData.get('action');
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

                if (data.success) {
                    feedbackMessage.className = 'alert alert-success';
                    // Atualiza o status do item na UI
                    const itemCard = document.querySelector(`.item-detail-card[data-item-id="${itemId}"]`);
                    if (itemCard) {
                        const statusBadge = itemCard.querySelector('.item-status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = data.new_item_status;
                            statusBadge.className = 'badge item-status-badge ';
                            if (data.new_item_status === 'Confirmado') {
                                statusBadge.classList.add('badge-success');
                            } else if (data.new_item_status === 'Nao Confirmado') {
                                statusBadge.classList.add('badge-danger');
                            } else {
                                statusBadge.classList.add('badge-warning'); // Pendente ou outro
                            }
                        }
                        // Esconde os botões de ação para o item
                        const actionButtons = itemCard.querySelector('.item-action-form');
                        if (actionButtons) {
                            actionButtons.style.display = 'none';
                        }
                        // Esconde o formulário de justificativa se estiver visível
                        hideItemJustificativaForm(notifId, itemId);
                    }

                    // Atualiza o status geral da notificação na UI
                    const notifItem = document.querySelector(`.notification-item[data-notif-id="${notifId}"]`);
                    if (notifItem) {
                        const notifStatusBadge = notifItem.querySelector('.badge');
                        const notifIcon = notifItem.querySelector('.fas');

                        if (notifStatusBadge) {
                            notifStatusBadge.textContent = data.new_notif_status;
                            notifStatusBadge.className = 'badge ';
                            if (data.new_notif_status === 'Pendente') {
                                notifStatusBadge.classList.add('badge-warning');
                                notifIcon.className = 'fas fa-envelope';
                            } else if (data.new_notif_status === 'Confirmado') {
                                notifStatusBadge.classList.add('badge-success');
                                notifIcon.className = 'fas fa-envelope-open';
                            } else if (data.new_notif_status === 'Em Disputa') {
                                notifStatusBadge.classList.add('badge-danger');
                                notifIcon.className = 'fas fa-envelope';
                            } else if (data.new_notif_status === 'Movimento Desfeito') {
                                notifStatusBadge.classList.add('badge-info');
                                notifIcon.className = 'fas fa-envelope-open';
                            } else {
                                notifStatusBadge.classList.add('badge-secondary');
                            }
                        }
                    }

                } else {
                    feedbackMessage.className = 'alert alert-danger';
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
});
</script>

<?php require_once 'includes/footer.php'; ?>