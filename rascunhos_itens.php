<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Apenas administradores podem acessar esta página
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Processar ações (excluir, finalizar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_rascunho'])) {
        $rascunho_id = (int)$_POST['rascunho_id'];
        $sql_delete = "DELETE FROM rascunhos_itens WHERE id = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        if ($stmt_delete->execute([$rascunho_id])) {
            $message = "Rascunho excluído com sucesso.";
        } else {
            $error = "Erro ao excluir o rascunho.";
        }
    } elseif (isset($_POST['finalize_rascunho'])) {
        $rascunho_id = (int)$_POST['rascunho_id'];
        
        // Obter dados do rascunho
        $sql_get = "SELECT * FROM rascunhos_itens WHERE id = ?";
        $stmt_get = $pdo->prepare($sql_get);
        $stmt_get->execute([$rascunho_id]);
        $rascunho = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        if ($rascunho) {
            try {
                $pdo->beginTransaction();
                
                // 1. Inserir na tabela principal 'itens'
                $sql_insert = "INSERT INTO itens (
                    processo_documento, nome, descricao_detalhada, numero_serie, quantidade,
                    patrimonio_novo, patrimonio_secundario, local_id, responsavel_id, estado,
                    observacao, usuario_anterior_id, empenho_id, empenho, data_emissao_empenho,
                    fornecedor, cnpj_cpf_fornecedor, cnpj_fornecedor, categoria, valor_nf,
                    nd_nota_despesa, unidade_medida, valor, tipo_aquisicao,
                    tipo_aquisicao_descricao, numero_documento, nota_fiscal_documento,
                    data_entrada_aceitacao, data_cadastro, status_confirmacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
                
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([
                    $rascunho['processo_documento'],
                    $rascunho['nome'],
                    $rascunho['descricao_detalhada'],
                    $rascunho['numero_serie'],
                    $rascunho['quantidade'],
                    $rascunho['patrimonio_novo'],
                    $rascunho['patrimonio_secundario'],
                    $rascunho['local_id'],
                    $rascunho['responsavel_id'],
                    $rascunho['estado'],
                    $rascunho['observacao'],
                    $rascunho['usuario_anterior_id'],
                    $rascunho['empenho_id'],
                    $rascunho['empenho'],
                    $rascunho['data_emissao_empenho'],
                    $rascunho['fornecedor'],
                    $rascunho['cnpj_cpf_fornecedor'],
                    $rascunho['cnpj_fornecedor'],
                    $rascunho['categoria'],
                    $rascunho['valor_nf'],
                    $rascunho['nd_nota_despesa'],
                    $rascunho['unidade_medida'],
                    $rascunho['valor'],
                    $rascunho['tipo_aquisicao'],
                    $rascunho['tipo_aquisicao_descricao'],
                    $rascunho['numero_documento'],
                    $rascunho['nota_fiscal_documento'],
                    $rascunho['data_entrada_aceitacao'],
                    'Confirmado' // Status confirmado para itens finalizados
                ]);
                
                $novo_item_id = $pdo->lastInsertId();
                
                // 2. Excluir o rascunho
                $sql_delete = "DELETE FROM rascunhos_itens WHERE id = ?";
                $stmt_delete = $pdo->prepare($sql_delete);
                $stmt_delete->execute([$rascunho_id]);
                
                $pdo->commit();
                $message = "Rascunho finalizado e item criado com sucesso (ID: $novo_item_id).";
            } catch (Exception $e) {
                $pdo->rollback();
                $error = "Erro ao finalizar o rascunho: " . $e->getMessage();
            }
        } else {
            $error = "Rascunho não encontrado.";
        }
    }
}

// Buscar todos os rascunhos
$sql = "SELECT r.*, l.nome as local_nome, u.nome as responsavel_nome
        FROM rascunhos_itens r
        LEFT JOIN locais l ON r.local_id = l.id
        LEFT JOIN usuarios u ON r.responsavel_id = u.id
        ORDER BY r.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rascunhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar locais e usuários para os dropdowns
$locais_result = $pdo->query("SELECT id, nome FROM locais ORDER BY nome ASC");
$locais = $locais_result->fetchAll(PDO::FETCH_ASSOC);

$usuarios_result = $pdo->query("SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC");
$usuarios = $usuarios_result->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .rascunhos-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .rascunhos-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .rascunhos-list {
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
    }
    .rascunho-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }
    .rascunho-item:last-child {
        border-bottom: none;
    }
    .rascunho-item:hover {
        background-color: #f9f9f9;
    }
    .rascunho-title {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .rascunho-title a {
        color: #333;
        text-decoration: none;
    }
    .rascunho-title a:hover {
        color: #007bff;
        text-decoration: underline;
    }
    .rascunho-meta {
        font-size: 0.9em;
        color: #666;
        margin-bottom: 10px;
    }
    .rascunho-actions {
        display: flex;
        gap: 10px;
    }
    .btn-small {
        padding: 5px 10px;
        font-size: 0.9em;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    /* Estilos para campos de formulário */
    input[type="text"], input[type="number"], input[type="date"], select, textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }
    input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus, select:focus, textarea:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
    }
    label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #555;
    }
    .help-block {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 5px;
    }
    div {
        margin-bottom: 15px;
    }
</style>

<div class="rascunhos-container">
    <div class="rascunhos-header">
        <h2>Rascunhos de Itens</h2>
        <a href="item_add_rascunho.php" class="btn-custom">Novo Rascunho</a>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (empty($rascunhos)): ?>
        <div class="empty-state">
            <p>Nenhum rascunho encontrado.</p>
            <a href="item_add_rascunho.php" class="btn-custom">Criar seu primeiro rascunho</a>
        </div>
    <?php else: ?>
        <div class="rascunhos-list">
            <?php foreach ($rascunhos as $rascunho): ?>
                <div class="rascunho-item">
                    <div class="rascunho-title">
                        <a href="rascunho_details.php?id=<?php echo $rascunho['id']; ?>">
                            <?php echo htmlspecialchars($rascunho['nome']); ?>
                        </a>
                    </div>
                    <div class="rascunho-meta">
                        ID: <?php echo $rascunho['id']; ?> |
                        Criado em: <?php echo date('d/m/Y H:i', strtotime($rascunho['data_criacao'])); ?> |
                        Local: <?php echo htmlspecialchars($rascunho['local_nome'] ?? 'N/A'); ?> |
                        Responsável: <?php echo htmlspecialchars($rascunho['responsavel_nome'] ?? 'N/A'); ?>
                    </div>
                    <div class="rascunho-actions">
                        <a href="rascunho_details.php?id=<?php echo $rascunho['id']; ?>" class="btn-custom btn-small">Detalhes</a>
                        <a href="item_edit_rascunho.php?id=<?php echo $rascunho['id']; ?>" class="btn-custom btn-small">Editar</a>
                        
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="rascunho_id" value="<?php echo $rascunho['id']; ?>">
                            <button type="submit" name="finalize_rascunho" class="btn-custom btn-small"
                                    onclick="return confirm('Tem certeza que deseja finalizar este rascunho? Ele será movido para a lista de itens principais.')">
                                Finalizar
                            </button>
                        </form>
                        
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="rascunho_id" value="<?php echo $rascunho['id']; ?>">
                            <button type="submit" name="delete_rascunho" class="btn-danger btn-small"
                                    onclick="return confirm('Tem certeza que deseja excluir este rascunho? Esta ação não pode ser desfeita.')">
                                Excluir
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>