<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$item_id = $_GET['id'];

$sql_item = "SELECT 
                i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, i.data_cadastro, i.estado, i.observacao, 
                i.empenho, i.data_emissao_empenho, i.fornecedor, i.cnpj_fornecedor, i.categoria, i.valor_nf, i.nd_nota_despesa, i.unidade_medida, i.valor,
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
} else {
    echo "Erro ao preparar a consulta de detalhes do item: " . mysqli_error($link);
    $item = false;
}

?>

<h2>Detalhes do Item: <?php echo $item['nome']; ?></h2>
<p><a href="javascript:history.back()" class="btn-custom">Voltar</a></p>

<?php if($item): ?>
    <div>
        <p><strong>ID:</strong> <?php echo $item['id']; ?></p>
        <p><strong>Nome:</strong> <?php echo $item['nome']; ?></p>
        <p><strong>Patrimônio Novo:</strong> <?php echo $item['patrimonio_novo']; ?></p>
        <p><strong>Patrimônio Secundário:</strong> <?php echo $item['patrimonio_secundario']; ?></p>
        <p><strong>Data de Cadastro:</strong> <?php echo $item['data_cadastro']; ?></p>
        <p><strong>Estado:</strong> <?php echo $item['estado']; ?></p>
        <p><strong>Observação:</strong> <?php echo $item['observacao']; ?></p>
        <p><strong>Local:</strong> <?php echo $item['local_nome']; ?></p>
        <p><strong>Responsável:</strong> <?php echo $item['responsavel_nome']; ?> (<?php echo $item['responsavel_email']; ?>)</p>
        
        <h3>Informações de Aquisição</h3>
        <p><strong>Empenho:</strong> <?php echo htmlspecialchars($item['empenho']); ?></p>
        <p><strong>Data Emissão Empenho:</strong> <?php echo htmlspecialchars($item['data_emissao_empenho']); ?></p>
        <p><strong>Fornecedor:</strong> <?php echo htmlspecialchars($item['fornecedor']); ?></p>
        <p><strong>CNPJ Fornecedor:</strong> <?php echo htmlspecialchars($item['cnpj_fornecedor']); ?></p>
        <p><strong>Categoria:</strong> <?php echo htmlspecialchars($item['categoria']); ?></p>
        <p><strong>Número NF:</strong> <?php echo htmlspecialchars($item['valor_nf']); ?></p>
        <p><strong>ND-Nota de Despesa:</strong> <?php echo htmlspecialchars($item['nd_nota_despesa']); ?></p>
        <p><strong>Unidade de Medida:</strong> <?php echo htmlspecialchars($item['unidade_medida']); ?></p>
        <p><strong>Valor Unitário:</strong> <?php echo htmlspecialchars($item['valor']); ?></p>
    </div>
<?php else: ?>
    <p>Item não encontrado.</p>
<?php endif; ?>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>