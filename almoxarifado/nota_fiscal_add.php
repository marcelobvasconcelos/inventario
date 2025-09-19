<?php
// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Inicializar variáveis
$nota_numero = '';
$nota_valor = '';
$empenho_numero = '';
$fornecedor = '';
$cnpj = '';

// Se foi passado um empenho via GET, preencher o campo
if(isset($_GET['empenho']) && !empty($_GET['empenho'])){
    $empenho_numero = $_GET['empenho'];
}

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_nota_fiscal'])){
    $nota_numero = trim($_POST["nota_numero"]);
    $nota_valor = trim($_POST["nota_valor"]);
    $empenho_numero = trim($_POST["empenho_numero"]);
    $fornecedor = trim($_POST["fornecedor"]);
    $cnpj = trim($_POST["cnpj"]);

    // Validação
    if(empty($nota_numero) || empty($nota_valor) || empty($empenho_numero) || empty($fornecedor) || empty($cnpj)){
        $error = "Todos os campos são obrigatórios.";
    } else {
        // Verificar se já existe uma nota fiscal com o mesmo número
        $sql_check = "SELECT nota_numero FROM notas_fiscais WHERE nota_numero = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$nota_numero]);
        
        if($stmt_check->rowCount() > 0){
            $error = "Já existe uma nota fiscal com este número.";
        } else {
            // Verificar se o empenho existe
            $sql_check_empenho = "SELECT numero FROM empenhos_insumos WHERE numero = ?";
            $stmt_check_empenho = $pdo->prepare($sql_check_empenho);
            $stmt_check_empenho->execute([$empenho_numero]);
            
            if($stmt_check_empenho->rowCount() == 0){
                $error = "Empenho não encontrado.";
            } else {
                // Verificar saldo do empenho
                $sql_saldo = "SELECT saldo FROM empenhos_insumos WHERE numero = ?";
                $stmt_saldo = $pdo->prepare($sql_saldo);
                $stmt_saldo->execute([$empenho_numero]);
                $saldo_atual = $stmt_saldo->fetchColumn();

                if($saldo_atual < $nota_valor){
                    $error = "Saldo insuficiente no empenho. Saldo atual: R$ " . number_format($saldo_atual, 2, ',', '.') . ". Valor da nota: R$ " . number_format($nota_valor, 2, ',', '.');
                } else {
                    $pdo->beginTransaction();
                    try {
                        // Inserir nova nota fiscal
                        $sql_insert = "INSERT INTO notas_fiscais (nota_numero, nota_valor, empenho_numero, fornecedor, cnpj, saldo) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_insert = $pdo->prepare($sql_insert);
                        $stmt_insert->execute([$nota_numero, $nota_valor, $empenho_numero, $fornecedor, $cnpj, $nota_valor]);

                        // Deduzir do saldo do empenho
                        $novo_saldo = $saldo_atual - $nota_valor;
                        
                        // Verificar se o saldo ficaria negativo
                        if($novo_saldo < 0){
                            throw new Exception("Não é possível cadastrar a nota fiscal. O saldo do empenho ficaria negativo (R$ " . number_format($novo_saldo, 2, ',', '.') . ").");
                        }
                        
                        // Atualizar saldo e status do empenho
                        if($novo_saldo == 0){
                            // Saldo 0 = empenho fechado
                            $sql_update_saldo = "UPDATE empenhos_insumos SET saldo = ?, status = 'Fechado' WHERE numero = ?";
                        } else {
                            // Saldo > 0 = empenho continua aberto
                            $sql_update_saldo = "UPDATE empenhos_insumos SET saldo = ? WHERE numero = ?";
                        }
                        $stmt_update_saldo = $pdo->prepare($sql_update_saldo);
                        $stmt_update_saldo->execute([$novo_saldo, $empenho_numero]);

                        $pdo->commit();
                        $message = "Nota fiscal cadastrada com sucesso! Saldo do empenho atualizado.";
                        // Limpar campos
                        $nota_numero = '';
                        $nota_valor = '';
                        // Manter o empenho selecionado
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $error = "Erro ao cadastrar nota fiscal. Tente novamente. Detalhes: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Buscar todos os empenhos para o select
$sql_empenhos = "SELECT numero, saldo FROM empenhos_insumos WHERE status = 'Aberto' ORDER BY numero ASC";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$empenhos = $stmt_empenhos->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as notas fiscais cadastradas
$sql_notas = "SELECT nota_numero, nota_valor, empenho_numero, fornecedor, cnpj, saldo
              FROM notas_fiscais 
              ORDER BY nota_numero ASC";
$stmt_notas = $pdo->prepare($sql_notas);
$stmt_notas->execute();
$notas_fiscais = $stmt_notas->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Gerenciamento de Notas Fiscais</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <?php require_once 'menu_empenhos.php'; ?>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Cadastrar Nova Nota Fiscal</h3>
        </div>
        <div class="card-body">
            <form action="nota_fiscal_add.php" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nota_numero">Número da Nota Fiscal:</label>
                            <input type="text" class="form-control" id="nota_numero" name="nota_numero" value="<?php echo isset($nota_numero) ? htmlspecialchars($nota_numero) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nota_valor">Valor da Nota Fiscal:</label>
                            <input type="number" class="form-control" id="nota_valor" name="nota_valor" step="0.01" min="0" value="<?php echo isset($nota_valor) ? htmlspecialchars($nota_valor) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="empenho_numero">Empenho:</label>
                            <select class="form-control" id="empenho_numero" name="empenho_numero" required>
                                <option value="">Selecione um empenho</option>
                                <?php foreach($empenhos as $empenho): ?>
                                    <option value="<?php echo htmlspecialchars($empenho['numero']); ?>" <?php echo (isset($empenho_numero) && $empenho_numero == $empenho['numero']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($empenho['numero'] . ' (Saldo: R$ ' . number_format($empenho['saldo'], 2, ',', '.')); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fornecedor">Fornecedor:</label>
                            <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?php echo isset($fornecedor) ? htmlspecialchars($fornecedor) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cnpj">CNPJ do Fornecedor:</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?php echo isset($cnpj) ? htmlspecialchars($cnpj) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="cadastrar_nota_fiscal" class="btn btn-primary">Cadastrar Nota Fiscal</button>
                <a href="empenhos_index.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
    
    <?php if(!empty($notas_fiscais)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Notas Fiscais Cadastradas</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Valor</th>
                                <th>Saldo</th>
                                <th>Empenho</th>
                                <th>Fornecedor</th>
                                <th>CNPJ</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($notas_fiscais as $nota): ?>
                                <tr>
                                    <td><a href="nota_fiscal_detalhes.php?nota=<?php echo $nota['nota_numero']; ?>" class="text-primary"><?php echo htmlspecialchars($nota['nota_numero']); ?></a></td>
                                    <td>R$ <?php echo number_format($nota['nota_valor'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($nota['saldo'] ?? 0, 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($nota['empenho_numero']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['fornecedor']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['cnpj'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="nota_fiscal_edit.php?nota=<?php echo $nota['nota_numero']; ?>" class="btn btn-xs btn-warning" title="Editar Nota Fiscal"><i class="fas fa-edit"></i></a>
                                        <a href="nota_fiscal_delete.php?nota=<?php echo $nota['nota_numero']; ?>" class="btn btn-xs btn-danger" title="Excluir Nota Fiscal" onclick="return confirm('Tem certeza que deseja excluir esta nota fiscal?')"><i class="fas fa-trash"></i></a>
                                        <a href="entrada_material.php?nota=<?php echo $nota['nota_numero']; ?>" class="btn btn-xs btn-success" title="Adicionar Material"><i class="fas fa-plus"></i></a>
                                        <a href="import_materiais_csv.php?nota=<?php echo $nota['nota_numero']; ?>" class="btn btn-xs btn-primary" title="Importar Materiais via CSV"><i class="fas fa-file-csv"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
