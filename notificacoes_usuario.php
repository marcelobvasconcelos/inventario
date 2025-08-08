<?php
// Inicia a sessão PHP e inclui o cabeçalho e a conexão com o banco de dados
require_once 'includes/header.php';
require_once 'config/db.php';

// Redireciona para a página de login se o usuário não estiver logado
if(!isset($_SESSION["id"])){
    header("location: login.php");
    exit;
}

$usuario_logado_id = $_SESSION['id']; // ID do usuário atualmente logado
$mensagem = ''; // Variável para mensagens de sucesso
$erro = ''; // Variável para mensagens de erro

// Função para atualizar o status geral da notificação com base nos itens vinculados
function atualizarStatusNotificacao($pdo, $notificacao_id, $itens_ids_array) {
    $todos_confirmados = true;
    $algum_nao_confirmado = false;

    if (empty($itens_ids_array)) {
        // Se não há itens, a notificação pode ser considerada confirmada ou tratada de outra forma
        // Por simplicidade, vamos considerar como Confirmado se não houver itens para confirmar
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
                $todos_confirmados = false; // Se um não foi confirmado, nem todos foram confirmados
                break; // Não precisa verificar mais se já achou um não confirmado
            }
        }

        if ($algum_nao_confirmado) {
            $novo_status_notificacao = 'Nao Confirmado';
        } elseif ($todos_confirmados) {
            $novo_status_notificacao = 'Confirmado';
        } else {
            $novo_status_notificacao = 'Pendente';
        }
    }

    // Atualiza o status da notificação na tabela notificacoes
    $sql_update_notif_status = "UPDATE notificacoes SET status = ? WHERE id = ?";
    $stmt_update_notif_status = $pdo->prepare($sql_update_notif_status);
    $stmt_update_notif_status->execute([$novo_status_notificacao, $notificacao_id]);
}

// Processa as ações do formulário (Confirmar/Não Confirmar Item) quando submetido via POST
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['notificacao_id']) && isset($_POST['item_id']) && isset($_POST['action'])){
        $notificacao_id = $_POST['notificacao_id'];
        $item_id = $_POST['item_id'];
        $action = $_POST['action'];
        $justificativa = isset($_POST['justificativa']) ? trim($_POST['justificativa']) : '';

        // Inicia uma transação para garantir a atomicidade das operações
        $pdo->beginTransaction();
        try {
            // 1. Verificar se o item pertence à notificação e ao usuário
            $sql_check_item_notif = "SELECT n.usuario_id, n.itens_ids FROM notificacoes n WHERE n.id = ? AND n.usuario_id = ?";
            $stmt_check_item_notif = $pdo->prepare($sql_check_item_notif);
            $stmt_check_item_notif->execute([$notificacao_id, $usuario_logado_id]);
            $notificacao_info = $stmt_check_item_notif->fetch(PDO::FETCH_ASSOC);

            if (!$notificacao_info || !in_array($item_id, explode(',', $notificacao_info['itens_ids']))) {
                throw new Exception("Item ou notificação inválida.");
            }

            // 2. Atualizar o status_confirmacao do item específico
            if ($action == 'confirmar_item') {
                $sql_update_item = "UPDATE itens SET status_confirmacao = 'Confirmado' WHERE id = ?";
                $stmt_update_item = $pdo->prepare($sql_update_item);
                $stmt_update_item->execute([$item_id]);
                $mensagem = "Item #{$item_id} confirmado com sucesso!";
            } elseif ($action == 'nao_confirmar_item') {
                if (empty($justificativa)) {
                    throw new Exception("Por favor, forneça uma justificativa para não confirmar o item.");
                }
                $sql_update_item = "UPDATE itens SET status_confirmacao = 'Nao Confirmado' WHERE id = ?";
                $stmt_update_item = $pdo->prepare($sql_update_item);
                $stmt_update_item->execute([$item_id]);

                // Atualiza a justificativa na notificação principal (a última justificativa prevalece)
                $sql_update_notif_just = "UPDATE notificacoes SET justificativa = ?, data_resposta = NOW() WHERE id = ?";
                $stmt_update_notif_just = $pdo->prepare($sql_update_notif_just);
                $stmt_update_notif_just->execute([$justificativa, $notificacao_id]);

                $mensagem = "Item #{$item_id} não confirmado. Sua justificativa foi registrada.";
            }

            // 3. Reavaliar e atualizar o status geral da notificação
            $itens_ids_array = explode(',', $notificacao_info['itens_ids']);
            atualizarStatusNotificacao($pdo, $notificacao_id, $itens_ids_array);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao processar ação: " . $e->getMessage();
        }
    }
}

// Buscar todas as notificações para o usuário logado
// E para cada notificação, buscar os detalhes completos dos itens vinculados
$sql_notificacoes = "SELECT n.*, a.nome as administrador_nome FROM notificacoes n JOIN usuarios a ON n.administrador_id = a.id WHERE n.usuario_id = ? ORDER BY n.data_envio DESC";
$stmt_notificacoes = $pdo->prepare($sql_notificacoes);
$stmt_notificacoes->execute([$usuario_logado_id]);
$notificacoes = $stmt_notificacoes->fetchAll(PDO::FETCH_ASSOC);

