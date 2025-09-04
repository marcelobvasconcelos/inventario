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
        
        // Verificar se a requisição existe
        $sql_check = "SELECT id, usuario_id, status_notificacao FROM almoxarifado_requisicoes WHERE id = ?";
        $stmt_check = mysqli_prepare($link, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "i", $requisicao_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if ($row_check = mysqli_fetch_assoc($result_check)) {
            $usuario_requisitante_id = $row_check['usuario_id'];
            
            if ($acao == 'aprovar') {
                // Iniciar transação
                mysqli_autocommit($link, FALSE);
                
                try {
                    // Atualizar status da requisição para aprovada
                    $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'aprovada' WHERE id = ?";
                    $stmt_update_req = mysqli_prepare($link, $sql_update_req);
                    mysqli_stmt_bind_param($stmt_update_req, "i", $requisicao_id);
                    mysqli_stmt_execute($stmt_update_req);
                    mysqli_stmt_close($stmt_update_req);
                    
                    // Criar notificação para o requisitante
                    $mensagem_notificacao = "Sua requisição #" . $requisicao_id . " foi aprovada. Agora você pode agendar a entrega.";
                    $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes 
                        (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) 
                        VALUES (?, ?, ?, 'aprovada', ?, 'pendente')";
                    $stmt_notificacao = mysqli_prepare($link, $sql_notificacao);
                    mysqli_stmt_bind_param($stmt_notificacao, "iiis", $requisicao_id, $admin_id, $usuario_requisitante_id, $mensagem_notificacao);
                    mysqli_stmt_execute($stmt_notificacao);
                    mysqli_stmt_close($stmt_notificacao);
                    
                    // Commit
                    mysqli_commit($link);
                    mysqli_autocommit($link, TRUE);
                    
                    $response['success'] = true;
                    $response['message'] = 'Requisição aprovada com sucesso!';
                } catch (Exception $e) {
                    mysqli_rollback($link);
                    mysqli_autocommit($link, TRUE);
                    $response['message'] = 'Erro ao aprovar requisição: ' . $e->getMessage();
                }
            } elseif ($acao == 'rejeitar' && isset($_POST['justificativa'])) {
                $justificativa = trim($_POST['justificativa']);
                if (!empty($justificativa)) {
                    // Iniciar transação
                    mysqli_autocommit($link, FALSE);
                    
                    try {
                        // Atualizar status da requisição para rejeitada
                        $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'rejeitada' WHERE id = ?";
                        $stmt_update_req = mysqli_prepare($link, $sql_update_req);
                        mysqli_stmt_bind_param($stmt_update_req, "i", $requisicao_id);
                        mysqli_stmt_execute($stmt_update_req);
                        mysqli_stmt_close($stmt_update_req);
                        
                        // Criar notificação para o requisitante
                        $mensagem_notificacao = "Sua requisição #" . $requisicao_id . " foi rejeitada. Justificativa: " . $justificativa;
                        $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes 
                            (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) 
                            VALUES (?, ?, ?, 'rejeitada', ?, 'pendente')";
                        $stmt_notificacao = mysqli_prepare($link, $sql_notificacao);
                        mysqli_stmt_bind_param($stmt_notificacao, "iiis", $requisicao_id, $admin_id, $usuario_requisitante_id, $mensagem_notificacao);
                        mysqli_stmt_execute($stmt_notificacao);
                        mysqli_stmt_close($stmt_notificacao);
                        
                        // Commit
                        mysqli_commit($link);
                        mysqli_autocommit($link, TRUE);
                        
                        $response['success'] = true;
                        $response['message'] = 'Requisição rejeitada com sucesso!';
                    } catch (Exception $e) {
                        mysqli_rollback($link);
                        mysqli_autocommit($link, TRUE);
                        $response['message'] = 'Erro ao rejeitar requisição: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Por favor, informe a justificativa para a rejeição.';
                }
            } elseif ($acao == 'solicitar_informacoes' && isset($_POST['mensagem'])) {
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
                        
                        // Criar notificação para o requisitante
                        $mensagem_notificacao = "O administrador solicitou mais informações sobre a requisição #" . $requisicao_id . ": " . $mensagem;
                        $sql_notificacao = "INSERT INTO almoxarifado_requisicoes_notificacoes 
                            (requisicao_id, usuario_origem_id, usuario_destino_id, tipo, mensagem, status) 
                            VALUES (?, ?, ?, 'resposta_admin', ?, 'pendente')";
                        $stmt_notificacao = mysqli_prepare($link, $sql_notificacao);
                        mysqli_stmt_bind_param($stmt_notificacao, "iiis", $requisicao_id, $admin_id, $usuario_requisitante_id, $mensagem_notificacao);
                        mysqli_stmt_execute($stmt_notificacao);
                        mysqli_stmt_close($stmt_notificacao);
                        
                        // Adicionar mensagem à conversa
                        // Primeiro, precisamos encontrar a notificação original
                        $sql_notif_orig = "SELECT id FROM almoxarifado_requisicoes_notificacoes 
                                          WHERE requisicao_id = ? AND tipo = 'nova_requisicao' LIMIT 1";
                        $stmt_notif_orig = mysqli_prepare($link, $sql_notif_orig);
                        mysqli_stmt_bind_param($stmt_notif_orig, "i", $requisicao_id);
                        mysqli_stmt_execute($stmt_notif_orig);
                        $result_notif_orig = mysqli_stmt_get_result($stmt_notif_orig);
                        $row_notif_orig = mysqli_fetch_assoc($result_notif_orig);
                        $notificacao_id = $row_notif_orig['id'];
                        mysqli_stmt_close($stmt_notif_orig);
                        
                        // Agora adicionamos à conversa
                        $sql_conversa = "INSERT INTO almoxarifado_requisicoes_conversas 
                            (notificacao_id, usuario_id, mensagem, tipo_usuario) 
                            VALUES (?, ?, ?, 'administrador')";
                        $stmt_conversa = mysqli_prepare($link, $sql_conversa);
                        mysqli_stmt_bind_param($stmt_conversa, "iis", $notificacao_id, $admin_id, $mensagem);
                        mysqli_stmt_execute($stmt_conversa);
                        mysqli_stmt_close($stmt_conversa);
                        
                        // Commit
                        mysqli_commit($link);
                        mysqli_autocommit($link, TRUE);
                        
                        $response['success'] = true;
                        $response['message'] = 'Solicitação de informações enviada com sucesso!';
                    } catch (Exception $e) {
                        mysqli_rollback($link);
                        mysqli_autocommit($link, TRUE);
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

// Buscar todas as requisições pendentes
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

$result = mysqli_query($link, $sql);
$requisicoes = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Buscar itens da requisição
    $sql_itens = "SELECT ari.quantidade_solicitada, ap.nome as produto_nome, ap.unidade_medida
                  FROM almoxarifado_requisicoes_itens ari
                  JOIN almoxarifado_produtos ap ON ari.produto_id = ap.id
                  WHERE ari.requisicao_id = ?";
    $stmt_itens = mysqli_prepare($link, $sql_itens);
    mysqli_stmt_bind_param($stmt_itens, "i", $row['requisicao_id']);
    mysqli_stmt_execute($stmt_itens);
    $result_itens = mysqli_stmt_get_result($stmt_itens);
    $itens = [];
    while ($item = mysqli_fetch_assoc($result_itens)) {
        $itens[] = $item;
    }
    mysqli_stmt_close($stmt_itens);
    
    $row['itens'] = $itens;
    $requisicoes[] = $row;
}
?>

<div class="almoxarifado-header">
    <h2>Gerenciar Requisições de Almoxarifado</h2>
</div>

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
                            <p><strong>Local de Destino:</strong> <?php echo htmlspecialchars($requisicao['local_nome']); ?></p>
                            <p><strong>Justificativa:</strong> <?php echo htmlspecialchars($requisicao['justificativa']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Itens Solicitados:</strong></p>
                            <ul>
                                <?php foreach ($requisicao['itens'] as $item): ?>
                                    <li><?php echo htmlspecialchars($item['produto_nome']); ?> - 
                                        <?php echo $item['quantidade_solicitada']; ?> 
                                        <?php echo htmlspecialchars($item['unidade_medida']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="requisicao-actions mt-3">
                        <?php if ($requisicao['status_notificacao'] == 'pendente'): ?>
                            <form class="acao-form d-inline" data-requisicao-id="<?php echo $requisicao['requisicao_id']; ?>" data-acao="aprovar">
                                <button type="submit" class="btn btn-success">Aprovar</button>
                            </form>
                            
                            <button type="button" class="btn btn-info" onclick="showSolicitarInfoForm(<?php echo $requisicao['requisicao_id']; ?>)">
                                Solicitar Mais Informações
                            </button>
                            
                            <button type="button" class="btn btn-danger" onclick="showRejeitarForm(<?php echo $requisicao['requisicao_id']; ?>)">
                                Rejeitar
                            </button>
                            
                            <!-- Formulário para solicitar informações -->
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
                            
                            <!-- Formulário para rejeitar -->
                            <div id="rejeitar-form-<?php echo $requisicao['requisicao_id']; ?>" class="mt-3" style="display:none;">
                                <form class="rejeitar-form" data-requisicao-id="<?php echo $requisicao['requisicao_id']; ?>">
                                    <div class="form-group">
                                        <label>Justificativa para rejeição:</label>
                                        <textarea class="form-control" name="justificativa" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger">Rejeitar Requisição</button>
                                    <button type="button" class="btn btn-secondary" onclick="hideRejeitarForm(<?php echo $requisicao['requisicao_id']; ?>)">Cancelar</button>
                                </form>
                            </div>
                        <?php elseif ($requisicao['status_notificacao'] == 'em_discussao'): ?>
                            <p>Aguardando resposta do requisitante.</p>
                            <button type="button" class="btn btn-info" onclick="showSolicitarInfoForm(<?php echo $requisicao['requisicao_id']; ?>)">
                                Solicitar Mais Informações
                            </button>
                            
                            <!-- Formulário para solicitar informações -->
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
// Funções para mostrar/ocultar formulários
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
    // Processar formulários de ação
    document.querySelectorAll('.acao-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const requisicaoId = this.dataset.requisicaoId;
            const acao = this.dataset.acao;
            
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('requisicao_id', requisicaoId);
            formData.append('acao', acao);
            
            fetch('admin_notificacoes.php', {
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
    
    // Processar formulários de solicitação de informações
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
            
            fetch('admin_notificacoes.php', {
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
                    // Ocultar formulário
                    document.getElementById('solicitar-info-form-' + requisicaoId).style.display = 'none';
                    
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
    
    // Processar formulários de rejeição
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
            
            fetch('admin_notificacoes.php', {
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
                    this.querySelector('textarea[name="justificativa"]').value = '';
                    // Ocultar formulário
                    document.getElementById('rejeitar-form-' + requisicaoId).style.display = 'none';
                    
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