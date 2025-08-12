<?php
// Inicia a sessão PHP para gerenciar o estado do usuário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui o cabeçalho HTML padrão e a conexão com o banco de dados
require_once 'includes/header.php';
require_once 'config/db.php';

// Verifica se o usuário tem permissão para adicionar itens (Administrador ou Gestor)
if($_SESSION["permissao"] != 'Administrador' && $_SESSION["permissao"] != 'Gestor'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Inicializa variáveis para os campos do formulário e mensagens de erro
$nome = $patrimonio_novo = $patrimonio_secundario = $local_id = $responsavel_id = $estado = $observacao = "";
$nome_err = $patrimonio_novo_err = $local_id_err = $responsavel_id_err = $estado_err = "";

// Variável de controle para gestores sem local associado
$is_gestor_sem_local = false;

// Lógica específica para usuários com permissão de 'Gestor'
if($_SESSION["permissao"] == 'Gestor'){
    $usuario_logado_id = $_SESSION['id'];
    // Verifica se o gestor já tem algum item cadastrado sob sua responsabilidade
    $check_itens_sql = "SELECT COUNT(*) FROM itens WHERE responsavel_id = ?";
    if($stmt_check_itens = mysqli_prepare($link, $check_itens_sql)){
        mysqli_stmt_bind_param($stmt_check_itens, "i", $usuario_logado_id);
        mysqli_stmt_execute($stmt_check_itens);
        mysqli_stmt_bind_result($stmt_check_itens, $count_itens);
        mysqli_stmt_fetch($stmt_check_itens);
        mysqli_stmt_close($stmt_check_itens);

        // Se o gestor não tem itens, ele precisa selecionar um local primeiro
        if($count_itens == 0){
            $is_gestor_sem_local = true;
            // Obtém todos os locais disponíveis para que ele possa escolher
            $locais_sql = "SELECT id, nome FROM locais ORDER BY nome ASC";
            $locais_result = mysqli_query($link, $locais_sql);
        } else {
            // Se o gestor já tem itens, obtém apenas os locais associados a ele
            $locais_sql = "SELECT DISTINCT l.id, l.nome FROM locais l JOIN itens i ON l.id = i.local_id WHERE i.responsavel_id = ? ORDER BY l.nome ASC";
            if($stmt_locais = mysqli_prepare($link, $locais_sql)){
                mysqli_stmt_bind_param($stmt_locais, "i", $usuario_logado_id);
                mysqli_stmt_execute($stmt_locais);
                $locais_result = mysqli_stmt_get_result($stmt_locais);
            } else {
                echo "Erro ao preparar a consulta de locais: " . mysqli_error($link);
                $locais_result = false; // Para evitar erro no loop
            }
        }
    } else {
        echo "Erro ao verificar itens do gestor: " . mysqli_error($link);
        $locais_result = false; // Para evitar erro no loop
    }
} else { // Lógica para Administradores: obtém todos os locais
    $locais_sql = "SELECT id, nome FROM locais ORDER BY nome ASC";
    $locais_result = mysqli_query($link, $locais_sql);
}

// Obtém usuários disponíveis para serem responsáveis (apenas para Administrador)
if($_SESSION["permissao"] == 'Administrador'){
    $usuarios_sql = "SELECT id, nome FROM usuarios WHERE status = 'aprovado' ORDER BY nome ASC";
    $usuarios_result = mysqli_query($link, $usuarios_sql);
} else { // Para Gestores, o próprio gestor é o responsável
    $responsavel_id = $_SESSION['id']; // O próprio gestor é o responsável
    $usuarios_result = false; // Não precisa de um resultado de query para o dropdown de usuários
}

// Processa o formulário quando ele é submetido (método POST)
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Lógica para gestores que precisam selecionar um local primeiro
    if($is_gestor_sem_local && isset($_POST['selecionar_local_primeiro'])){
        $local_id = $_POST['local_id'];
        if(empty($local_id)){
            $local_id_err = "Por favor, selecione um local.";
        } else {
            // Redireciona para a própria página com o local_id selecionado na URL
            // Isso fará com que o formulário completo seja exibido com o local pré-selecionado
            header("location: item_add.php?local_id=" . $local_id);
            exit();
        }
    } else {
        // Validação e sanitização dos campos do formulário
        if(empty(trim($_POST["nome"]))){
            $nome_err = "Por favor, insira o nome do item.";
        } else {
            $nome = trim($_POST["nome"]);
        }

        if(empty(trim($_POST["patrimonio_novo"]))){
            $patrimonio_novo_err = "Por favor, insira o patrimônio.";
        } else {
            $patrimonio_novo = trim($_POST["patrimonio_novo"]);
        }

        $patrimonio_secundario = trim($_POST["patrimonio_secundario"]);
        $estado = $_POST["estado"];
        $observacao = trim($_POST["observacao"]);

        // Validação do local_id
        $local_id = $_POST['local_id'];
        if(empty($local_id)){
            $local_id_err = "Por favor, selecione um local.";
        } else {
            // Validação adicional para Gestores: verificar se o local é permitido
            if($_SESSION["permissao"] == 'Gestor' && !$is_gestor_sem_local){ // Apenas se o gestor já tem itens
                $usuario_logado_id = $_SESSION['id'];
                $check_local_sql = "SELECT COUNT(*) FROM itens WHERE responsavel_id = ? AND local_id = ?";
                if($stmt_check_local = mysqli_prepare($link, $check_local_sql)){
                    mysqli_stmt_bind_param($stmt_check_local, "ii", $usuario_logado_id, $local_id);
                    mysqli_stmt_execute($stmt_check_local);
                    mysqli_stmt_bind_result($stmt_check_local, $count);
                    mysqli_stmt_fetch($stmt_check_local);
                    mysqli_stmt_close($stmt_check_local);
                    if($count == 0){
                        $local_id_err = "Você não tem permissão para adicionar itens neste local.";
                    }
                } else {
                    echo "Erro ao verificar permissão do local: " . mysqli_error($link);
                }
            }
        }

        // Define o responsavel_id com base na permissão do usuário logado
        if($_SESSION["permissao"] == 'Gestor'){
            $responsavel_id = $_SESSION['id'];
        } else { // Administrador
            $responsavel_id = $_POST['responsavel_id'];
            if(empty($responsavel_id)){
                $responsavel_id_err = "Por favor, selecione um responsável.";
            }
        }

        // Se não houver erros de validação, insere o item no banco de dados
        if(empty($nome_err) && empty($patrimonio_novo_err) && empty($local_id_err) && empty($responsavel_id_err)){
            // Prepara a consulta SQL para inserir um novo item, incluindo o status_confirmacao como 'Pendente'
            $sql = "INSERT INTO itens (nome, patrimonio_novo, patrimonio_secundario, local_id, responsavel_id, estado, observacao, data_cadastro, status_confirmacao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Pendente')";

            if($stmt = mysqli_prepare($link, $sql)){
                // Vincula os parâmetros à consulta preparada
                mysqli_stmt_bind_param($stmt, "sssiiss", $nome, $patrimonio_novo, $patrimonio_secundario, $local_id, $responsavel_id, $estado, $observacao);

                // Executa a consulta de inserção
                if(mysqli_stmt_execute($stmt)){
                    $novo_item_id = mysqli_insert_id($link); // Obtém o ID do item recém-adicionado

                    // --- Lógica de Notificação --- //
                    // Prepara os dados para a notificação
                    $admin_id = $_SESSION['id']; // O administrador que está adicionando o item
                    $mensagem_notificacao = "Você recebeu um novo item: " . htmlspecialchars($nome) . " (Patrimônio: " . htmlspecialchars($patrimonio_novo) . "). Por favor, confirme o recebimento.";

                    // Prepara e executa a inserção da notificação usando PDO
                    $sql_notificacao = "INSERT INTO notificacoes (usuario_id, administrador_id, tipo, mensagem, status) VALUES (?, ?, ?, ?, 'Pendente')";
                    $stmt_notificacao = $pdo->prepare($sql_notificacao);
                    $stmt_notificacao->execute([$responsavel_id, $admin_id, 'atribuicao', $mensagem_notificacao]);

                    // Obtém o ID da notificação recém-criada
                    $notificacao_id = $pdo->lastInsertId();

                    // Insere o detalhe do item na nova tabela
                    $sql_insert_detalhes = "INSERT INTO notificacoes_itens_detalhes (notificacao_id, item_id, status_item) VALUES (?, ?, 'Pendente')";
                    $stmt_insert_detalhes = $pdo->prepare($sql_insert_detalhes);
                    $stmt_insert_detalhes->execute([$notificacao_id, $novo_item_id]);
                    // --- Fim Lógica de Notificação --- //

                    // Redireciona para a página de listagem de itens após o sucesso
                    header("location: itens.php");
                    exit();
                } else{
                    echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde. " . mysqli_error($link);
                }
            } else {
                echo "Erro ao preparar a consulta de inserção: " . mysqli_error($link);
            }
        }
    }
}

