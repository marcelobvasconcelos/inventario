<?php
// Inicia a sessão PHP e inclui o cabeçalho e a conexão com o banco de dados
require_once 'includes/header.php';
require_once 'config/db.php';

// Redireciona para a página de login se o usuário não estiver logado ou não for Administrador
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$administrador_logado_id = $_SESSION['id'];

// --- Processamento de Ações do Administrador via AJAX ---
if(isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'new_item_status' => '', 'new_notif_status' => ''];

    // Validação mais robusta dos dados recebidos
    if(isset($_POST['action'], $_POST['notificacao_id'], $_POST['item_id'])){ // Adicionado item_id aqui
        $notificacao_movimentacao_id = filter_var($_POST['notificacao_id'], FILTER_VALIDATE_INT);
        $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);
        $action = $_POST['action'];
        $admin_reply = isset($_POST['admin_reply']) ? trim($_POST['admin_reply']) : '';

        if (!$notificacao_movimentacao_id || !$item_id) {
            $response['message'] = 'ID de notificação ou item inválido.';
            echo json_encode($response);
            exit;
        }

        $pdo->beginTransaction();
        try {
            // Busca informações da notificação de movimentação para garantir que exista
            $sql_notif_mov_info = "SELECT nm.item_id, nm.movimentacao_id, nm.status_confirmacao FROM notificacoes_movimentacao nm WHERE nm.id = ? AND nm.item_id = ?";
            $stmt_notif_mov_info = $pdo->prepare($sql_notif_mov_info);
            $stmt_notif_mov_info->execute([$notificacao_movimentacao_id, $item_id]);
            $notif_mov_data = $stmt_notif_mov_info->fetch(PDO::FETCH_ASSOC);

            if (!$notif_mov_data) {
                throw new Exception("Notificação de movimentação não encontrada ou item não corresponde.");
            }
            $movimentacao_id = $notif_mov_data['movimentacao_id'];
            $data_atualizacao = date('Y-m-d H:i:s');

            if ($action == 'responder_item_disputa') {
                if (empty($admin_reply)) {
                    throw new Exception("Por favor, forneça uma resposta para a disputa.");
                }
                // Atualiza o registro em notificacoes_movimentacao com a resposta do admin e muda o status para 'Replicado'
                $new_item_status = 'Replicado'; // Changed to uppercase
                $sql_update_notif_mov = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, resposta_admin = ?, data_atualizacao = ? WHERE id = ?";
                $stmt_update_notif_mov = $pdo->prepare($sql_update_notif_mov);
                $stmt_update_notif_mov->execute([$new_item_status, $admin_reply, $data_atualizacao, $notificacao_movimentacao_id]);

                // O status do item na tabela 'itens' deve voltar para 'Pendente' para que o usuário possa reconfirmar
                $sql_update_item_main = "UPDATE itens SET status_confirmacao = 'Pendente' WHERE id = ?";
                $stmt_update_item_main = $pdo->prepare($sql_update_item_main);
                $stmt_update_item_main->execute([$item_id]);

                $response['message'] = "Resposta enviada para o item #{$item_id}. Status do item atualizado para Replicado (aguardando nova confirmação do usuário). ";
                $response['new_item_status'] = $new_item_status;

            } elseif ($action == 'desfazer_movimentacao_item') {
                // Busca os dados da movimentação original para reverter
                $sql_get_mov_details = "SELECT local_origem_id, usuario_anterior_id FROM movimentacoes WHERE id = ?";
                $stmt_get_mov_details = $pdo->prepare($sql_get_mov_details);
                $stmt_get_mov_details->execute([$movimentacao_id]);
                $mov_details = $stmt_get_mov_details->fetch(PDO::FETCH_ASSOC);

                if ($mov_details) {
                    // Reverte o item para o local e responsável de origem da movimentação
                    $new_item_status = 'Movimento Desfeito';
                    $sql_update_item_main = "UPDATE itens SET local_id = ?, responsavel_id = ?, status_confirmacao = ? WHERE id = ?";
                    $stmt_update_item_main = $pdo->prepare($sql_update_item_main);
                    $stmt_update_item_main->execute([$mov_details['local_origem_id'], $mov_details['usuario_anterior_id'], $new_item_status, $item_id]);

                    // Atualiza o status na notificacoes_movimentacao para 'Movimento Desfeito'
                    $sql_update_notif_mov = "UPDATE notificacoes_movimentacao SET status_confirmacao = ?, data_atualizacao = ? WHERE id = ?";
                    $stmt_update_notif_mov = $pdo->prepare($sql_update_notif_mov);
                    $stmt_update_notif_mov->execute([$new_item_status, $data_atualizacao, $notificacao_movimentacao_id]);

                    $response['message'] = "Movimentação do item #{$item_id} desfeita. Status do item atualizado para Movimento Desfeito.";
                    $response['new_item_status'] = $new_item_status;
                } else {
                    throw new Exception("Não foi possível desfazer a movimentação do item #{$item_id}: Detalhes da movimentação não encontrados.");
                }
            } else {
                throw new Exception("Ação inválida.");
            }

            // --- Lógica para determinar o status geral da notificação (movimentacao_id) ---
            $new_notif_status = 'Pendente'; // Status padrão
            if($movimentacao_id) {
                // 2. Obter todos os status para essa movimentação
                $stmt_statuses = $pdo->prepare("SELECT status_confirmacao FROM notificacoes_movimentacao WHERE movimentacao_id = ?");
                $stmt_statuses->execute([$movimentacao_id]);
                $statuses = $stmt_statuses->fetchAll(PDO::FETCH_COLUMN);

                // 3. Calcular o status geral
                $unique_statuses = array_unique($statuses);
                if (in_array('Em Disputa', $unique_statuses) || in_array('Pendente', $unique_statuses) || in_array('Replicado', $unique_statuses)) { // Changed 'replicado' to 'Replicado'
                    $new_notif_status = 'Pendente'; // Se ainda há itens pendentes, em disputa ou replicados, a notificação geral fica pendente.
                } elseif (count($unique_statuses) == 1 && $unique_statuses[0] == 'Confirmado') {
                    $new_notif_status = 'Confirmado';
                } elseif (count($unique_statuses) == 1 && $unique_statuses[0] == 'Movimento Desfeito') {
                    $new_notif_status = 'Movimento Desfeito';
                } else {
                    // Se houver uma mistura de "Confirmado" e "Movimento Desfeito", mas sem pendentes/disputas/replicados
                    $new_notif_status = 'Confirmado'; // Ou um status como 'Parcialmente Resolvido' se for o caso
                }
            }
            
            $pdo->commit();
            $response['success'] = true;
            $response['new_notif_status'] = $new_notif_status; // Envia o status geral calculado

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = "Erro ao processar ação: " . $e->getMessage();
            error_log("Erro na ação do administrador: " . $e->getMessage()); // Log do erro para depuração
        }
    } else {
        $response['message'] = 'Dados incompletos na requisição.';
    }
    echo json_encode($response);
    exit; // Importante para parar a execução após a resposta AJAX
}

