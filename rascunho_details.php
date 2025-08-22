<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem acessar esta página
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$rascunho_id = $_GET['id'];

$sql_rascunho = "SELECT 
                r.*, -- Seleciona todas as colunas da tabela de rascunhos para garantir que todos os detalhes sejam exibidos
                l.nome as local_nome, 
                u.nome as responsavel_nome
              FROM rascunhos_itens r
              LEFT JOIN locais l ON r.local_id = l.id
              LEFT JOIN usuarios u ON r.responsavel_id = u.id
              WHERE r.id = ?";

$stmt_rascunho = $pdo->prepare($sql_rascunho);
$stmt_rascunho->execute([$rascunho_id]);
$rascunho = $stmt_rascunho->fetch(PDO::FETCH_ASSOC);

if(!$rascunho) {
    echo "<div class='alert alert-danger'>Rascunho não encontrado.</div>";
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

<h2>Detalhes do Rascunho: <?php echo $rascunho ? $rascunho['nome'] : ''; ?></h2>
<p><a href="javascript:history.back()" class="btn-custom">Voltar</a></p>

<?php if($rascunho): ?>
    <div class="details-container">
        <div class="details-section">
            <h3>Dados Gerais</h3>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($rascunho['id'] ?? 'Não preenchido'); ?></p>
            <p><strong>Processo/Documento:</strong> <?php echo htmlspecialchars($rascunho['processo_documento'] ?? 'Não preenchido'); ?></p>
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($rascunho['nome'] ?? 'Não preenchido'); ?></p>
            <p><strong>Descrição Detalhada:</strong> <?php echo nl2br(htmlspecialchars($rascunho['descricao_detalhada'] ?? 'Não preenchido')); ?></p>
            <p><strong>Número de Série:</strong> <?php echo htmlspecialchars($rascunho['numero_serie'] ?? 'Não preenchido'); ?></p>
            <p><strong>Quantidade:</strong> <?php echo htmlspecialchars($rascunho['quantidade'] ?? 'Não preenchido'); ?></p>
            <p><strong>Patrimônio Principal:</strong> <?php echo htmlspecialchars($rascunho['patrimonio_novo'] ?? 'Não preenchido'); ?></p>
            <p><strong>Patrimônio Secundário:</strong> <?php echo htmlspecialchars($rascunho['patrimonio_secundario'] ?? 'Não preenchido'); ?></p>
            <p><strong>Estado:</strong> <?php echo htmlspecialchars($rascunho['estado'] ?? 'Não preenchido'); ?></p>
            <p><strong>Data de Cadastro:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rascunho['data_cadastro'] ?? 'now'))); ?></p>
            <p><strong>Observação:</strong> <?php echo nl2br(htmlspecialchars($rascunho['observacao'] ?? 'Não preenchido')); ?></p>
        </div>

        <div class="details-section">
            <h3>Localização e Responsabilidade</h3>
            <p><strong>Local:</strong> <?php echo htmlspecialchars($rascunho['local_nome'] ?? 'Não preenchido'); ?></p>
            <p><strong>Responsável:</strong> <?php echo htmlspecialchars($rascunho['responsavel_nome'] ?? 'Não preenchido'); ?></p>
            <p><strong>Usuário Anterior:</strong> <?php echo htmlspecialchars($rascunho['usuario_anterior_id'] ?? 'Não preenchido'); ?></p>
        </div>

        <div class="details-section">
            <h3>Dados de Aquisição</h3>
            <p><strong>Empenho ID:</strong> <?php echo htmlspecialchars($rascunho['empenho_id'] ?? 'Não preenchido'); ?></p>
            <p><strong>Empenho:</strong> <?php echo htmlspecialchars($rascunho['empenho'] ?? 'Não preenchido'); ?></p>
            <p><strong>Data Emissão Empenho:</strong> <?php echo htmlspecialchars($rascunho['data_emissao_empenho'] ?? 'Não preenchido'); ?></p>
            <p><strong>Fornecedor:</strong> <?php echo htmlspecialchars($rascunho['fornecedor'] ?? 'Não preenchido'); ?></p>
            <p><strong>CNPJ/CPF Fornecedor:</strong> <?php echo htmlspecialchars($rascunho['cnpj_cpf_fornecedor'] ?? 'Não preenchido'); ?></p>
            <p><strong>CNPJ Fornecedor:</strong> <?php echo htmlspecialchars($rascunho['cnpj_fornecedor'] ?? 'Não preenchido'); ?></p>
            <p><strong>Categoria:</strong> <?php echo htmlspecialchars($rascunho['categoria'] ?? 'Não preenchido'); ?></p>
            <p><strong>Valor NF:</strong> R$ <?php echo htmlspecialchars(number_format($rascunho['valor_nf'] ?? 0, 2, ',', '.')); ?></p>
            <p><strong>ND Nota Despesa:</strong> <?php echo htmlspecialchars($rascunho['nd_nota_despesa'] ?? 'Não preenchido'); ?></p>
            <p><strong>Unidade Medida:</strong> <?php echo htmlspecialchars($rascunho['unidade_medida'] ?? 'Não preenchido'); ?></p>
            <p><strong>Valor:</strong> R$ <?php echo htmlspecialchars(number_format($rascunho['valor'] ?? 0, 2, ',', '.')); ?></p>
            <p><strong>Tipo Aquisição:</strong> <?php echo htmlspecialchars($rascunho['tipo_aquisicao'] ?? 'Não preenchido'); ?></p>
            <p><strong>Tipo Aquisição Descrição:</strong> <?php echo htmlspecialchars($rascunho['tipo_aquisicao_descricao'] ?? 'Não preenchido'); ?></p>
            <p><strong>Número Documento:</strong> <?php echo htmlspecialchars($rascunho['numero_documento'] ?? 'Não preenchido'); ?></p>
            <p><strong>Nota Fiscal/Documento:</strong> <?php echo htmlspecialchars($rascunho['nota_fiscal_documento'] ?? 'Não preenchido'); ?></p>
            <p><strong>Data de Entrada/Aceitação:</strong> <?php echo htmlspecialchars($rascunho['data_entrada_aceitacao'] ?? 'Não preenchido'); ?></p>
            <p><strong>Status Confirmação:</strong> <?php echo htmlspecialchars($rascunho['status_confirmacao'] ?? 'Não preenchido'); ?></p>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="item_edit_rascunho.php?id=<?php echo $rascunho['id']; ?>" class="btn-custom">Editar Rascunho</a>
        <a href="rascunhos_itens.php" class="btn-secondary">Voltar para Lista</a>
    </div>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
?>