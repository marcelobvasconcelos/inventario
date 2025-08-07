<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Validação da sessão e permissão
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["permissao"] != 'Administrador'){
    echo "<main><h2>Acesso negado.</h2></main>";
    require_once 'includes/footer.php';
    exit;
}

// Validação do ID do usuário
if(!isset($_GET['id']) || empty(trim($_GET['id'])) || !ctype_digit($_GET['id'])){
    echo "<main><h2>ID de usuário inválido.</h2></main>";
    require_once 'includes/footer.php';
    exit;
}

$id = $_GET['id'];
$nome = $email = $permissao_id = "";
$senha_err = $nome_err = $email_err = "";

// Obter perfis disponíveis
$perfis_sql = "SELECT id, nome FROM perfis ORDER BY nome ASC";
$perfis_result = mysqli_query($link, $perfis_sql);

// Processamento do formulário quando enviado
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validação dos campos (exemplo simples)
    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira um nome.";
    } else {
        $nome = trim($_POST["nome"]);
    }

    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, insira um email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    $permissao_id = $_POST["permissao_id"];

    // Validação da nova senha (se preenchida)
    if(!empty($_POST['nova_senha'])){
        if(strlen($_POST['nova_senha']) < 6){
            $senha_err = "A senha deve ter no mínimo 6 caracteres.";
        } elseif($_POST['nova_senha'] != $_POST['confirmar_nova_senha']){
            $senha_err = "As senhas não coincidem.";
        }
    }

    // Se não houver erros, atualize o banco de dados
    if(empty($nome_err) && empty($email_err) && empty($senha_err)){
        // Prepara a query de atualização
        if(!empty($_POST['nova_senha'])){
            $sql = "UPDATE usuarios SET nome = ?, email = ?, permissao_id = ?, senha = ? WHERE id = ?";
            $hashed_password = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, permissao_id = ? WHERE id = ?";
        }

        if($stmt = mysqli_prepare($link, $sql)){
            // Binda os parâmetros
            if(!empty($_POST['nova_senha'])){
                mysqli_stmt_bind_param($stmt, "ssisi", $nome, $email, $permissao_id, $hashed_password, $id);
            } else {
                mysqli_stmt_bind_param($stmt, "ssii", $nome, $email, $permissao_id, $id);
            }

            if(mysqli_stmt_execute($stmt)){
                header("location: usuarios.php");
                exit();
            } else{
                echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Busca os dados atuais do usuário para preencher o formulário
$sql_user = "SELECT u.nome, u.email, u.permissao_id, p.nome as perfil_nome FROM usuarios u JOIN perfis p ON u.permissao_id = p.id WHERE u.id = ?";
if($stmt_user = mysqli_prepare($link, $sql_user)){
    mysqli_stmt_bind_param($stmt_user, "i", $id);
    if(mysqli_stmt_execute($stmt_user)){
        $result = mysqli_stmt_get_result($stmt_user);
        if(mysqli_num_rows($result) == 1){
            $usuario = mysqli_fetch_assoc($result);
            $nome = $usuario['nome'];
            $email = $usuario['email'];
            $permissao_id = $usuario['permissao_id'];
        } else {
            echo "<main><h2>Usuário não encontrado.</h2></main>";
            require_once 'includes/footer.php';
            exit;
        }
    }
    mysqli_stmt_close($stmt_user);
}

mysqli_close($link);
?>

<main>
    <h2>Editar Usuário</h2>
    <p>Preencha os campos para editar o usuário. Deixe os campos de senha em branco para não alterá-la.</p>

    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>" method="post">
        <div>
            <label>Nome</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome); ?>">
            <span class="help-block"><?php echo $nome_err; ?></span>
        </div>
        <div>
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <span class="help-block"><?php echo $email_err; ?></span>
        </div>
        <div>
            <label>Permissão</label>
            <select name="permissao_id">
                <?php 
                // Resetar o ponteiro do resultado para o início para usar novamente
                mysqli_data_seek($perfis_result, 0);
                while($perfil = mysqli_fetch_assoc($perfis_result)): 
                ?>
                    <option value="<?php echo $perfil['id']; ?>" <?php echo ($perfil['id'] == $permissao_id) ? 'selected' : ''; ?>><?php echo $perfil['nome']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <hr>
        <div>
            <label>Nova Senha</label>
            <input type="password" name="nova_senha" class="form-control">
            <span class="help-block"><?php echo $senha_err; ?></span>
        </div>
        <div>
            <label>Confirmar Nova Senha</label>
            <input type="password" name="confirmar_nova_senha" class="form-control">
        </div>
        <div>
            <input type="submit" class="btn btn-primary" value="Salvar Alterações">
            <a href="usuarios.php" class="btn btn-default">Cancelar</a>
        </div>
    </form>
</main>

<?php
require_once 'includes/footer.php';
?>