// Se o local_id foi passado via GET (após o gestor selecionar o local na primeira etapa)
if(isset($_GET['local_id']) && $_SESSION["permissao"] == 'Gestor' && $is_gestor_sem_local){
    $local_id = $_GET['local_id'];
}

?>

<h2>Adicionar Novo Item</h2>

<?php if($is_gestor_sem_local && empty($local_id)): ?>
    <div class="alert alert-info">
        Você ainda não possui nenhum item cadastrado. Para começar, por favor, selecione um local para associar ao seu primeiro item.
    </div>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div>
            <label>Selecione um Local</label>
            <select name="local_id" required>
                <option value="">Selecione um Local</option>
                <?php if($locais_result): ?>
                    <?php while($local = mysqli_fetch_assoc($locais_result)): ?>
                        <option value="<?php echo $local['id']; ?>"><?php echo $local['nome']; ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <span class="help-block"><?php echo $local_id_err; ?></span>
        </div>
        <div>
            <input type="hidden" name="selecionar_local_primeiro" value="1">
            <input type="submit" value="Continuar">
        </div>
    </form>
    <div class="alert alert-info" style="margin-top: 20px;">
        Não encontrou seu local? <a href="local_request.php" class="btn-custom">Solicitar Novo Local</a>
    </div>
<?php else: ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div>
            <label>Nome</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
            <span class="help-block"><?php echo $nome_err; ?></span>
        </div>
        <div>
            <label>Patrimônio Novo</label>
            <input type="text" name="patrimonio_novo" value="<?php echo htmlspecialchars($patrimonio_novo); ?>" required>
            <span class="help-block"><?php echo $patrimonio_novo_err; ?></span>
        </div>
        <div>
            <label>Patrimônio Secundário (Opcional)</label>
            <input type="text" name="patrimonio_secundario" value="<?php echo htmlspecialchars($patrimonio_secundario); ?>">
        </div>
        <div>
            <label>Local</label>
            <select name="local_id" required <?php echo ($is_gestor_sem_local && !empty($local_id)) ? 'disabled' : ''; ?>>
                <option value="">Selecione um Local</option>
                <?php if($locais_result): ?>
                    <?php mysqli_data_seek($locais_result, 0); // Reset pointer for re-use ?>
                    <?php while($local = mysqli_fetch_assoc($locais_result)): ?>
                        <option value="<?php echo $local['id']; ?>" <?php echo ($local['id'] == $local_id) ? 'selected' : ''; ?>><?php echo $local['nome']; ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <?php if($is_gestor_sem_local && !empty($local_id)): ?>
                <input type="hidden" name="local_id" value="<?php echo htmlspecialchars($local_id); ?>">
            <?php endif; ?>
            <span class="help-block"><?php echo $local_id_err; ?></span>
        </div>
        <div>
            <label>Responsável</label>
            <?php if($_SESSION["permissao"] == 'Gestor'): ?>
                <input type="text" value="<?php echo $_SESSION['nome']; ?>" disabled>
                <input type="hidden" name="responsavel_id" value="<?php echo $_SESSION['id']; ?>">
            <?php else: // Administrador ?>
                <select name="responsavel_id" required>
                    <option value="">Selecione um Responsável</option>
                    <?php if($usuarios_result): ?>
                        <?php while($usuario = mysqli_fetch_assoc($usuarios_result)): ?>
                            <option value="<?php echo $usuario['id']; ?>" <?php echo ($usuario['id'] == $responsavel_id) ? 'selected' : ''; ?>><?php echo $usuario['nome']; ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <span class="help-block"><?php echo $responsavel_id_err; ?></span>
            <?php endif; ?>
        </div>
        <div>
            <label>Estado</label>
            <select name="estado" required>
                <option value="">Selecione o Estado</option>
                <option value="Bom" <?php echo ($estado == 'Bom') ? 'selected' : ''; ?>>Bom</option>
                <option value="Razoável" <?php echo ($estado == 'Razoável') ? 'selected' : ''; ?>>Razoável</option>
                <option value="Inservível" <?php echo ($estado == 'Inservível') ? 'selected' : ''; ?>>Inservível</option>
            </select>
            <span class="help-block"><?php echo $estado_err; ?></span>
        </div>
        <div>
            <label>Observação (Opcional)</label>
            <textarea name="observacao"><?php echo htmlspecialchars($observacao); ?></textarea>
        </div>
        <div>
            <input type="submit" value="Adicionar">
            <a href="itens.php" class="btn-custom">Cancelar</a>
        </div>
    </form>
<?php endif; ?>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>