<?php
ob_start();
require_once '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION["id"])) {
    header("location: ../login.php");
    exit;
}

$usuario_logado_id = $_SESSION['id'];
$usuario_logado_nome = $_SESSION['nome'];

// Processar ações via AJAX
if (isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    // Limpa qualquer saída anterior e garante cabeçalhos JSON
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
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
            } elseif ($acao == 'agendar' && isset($_POST['data_inicio'], $_POST['data_fim'])) {
                $data_inicio_raw = trim($_POST['data_inicio']);
                $data_fim_raw = trim($_POST['data_fim']);
                $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

                // Normaliza datas do input datetime-local (substitui 'T' e garante segundos)
                $normalize_dt = function($s) {
                    if ($s === '' || $s === null) return null;
                    $s = str_replace('T', ' ', $s);
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $s)) {
                        $s .= ':00';
                    }
                    $ts = strtotime($s);
                    return $ts ? date('Y-m-d H:i:s', $ts) : null;
                };

                $data_inicio = $normalize_dt($data_inicio_raw);
                $data_fim = $normalize_dt($data_fim_raw);

                if ($data_inicio && $data_fim && strtotime($data_inicio) <= strtotime($data_fim)) {
                    $pdo->beginTransaction();
                    try {
                        $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'agendada' WHERE id = ?";
                        $stmt_update_req = $pdo->prepare($sql_update_req);
                        $stmt_update_req->execute([$requisicao_id]);

                        $sql_update_notif = "UPDATE almoxarifado_requisicoes_notificacoes SET status = 'concluida' WHERE id = ?";
                        $stmt_update_notif = $pdo->prepare($sql_update_notif);
                        $stmt_update_notif->execute([$notificacao_id]);

                        // Armazena a data de início, e registra o período nas observações
                        $periodo_texto = 'Período solicitado: ' . date('d/m/Y H:i', strtotime($data_inicio)) . ' a ' . date('d/m/Y H:i', strtotime($data_fim));
                        $observacoes_final = trim($periodo_texto . (strlen($observacoes) ? '. Observações: ' . $observacoes : ''));
                        $sql_agendamento = "INSERT INTO almoxarifado_agendamentos (requisicao_id, data_agendamento, observacoes) VALUES (?, ?, ?)";
                        $stmt_agendamento = $pdo->prepare($sql_agendamento);
                        $stmt_agendamento->execute([$requisicao_id, $data_inicio, $observacoes_final]);

                        // Registrar na conversa que o usuário solicitou período de entrega
                        $mensagem_conversa = "{$usuario_logado_nome} solicitou entrega entre " . date('d/m/Y H:i', strtotime($data_inicio)) . " e " . date('d/m/Y H:i', strtotime($data_fim));
                        if (!empty($observacoes)) {
                            $mensagem_conversa .= ". Observações: " . $observacoes;
                        }

                        $sql_conversa = "INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES (?, ?, ?, 'requisitante')";
                        $stmt_conversa = $pdo->prepare($sql_conversa);
                        $stmt_conversa->execute([$notificacao_id, $usuario_logado_id, $mensagem_conversa]);

                        $pdo->commit();
                        $response['success'] = true;
                        $response['message'] = 'Agendamento solicitado com sucesso!';
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $response['message'] = 'Erro ao agendar: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Informe um período válido (data inicial e final).';
                }
            } elseif ($acao == 'confirmar_recebimento') {
                $pdo->beginTransaction();
                try {
                    $sql_update_req = "UPDATE almoxarifado_requisicoes SET status_notificacao = 'concluida' WHERE id = ?";
                    $stmt_update_req = $pdo->prepare($sql_update_req);
                    $stmt_update_req->execute([$requisicao_id]);
                    
                    $sql_update_notif = "UPDATE almoxarifado_requisicoes_notificacoes SET status = 'concluida' WHERE id = ?";
                    $stmt_update_notif = $pdo->prepare($sql_update_notif);
                    $stmt_update_notif->execute([$notificacao_id]);
                    
                    // Registrar na conversa que o usuário confirmou o recebimento
                    $mensagem_conversa = "{$usuario_logado_nome} confirmou o recebimento dos produtos.";
                    $sql_conversa = "INSERT INTO almoxarifado_requisicoes_conversas (notificacao_id, usuario_id, mensagem, tipo_usuario) VALUES (?, ?, ?, 'requisitante')";
                    $stmt_conversa = $pdo->prepare($sql_conversa);
                    $stmt_conversa->execute([$notificacao_id, $usuario_logado_id, $mensagem_conversa]);
                    
                    $pdo->commit();
                    $response['success'] = true;
                    $response['message'] = 'Recebimento confirmado com sucesso!';
                } catch (Exception $e) {
                    $pdo->rollback();
                    $response['message'] = 'Erro ao confirmar recebimento: ' . $e->getMessage();
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
        ar.justificativa,
        ar.usuario_id as requisicao_usuario_id
    FROM almoxarifado_requisicoes_notificacoes arn
    JOIN almoxarifado_requisicoes ar ON arn.requisicao_id = ar.id
    WHERE arn.usuario_destino_id = ?
    AND ar.usuario_id = ?  -- Garante que o usuário só veja notificações de requisições que ele mesmo criou
    AND arn.id = (
        SELECT MAX(arn2.id)
        FROM almoxarifado_requisicoes_notificacoes arn2
        WHERE arn2.requisicao_id = arn.requisicao_id
        AND arn2.usuario_destino_id = ?
    )
    ORDER BY arn.data_criacao DESC
    LIMIT 50
";

// Detectar nome da coluna automaticamente (uma vez só)
$sql_check_column = "SHOW COLUMNS FROM almoxarifado_requisicoes_itens";
$stmt_check = $pdo->prepare($sql_check_column);
$stmt_check->execute();
$columns = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

$column_name = 'produto_id'; // padrão
foreach ($columns as $col) {
    if ($col['Field'] == 'material_id') {
        $column_name = 'material_id';
        break;
    } elseif ($col['Field'] == 'produto_id') {
        $column_name = 'produto_id';
        break;
    }
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_logado_id, $usuario_logado_id, $usuario_logado_id]);
$notificacoes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Obter os itens da requisição
    $sql_itens = "SELECT ari.quantidade_solicitada, m.nome as material_nome
                  FROM almoxarifado_requisicoes_itens ari
                  JOIN almoxarifado_materiais m ON ari.$column_name = m.id
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
    $notificacoes[] = $row;
}
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Minhas Notificações</h2>
        <?php
        $is_privileged_user = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);
        require_once 'menu_almoxarifado.php';
        ?>
    </div>

    <div id="feedback-message" class="alert" style="display:none;"></div>
    
    <?php if (empty($notificacoes)): ?>
        <div class="alert alert-info">
            Você não possui notificações no momento.
        </div>
    <?php else: ?>
        <div class="inbox-container">
            <div class="inbox-header">
                <div class="row font-weight-bold border-bottom py-2">
                    <div class="col-1">Requisição</div>
                    <div class="col-2">Data</div>
                    <div class="col-2">Status</div>
                    <div class="col-5">Última Mensagem</div>
                    <div class="col-2">Ações</div>
                </div>
            </div>
            
            <div class="inbox-list">
                <?php foreach ($notificacoes as $notificacao): ?>
                    <div class="inbox-item row border-bottom py-3 notificacao-item" data-requisicao-id="<?php echo $notificacao['requisicao_id']; ?>" data-notificacao-id="<?php echo $notificacao['notificacao_id']; ?>">
                        <div class="col-1">
                            <strong>#<?php echo $notificacao['requisicao_id']; ?></strong>
                        </div>
                        <div class="col-2">
                            <?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?>
                        </div>
                        <div class="col-2">
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
                        <div class="col-5">
                            <div class="ultima-mensagem">
                                <?php echo htmlspecialchars(substr($notificacao['mensagem'], 0, 100)); ?>
                                <?php if (strlen($notificacao['mensagem']) > 100): ?>
                                    ...
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-2">
                            <?php if ($notificacao['notificacao_status'] != 'concluida'): ?>
                                <?php if ($notificacao['requisicao_status'] == 'aprovada'): ?>
                                    <!-- Botão removido - agendamento aparece ao expandir a notificação -->
                                <?php elseif ($notificacao['requisicao_status'] == 'em_discussao'): ?>
                                    <!-- Botão de Responder movido para baixo do histórico dentro dos detalhes -->
                                <?php elseif ($notificacao['requisicao_status'] == 'agendada'): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="confirmarRecebimento(<?php echo $notificacao['notificacao_id']; ?>)">Receber</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Concluída</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Detalhes da notificação (inicialmente ocultos) -->
                    <div class="notificacao-details-expanded" id="details-<?php echo $notificacao['requisicao_id']; ?>" style="display: none;">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Detalhes da Requisição #<?php echo $notificacao['requisicao_id']; ?></h5>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleNotificacaoDetails(<?php echo $notificacao['requisicao_id']; ?>)">
                                        Fechar
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Data da Notificação:</strong> <?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></p>
                                        <p><strong>Status:</strong> 
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
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if (!empty($notificacao['itens'])): ?>
                                            <p><strong>Itens solicitados:</strong></p>
                                            <ul>
                                                <?php foreach($notificacao['itens'] as $item): ?>
                                                    <li><?php echo $item['quantidade_solicitada']; ?> x <?php echo htmlspecialchars($item['material_nome']); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <p><strong>Última Mensagem:</strong></p>
                                <div class="alert alert-info">
                                    <?php echo nl2br(htmlspecialchars($notificacao['mensagem'])); ?>
                                </div>

                                <?php if (!empty($notificacao['conversa'])): ?>
                                <hr>
                                <div class="conversa-historico">
                                    <h6>Histórico da Conversa:</h6>
                                    <?php foreach($notificacao['conversa'] as $msg): ?>
                                        <div class="chat-message-wrapper <?php echo ($msg['tipo_usuario'] == 'requisitante') ? 'align-right' : 'align-left'; ?>">
                                            <div class="mensagem-chat <?php echo ($msg['tipo_usuario'] == 'requisitante') ? 'mensagem-requisitante' : 'mensagem-admin'; ?>">
                                                <strong><?php echo htmlspecialchars($msg['autor_nome']); ?>:</strong>
                                                <p><?php echo nl2br(htmlspecialchars($msg['mensagem'])); ?></p>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($msg['data_mensagem'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($notificacao['notificacao_status'] != 'concluida'): ?>
                                    <?php if ($notificacao['requisicao_status'] == 'em_discussao'): ?>
                                        <hr>
                                        <div class="notification-actions">
                                            <button type="button" class="btn btn-info mb-2" onclick="showResponder(<?php echo $notificacao['notificacao_id']; ?>)">Responder</button>
                                            <div id="resposta-form-<?php echo $notificacao['notificacao_id']; ?>" style="display:none;">
                                                <form class="resposta-form" data-notificacao-id="<?php echo $notificacao['notificacao_id']; ?>">
                                                    <div class="form-group">
                                                        <label>Mensagem para o administrador:</label>
                                                        <textarea class="form-control" name="mensagem" rows="3" required></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-info">Enviar Resposta</button>
                                                    <button type="button" class="btn btn-secondary" onclick="hideResponder(<?php echo $notificacao['notificacao_id']; ?>)">Cancelar</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php elseif ($notificacao['requisicao_status'] == 'agendada'): ?>
                                        <hr>
                                        <div class="notification-actions">
                                            <button type="button" class="btn btn-success" onclick="confirmarRecebimento(<?php echo $notificacao['notificacao_id']; ?>)">Confirmar Recebimento</button>
                                            <p class="text-muted mt-2">Clique em "Confirmar Recebimento" quando você receber os produtos.</p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="notification-actions">
                                        <button type="button" class="btn btn-secondary" disabled>Processo Concluído</button>
                                        <p class="text-muted mt-2">Este processo foi concluído e os produtos foram considerados recebidos.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($notificacao['notificacao_status'] != 'concluida' && $notificacao['requisicao_status'] == 'aprovada'): ?>
                                    <hr>
                                    <div class="notification-actions">
                                        <div id="agendamento-form-<?php echo $notificacao['notificacao_id']; ?>">
                                            <form class="agendamento-form" data-notificacao-id="<?php echo $notificacao['notificacao_id']; ?>">
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label>Período de Entrega - Início:</label>
                                                        <input type="datetime-local" class="form-control" name="data_inicio" id="data_inicio_<?php echo $notificacao['notificacao_id']; ?>" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label>Período de Entrega - Fim:</label>
                                                        <input type="datetime-local" class="form-control" name="data_fim" id="data_fim_<?php echo $notificacao['notificacao_id']; ?>" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Observações:</label>
                                                    <textarea class="form-control" name="observacoes" rows="2"></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Agendar Entrega</button>
                                                <button type="button" class="btn btn-success" onclick="confirmarRecebimento(<?php echo $notificacao['notificacao_id']; ?>)">Confirmar Recebimento</button>
                                                <button type="button" class="btn btn-secondary" onclick="hideAgendamento(<?php echo $notificacao['notificacao_id']; ?>)">Cancelar</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.inbox-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.inbox-header {
    background: #f8f9fa;
}

.inbox-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.inbox-item:hover {
    background-color: #f8f9fa;
}

.ultima-mensagem {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notificacao-details-expanded {
    background: #f8f9fa;
}

.chat-message-wrapper {
    margin-bottom: 15px;
}

.mensagem-chat {
    padding: 10px;
    border-radius: 8px;
    max-width: 80%;
}

.mensagem-admin {
    background: #e3f2fd;
    margin-left: auto;
}

.mensagem-requisitante {
    background: #f5f5f5;
    margin-right: auto;
}

.align-right {
    text-align: right;
}

.align-left {
    text-align: left;
}
</style>

<script>
// Funções para mostrar/ocultar formulários
function showAgendamento(notificacaoId) {
    document.getElementById('agendamento-form-' + notificacaoId).style.display = 'block';
    // Scroll para o formulário de agendamento
    document.getElementById('agendamento-form-' + notificacaoId).scrollIntoView({ behavior: 'smooth' });
}

function hideAgendamento(notificacaoId) {
    document.getElementById('agendamento-form-' + notificacaoId).style.display = 'none';
    if (event) event.stopPropagation();
}

function showResponder(notificacaoId) {
    document.getElementById('resposta-form-' + notificacaoId).style.display = 'block';
    if (event) event.stopPropagation();
}

function hideResponder(notificacaoId) {
    document.getElementById('resposta-form-' + notificacaoId).style.display = 'none';
    if (event) event.stopPropagation();
}

// Função para alternar detalhes
function toggleNotificacaoDetails(requisicaoId, notificacaoId) {
    const details = document.getElementById('details-' + requisicaoId);
    if (details.style.display === 'none') {
        // Ocultar todos os detalhes primeiro
        document.querySelectorAll('.notificacao-details-expanded').forEach(el => {
            el.style.display = 'none';
        });
        // Mostrar os detalhes desta notificação
        details.style.display = 'block';
        
        // Rolando suavemente para os detalhes
        details.scrollIntoView({ behavior: 'smooth' });
        
        // Adicionar validação de datas
        if (notificacaoId) {
            const dataInicio = document.getElementById('data_inicio_' + notificacaoId);
            const dataFim = document.getElementById('data_fim_' + notificacaoId);
            
            // Definir data mínima para hoje
            const now = new Date();
            const minDate = now.toISOString().slice(0, 16);
            dataInicio.min = minDate;
            dataFim.min = minDate;
            
            // Adicionar listener para validar datas
            dataInicio.addEventListener('change', function() {
                if (this.value) {
                    dataFim.min = this.value;
                    if (dataFim.value && new Date(dataFim.value) < new Date(this.value)) {
                        dataFim.value = this.value;
                    }
                }
            });
        }
    } else {
        details.style.display = 'none';
    }
}

// Função para confirmar recebimento
function confirmarRecebimento(notificacaoId) {
    if (confirm('Tem certeza que deseja confirmar o recebimento dos produtos?')) {
        const formData = new FormData();
        formData.append('is_ajax', 'true');
        formData.append('notificacao_id', notificacaoId);
        formData.append('acao', 'confirmar_recebimento');
        fetch('notificacoes.php', { method: 'POST', body: formData })
        .then(response => {
            // Verifica se a resposta é JSON
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
    }
}

// Adicionar evento de clique para os itens da lista
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.inbox-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Evitar que o clique nos botões dentro do item acione o toggle
            if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'INPUT') {
                const requisicaoId = this.dataset.requisicaoId;
                // Para notificações aprovadas, passar também o notificacaoId
                const notificacaoId = this.dataset.notificacaoId || null;
                toggleNotificacaoDetails(requisicaoId, notificacaoId);
            }
        });
    });
    
    // Inicializar validação de datas para todos os formulários de agendamento visíveis
    document.querySelectorAll('.agendamento-form').forEach(form => {
        const notificacaoId = form.dataset.notificacaoId;
        if (notificacaoId) {
            const dataInicio = document.getElementById('data_inicio_' + notificacaoId);
            const dataFim = document.getElementById('data_fim_' + notificacaoId);
            
            if (dataInicio && dataFim) {
                // Definir data mínima para hoje
                const now = new Date();
                const minDate = now.toISOString().slice(0, 16);
                dataInicio.min = minDate;
                dataFim.min = minDate;
                
                // Adicionar listener para validar datas
                dataInicio.addEventListener('change', function() {
                    if (this.value) {
                        dataFim.min = this.value;
                        if (dataFim.value && new Date(dataFim.value) < new Date(this.value)) {
                            dataFim.value = this.value;
                        }
                    }
                });
                
                // Adicionar listener para data fim também
                dataFim.addEventListener('change', function() {
                    if (this.value && dataInicio.value && new Date(this.value) < new Date(dataInicio.value)) {
                        alert('A data final não pode ser anterior à data inicial.');
                        this.value = dataInicio.value;
                    }
                });
            }
        }
        
        // Adicionar listener para os formulários AJAX
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const notificacaoId = this.dataset.notificacaoId;
            const dataInicio = this.querySelector('input[name="data_inicio"]').value;
            const dataFim = this.querySelector('input[name="data_fim"]').value;
            const observacoes = this.querySelector('textarea[name="observacoes"]').value;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('notificacao_id', notificacaoId);
            formData.append('acao', 'agendar');
            formData.append('data_inicio', dataInicio);
            formData.append('data_fim', dataFim);
            formData.append('observacoes', observacoes);
            fetch('notificacoes.php', { method: 'POST', body: formData })
            .then(response => {
                // Verifica se a resposta é JSON
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
    
    // Atualizar os event listeners para os formulários de resposta
    document.querySelectorAll('.resposta-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const notificacaoId = this.dataset.notificacaoId;
            const mensagem = this.querySelector('textarea[name="mensagem"]').value;
            const formData = new FormData();
            formData.append('is_ajax', 'true');
            formData.append('notificacao_id', notificacaoId);
            formData.append('acao', 'responder');
            formData.append('mensagem', mensagem);
            fetch('notificacoes.php', { method: 'POST', body: formData })
            .then(response => {
                // Verifica se a resposta é JSON
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

