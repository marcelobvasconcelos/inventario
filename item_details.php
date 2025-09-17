<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$item_id = $_GET['id'];

$sql_item = "SELECT 
                i.id, i.processo_documento, i.fornecedor, i.cnpj_cpf_fornecedor, i.nome, i.descricao_detalhada, i.numero_serie, i.quantidade, i.valor, i.nota_fiscal_documento, i.data_entrada_aceitacao, i.patrimonio_novo, i.patrimonio_secundario, i.data_cadastro, i.estado, i.observacao, 
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
?>

<h2>Detalhes do Item: <?php echo $item ? $item['nome'] : ''; ?></h2>
<p><a href="javascript:history.back()" class="btn-custom">Voltar</a></p>

<?php if($item): ?>
    <div>
        <p><strong>ID:</strong> <?php echo $item['id'] ?? 'Não preenchido'; ?></p>
        <p><strong>Processo/Documento:</strong> <?php echo !empty($item['processo_documento']) ? $item['processo_documento'] : 'Não preenchido'; ?></p>
        <p><strong>Fornecedor:</strong> <?php echo !empty($item['fornecedor']) ? $item['fornecedor'] : 'Não preenchido'; ?></p>
        <p><strong>CNPJ ou CPF Fornecedor:</strong> <?php echo !empty($item['cnpj_cpf_fornecedor']) ? $item['cnpj_cpf_fornecedor'] : 'Não preenchido'; ?></p>
        <p><strong>Nome:</strong> <?php echo !empty($item['nome']) ? $item['nome'] : 'Não preenchido'; ?></p>
        <p><strong>Descrição Detalhada:</strong> <?php echo !empty($item['descricao_detalhada']) ? $item['descricao_detalhada'] : 'Não preenchido'; ?></p>
        <p><strong>Número de Série:</strong> <?php echo !empty($item['numero_serie']) ? $item['numero_serie'] : 'Não preenchido'; ?></p>
        <p><strong>Quantidade:</strong> <?php echo isset($item['quantidade']) ? $item['quantidade'] : 'Não preenchido'; ?></p>
        <p><strong>Valor Unitário:</strong> <?php echo isset($item['valor']) ? $item['valor'] : 'Não preenchido'; ?></p>
        <p><strong>Nota Fiscal/Documento:</strong> <?php echo !empty($item['nota_fiscal_documento']) ? $item['nota_fiscal_documento'] : 'Não preenchido'; ?></p>
        <p><strong>Data de Entrada/Aceitação:</strong> <?php echo !empty($item['data_entrada_aceitacao']) ? $item['data_entrada_aceitacao'] : 'Não preenchido'; ?></p>
        <p><strong>Patrimônio Novo:</strong> <?php echo !empty($item['patrimonio_novo']) ? $item['patrimonio_novo'] : 'Não preenchido'; ?></p>
        <p><strong>Patrimônio Secundário:</strong> <?php echo !empty($item['patrimonio_secundario']) ? $item['patrimonio_secundario'] : 'Não preenchido'; ?></p>
        <p><strong>Data de Cadastro:</strong> <?php echo !empty($item['data_cadastro']) ? $item['data_cadastro'] : 'Não preenchido'; ?></p>
        <p><strong>Estado:</strong> <?php echo !empty($item['estado']) ? $item['estado'] : 'Não preenchido'; ?></p>
        <p><strong>Observação:</strong> <?php echo !empty($item['observacao']) ? $item['observacao'] : 'Não preenchido'; ?></p>
        <p><strong>Local:</strong> <?php echo !empty($item['local_nome']) ? $item['local_nome'] : 'Não preenchido'; ?></p>
        <p><strong>Responsável:</strong> <?php echo !empty($item['responsavel_nome']) ? $item['responsavel_nome'] : 'Não preenchido'; ?> (<?php echo !empty($item['responsavel_email']) ? $item['responsavel_email'] : 'Não preenchido'; ?>)</p>
    </div>
<?php endif; ?>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>