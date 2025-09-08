<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

if (!isset($_SESSION["id"])) {
    header("location: ../../login.php");
    exit;
}

$usuario_logado_id = $_SESSION['id'];

// Processar ações via AJAX
if (isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['notificacao_id'], $_POST['acao'])) {
        $notificacao_id = filter_var($_POST['notificacao_id'], FILTER_VALIDATE_INT);
        $acao = $_POST['acao'];
        
        $sql_check = "SELECT id, requisicao_id FROM almoxarifado_requisicoes_notificacoes WHERE id = ? AND usuario_destino_id = ? AND status != 'concluida'";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$notificacao_id, $usuario_logado_id]);
        
        if ($row_check = $stmt_check->fetch(PDO::FETCH_ASSOC)) {
            $requisicao_id = $row_check['requisicao_id'];
            
            if ($acao == 'responder' && isset($_POST['mensagem'])) {
                $mensagem = trim($_POST['mensagem']);
                if (!empty($mensagem)) {
                    $pdo->beginTransaction();
                    try {
                        $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'em_discussao' WHERE id = ?";
                        $stmt_update_req = $pdo->prepare($sql_update_req);
                        $stmt_update_req->execute([$requisicao_id]);
                        
                        $sql_update_notif = "UPDATE almoxarifado_requisicoes_notificacoes SET status = 'respondida' WHERE id = ?";
                        $stmt_update_notif = $pdo->prepare($sql_update_notif);
                        $stmt_update_notif->execute([$notificacao_id]);
                        
                        $sql_conversa = "INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES (?, ?, ?, 'requisitante')";
                        $stmt_conversa = $pdo->prepare($sql_conversa);
                        $stmt_conversa->execute([$notificacao_id, $usuario_logado_id, $mensagem]);
                        
                        $pdo->commit();
                        $response['success'] = true;
                        $response['message'] = 'Resposta enviada com sucesso!';
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $response['message'] = 'Erro ao enviar resposta: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Por favor, informe uma mensagem.';
                }
            } elseif ($acao == 'agendar' && isset($_POST['data_agendamento'])) {
                $data_agendamento = trim($_POST['data_agendamento']);
                $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';
                
                if (!empty($data_agendamento)) {
                    $pdo->beginTransaction();
                    try {
                        $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'agendada' WHERE id = ?";
                        $stmt_update_req = $pdo->prepare($sql_update_req);
                        $stmt_update_req->execute([$requisicao_id]);
                        
                        $sql_update_notif = "UPDATE almoxarifado_requisicoes_notificacoes SET status = 'concluida' WHERE id = ?";
                        $stmt_update_notif = $pdo->prepare($sql_update_notif);
                        $stmt_update_notif->execute([$notificacao_id]);
                        
                        $sql_agendamento = "INSERT INTO almoxarifado_agendamentos (requisicao_id, data_agendamento, observacoes) VALUES (?, ?, ?)";
                        $stmt_agendamento = $pdo->prepare($sql_agendamento);
                        $stmt_agendamento->execute([$requisicao_id, $data_agendamento, $observacoes]);
                        
                        $pdo->commit();
                        $response['success'] = true;
                        $response['message'] = 'Agendamento realizado com sucesso!';
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $response['message'] = 'Erro ao agendar: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Por favor, informe a data de agendamento.';
                }
            }
        } else {
            $response['message'] = 'Notificação inválida.';
        }
    } else {
        $response['message'] = 'Dados incompletos.';
    }
    
    echo json_encode($response);
    exit;
}

$sql = "
    SELECT 
        arn.id as notificacao_id,
        arn.requisicao_id,
        arn.mensagem,
        arn.status as notificacao_status,
        arn.data_criacao,
        ar.status_notificacao as requisicao_status,
        ar.justificativa
    FROM almoxarifado_requisicoes_notificacoes arn
    JOIN almoxarifado_requisicoes ar ON arn.requisicao_id = ar.id
    WHERE arn.usuario_destino_id = ?
    ORDER BY arn.data_criacao DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_logado_id]);
$notificacoes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sql_conversa = "SELECT c.mensagem, c.data_mensagem, u.nome as autor_nome, c.tipo_usuario FROM almoxarifado_requisicoes_conversas c JOIN almoxarifado_requisicoes_notificacoes n ON c.notificacao_id = n.id JOIN usuarios u ON c.usuario_id = u.id WHERE n.requisicao_id = ? ORDER BY c.data_mensagem ASC";
    $stmt_conversa = $pdo->prepare($sql_conversa);
    $stmt_conversa->execute([$row['requisicao_id']]);
    $conversa = $stmt_conversa->fetchAll(PDO::FETCH_ASSOC);
    $row['conversa'] = $conversa;
    $notificacoes[] = $row;
}
?>

