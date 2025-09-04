<?php
// almoxarifado/detalhes_requisicao.php - Página para visualizar detalhes da requisição
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Verificar se o usuário tem permissão de administrador, almoxarife, visualizador ou gestor
if ($_SESSION["permissao"] != 'Administrador' && $_SESSION["permissao"] != 'Almoxarife' && $_SESSION["permissao"] != 'Visualizador' && $_SESSION["permissao"] != 'Gestor') {
    header("location: index.php");
    exit;
}

// Verificar se foi passado um ID de requisição
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: index.php");
    exit;
}

$requisicao_id = (int)$_GET['id'];

// Buscar os detalhes da requisição
$sql_requisicao = "
    SELECT 
        r.id, 
        r.codigo_requisicao,
        r.usuario_id, 
        r.local_id, 
        r.data_requisicao, 
        r.status, 
        r.justificativa,
        u.nome as usuario_nome,
        l.nome as local_nome
    FROM almoxarifado_requisicoes r
    JOIN usuarios u ON r.usuario_id = u.id
    LEFT JOIN locais l ON r.local_id = l.id
    WHERE r.id = ?
";

$requisicao = null;
if($stmt_requisicao = mysqli_prepare($link, $sql_requisicao)){
    mysqli_stmt_bind_param($stmt_requisicao, "i", $requisicao_id);
    if(mysqli_stmt_execute($stmt_requisicao)){
        $result_requisicao = mysqli_stmt_get_result($stmt_requisicao);
        $requisicao = mysqli_fetch_assoc($result_requisicao);
    }
    mysqli_stmt_close($stmt_requisicao);
}

// Verificar se a requisição existe
if (!$requisicao) {
    header("location: index.php");
    exit;
}

// Verificar se o usuário tem permissão para ver esta requisição
// Usuários podem ver suas próprias requisições, administradores e almoxarifes podem ver todas
if ($_SESSION["permissao"] == 'Visualizador' && $requisicao['usuario_id'] != $_SESSION['id']) {
    header("location: index.php");
    exit;
}

// Buscar os itens da requisição
$sql_itens = "
    SELECT 
        i.quantidade_solicitada,
        i.quantidade_entregue,
        p.nome as produto_nome,
        p.unidade_medida as produto_unidade
    FROM almoxarifado_requisicoes_itens i
    JOIN almoxarifado_produtos p ON i.produto_id = p.id
    WHERE i.requisicao_id = ?
";

$itens = [];
if($stmt_itens = mysqli_prepare($link, $sql_itens)){
    mysqli_stmt_bind_param($stmt_itens, "i", $requisicao_id);
    if(mysqli_stmt_execute($stmt_itens)){
        $result_itens = mysqli_stmt_get_result($stmt_itens);
        while($row = mysqli_fetch_assoc($result_itens)){
            $itens[] = $row;
        }
    }
    mysqli_stmt_close($stmt_itens);
}
?>

<div class="almoxarifado-header">
    <h2>Detalhes da Requisição</h2>
</div>

<div class="almoxarifado-form-section">
    <h3>Informações Gerais</h3>
    
    <div class="form-group">
        <label>Código da Requisição:</label>
        <p><?php echo 'REQ-' . str_pad($requisicao['id'], 6, '0', STR_PAD_LEFT); ?></p>
    </div>
    
    <div class="form-group">
        <label>Data da Requisição:</label>
        <p><?php echo date('d/m/Y H:i:s', strtotime($requisicao['data_requisicao'])); ?></p>
    </div>
    
    <div class="form-group">
        <label>Status:</label>
        <p>
            <?php 
                $status = $requisicao['status'];
                $status_class = '';
                switch($status) {
                    case 'pendente':
                        $status_class = 'badge-warning';
                        break;
                    case 'aprovada':
                        $status_class = 'badge-success';
                        break;
                    case 'rejeitada':
                        $status_class = 'badge-danger';
                        break;
                    case 'concluida':
                        $status_class = 'badge-info';
                        break;
                    default:
                        $status_class = 'badge-secondary';
                }
            ?>
            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
        </p>
    </div>
    
    <div class="form-group">
        <label>Solicitante:</label>
        <p><?php echo htmlspecialchars($requisicao['usuario_nome']); ?></p>
    </div>
    
    <div class="form-group">
        <label>Local de Destino:</label>
        <p><?php echo htmlspecialchars($requisicao['local_nome'] ?? 'Não especificado'); ?></p>
    </div>
    
    <div class="form-group">
        <label>Justificativa:</label>
        <p><?php echo nl2br(htmlspecialchars($requisicao['justificativa'])); ?></p>
    </div>
</div>

<div class="almoxarifado-form-section">
    <h3>Itens Requisitados</h3>
    
    <table class="almoxarifado-table">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Unidade</th>
                <th>Quantidade Solicitada</th>
                <th>Quantidade Entregue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($itens) > 0): ?>
                <?php foreach($itens as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['produto_nome']); ?></td>
                    <td><?php echo htmlspecialchars($item['produto_unidade']); ?></td>
                    <td><?php echo $item['quantidade_solicitada']; ?></td>
                    <td><?php echo $item['quantidade_entregue']; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Nenhum item encontrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="form-group">
    <a href="index.php" class="btn btn-secondary">Voltar</a>
    <?php if($_SESSION["permissao"] == 'Administrador' || $_SESSION["permissao"] == 'Almoxarife'): ?>
        <?php if($requisicao['status'] == 'pendente'): ?>
            <button type="button" class="btn btn-success" onclick="aprovarRequisicao(<?php echo $requisicao['id']; ?>)">Aprovar</button>
            <button type="button" class="btn btn-danger" onclick="rejeitarRequisicao(<?php echo $requisicao['id']; ?>)">Rejeitar</button>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function aprovarRequisicao(requisicaoId) {
    if (confirm('Tem certeza que deseja aprovar esta requisição?')) {
        fetch('../api/almoxarifado_aprovar_requisicao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ requisicao_id: requisicaoId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Requisição aprovada com sucesso!');
                location.reload();
            } else {
                alert('Erro ao aprovar requisição: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao aprovar requisição.');
        });
    }
}

function rejeitarRequisicao(requisicaoId) {
    if (confirm('Tem certeza que deseja rejeitar esta requisição?')) {
        fetch('../api/almoxarifado_rejeitar_requisicao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ requisicao_id: requisicaoId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Requisição rejeitada com sucesso!');
                location.reload();
            } else {
                alert('Erro ao rejeitar requisição: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao rejeitar requisição.');
        });
    }
}
</script>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>