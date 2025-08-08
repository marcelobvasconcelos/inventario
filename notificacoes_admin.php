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

// Obtém o status de filtro da URL, padrão é 'Todos'
$filtro_status = isset($_GET['status']) ? $_GET['status'] : 'Todos';

// SQL base para buscar notificações, juntando com tabelas de usuários para obter nomes
$sql_notificacoes = "SELECT n.*, u.nome as usuario_nome, a.nome as administrador_nome FROM notificacoes n JOIN usuarios u ON n.usuario_id = u.id JOIN usuarios a ON n.administrador_id = a.id";
$params = []; // Array para armazenar parâmetros da consulta preparada

// Adiciona cláusula WHERE se um filtro de status específico for selecionado
if ($filtro_status != 'Todos') {
    $sql_notificacoes .= " WHERE n.status = ?";
    $params[] = $filtro_status; // Adiciona o status ao array de parâmetros
}

// Ordena as notificações pela data de envio (mais recente primeiro)
$sql_notificacoes .= " ORDER BY n.data_envio DESC";

// Prepara e executa a consulta SQL
$stmt_notificacoes = $pdo->prepare($sql_notificacoes);
$stmt_notificacoes->execute($params);
$notificacoes = $stmt_notificacoes->fetchAll(PDO::FETCH_ASSOC);

// Para cada notificação, buscar os detalhes dos itens vinculados
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
}

?>

<div class="container mt-5">
    <h2>Gerenciar Notificações de Inventário</h2>
    <p>Visualize o status das notificações de movimentação e as justificativas dos usuários.</p>

    <div class="mb-3">
        <label for="filtroStatus">Filtrar por Status:</label>
        <select id="filtroStatus" class="form-control" onchange="window.location.href='notificacoes_admin.php?status=' + this.value">
            <option value="Todos" <?php echo ($filtro_status == 'Todos') ? 'selected' : ''; ?>>Todos</option>
            <option value="Pendente" <?php echo ($filtro_status == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
            <option value="Confirmado" <?php echo ($filtro_status == 'Confirmado') ? 'selected' : ''; ?>>Confirmado</option>
            <option value="Nao Confirmado" <?php echo ($filtro_status == 'Nao Confirmado') ? 'selected' : ''; ?>>Não Confirmado</option>
        </select>
    </div>

    <?php if (empty($notificacoes)): // Exibe mensagem se não houver notificações com o filtro selecionado ?>
        <div class="alert alert-info">Nenhuma notificação encontrada com o filtro selecionado.</div>
    <?php else: // Itera sobre as notificações e as exibe ?>
        <?php foreach ($notificacoes as $notificacao): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Notificação #<?php echo $notificacao['id']; ?> - Para: <?php echo htmlspecialchars($notificacao['usuario_nome']); ?></h5>
                    <p class="card-text"><strong>Enviada por:</strong> <?php echo htmlspecialchars($notificacao['administrador_nome']); ?></p>
                    <p class="card-text"><strong>Tipo:</strong> <?php echo htmlspecialchars($notificacao['tipo']); ?></p>
                    <p class="card-text"><strong>Mensagem:</strong> <?php echo nl2br(htmlspecialchars($notificacao['mensagem'])); ?></p>
                    <p class="card-text"><strong>Status:</strong> 
                        <span class="badge badge-<?php 
                            // Define a classe CSS do badge com base no status da notificação
                            if($notificacao['status'] == 'Pendente') echo 'warning';
                            else if($notificacao['status'] == 'Confirmado') echo 'success';
                            else echo 'danger';
                        ?>">
                            <?php echo htmlspecialchars($notificacao['status']); ?>
                        </span>
                    </p>
                    <p class="card-text"><small class="text-muted">Enviado em: <?php echo date('d/m/Y H:i', strtotime($notificacao['data_envio'])); ?></small></p>

                    <?php if ($notificacao['status'] != 'Pendente' && !empty($notificacao['data_resposta'])): // Exibe a data de resposta se a notificação não estiver pendente ?>
                        <p class="card-text"><small class="text-muted">Respondido em: <?php echo date('d/m/Y H:i', strtotime($notificacao['data_resposta'])); ?></small></p>
                    <?php endif; ?>

                    <?php if ($notificacao['status'] == 'Nao Confirmado' && !empty($notificacao['justificativa'])): // Exibe a justificativa se a notificação foi não confirmada ?>
                        <div class="alert alert-danger mt-3">
                            <strong>Justificativa do Usuário:</strong> <?php echo nl2br(htmlspecialchars($notificacao['justificativa'])); ?><br>
                        </div>
                    <?php endif; ?>

                    <h6 class="mt-4">Detalhes dos Itens Associados:</h6>
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
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Nenhum item associado a esta notificação.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>