// --- Lógica para exibir uma única notificação ou todas ---
$notificacao_unica_id = isset($_GET['notif_id']) ? (int)$_GET['notif_id'] : 0;
$notificacoes = []; // Este array agora conterá os registros de notificacoes_movimentacao

// Obtém o status de filtro da URL, padrão é 'Todos'
$filtro_status = isset($_GET['status']) ? $_GET['status'] : 'Todos';

// SQL base para buscar notificações de movimentação
$sql = "
    SELECT
        nm.id, nm.status_confirmacao, nm.justificativa_usuario, nm.resposta_admin,
        nm.data_notificacao, nm.data_atualizacao,
        i.id as item_id, i.nome as item_nome, i.patrimonio_novo, i.patrimonio_secundario, i.estado, i.observacao,
        l.nome as local_nome,
        resp.nome as responsavel_nome,
        mov.usuario_id as admin_id,
        admin_user.nome as admin_nome,
        nm.usuario_notificado_id,
        user_notified.nome as usuario_notificado_nome,
        mov.data_movimentacao
    FROM notificacoes_movimentacao nm
    JOIN itens i ON nm.item_id = i.id
    JOIN movimentacoes mov ON nm.movimentacao_id = mov.id
    JOIN usuarios admin_user ON mov.usuario_id = admin_user.id -- Admin que fez a movimentação
    JOIN usuarios user_notified ON nm.usuario_notificado_id = user_notified.id -- Usuário que recebeu a notificação
    LEFT JOIN locais l ON i.local_id = l.id
    LEFT JOIN usuarios resp ON i.responsavel_id = resp.id
    WHERE 1=1
