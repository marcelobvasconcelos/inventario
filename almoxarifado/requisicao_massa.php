<?php
// requisicao_massa.php - Página para requisição em massa de materiais
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar se o usuário tem permissão
$allowed_roles = ['Administrador', 'Almoxarife', 'Visualizador', 'Gestor'];
if (!isset($_SESSION['permissao']) || !in_array($_SESSION['permissao'], $allowed_roles)) {
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para acessar este módulo.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Verificar se foram passados materiais selecionados
if (!isset($_GET['materiais']) || empty($_GET['materiais'])) {
    header("location: index.php");
    exit;
}

// Decodificar os materiais selecionados
$materiais_selecionados = json_decode(urldecode($_GET['materiais']), true);

if (empty($materiais_selecionados)) {
    header("location: index.php");
    exit;
}

// Buscar locais disponíveis para entrega
$locais_result = $pdo->query("SELECT id, nome FROM locais ORDER BY nome ASC");
$locais = $locais_result->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário de requisição
$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_requisicao'])) {
    $local_id = (int)$_POST['local_id'];
    $justificativa = trim($_POST['justificativa'] ?? '');
    $itens_validos = [];

    // Validar e coletar itens
    foreach ($materiais_selecionados as $material) {
        $material_id = $material['id'];
        $quantidade_solicitada = (int)($_POST["quantidade_{$material_id}"] ?? 0);

        if ($quantidade_solicitada > 0) {
            // Verificar se o material existe e obter informações
            $sql_material = "SELECT * FROM almoxarifado_materiais WHERE id = ?";
            $stmt_material = $pdo->prepare($sql_material);
            $stmt_material->execute([$material_id]);
            $material_info = $stmt_material->fetch(PDO::FETCH_ASSOC);

            if ($material_info) {
                // Verificar se a quantidade solicitada excede o máximo permitido
                $exige_justificativa = false;
                if ($material_info['quantidade_maxima_requisicao'] !== null &&
                    $quantidade_solicitada > $material_info['quantidade_maxima_requisicao']) {
                    $exige_justificativa = true;
                }

                $itens_validos[] = [
                    'id' => $material_id,
                    'quantidade_solicitada' => $quantidade_solicitada,
                    'exige_justificativa' => $exige_justificativa,
                    'nome' => $material_info['nome']
                ];
            }
        }
    }

    // Verificar se há itens para requisitar
    if (empty($itens_validos)) {
        $error = "Selecione pelo menos um material e informe uma quantidade válida.";
    } else {
        // Verificar se algum item exige justificativa
        $exige_justificativa_global = false;
        $itens_com_excesso = [];
        foreach ($itens_validos as $item) {
            if ($item['exige_justificativa']) {
                $exige_justificativa_global = true;
                $itens_com_excesso[] = $item['nome'];
            }
        }

        // Validar justificativa se necessário
        if ($exige_justificativa_global && empty($justificativa)) {
            $error = "O campo 'Justificativa' é obrigatório, pois alguns itens ultrapassam a quantidade máxima permitida: " . implode(', ', $itens_com_excesso);
        } else {
            // Processar a requisição
            try {
                $pdo->beginTransaction();

                // 1. Inserir a requisição na tabela principal
                $sql_requisicao = "INSERT INTO almoxarifado_requisicoes (usuario_id, local_id, data_requisicao, justificativa)
                                  VALUES (?, ?, NOW(), ?)";
                $stmt_requisicao = $pdo->prepare($sql_requisicao);
                $stmt_requisicao->execute([$_SESSION['id'], $local_id, $justificativa]);
                $requisicao_id = $pdo->lastInsertId();

                // 2. Inserir os itens da requisição
                $sql_item = "INSERT INTO almoxarifado_requisicoes_itens (requisicao_id, produto_id, quantidade_solicitada)
                            VALUES (?, ?, ?)";
                $stmt_item = $pdo->prepare($sql_item);

                foreach ($itens_validos as $item) {
                    $stmt_item->execute([$requisicao_id, $item['id'], $item['quantidade_solicitada']]);
                }

                // 3. Criar notificações para os administradores
                $sql_admin_notificacao = "INSERT INTO notificacoes_movimentacao (movimentacao_id, item_id, usuario_notificado_id, status_confirmacao, data_notificacao)
                                        VALUES (?, ?, ?, 'Pendente', NOW())";

                $administradores = $pdo->query("SELECT u.id FROM usuarios u JOIN perfis p ON u.permissao_id = p.id WHERE p.nome = 'Administrador'")->fetchAll(PDO::FETCH_COLUMN);

                foreach ($itens_validos as $item) {
                    foreach ($administradores as $admin_id) {
                        $stmt_notif = $pdo->prepare($sql_admin_notificacao);
                        $stmt_notif->execute([$requisicao_id, $item['id'], $admin_id]);
                    }
                }

                $pdo->commit();
                $message = "Requisição enviada com sucesso! Você será notificado quando for processada.";

                // Redirecionar após 2 segundos
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 2000);
                </script>";

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erro ao processar requisição: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Requisição em Massa de Materiais</h2>
        <?php
        $is_privileged_user = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);
        require_once 'menu_almoxarifado.php';
        ?>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Instruções:</strong> Selecione as quantidades desejadas para cada material, escolha o local de entrega e envie sua requisição.
        <?php if (!$is_privileged_user): ?>
        A justificativa é obrigatória apenas se algum item ultrapassar a quantidade máxima permitida.
        <?php endif; ?>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Materiais Selecionados</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Unidade</th>
                                <th>Quantidade Solicitada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiais_selecionados as $material): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($material['codigo']); ?></td>
                                    <td><?php echo htmlspecialchars($material['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($material['unidade']); ?></td>
                                    <td>
                                        <input type="number" name="quantidade_<?php echo $material['id']; ?>"
                                               class="form-control" style="width: 100px;" min="0" step="0.01"
                                               placeholder="0" required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="local_id">Local de Entrega:</label>
                            <select name="local_id" id="local_id" class="form-control" required>
                                <option value="">Selecione o local...</option>
                                <?php foreach ($locais as $local): ?>
                                    <option value="<?php echo $local['id']; ?>">
                                        <?php echo htmlspecialchars($local['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="justificativa">Justificativa (opcional):</label>
                            <textarea name="justificativa" id="justificativa" class="form-control" rows="3"
                                      placeholder="Explique o motivo da requisição, especialmente se ultrapassar quantidades máximas permitidas."></textarea>
                            <small class="form-text text-muted">
                                Obrigatório apenas se algum item ultrapassar a quantidade máxima permitida.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" name="enviar_requisicao" class="btn-custom">
                        <i class="fas fa-paper-plane"></i> Enviar Requisição
                    </button>
                    <a href="index.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
}

.card-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    font-weight: 600;
}

.card-header h3 {
    margin: 0;
    font-size: 1.2em;
}

.btn-custom {
    background-color: #28a745;
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    font-weight: 600;
    cursor: pointer;
}

.btn-custom:hover {
    background-color: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php
require_once '../includes/footer.php';
?>