<div class="container">
    <h2>Minhas Notificações</h2>
    
    <div id="feedback-message" class="alert" style="display:none;"></div>
    
    <?php if (empty($notificacoes)): ?>
        <div class="alert alert-info">
            Você não possui notificações no momento.
        </div>
    <?php else: ?>
        <div class="notification-inbox">
            <?php foreach ($notificacoes as $notificacao): ?>
                <div class="notification-item card mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Requisição #<?php echo $notificacao['requisicao_id']; ?></strong> - 
                                <span class="badge badge-<?php 
                                    switch($notificacao['requisicao_status']) {
                                        case 'pendente': echo 'warning'; break;
                                        case 'em_discussao': echo 'info'; break;
                                        case 'aprovada': echo 'success'; break;
                                        case 'rejeitada': echo 'danger'; break;
                                        case 'agendada': echo 'primary'; break;
                                        case 'concluida': echo 'secondary'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $notificacao['requisicao_status'])); ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><strong>Última Mensagem:</strong> <?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                        
                        <?php if (!empty($notificacao['conversa'])): ?>
                        <hr>
                        <div class="conversa-historico">
                            <h6>Histórico da Conversa:</h6>
                            <?php foreach($notificacao['conversa'] as $msg): ?>
                                <div class="mensagem-chat <?php echo ($msg['tipo_usuario'] == 'requisitante') ? 'mensagem-requisitante' : 'mensagem-admin'; ?>">
                                    <strong><?php echo htmlspecialchars($msg['autor_nome']); ?>:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($msg['mensagem'])); ?></p>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($msg['data_mensagem'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($notificacao['notificacao_status'] != 'concluida'): ?>
                            <hr>
                            <div class="notification-actions">
                                <?php if ($notificacao['requisicao_status'] == 'aprovada'): ?>
                                    <form class="agendamento-form" data-notificacao-id="<?php echo $notificacao['notificacao_id']; ?>">
                                        <div class="form-group">
                                            <label>Data de Agendamento:</label>
                                            <input type="datetime-local" class="form-control" name="data_agendamento" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Observações:</label>
                                            <textarea class="form-control" name="observacoes" rows="2"></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Agendar Entrega</button>
                                    </form>
                                <?php elseif ($notificacao['requisicao_status'] == 'em_discussao'): ?>
                                    <form class="resposta-form" data-notificacao-id="<?php echo $notificacao['notificacao_id']; ?>">
                                        <div class="form-group">
                                            <label>Mensagem para o administrador:</label>
                                            <textarea class="form-control" name="mensagem" rows="3" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-info">Enviar Resposta</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.resposta-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const notificacaoId = this.dataset.notificacaoId;
            const mensagem = this.querySelector('textarea[name="mensagem"]').value;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('notificacao_id', notificacaoId);
            formData.append('acao', 'responder');
            formData.append('mensagem', mensagem);
            fetch('notificacoes.php', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    const feedbackMessage = document.getElementById('feedback-message');
                    feedbackMessage.style.display = 'block';
                    feedbackMessage.textContent = data.message;
                    feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                    if (data.success) {
                        this.querySelector('textarea[name="mensagem"]').value = '';
                        setTimeout(() => { location.reload(); }, 1000);
                    }
                } catch (e) {
                    const messageMatch = text.match(/"message":"([^"]*)"/);
                    if (messageMatch && messageMatch[1]) {
                        const feedbackMessage = document.getElementById('feedback-message');
                        feedbackMessage.style.display = 'block';
                        feedbackMessage.textContent = messageMatch[1];
                        feedbackMessage.className = 'alert alert-success';
                        setTimeout(() => { location.reload(); }, 1000);
                    } else {
                        console.error('Não foi possível extrair a mensagem da resposta:', text);
                    }
                }
            })
            .catch(error => {
                console.error('Erro de rede:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro de rede ao processar sua solicitação.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });
    
    document.querySelectorAll('.agendamento-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const notificacaoId = this.dataset.notificacaoId;
            const dataAgendamento = this.querySelector('input[name="data_agendamento"]').value;
            const observacoes = this.querySelector('textarea[name="observacoes"]').value;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('notificacao_id', notificacaoId);
            formData.append('acao', 'agendar');
            formData.append('data_agendamento', dataAgendamento);
            formData.append('observacoes', observacoes);
            fetch('notificacoes.php', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    const feedbackMessage = document.getElementById('feedback-message');
                    feedbackMessage.style.display = 'block';
                    feedbackMessage.textContent = data.message;
                    feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                    if (data.success) {
                        setTimeout(() => { location.reload(); }, 1000);
                    }
                } catch (e) {
                    const messageMatch = text.match(/"message":"([^"]*)"/);
                    if (messageMatch && messageMatch[1]) {
                        const feedbackMessage = document.getElementById('feedback-message');
                        feedbackMessage.style.display = 'block';
                        feedbackMessage.textContent = messageMatch[1];
                        feedbackMessage.className = 'alert alert-success';
                        setTimeout(() => { location.reload(); }, 1000);
                    } else {
                        console.error('Não foi possível extrair a mensagem da resposta:', text);
                    }
                }
            })
            .catch(error => {
                console.error('Erro de rede:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro de rede ao processar sua solicitação.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });
});
</script>

<?php
require_once '../../includes/footer.php';
?>
