<?php
require_once '../../includes/header.php';
require_once '../../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$message = '';
$error = '';

// Inicializar variáveis
$nota_numero = '';
$nota_valor = '';
$empenho_numero = '';

// Se foi passado um empenho via GET, preencher o campo
if(isset($_GET['empenho']) && !empty($_GET['empenho'])){
    $empenho_numero = $_GET['empenho'];
}

// Processar formulário de cadastro
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_nota_fiscal'])){
    $nota_numero = trim($_POST["nota_numero"]);
    $nota_valor = trim($_POST["nota_valor"]);
    $empenho_numero = trim($_POST["empenho_numero"]);
    
    // Validação
    if(empty($nota_numero) || empty($nota_valor) || empty($empenho_numero)){
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
                // Inserir nova nota fiscal
                $sql_insert = "INSERT INTO notas_fiscais (nota_numero, nota_valor, empenho_numero) VALUES (?, ?, ?)";
                $stmt_insert = $pdo->prepare($sql_insert);
                
                if($stmt_insert->execute([$nota_numero, $nota_valor, $empenho_numero])){
                    $message = "Nota fiscal cadastrada com sucesso!";
                    // Limpar campos
                    $nota_numero = '';
                    $nota_valor = '';
                    // Manter o empenho selecionado
                } else {
                    $error = "Erro ao cadastrar nota fiscal. Tente novamente.";
                }
            }
        }
    }
}

// Buscar todos os empenhos para o select
$sql_empenhos = "SELECT numero FROM empenhos_insumos ORDER BY numero ASC";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$empenhos = $stmt_empenhos->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as notas fiscais cadastradas com seus empenhos
$sql_notas = "SELECT nf.*, ei.fornecedor 
              FROM notas_fiscais nf 
              JOIN empenhos_insumos ei ON nf.empenho_numero = ei.numero 
              ORDER BY nf.nota_numero ASC";
$stmt_notas = $pdo->prepare($sql_notas);
$stmt_notas->execute();
$notas_fiscais = $stmt_notas->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2>Gerenciamento de Notas Fiscais</h2>
    
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
                                        <?php echo htmlspecialchars($empenho['numero']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="cadastrar_nota_fiscal" class="btn btn-primary">Cadastrar Nota Fiscal</button>
                <a href="index.php" class="btn btn-secondary">Voltar</a>
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
                                <th>Empenho</th>
                                <th>Fornecedor</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($notas_fiscais as $nota): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($nota['nota_numero']); ?></td>
                                    <td>R$ <?php echo number_format($nota['nota_valor'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($nota['empenho_numero']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['fornecedor']); ?></td>
                                    <td>
                                        <a href="material_add.php?nota=<?php echo $nota['nota_numero']; ?>" class="btn btn-sm btn-success">Adicionar Materiais</a>
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
require_once '../../includes/footer.php';
?>