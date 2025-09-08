<?php
// almoxarifado/admin_notificacoes.php - Interface do administrador para gerenciar notificações
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões - apenas administradores
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["permissao"] != 'Administrador') {
    header("location: ../login.php");
    exit;
}

$admin_id = $_SESSION['id'];

// Processar ações via AJAX
if (isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['requisicao_id'], $_POST['acao'])) {
        $requisicao_id = filter_var($_POST['requisicao_id'], FILTER_VALIDATE_INT);
        $acao = $_POST['acao'];
        
        $sql_check = "SELECT id, usuario_id, status_notificacao FROM almoxarifado_requisicoes WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$requisicao_id]);
        
        if ($row_check = $stmt_check->fetch(PDO::FETCH_ASSOC)) {
            $usuario_requisitante_id = $row_check['usuario_id'];
            
            if ($acao == 'aprovar') {
                $pdo->beginTransaction();
                try {
                    $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'aprovada' WHERE id = ?";
                    $stmt_update_req = $pdo->prepare($sql_update_req);
                    $stmt_update_req->execute([$requisicao_id]);
                    
                    $mensagem_notificacao = "Sua requisição #" . $requisicao_id . " foi aprovada. Agora você pode agendar a entrega.";
                    $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) VALUES (?, ?, ?, 'aprovada', ?, 'pendente')";
                    $stmt_notificacao = $pdo->prepare($sql_notificacao);
                    $stmt_notificacao->execute([$requisicao_id, $admin_id, $usuario_requisitante_id, $mensagem_notificacao]);
                    
                    $pdo->commit();
                    $response['success'] = true;
                    $response['message'] = 'Requisição aprovada com sucesso!';
                } catch (Exception $e) {
                    $pdo->rollback();
                    $response['message'] = 'Erro ao aprovar requisição: ' . $e->getMessage();
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
                        $response['success'] = true;
                        $response['message'] = 'Requisição rejeitada com sucesso!';
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $response['message'] = 'Erro ao rejeitar requisição: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Por favor, informe a justificativa da rejeição.';
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

                        $sql_conversa = "INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES (?, ?, ?, 'admin')";
                        $stmt_conversa = $pdo->prepare($sql_conversa);
                        $stmt_conversa->execute([$notificacao_id, $admin_id, $mensagem]);
                        
                        $pdo->commit();
                        $response['success'] = true;
                        $response['message'] = 'Solicitação de informações enviada com sucesso!';
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $response['message'] = 'Erro ao solicitar informações: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Por favor, informe a mensagem solicitando informações.';
                }
            }
        } else {
            $response['message'] = 'Requisição inválida.';
        }
    } else {
        $response['message'] = 'Dados incompletos.';
    }
    
    echo json_encode($response);
    exit;
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
        l.nome as local_nome
    FROM almoxarifado_requisicoes ar
    JOIN usuarios u ON ar.usuario_id = u.id
    LEFT JOIN locais l ON ar.local_id = l.id
    WHERE ar.status_notificacao IN ('pendente', 'em_discussao')
    ORDER BY ar.data_requisicao DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$requisicoes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sql_itens = "SELECT ari.quantidade_solicitada, m.nome as material_nome
                  FROM almoxarifado_requisicoes_itens ari
                  JOIN materiais m ON ari.produto_id = m.id
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

    $requisicoes[] = $row;
}
?>

<div class="container">
    <h2>Gerenciar Requisições de Materiais</h2>
    
    <div id="feedback-message" class="alert" style="display:none;"></div>
    
    <?php if (empty($requisicoes)): ?>
        <div class="alert alert-info">
            Não há requisições pendentes no momento.
        </div>
    <?php else: ?>
        <div class="requisicoes-list">
            <?php foreach ($requisicoes as $requisicao): ?>
                <div class="requisicao-item card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Requisição #<?php echo $requisicao['requisicao_id']; ?></strong> - 
                                <span class="badge badge-<?php 
                                    switch($requisicao['status_notificacao']) {
                                        case 'pendente': echo 'warning'; break;
                                        case 'em_discussao': echo 'info'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $requisicao['status_notificacao'])); ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($requisicao['data_requisicao'])); ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Requisitante:</strong> <?php echo htmlspecialchars($requisicao['usuario_nome']); ?></p>
                                <p><strong>Local:</strong> <?php echo htmlspecialchars($requisicao['local_nome']); ?></p>
                                <p><strong>Justificativa:</strong> <?php echo htmlspecialchars($requisicao['justificativa']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Itens solicitados:</strong></p>
                                <ul>
                                    <?php foreach($requisicao['itens'] as $item): ?>
                                        <li><?php echo $item['quantidade_solicitada']; ?> x <?php echo htmlspecialchars($item['material_nome']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <?php if (!empty($requisicao['conversa'])): ?>
                        <hr>
                        <div class="conversa-historico">
                            <h6>Histórico da Conversa:</h6>
                            <?php foreach($requisicao['conversa'] as $msg): ?>
                                <div class="mensagem-chat <?php echo ($msg['tipo_usuario'] == 'admin') ? 'mensagem-admin' : 'mensagem-requisitante'; ?>">
                                    <strong><?php echo htmlspecialchars($msg['autor_nome']); ?>:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($msg['mensagem'])); ?></p>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($msg['data_mensagem'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($requisicao['status_notificacao'] == 'pendente'): ?>
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
                        <?php elseif ($requisicao['status_notificacao'] == 'em_discussao'): ?>
                            <hr>
                            <p><strong>Ações disponíveis:</strong></p>
                            <div class="acao-buttons">
                                <form class="acao-form d-inline" data-requisicao-id="<?php echo $requisicao['requisicao_id']; ?>" data-acao="aprovar">
                                    <button type="submit" class="btn btn-success">Aprovar Requisição</button>
                                </form>
                                
                                <button type="button" class="btn btn-info" onclick="showSolicitarInfoForm(<?php echo $requisicao['requisicao_id']; ?>)">
                                    Enviar Nova Mensagem
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

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.acao-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const requisicaoId = this.dataset.requisicaoId;
            const acao = this.dataset.acao;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('requisicao_id', requisicaoId);
            formData.append('acao', acao);
            fetch('admin_notificacoes.php', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    const feedbackMessage = document.getElementById('feedback-message');
                    feedbackMessage.style.display = 'block';
                    feedbackMessage.textContent = data.message;
                    feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                    if (data.success) { setTimeout(() => { location.reload(); }, 1000); }
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
                        document.getElementById('solicitar-info-form-' + requisicaoId).style.display = 'none';
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
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    const feedbackMessage = document.getElementById('feedback-message');
                    feedbackMessage.style.display = 'block';
                    feedbackMessage.textContent = data.message;
                    feedbackMessage.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                    if (data.success) {
                        this.querySelector('textarea[name="justificativa"]').value = '';
                        document.getElementById('rejeitar-form-' + requisicaoId).style.display = 'none';
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
require_once '../includes/footer.php';
?>
