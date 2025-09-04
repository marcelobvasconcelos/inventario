<?php
// almoxarifado/minhas_notificacoes.php - Página para usuários visualizarem suas notificações
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$usuario_logado_id = $_SESSION['id'];

// Processar respostas via AJAX
if (isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['notificacao_id'], $_POST['acao'])) {
        $notificacao_id = filter_var($_POST['notificacao_id'], FILTER_VALIDATE_INT);
        $acao = $_POST['acao'];
        
        // Verificar se a notificação pertence ao usuário
        $sql_check = "SELECT id, requisicao_id FROM almoxarifado_requisicoes_notificacoes 
                      WHERE id = ? AND usuario_destino_id = ? AND status != 'concluida'";
        $stmt_check = mysqli_prepare($link, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "ii", $notificacao_id, $usuario_logado_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if ($row_check = mysqli_fetch_assoc($result_check)) {
            $requisicao_id = $row_check['requisicao_id'];
            
            if ($acao == 'aprovar') {
                // Atualizar status da requisição para aprovada
                $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'aprovada' WHERE id = ?";
                $stmt_update_req = mysqli_prepare($link, $sql_update_req);
                mysqli_stmt_bind_param($stmt_update_req, "i", $requisicao_id);
                mysqli_stmt_execute($stmt_update_req);
                mysqli_stmt_close($stmt_update_req);
                
                // Atualizar status da notificação para concluída
                $sql_update_notif = "UPDATE almoxarifado_requisicoes_notificacoes SET status = 'concluida' WHERE id = ?";
                $stmt_update_notif = mysqli_prepare($link, $sql_update_notif);
                mysqli_stmt_bind_param($stmt_update_notif, "i", $notificacao_id);
                mysqli_stmt_execute($stmt_update_notif);
                mysqli_stmt_close($stmt_update_notif);
                
                $response['success'] = true;
                $response['message'] = 'Requisição aprovada com sucesso!';
            } elseif ($acao == 'responder' && isset($_POST['mensagem'])) {
                $mensagem = trim($_POST['mensagem']);
                if (!empty($mensagem)) {
                    // Iniciar transação
                    mysqli_autocommit($link, FALSE);
                    
                    try {
                        // Atualizar status da requisição para em_discussao
                        $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'em_discussao' WHERE id = ?";
                        $stmt_update_req = mysqli_prepare($link, $sql_update_req);
                        mysqli_stmt_bind_param($stmt_update_req, "i", $requisicao_id);
                        mysqli_stmt_execute($stmt_update_req);
                        mysqli_stmt_close($stmt_update_req);
                        
                        // Atualizar status da notificação para respondida
                        $sql_update_notif = "UPDATE almoxarifado_requisicoes_notificacoes SET status = 'respondida' WHERE id = ?";
                        $stmt_update_notif = mysqli_prepare($link, $sql_update_notif);
                        mysqli_stmt_bind_param($stmt_update_notif, "i", $notificacao_id);
                        mysqli_stmt_execute($stmt_update_notif);
                        mysqli_stmt_close($stmt_update_notif);
                        
                        // Buscar o administrador que fez a requisição original
                        $sql_admin = "SELECT usuario_id FROM almoxarifado_requisicoes WHERE id = ?";
                        $stmt_admin = mysqli_prepare($link, $sql_admin);
                        mysqli_stmt_bind_param($stmt_admin, "i", $requisicao_id);
                        mysqli_stmt_execute($stmt_admin);
                        $result_admin = mysqli_stmt_get_result($stmt_admin);
                        $row_admin = mysqli_fetch_assoc($result_admin);
                        $admin_id = $row_admin['usuario_id'];
                        mysqli_stmt_close($stmt_admin);
                        
                        // Criar nova notificação para o administrador
                        $mensagem_notificacao = "Nova resposta à requisição #" . $requisicao_id . " de " . $_SESSION['nome'] . ": " . $mensagem;
                        $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes 
                            (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) 
                            VALUES (?, ?, ?, 'resposta_usuario', ?, 'pendente')";
                        $stmt_notificacao = mysqli_prepare($link, $sql_notificacao);
                        mysqli_stmt_bind_param($stmt_notificacao, "iiis", $requisicao_id, $usuario_logado_id, $admin_id, $mensagem_notificacao);
                        mysqli_stmt_execute($stmt_notificacao);
                        mysqli_stmt_close($stmt_notificacao);
                        
                        // Adicionar mensagem à conversa
                        $sql_conversa = "INSERT INTO almoxarifado_requisicoes_conversas 
                            (notificacao_id, usuario_id, mensagem, tipo_usuario) 
                            VALUES (?, ?, ?, 'requisitante')";
                        $stmt_conversa = mysqli_prepare($link, $sql_conversa);
                        mysqli_stmt_bind_param($stmt_conversa, "iis", $notificacao_id, $usuario_logado_id, $mensagem);
                        mysqli_stmt_execute($stmt_conversa);
                        mysqli_stmt_close($stmt_conversa);
                        
                        // Commit
                        mysqli_commit($link);
                        mysqli_autocommit($link, TRUE);
                        
                        $response['success'] = true;
                        $response['message'] = 'Resposta enviada com sucesso!';
                    } catch (Exception $e) {
                        mysqli_rollback($link);
                        mysqli_autocommit($link, TRUE);
                        $response['message'] = 'Erro ao enviar resposta: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Por favor, informe uma mensagem.';
                }
            } elseif ($acao == 'agendar' && isset($_POST['data_agendamento'])) {
                $data_agendamento = trim($_POST['data_agendamento']);
                $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';
                
                if (!empty($data_agendamento)) {
                    // Iniciar transação
                    mysqli_autocommit($link, FALSE);
                    
                    try {
                        // Atualizar status da requisição para agendada
                        $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'agendada' WHERE id = ?";
                        $stmt_update_req = mysqli_prepare($link, $sql_update_req);
                        mysqli_stmt_bind_param($stmt_update_req, "i", $requisicao_id);
                        mysqli_stmt_execute($stmt_update_req);
                        mysqli_stmt_close($stmt_update_req);
                        
                        // Atualizar status da notificação para concluída
                        $sql_update_notif = "UPDATE almoxarifado_requisicoes_notificacoes SET status = 'concluida' WHERE id = ?";
                        $stmt_update_notif = mysqli_prepare($link, $sql_update_notif);
                        mysqli_stmt_bind_param($stmt_update_notif, "i", $notificacao_id);
                        mysqli_stmt_execute($stmt_update_notif);
                        mysqli_stmt_close($stmt_update_notif);
                        
                        // Inserir agendamento
                        $sql_agendamento = "INSERT INTO almoxarifado_agendamentos 
                            (requisicao_id, data_agendamento, observacoes) 
                            VALUES (?, ?, ?)";
                        $stmt_agendamento = mysqli_prepare($link, $sql_agendamento);
                        mysqli_stmt_bind_param($stmt_agendamento, "iss", $requisicao_id, $data_agendamento, $observacoes);
                        mysqli_stmt_execute($stmt_agendamento);
                        mysqli_stmt_close($stmt_agendamento);
                        
                        // Commit
                        mysqli_commit($link);
                        mysqli_autocommit($link, TRUE);
                        
                        $response['success'] = true;
                        $response['message'] = 'Agendamento realizado com sucesso!';
                    } catch (Exception $e) {
                        mysqli_rollback($link);
                        mysqli_autocommit($link, TRUE);
                        $response['message'] = 'Erro ao realizar agendamento: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Por favor, informe a data de agendamento.';
                }
            }
        } else {
            $response['message'] = 'Notificação inválida ou já foi processada.';
        }
    } else {
        $response['message'] = 'Dados incompletos.';
    }
    
    echo json_encode($response);
    exit;
}

// Buscar notificações do usuário
$sql = "
    SELECT 
        nrn.id as notificacao_id,
        nrn.requisicao_id,
        nrn.tipo,
        nrn.mensagem,
        nrn.status as notificacao_status,
        nrn.data_criacao,
        ar.status_notificacao as requisicao_status,
        ar.justificativa
    FROM almoxarifado_requisicoes_notificacoes nrn
    JOIN almoxarifado_requisicoes ar ON nrn.requisicao_id = ar.id
    WHERE nrn.usuario_destino_id = ? 
    ORDER BY nrn.data_criacao DESC
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "i", $usuario_logado_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notificacoes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notificacoes[] = $row;
}
mysqli_stmt_close($stmt);
?>

<div class="almoxarifado-header">
    <h2>Minhas Notificações</h2>
</div>

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
                    <p><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                    <p><strong>Justificativa:</strong> <?php echo htmlspecialchars($notificacao['justificativa']); ?></p>
                    
                    <?php if ($notificacao['notificacao_status'] != 'concluida'): ?>
                        <div class="notification-actions">
                            <?php if ($notificacao['requisicao_status'] == 'aprovada'): ?>
                                <!-- Formulário para agendamento -->
                                <form class="agendamento-form" data-notificacao-id="<?php echo $notificacao['notificacao_id']; ?>">
                                    <div class="form-group">
                                        <label>Data de Agendamento:</label>
                                        <input type="datetime-local" class="form-control" name="data_agendamento" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Observações:</label>
                                        <textarea class="form-control" name="observacoes" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">Agendar Entrega</button>
                                </form>
                            <?php elseif ($notificacao['requisicao_status'] == 'em_discussao'): ?>
                                <!-- Formulário para resposta -->
                                <form class="resposta-form" data-notificacao-id="<?php echo $notificacao['notificacao_id']; ?>">
                                    <div class="form-group">
                                        <label>Sua Resposta:</label>
                                        <textarea class="form-control" name="mensagem" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Enviar Resposta</button>
                                </form>
                            <?php elseif ($notificacao['requisicao_status'] == 'pendente'): ?>
                                <p>Aguardando avaliação do administrador.</p>
                            <?php endif; ?>
                            
                            <?php if ($notificacao['requisicao_status'] == 'aprovada' || $notificacao['requisicao_status'] == 'em_discussao' || $notificacao['requisicao_status'] == 'pendente'): ?>
                                <form class="acao-form d-inline" data-notificacao-id="<?php echo $notificacao['notificacao_id']; ?>" data-acao="aprovar">
                                    <button type="submit" class="btn btn-success">Aprovar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Esta notificação já foi processada.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Processar formulários de ação
    document.querySelectorAll('.acao-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const notificacaoId = this.dataset.notificacaoId;
            const acao = this.dataset.acao;
            
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('notificacao_id', notificacaoId);
            formData.append('acao', acao);
            
            fetch('minhas_notificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;
                feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                
                if (data.success) {
                    // Recarregar a página após ação bem-sucedida
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });
    
    // Processar formulários de resposta
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
            
            fetch('minhas_notificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;
                feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                
                if (data.success) {
                    // Limpar formulário
                    this.querySelector('textarea[name="mensagem"]').value = '';
                    
                    // Recarregar a página após ação bem-sucedida
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });
    
    // Processar formulários de agendamento
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
            
            fetch('minhas_notificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = data.message;
                feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                
                if (data.success) {
                    // Recarregar a página após ação bem-sucedida
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                const feedbackMessage = document.getElementById('feedback-message');
                feedbackMessage.style.display = 'block';
                feedbackMessage.textContent = 'Ocorreu um erro ao processar sua solicitação.';
                feedbackMessage.className = 'alert alert-danger';
            });
        });
    });
});
</script>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>