// Para cada notificação, buscar os detalhes dos itens
foreach ($notificacoes as &$notificacao) {
    $notificacao['detalhes_itens'] = [];
    if (!empty($notificacao['itens_ids'])) {
        $itens_ids_array = explode(',', $notificacao['itens_ids']);
        $placeholders = implode(',', array_fill(0, count($itens_ids_array), '?'));
        
        $sql_detalhes_itens = "SELECT 
                                i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, 
                                l.nome as local_nome, u.nome as responsavel_nome, i.estado, i.observacao, i.status_confirmacao
                               FROM itens i
                               LEFT JOIN locais l ON i.local_id = l.id
                               LEFT JOIN usuarios u ON i.responsavel_id = u.id
                               WHERE i.id IN ($placeholders)";
        $stmt_detalhes_itens = $pdo->prepare($sql_detalhes_itens);
        foreach ($itens_ids_array as $k => $id) {
            $stmt_detalhes_itens->bindValue(($k+1), $id, PDO::PARAM_INT);
        }
        $stmt_detalhes_itens->execute();
        $notificacao['detalhes_itens'] = $stmt_detalhes_itens->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Lógica para construir o assunto da notificação (Gmail-like) ---
    $subject_items = [];
    if (!empty($notificacao['detalhes_itens'])) {
        foreach ($notificacao['detalhes_itens'] as $item) {
            $subject_items[] = htmlspecialchars($item['nome']);
        }
        if (count($subject_items) > 2) { // Truncar com 2 para ficar mais curto
            $truncated_items = array_slice($subject_items, 0, 2);
            $subject_str = implode(', ', $truncated_items) . '... e mais ' . (count($subject_items) - 2) . ' item(s)';
        } else {
            $subject_str = implode(', ', $subject_items);
        }
    } else {
        $subject_str = 'Nenhum item associado';
    }
    $notificacao['assunto_titulo'] = "Notificação de " . htmlspecialchars($notificacao['tipo']);
    $notificacao['assunto_resumo'] = $subject_str;
    // --- Fim Lógica para construir o assunto --- //
}

?>

<div class="container mt-5">
    <h2>Minhas Notificações de Inventário</h2>
    <p>Aqui você pode visualizar e confirmar as movimentações de itens atribuídos a você.</p>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <?php if (empty($notificacoes)): ?>
        <div class="alert alert-info">Você não possui notificações no momento.</div>
    <?php else: ?>
        <div class="notification-inbox">
            <?php foreach ($notificacoes as $notificacao): ?>
                <div class="notification-item card mb-2" data-notif-id="<?php echo $notificacao['id']; ?>">
                    <div class="card-header notification-summary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($notificacao['administrador_nome']); ?></strong>
                                <strong class="ml-2"><?php echo $notificacao['assunto_titulo']; ?>:</strong>
                                <span class="assunto-resumo"><?php echo htmlspecialchars($notificacao['assunto_resumo']); ?></span>
                            </div>
                            <div>
                                <span class="badge badge-<?php 
                                    if($notificacao['status'] == 'Pendente') echo 'warning';
                                    else if($notificacao['status'] == 'Confirmado') echo 'success';
                                    else echo 'danger';
                                ?>">
                                    <?php echo htmlspecialchars($notificacao['status']); ?>
                                </span>
                                <small class="text-muted ml-2"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_envio'])); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body notification-details" style="display: none;">
                        <p><strong>Mensagem Geral:</strong> <?php echo nl2br(htmlspecialchars($notificacao['mensagem'])); ?></p>
                        <?php if ($notificacao['status'] == 'Nao Confirmado' && !empty($notificacao['justificativa'])): ?>
                            <div class="alert alert-warning mt-2">
                                <strong>Sua Justificativa:</strong> <?php echo nl2br(htmlspecialchars($notificacao['justificativa'])); ?><br>
                                <small>Respondido em: <?php echo date('d/m/Y H:i', strtotime($notificacao['data_resposta'])); ?></small>
                            </div>
                        <?php endif; ?>

                        <h6 class="mt-4">Detalhes dos Itens:</h6>
                        <?php if (!empty($notificacao['detalhes_itens'])): ?>
                            <?php foreach ($notificacao['detalhes_itens'] as $item): ?>
                                <div class="item-detail-card card mb-2">
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
                                                <span class="badge badge-<?php 
                                                    if($item['status_confirmacao'] == 'Pendente') echo 'warning';
                                                    else if($item['status_confirmacao'] == 'Confirmado') echo 'success';
                                                    else echo 'danger';
                                                ?>">
                                                    <?php echo htmlspecialchars($item['status_confirmacao']); ?>
                                                </span>
                                            </li>
                                        </ul>

                                        <?php if ($item['status_confirmacao'] == 'Pendente'): ?>
                                            <form action="notificacoes_usuario.php" method="post" class="mt-2 item-action-form">
                                                <input type="hidden" name="notificacao_id" value="<?php echo $notificacao['id']; ?>">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" name="action" value="confirmar_item" class="btn btn-success btn-sm">Confirmar Item</button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="showItemJustificativaForm(<?php echo $notificacao['id']; ?>, <?php echo $item['id']; ?>)">Não Confirmar Item</button>
                                            </form>
                                            <div id="item_justificativa_form_<?php echo $notificacao['id']; ?>_<?php echo $item['id']; ?>" style="display:none; margin-top: 10px;">
                                                <form action="notificacoes_usuario.php" method="post">
                                                    <input type="hidden" name="notificacao_id" value="<?php echo $notificacao['id']; ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
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
// Função para expandir/colapsar os detalhes da notificação
document.querySelectorAll('.notification-summary').forEach(summary => {
    summary.addEventListener('click', () => {
        const details = summary.nextElementSibling;
        if (details.style.display === 'none' || details.style.display === '') {
            details.style.display = 'block';
        } else {
            details.style.display = 'none';
        }
    });
});

// Funções para exibir/esconder o formulário de justificativa para itens individuais
function showItemJustificativaForm(notifId, itemId) {
    document.getElementById('item_justificativa_form_' + notifId + '_' + itemId).style.display = 'block';
}

function hideItemJustificativaForm(notifId, itemId) {
    document.getElementById('item_justificativa_form_' + notifId + '_' + itemId).style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>