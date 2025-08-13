<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$item_id = $_GET['id'];

$sql_item = "SELECT 
                i.*, -- Seleciona todas as colunas da tabela de itens para garantir que todos os detalhes sejam exibidos
                l.nome as local_nome, 
                u.nome as responsavel_nome, u.email as responsavel_email
              FROM itens i
              LEFT JOIN locais l ON i.local_id = l.id
              LEFT JOIN usuarios u ON i.responsavel_id = u.id
              WHERE i.id = ?";

if($stmt_item = mysqli_prepare($link, $sql_item)){
    mysqli_stmt_bind_param($stmt_item, "i", $item_id);
    mysqli_stmt_execute($stmt_item);
    $result_item = mysqli_stmt_get_result($stmt_item);
    $item = mysqli_fetch_assoc($result_item);
    if(!$item) {
        echo "<div class='alert alert-danger'>Item não encontrado.</div>";
    }
} else {
    echo "Erro ao preparar a consulta de detalhes do item: " . mysqli_error($link);
    $item = false;
}

// Busca o histórico de movimentações do item
$sql_movimentacoes = "SELECT 
                        m.data_movimentacao, 
                        lo.nome as local_origem, 
                        ld.nome as local_destino,
                        ua.nome as responsavel_anterior,
                        ur.nome as realizado_por
                    FROM movimentacoes m
                    JOIN locais lo ON m.local_origem_id = lo.id
                    JOIN locais ld ON m.local_destino_id = ld.id
                    JOIN usuarios ur ON m.usuario_id = ur.id
                    LEFT JOIN usuarios ua ON m.usuario_anterior_id = ua.id
                    WHERE m.item_id = ?
                    ORDER BY m.data_movimentacao DESC";

$movimentacoes = [];
if($stmt_mov = mysqli_prepare($link, $sql_movimentacoes)){
    mysqli_stmt_bind_param($stmt_mov, "i", $item_id);
    mysqli_stmt_execute($stmt_mov);
    $result_mov = mysqli_stmt_get_result($stmt_mov);
    while($row = mysqli_fetch_assoc($result_mov)){
        $movimentacoes[] = $row;
    }
    mysqli_stmt_close($stmt_mov);
}

?>

<style>
    .details-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .details-section {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    .details-section h3 {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }
    .details-section p {
        margin: 10px 0;
        line-height: 1.6;
    }
    .details-section p strong {
        color: #555;
        min-width: 180px;
        display: inline-block;
    }
</style>

<h2>Detalhes do Item: <?php echo $item ? $item['nome'] : ''; ?></h2>
<p><a href="javascript:history.back()" class="btn-custom">Voltar</a></p>

<?php if($item): ?>
    <div class="details-container">
        <div class="details-section">
            <h3>Dados Gerais</h3>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($item['id'] ?? 'Não preenchido'); ?></p>
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($item['nome'] ?? 'Não preenchido'); ?></p>
            <p><strong>Patrimônio Principal:</strong> <?php echo htmlspecialchars($item['patrimonio_novo'] ?? 'Não preenchido'); ?></p>
            <p><strong>Patrimônio Secundário:</strong> <?php echo htmlspecialchars($item['patrimonio_secundario'] ?? 'Não preenchido'); ?></p>
            <p><strong>Número de Série:</strong> <?php echo htmlspecialchars($item['numero_serie'] ?? 'Não preenchido'); ?></p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($item['estado'] ?? 'Não preenchido'); ?></p>
            <p><strong>Data de Cadastro:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($item['data_cadastro'] ?? 'now'))); ?></p>
            <p><strong>Descrição Detalhada:</strong> <?php echo nl2br(htmlspecialchars($item['descricao_detalhada'] ?? 'Não preenchido')); ?></p>
            <p><strong>Observação:</strong> <?php echo nl2br(htmlspecialchars($item['observacao'] ?? 'Não preenchido')); ?></p>
        </div>

        <div class="details-section">
            <h3>Localização e Responsabilidade</h3>
            <p><strong>Local Atual:</strong> <?php echo htmlspecialchars($item['local_nome'] ?? 'Não preenchido'); ?></p>
            <p><strong>Responsável Atual:</strong> <?php echo htmlspecialchars($item['responsavel_nome'] ?? 'Não preenchido'); ?></p>
            <p><strong>Email do Responsável:</strong> <?php echo htmlspecialchars($item['responsavel_email'] ?? 'Não preenchido'); ?></p>
        </div>

        <div class="details-section">
            <h3>Dados de Aquisição</h3>
            <p><strong>Processo/Documento:</strong> <?php echo htmlspecialchars($item['processo_documento'] ?? 'Não preenchido'); ?></p>
            <p><strong>Empenho:</strong> <?php echo htmlspecialchars($item['empenho'] ?? 'Não preenchido'); ?></p>
            <p><strong>Data Emissão Empenho:</strong> <?php echo htmlspecialchars($item['data_emissao_empenho'] ?? 'Não preenchido'); ?></p>
            <p><strong>Fornecedor:</strong> <?php echo htmlspecialchars($item['fornecedor'] ?? 'Não preenchido'); ?></p>
            <p><strong>CNPJ Fornecedor:</strong> <?php echo htmlspecialchars($item['cnpj_fornecedor'] ?? 'Não preenchido'); ?></p>
            <p><strong>Nota Fiscal:</strong> <?php echo htmlspecialchars($item['nota_fiscal_documento'] ?? 'Não preenchido'); ?></p>
            <p><strong>Valor Unitário:</strong> R$ <?php echo htmlspecialchars(number_format($item['valor'] ?? 0, 2, ',', '.')); ?></p>
            <p><strong>Data de Entrada:</strong> <?php echo htmlspecialchars($item['data_entrada_aceitacao'] ?? 'Não preenchido'); ?></p>
        </div>
    </div>

    <div class="details-section">
        <h3>Histórico de Movimentações</h3>
        <?php if(!empty($movimentacoes)): ?>
            <table class="table-striped table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Local de Origem</th>
                        <th>Local de Destino</th>
                        <th>Responsável Anterior</th>
                        <th>Movimentado Por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($movimentacoes as $mov): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($mov['data_movimentacao']))); ?></td>
                        <td><?php echo htmlspecialchars($mov['local_origem']); ?></td>
                        <td><?php echo htmlspecialchars($mov['local_destino']); ?></td>
                        <td><?php echo htmlspecialchars($mov['responsavel_anterior'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($mov['realizado_por']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma movimentação registrada para este item.</p>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>