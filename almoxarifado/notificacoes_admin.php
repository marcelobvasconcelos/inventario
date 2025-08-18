<?php
require_once '../includes/header.php';
require_once '../config/db.php';

if ($_SESSION["permissao"] != 'Administrador') {
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    require_once '../includes/footer.php';
    exit;
}

$sql = "
    SELECT
        n.id as notificacao_id,
        n.mensagem,
        n.status as notificacao_status,
        n.data_criacao,
        am.id as item_id,
        am.nome as item_nome,
        am.status_confirmacao as item_status_confirmacao,
        u.nome as usuario_nome,
        nar.justificativa,
        nar.data_resposta
    FROM notificacoes n
    JOIN notificacoes_almoxarifado_detalhes nad ON n.id = nad.notificacao_id
    JOIN almoxarifado_materiais am ON nad.item_id = am.id
    JOIN usuarios u ON n.usuario_id = u.id
    LEFT JOIN notificacoes_almoxarifado_respostas nar ON n.id = nar.notificacao_id AND am.id = nar.item_id
    WHERE n.tipo = 'atribuicao_almoxarifado'
    ORDER BY n.data_criacao DESC
";

$stmt = $pdo->query($sql);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">
    <h2><i class="fas fa-bell"></i> Notificações do Almoxarifado (Admin)</h2>
    <p>Visualize as confirmações e justificativas dos usuários para os itens de almoxarifado.</p>

    <?php if (empty($notificacoes)): ?>
        <div class="alert alert-info">Nenhuma notificação do almoxarifado encontrada.</div>
    <?php else: ?>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID Not.</th>
                    <th>Usuário</th>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Justificativa</th>
                    <th>Data Notificação</th>
                    <th>Data Resposta</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notificacoes as $notificacao): ?>
                    <tr>
                        <td><?php echo $notificacao['notificacao_id']; ?></td>
                        <td><?php echo htmlspecialchars($notificacao['usuario_nome']); ?></td>
                        <td><?php echo htmlspecialchars($notificacao['item_nome']); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($notificacao['item_status_confirmacao']); ?></span></td>
                        <td><?php echo htmlspecialchars($notificacao['justificativa']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></td>
                        <td><?php echo $notificacao['data_resposta'] ? date('d/m/Y H:i', strtotime($notificacao['data_resposta'])) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
