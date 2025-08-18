<?php
require_once '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION["id"])) {
    header("location: ../login.php");
    exit;
}

$usuario_logado_id = $_SESSION['id'];

if (isset($_POST['is_ajax'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'new_item_status' => ''];

    if (isset($_POST['notificacao_id'], $_POST['item_id'], $_POST['action'])) {
        $notificacao_id = filter_var($_POST['notificacao_id'], FILTER_VALIDATE_INT);
        $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);
        $action = $_POST['action'];

        $pdo->beginTransaction();
        try {
            if ($action === 'confirmar_item') {
                $new_status = 'Confirmado';
                $sql_update_item = "UPDATE almoxarifado_materiais SET status_confirmacao = ? WHERE id = ?";
                $pdo->prepare($sql_update_item)->execute([$new_status, $item_id]);

                $sql_update_notif = "UPDATE notificacoes SET status = 'Lida' WHERE id = ?";
                $pdo->prepare($sql_update_notif)->execute([$notificacao_id]);

                $response['message'] = 'Item confirmado com sucesso!';
            } elseif ($action === 'nao_confirmar_item') {
                $justificativa = trim($_POST['justificativa']);
                if (empty($justificativa)) {
                    throw new Exception('A justificativa é obrigatória.');
                }

                $new_status = 'Nao Confirmado';
                $sql_update_item = "UPDATE almoxarifado_materiais SET status_confirmacao = ? WHERE id = ?";
                $pdo->prepare($sql_update_item)->execute([$new_status, $item_id]);

                $sql_insert_resposta = "INSERT INTO notificacoes_almoxarifado_respostas (notificacao_id, item_id, usuario_id, justificativa) VALUES (?, ?, ?, ?)";
                $pdo->prepare($sql_insert_resposta)->execute([$notificacao_id, $item_id, $usuario_logado_id, $justificativa]);

                $sql_update_notif = "UPDATE notificacoes SET status = 'Nao Lida' WHERE id = ?";
                $pdo->prepare($sql_update_notif)->execute([$notificacao_id]);

                $response['message'] = 'Justificativa enviada. O administrador será notificado.';
            } else {
                throw new Exception('Ação inválida.');
            }

            $pdo->commit();
            $response['success'] = true;
            $response['new_item_status'] = $new_status;

        } catch (Exception $e) {
            $pdo->rollBack();
            $response['message'] = "Erro: " . $e->getMessage();
        }
    }
    echo json_encode($response);
    exit;
}


// Lógica para buscar notificações do almoxarifado para o usuário logado
$sql = "
    SELECT 
        n.id as notificacao_id,
        n.mensagem,
        n.status as notificacao_status,
        n.data_criacao,
        am.id as item_id,
        am.nome as item_nome,
        am.estado as item_estado,
        am.status_confirmacao as item_status_confirmacao,
        u.nome as admin_nome
    FROM notificacoes n
    JOIN notificacoes_almoxarifado_detalhes nad ON n.id = nad.notificacao_id
    JOIN almoxarifado_materiais am ON nad.item_id = am.id
    JOIN usuarios u ON n.administrador_id = u.id
    WHERE n.usuario_id = ? AND n.tipo = 'atribuicao_almoxarifado'
    ORDER BY n.data_criacao DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_logado_id]);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">
    <h2><i class="fas fa-bell"></i> Notificações do Almoxarifado</h2>
    <p>Aqui você pode visualizar e confirmar os itens de almoxarifado atribuídos a você.</p>

    <div id="feedback-message" class="alert" style="display:none;"></div>

    <?php if (empty($notificacoes)): ?>
        <div class="alert alert-info">Você não possui notificações do almoxarifado no momento.</div>
    <?php else: ?>
        <div class="notification-inbox">
            <?php foreach ($notificacoes as $notificacao): ?>
                <div class="notification-item card mb-2">
                    <div class="card-body">
                        <p><strong>De:</strong> <?php echo htmlspecialchars($notificacao['admin_nome']); ?></p>
                        <p><strong>Mensagem:</strong> <?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                        <p><strong>Item:</strong> <?php echo htmlspecialchars($notificacao['item_nome']); ?></p>
                        <p><strong>Estado do Item:</strong> <?php echo htmlspecialchars($notificacao['item_estado']); ?></p>
                        <p><strong>Status:</strong> <span class="badge"><?php echo htmlspecialchars($notificacao['item_status_confirmacao']); ?></span></p>
                        <p><small>Recebido em: <?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></small></p>
                        
                        <?php if ($notificacao['item_status_confirmacao'] == 'Pendente'): ?>
                            <div class="item-actions-container">
                                <form class="item-action-form d-inline" data-notif-id="<?php echo $notificacao['notificacao_id']; ?>" data-item-id="<?php echo $notificacao['item_id']; ?>">
                                    <input type="hidden" name="action" value="confirmar_item">
                                    <button type="submit" class="btn-custom"><i class="fas fa-check"></i> Confirmar</button>
                                </form>
                                <button type="button" class="btn-danger ml-2" onclick="showJustificativaForm(<?php echo $notificacao['notificacao_id']; ?>, <?php echo $notificacao['item_id']; ?>)"><i class="fas fa-times"></i> Não Confirmar</button>
                                <div id="justificativa_form_<?php echo $notificacao['notificacao_id']; ?>_<?php echo $notificacao['item_id']; ?>" style="display:none; margin-top: 10px;">
                                    <form class="item-action-form" data-notif-id="<?php echo $notificacao['notificacao_id']; ?>" data-item-id="<?php echo $notificacao['item_id']; ?>">
                                        <input type="hidden" name="action" value="nao_confirmar_item">
                                        <div class="form-group">
                                            <label for="justificativa_<?php echo $notificacao['notificacao_id']; ?>_<?php echo $notificacao['item_id']; ?>">Justificativa:</label>
                                            <textarea name="justificativa" id="justificativa_<?php echo $notificacao['notificacao_id']; ?>_<?php echo $notificacao['item_id']; ?>" class="form-control" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" class="btn-custom"><i class="fas fa-paper-plane"></i> Enviar Justificativa</button>
                                        <button type="button" class="btn-danger" onclick="hideJustificativaForm(<?php echo $notificacao['notificacao_id']; ?>, <?php echo $notificacao['item_id']; ?>)"><i class="fas fa-times"></i> Cancelar</button>
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