";

$params = [];

if ($notificacao_unica_id > 0) {
    $sql .= " AND nm.id = ?";
    $params[] = $notificacao_unica_id;
} else {
    // Adiciona cláusula WHERE se um filtro de status específico for selecionado
    if ($filtro_status != 'Todos') {
        $sql .= " AND nm.status_confirmacao = ?";
        $params[] = $filtro_status;
    }
}

$sql .= " ORDER BY nm.data_notificacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notificacoes_movimentacao_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Reestrutura os dados para compatibilidade com o HTML existente
foreach ($notificacoes_movimentacao_raw as $nm) {
    $notificacoes[] = [
        'id' => $nm['id'], // ID da notificacoes_movimentacao
        'tipo' => 'transferencia', // Tipo fixo para compatibilidade
        'mensagem' => "Movimentação do item: " . htmlspecialchars($nm['item_nome']) . " (Patrimônio: " . htmlspecialchars($nm['patrimonio_novo']) . "). Status: " . htmlspecialchars($nm['status_confirmacao']),
        'status' => $nm['status_confirmacao'], // Status do item
        'data_envio' => $nm['data_notificacao'],
        'usuario_nome' => $nm['usuario_notificado_nome'], // O usuário que recebeu a notificação
        'administrador_nome' => $nm['admin_nome'], // O admin que fez a movimentação
        'assunto_titulo' => 'Movimentação de Item',
        'assunto_resumo' => "Item: " . htmlspecialchars($nm['item_nome']) . " - Status: " . htmlspecialchars($nm['status_confirmacao']),
        'justificativa' => $nm['justificativa_usuario'], // Justificativa do usuário
        'data_resposta' => $nm['data_atualizacao'], // Data da justificativa/resposta
        'admin_reply' => $nm['resposta_admin'], // Resposta do admin
        'admin_reply_date' => $nm['data_atualizacao'], // Data da resposta do admin
        'detalhes_itens' => [ // Cada "notificacao" terá apenas um "detalhes_itens"
            [
                'id' => $nm['item_id'],
                'status_confirmacao' => $nm['status_confirmacao'],
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
        ]
    ];
}

?>

<div class="container mt-5">
    <?php if ($notificacao_unica_id > 0): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Detalhes da Notificação <i class="fas fa-shield-alt" style="color: #124a80;"></i></h2>
            <a href="notificacoes_admin.php" class="btn btn-primary btn-sm">Voltar para Notificações</a>
        </div>
    <?php else: ?>
        <h2>Gerenciar Notificações de Inventário <i class="fas fa-shield-alt" style="color: #124a80;"></i></h2>
        <p>Visualize o status das notificações de movimentação e as justificativas dos usuários.</p>

        <div class="mb-3">
            <label for="filtroStatus">Filtrar por Status:</label>
            <select id="filtroStatus" class="form-control" onchange="window.location.href='notificacoes_admin.php?status=' + this.value">
                <option value="Todos" <?php echo ($filtro_status == 'Todos') ? 'selected' : ''; ?>>Todos</option>
                <option value="Pendente" <?php echo ($filtro_status == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                <option value="Confirmado" <?php echo ($filtro_status == 'Confirmado') ? 'selected' : ''; ?>>Confirmado</option>
                <option value="Nao Confirmado" <?php echo ($filtro_status == 'Nao Confirmado') ? 'selected' : ''; ?>>Não Confirmado</option>
                <option value="Em Disputa" <?php echo ($filtro_status == 'Em Disputa') ? 'selected' : ''; ?>>Em Disputa</option>
                <option value="Movimento Desfeito" <?php echo ($filtro_status == 'Movimento Desfeito') ? 'selected' : ''; ?>>Movimento Desfeito</option>
            </select>
        </div>
    <?php endif; ?>

    <div id="feedback-message" class="alert" style="display:none;"></div>

    <?php if (empty($notificacoes)): ?>
        <div class="alert alert-info">Nenhuma notificação encontrada com o filtro selecionado.</div>
    <?php else: ?>
        <div class="notification-inbox">
            <?php foreach ($notificacoes as $notificacao): ?>
                <div class="notification-item card mb-2" data-notif-id="<?php echo $notificacao['id']; ?>">
                    <div class="card-header notification-summary" onclick="window.location.href='notificacoes_admin.php?notif_id=<?php echo $notificacao['id']; ?>';">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas <?php 
                                    if ($notificacao['status'] == 'Pendente' || $notificacao['status'] == 'Em Disputa') echo 'fa-envelope';
                                    else if ($notificacao['status'] == 'Confirmado' || $notificacao['status'] == 'Movimento Desfeito') echo 'fa-envelope-open';
                                    else echo 'fa-envelope'; // Fallback
                                ?>"></i>
                                <strong><?php echo htmlspecialchars($notificacao['usuario_nome']); ?></strong>
                                <strong class="ml-2"><?php echo $notificacao['assunto_titulo']; ?>:</strong>
                                <span class="assunto-resumo"><?php echo htmlspecialchars($notificacao['assunto_resumo']); ?></span>
                            </div>
                            <div>
                                <span class="badge badge-<?php 
                                    if($notificacao['status'] == 'Pendente') echo 'warning';
                                    else if($notificacao['status'] == 'Confirmado') echo 'success';
                                    else if($notificacao['status'] == 'Nao Confirmado') echo 'danger';
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
                        
                        <?php if ($notificacao['status'] == 'Nao Confirmado' || $notificacao['status'] == 'Em Disputa'): ?>
                            <div class="alert alert-warning mt-2">
                                <strong>Justificativa do Usuário:</strong> <?php echo nl2br(htmlspecialchars($notificacao['justificativa'])); ?><br>
                                <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($notificacao['data_resposta'])); ?></small>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($notificacao['admin_reply'])): ?>
                            <div class="alert alert-info mt-2">
                                <strong>Sua Resposta Anterior:</strong> <?php echo nl2br(htmlspecialchars($notificacao['admin_reply'])); ?><br>
                                <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($notificacao['admin_reply_date'])); ?></small>
                            </div>
                        <?php endif; ?>

                        <h6 class="mt-4">Detalhes dos Itens Associados:</h6>
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
                                            <li><strong>Estado:</strong> <?php echo nl2br(htmlspecialchars($item['observacao'])); ?></li>
                                            <li><strong>Status Confirmação:</strong> 
                                                <span class="badge badge-<?php 
                                                    if($item['status_confirmacao'] == 'Pendente') echo 'warning';
                                                    else if($item['status_confirmacao'] == 'Confirmado') echo 'success';
                                                    else echo 'danger';
                                                ?>">
                                                    <?php echo htmlspecialchars($item['status_confirmacao']); ?>
                                                </span>
                                            </li>
                                        </ul>
                                        <?php if (!empty($item['admin_reply'])): ?>
                                            <div class="alert alert-info mt-2">
                                                <strong>Sua Resposta Anterior (Item):</strong> <?php echo nl2br(htmlspecialchars($item['admin_reply'])); ?><br>
                                                <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($item['admin_reply_date'])); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($item['status_confirmacao'] == 'Nao Confirmado' || $item['status_confirmacao'] == 'Em Disputa' || $item['status_confirmacao'] == 'replicado'): ?>
                                            <div class="admin-item-actions mt-2">
                                                <form class="d-inline-block admin-item-action-form" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="action" value="desfazer_movimentacao_item">
                                                    <button type="submit" class="btn btn-danger btn-sm">Desfazer Movimentação</button>
                                                </form>
                                                <button type="button" class="btn btn-primary btn-sm ml-2" onclick="toggleAdminItemReplyForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Responder</button>
                                                <div id="admin_item_reply_form_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>" style="display:none; margin-top: 10px;">
                                                    <form class="admin-item-action-form" data-notif-id="<?php echo $notificacao['id']; ?>" data-item-id="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="action" value="responder_item_disputa">
                                                        <div class="form-group">
                                                            <label for="admin_item_reply_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>">Sua Resposta para este item:</label>
                                                            <textarea name="admin_reply" id="admin_item_reply_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>" class="form-control" rows="2" required><?php echo htmlspecialchars($item['admin_reply'] ?? ''); ?></textarea>
                                                        </div>
                                                        <button type="submit" class="btn btn-success btn-sm">Enviar Resposta</button>
                                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAdminItemReplyForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Cancelar</button>
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
// Função para exibir/esconder o formulário de resposta do administrador
function toggleAdminReplyForm(notifId) {
    const form = document.getElementById('admin_reply_form_' + notifId);
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.admin-item-action-form').forEach(form => { // Changed selector to target item-specific forms
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Previne o recarregamento da página

            const formData = new FormData(this);
            formData.append('is_ajax', 'true'); // Indica que é uma requisição AJAX
            formData.append('notificacao_id', this.dataset.notifId);
            formData.append('item_id', this.dataset.itemId); // Adicionado item_id

            const action = formData.get('action');
            const notifId = this.dataset.notifId;
            const itemId = this.dataset.itemId; // Captura itemId

            fetch('notificacoes_admin.php', {
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

                    // Atualiza o status do item específico na UI
                    const itemCard = document.querySelector(`.item-detail-card[data-item-id="${itemId}"]`);
                    if (itemCard) {
                        const statusBadge = itemCard.querySelector('.badge');
                        if (statusBadge) {
                            statusBadge.textContent = data.new_item_status;
                            updateBadgeClass(statusBadge, data.new_item_status); // Reutiliza função de update de badge
                        }
                        const actionContainer = itemCard.querySelector('.admin-item-actions');
                        if (actionContainer) {
                            actionContainer.style.display = 'none'; // Esconde os botões de ação do item
                        }
                        // Esconde o formulário de resposta do admin para este item
                        if (action === 'responder_item_disputa') {
                            toggleAdminItemReplyForm(notifId, itemId);
                        }
                    }

                    // Atualiza o status geral da notificação na UI
                    const notifItem = document.querySelector(`.notification-item[data-notif-id="${notifId}"]`);
                    if (notifItem) {
                        const notifStatusBadge = notifItem.querySelector('.badge'); // Seletor mais específico para o badge geral
                        const notifIcon = notifItem.querySelector('.fas');

                        if (notifStatusBadge && data.new_notif_status) { // Verifica se new_notif_status foi enviado
                            notifStatusBadge.textContent = data.new_notif_status;
                            updateBadgeClass(notifStatusBadge, data.new_notif_status); // Reutiliza função de update de badge
                        }
                        if (notifIcon && data.new_notif_status) {
                            updateNotifIcon(notifIcon, data.new_notif_status); // Reutiliza função de update de ícone
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

    // Funções auxiliares para atualizar a classe do badge e o ícone (copiadas de notificacoes_usuario.php)
    function updateBadgeClass(badge, status) {
        badge.className = 'badge '; // Reseta classes
        if (status === 'Pendente') badge.classList.add('badge-warning');
        else if (status === 'Confirmado') badge.classList.add('badge-success');
        else if (status === 'Nao Confirmado' || status === 'Em Disputa' || status === 'Replicado') badge.classList.add('badge-danger'); // Adicionado 'Replicado'
        else if (status === 'Movimento Desfeito') badge.classList.add('badge-info');
        else badge.classList.add('badge-secondary');
    }

    function updateNotifIcon(icon, status) {
        icon.className = 'fas'; // Reseta classes
        if (status === 'Pendente' || status === 'Em Disputa' || status === 'Replicado') icon.classList.add('fa-envelope'); // Adicionado 'Replicado'
        else if (status === 'Confirmado' || status === 'Movimento Desfeito') icon.classList.add('fa-envelope-open');
        else icon.classList.add('fa-envelope');
    }

    // Função para exibir/esconder o formulário de resposta do administrador para ITENS
    function toggleAdminItemReplyForm(notifId, itemId) {
        const form = document.getElementById(`admin_item_reply_form_${notifId}_${itemId}`);
        if (form) {
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>