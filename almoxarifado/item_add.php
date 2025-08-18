<?php
// Inicia a sessão PHP para gerenciar o estado do usuário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o cabeçalho HTML padrão e a conexão com o banco de dados
require_once '../includes/header.php';
require_once '../config/db.php';

// Verifica se o usuário tem permissão para adicionar itens (Administrador)
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Buscar locais e responsáveis para os dropdowns
$locais = $pdo->query("SELECT id, nome FROM locais ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$responsaveis = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Inicializa variáveis para os campos do formulário e mensagens de erro
$nome = $local_id = $responsavel_id = $estado = "";
$nome_err = $local_id_err = $responsavel_id_err = $estado_err = "";

// Processa o formulário quando ele é submetido (método POST)
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validação e sanitização dos campos do formulário
    if(empty(trim($_POST["nome"]))) {
        $nome_err = "Por favor, insira o nome do item.";
    } else {
        $nome = trim($_POST["nome"]);
    }
    
    if(empty(trim($_POST["local_id"]))) {
        $local_id_err = "Por favor, selecione o local.";
    } else {
        $local_id = trim($_POST["local_id"]);
    }

    if(empty(trim($_POST["responsavel_id"]))) {
        $responsavel_id_err = "Por favor, selecione o responsável.";
    } else {
        $responsavel_id = trim($_POST["responsavel_id"]);
    }

    if(empty(trim($_POST["estado"]))) {
        $estado_err = "Por favor, insira o estado do item.";
    } else {
        $estado = trim($_POST["estado"]);
    }
    
    // Se não houver erros de validação, insere o item no banco de dados
    if(empty($nome_err) && empty($local_id_err) && empty($responsavel_id_err) && empty($estado_err)){
        // Verificar se a tabela almoxarifado_materiais existe e tem a estrutura correta
        try {
            // Inserir na tabela correta
            $sql = "INSERT INTO almoxarifado_materiais (nome, local_id, responsavel_id, estado, status_confirmacao) VALUES (?, ?, ?, ?, ?)";
            
            if($stmt = $pdo->prepare($sql)){
                $status_confirmacao = ($responsavel_id == $_SESSION['id']) ? 'Confirmado' : 'Pendente';
                $stmt->execute([$nome, $local_id, $responsavel_id, $estado, $status_confirmacao]);
                $novo_item_id = $pdo->lastInsertId();

                // Notifica o usuário responsável se não for o próprio admin que está adicionando
                if ($responsavel_id != $_SESSION['id']) {
                    $mensagem_notificacao = "Um novo item de almoxarifado foi atribuído a você: " . htmlspecialchars($nome);
                    $sql_notificacao = "INSERT INTO notificacoes (usuario_id, administrador_id, tipo, mensagem, status) VALUES (?, ?, ?, ?, 'Pendente')";
                    $stmt_notificacao = $pdo->prepare($sql_notificacao);
                    $stmt_notificacao->execute([$responsavel_id, $_SESSION['id'], 'atribuicao_almoxarifado', $mensagem_notificacao]);

                    // Detalhe do item na notificação
                    $notificacao_id = $pdo->lastInsertId();
                    $sql_insert_detalhes = "INSERT INTO notificacoes_almoxarifado_detalhes (notificacao_id, item_id, status_item) VALUES (?, ?, ?)";
                    $stmt_insert_detalhes = $pdo->prepare($sql_insert_detalhes);
                    $stmt_insert_detalhes->execute([$notificacao_id, $novo_item_id, $status_confirmacao]);
                }

                header("location: itens.php");
                exit();
            }
        } catch(PDOException $e){
            echo "<div class='alert alert-danger'>Erro ao inserir item: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2><i class="fas fa-plus-circle"></i> Adicionar Novo Item ao Almoxarifado</h2>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div>
        <label for="nome">Nome</label>
        <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
        <span class="help-block"><?php echo $nome_err; ?></span>
    </div>
    <div>
        <label for="local_id">Local</label>
        <select name="local_id" id="local_id" required>
            <option value="">Selecione um local</option>
            <?php foreach ($locais as $local): ?>
                <option value="<?php echo $local['id']; ?>" <?php echo ($local_id == $local['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($local['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="help-block"><?php echo $local_id_err; ?></span>
    </div>
    <div>
        <label for="responsavel_id">Responsável</label>
        <select name="responsavel_id" id="responsavel_id" required>
            <option value="">Selecione um responsável</option>
            <?php foreach ($responsaveis as $responsavel): ?>
                <option value="<?php echo $responsavel['id']; ?>" <?php echo ($responsavel_id == $responsavel['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($responsavel['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="help-block"><?php echo $responsavel_id_err; ?></span>
    </div>
    <div>
        <label for="estado">Estado</label>
        <input type="text" name="estado" id="estado" value="<?php echo htmlspecialchars($estado); ?>" required>
        <span class="help-block"><?php echo $estado_err; ?></span>
    </div>
    <div>
        <input type="submit" value="Adicionar Item">
        <a href="itens.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
require_once '../includes/footer.php';
?>