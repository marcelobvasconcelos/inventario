<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$user_data = null;

// Apenas usuários logados podem editar seu perfil
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$usuario_id = $_SESSION['id'];
$nova_senha = $confirm_senha = "";
$senha_err = $confirm_senha_err = "";

// Buscar dados do usuário para exibição
$sql_user = "SELECT nome, email FROM usuarios WHERE id = ?";
if($stmt_user = mysqli_prepare($link, $sql_user)){
    mysqli_stmt_bind_param($stmt_user, "i", $usuario_id);
    if(mysqli_stmt_execute($stmt_user)){
        $result_user = mysqli_stmt_get_result($stmt_user);
        $user_data = mysqli_fetch_assoc($result_user);
    }
    mysqli_stmt_close($stmt_user);
}


if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Valida a nova senha
    if(empty(trim($_POST["nova_senha"]))){ 
        $senha_err = "Por favor, insira a nova senha.";     
    } elseif(strlen(trim($_POST["nova_senha"])) < 6){
        $senha_err = "A senha deve ter no mínimo 6 caracteres.";
    } else{
        $nova_senha = trim($_POST["nova_senha"]);
    }
    
    // Valida a confirmação da senha
    if(empty(trim($_POST["confirm_senha"]))){ 
        $confirm_senha_err = "Por favor, confirme a senha.";     
    } else{
        $confirm_senha = trim($_POST["confirm_senha"]);
        if(empty($senha_err) && ($nova_senha != $confirm_senha)){
            $confirm_senha_err = "As senhas não coincidem.";
        }
    }
        
    // Se não houver erros, atualiza a senha
    if(empty($senha_err) && empty($confirm_senha_err)){
        $sql_update = "UPDATE usuarios SET senha = ? WHERE id = ?";
        
        if($stmt_update = mysqli_prepare($link, $sql_update)){
            mysqli_stmt_bind_param($stmt_update, "si", $param_password, $usuario_id);
            
            $param_password = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            if(mysqli_stmt_execute($stmt_update)){
                session_destroy();
                header("location: login.php?status=senha_alterada");
                exit();
            } else{
                echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }

            mysqli_stmt_close($stmt_update);
        }
    }
}

mysqli_close($link);
?>

<style>
.profile-container { max-width: 700px; margin: auto; }
.profile-section { background: #fff; padding: 20px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #b8daff; }
.profile-section h3 { margin-top: 0; color: #0056b3; }
.profile-data p { margin: 10px 0; font-size: 1.1em; }
.profile-data strong { color: #333; }
.invalid-feedback { color: #721c24; font-size: 0.9em; }
.is-invalid { border-color: #f5c6cb !important; }
.form-info { margin-top: 20px; padding: 15px; background-color: #e7f3ff; border: 1px solid #b8daff; border-radius: 4px; font-size: 0.9em; }
</style>

<div class="profile-container">
    <h2>Meu Perfil</h2>

    <div class="profile-section">
        <h3>Dados do Usuário</h3>
        <div class="profile-data">
            <?php if ($user_data): ?>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
            <?php else: ?>
                <p>Não foi possível carregar os dados do usuário.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-section">
        <h3>Alterar Senha</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>Nova Senha</label>
                <input type="password" name="nova_senha" class="<?php echo (!empty($senha_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $senha_err; ?></span>
            </div>
            <div>
                <label>Confirmar Nova Senha</label>
                <input type="password" name="confirm_senha" class="<?php echo (!empty($confirm_senha_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $confirm_senha_err; ?></span>
            </div>
            <div>
                <input type="submit" class="btn-custom" value="Alterar Senha">
            </div>
        </form>
        <div class="form-info">
            <p><strong>Nota de Segurança:</strong> Sua senha deve ter no mínimo 6 caracteres. Para uma senha mais forte, use uma combinação de letras maiúsculas, minúsculas, números e símbolos.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>