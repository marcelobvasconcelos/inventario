<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado.</div>";
    require_once '../includes/footer.php';
    exit;
}

$message = '';
$error = '';

$nota_numero = isset($_GET['nota']) ? $_GET['nota'] : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';

// Processar upload do CSV
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['importar_csv'])){
    $categoria = trim($_POST["categoria"]);

    if(empty($categoria)){
        $error = "Categoria é obrigatória.";
    } elseif(!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != UPLOAD_ERR_OK){
        $error = "Arquivo CSV é obrigatório.";
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");

        if($handle === false){
            $error = "Erro ao abrir o arquivo CSV.";
        } else {
            $pdo->beginTransaction();
            $success_count = 0;
            $error_count = 0;
            $line = 1;

            try {
                // Pular cabeçalho
                fgetcsv($handle, 1000, ";");

                while(($data = fgetcsv($handle, 1000, ";")) !== false){
                    $line++;
                    if(count($data) < 5){
                        $error_count++;
                        continue;
                    }

                    // Processar dados, converter codificação e limitar tamanho
                    $nome = substr(iconv('Windows-1252', 'UTF-8//IGNORE', trim($data[0])), 0, 100);
                    $descricao = substr(iconv('Windows-1252', 'UTF-8//IGNORE', trim($data[1])), 0, 255);
                    $unidade_medida = substr(iconv('Windows-1252', 'UTF-8//IGNORE', trim($data[2])), 0, 20);
                    // Ignorar estoque_inicial - materiais sempre começam com estoque zero
                    $quantidade_maxima = trim($data[4]);

                    if(empty($nome) || empty($unidade_medida)){
                        $error_count++;
                        continue;
                    }

                    // Gerar código
                    $last_id = $pdo->query("SELECT MAX(id) FROM almoxarifado_materiais")->fetchColumn();
                    $new_id = $last_id + 1;
                    $codigo = 'MAT-' . str_pad($new_id, 5, '0', STR_PAD_LEFT);

                    // Inserir material com estoque zero
                    $sql_insert = "INSERT INTO almoxarifado_materiais (codigo, nome, descricao, unidade_medida, estoque_atual, valor_unitario, quantidade_maxima_requisicao, categoria, data_criacao, usuario_criacao) VALUES (?, ?, ?, ?, 0, 0, ?, ?, NOW(), ?)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->execute([$codigo, $nome, $descricao, $unidade_medida, $quantidade_maxima, $categoria, $_SESSION['id']]);

                    $success_count++;
                }

                $pdo->commit();
                $message = "Importação concluída! $success_count materiais importados com sucesso.";
                if($error_count > 0){
                    $message .= " $error_count linhas com erro foram ignoradas.";
                }

            } catch (Exception $e) {
                $pdo->rollback();
                $error = "Erro durante a importação: " . $e->getMessage();
            }

            fclose($handle);
        }
    }
}

// Buscar notas fiscais
$sql_notas = "SELECT nota_numero FROM notas_fiscais ORDER BY nota_numero ASC";
$stmt_notas = $pdo->prepare($sql_notas);
$stmt_notas->execute();
$notas = $stmt_notas->fetchAll(PDO::FETCH_COLUMN);

// Buscar categorias
$sql_categorias = "SELECT CONCAT(COALESCE(numero, CAST(id AS CHAR)), ' - ', descricao) as categoria FROM almoxarifado_categorias ORDER BY id ASC";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Importar Materiais via CSV</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Importar Materiais</h3>
        </div>
        <div class="card-body">
            <form action="import_materiais_csv.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="categoria">Categoria:</label>
                    <select class="form-control" id="categoria" name="categoria" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($categoria == $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="csv_file">Arquivo CSV:</label>
                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                    <small class="form-text text-muted">
                        O arquivo deve ter as colunas: nome;descrição;unidade_medida;campo_ignorado;quantidade_maxima_requisicao<br>
                        <strong>Limites:</strong> Nome (100 caracteres), Descrição (255 caracteres), Unidade (20 caracteres)<br>
                        <strong>Nota:</strong> Materiais serão cadastrados com estoque zero. Use "Entrada de Material" para adicionar estoque.<br>
                        <strong>Codificação:</strong> Use CSV padrão (ANSI/Windows-1252) ou UTF-8 sem BOM.
                    </small>
                </div>

                <button type="submit" name="importar_csv" class="btn btn-primary">Importar Materiais</button>
                <a href="nota_fiscal_add.php" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>