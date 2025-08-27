<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['permissao'] != 'Administrador') {
    header("Location: login.php");
    exit();
}






require 'config/db.php';
require 'includes/header.php';

$mensagem = '';
$erro = '';

// Buscar configurações atuais
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('cabecalho_padrao_pdf', 'logo_path')");
$configuracoes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$cabecalho_padrao = $configuracoes['cabecalho_padrao_pdf'] ?? '';
$logo_path = $configuracoes['logo_path'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Atualizar o cabeçalho
    if (isset($_POST['cabecalho_padrao_pdf'])) {
        $novo_cabecalho = $_POST['cabecalho_padrao_pdf'];
        // Certificar-se de que a string esteja em UTF-8
        $novo_cabecalho = mb_convert_encoding($novo_cabecalho, 'UTF-8', 'auto');
        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'cabecalho_padrao_pdf'");
        if ($stmt->execute([$novo_cabecalho])) {
            $cabecalho_padrao = $novo_cabecalho;
            $mensagem = "Cabeçalho atualizado com sucesso!";
        } else {
            $erro = "Erro ao atualizar o cabeçalho.";
        }
    }

    // Upload da logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $logo = $_FILES['logo'];
        $upload_dir = 'uploads/';
        $extensao = strtolower(pathinfo($logo['name'], PATHINFO_EXTENSION));
        $novo_nome = "logo." . $extensao;
        $caminho_completo = $upload_dir . $novo_nome;

        // Validar tipo de arquivo
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($extensao, $extensoes_permitidas)) {
            if (move_uploaded_file($logo['tmp_name'], $caminho_completo)) {
                // Atualizar caminho no banco de dados
                $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'logo_path'");
                if ($stmt->execute([$caminho_completo])) {
                    $logo_path = $caminho_completo;
                    $mensagem .= " Logo atualizada com sucesso!";
                } else {
                    $erro .= " Erro ao salvar o caminho da logo no banco de dados.";
                }
            } else {
                $erro .= " Erro ao mover o arquivo da logo.";
            }
        } else {
            $erro .= " Formato de arquivo inválido. Apenas JPG, JPEG, PNG e GIF são permitidos.";
        }
    }
}
?>

<div class="container mt-5">
    <h2>Configurações do Relatório PDF</h2>
    <p>Edite o texto padrão do cabeçalho e envie a logo da sua organização.</p>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Logo Atual</h5>
                    <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo Atual" class="img-fluid" style="max-height: 150px;">
                    <?php else: ?>
                        <p>Nenhuma logo definida ou o arquivo não foi encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Alterar Logo</h5>
                    <form action="configuracoes_pdf.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="logo">Selecione a nova imagem da logo:</label>
                            <input type="file" class="form-control-file" id="logo" name="logo" accept="image/png, image/jpeg, image/gif">
                        </div>
                        <button type="submit" class="btn btn-primary mt-2">Enviar Nova Logo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Cabeçalho Padrão do Relatório</h5>
            <form action="configuracoes_pdf.php" method="post">
                <div class="form-group">
                    <label for="cabecalho_padrao_pdf">Texto do cabeçalho:</label>
                    <textarea class="form-control" id="cabecalho_padrao_pdf" name="cabecalho_padrao_pdf" rows="5"><?php echo htmlspecialchars($cabecalho_padrao); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Salvar Cabeçalho</to>
            </form>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
