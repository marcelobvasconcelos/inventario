<?php
session_start();
 
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
 
require_once "config/db.php";
 
$email = $senha = "";
$email_err = $senha_err = $login_err = "";
 
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, insira o email.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    if(empty(trim($_POST["senha"]))){
        $senha_err = "Por favor, insira a senha.";
    } else{
        $senha = trim($_POST["senha"]);
    }
    
    if(empty($email_err) && empty($senha_err)){
        $sql = "SELECT u.id, u.nome, u.email, u.senha, p.nome as perfil_nome, u.status FROM usuarios u JOIN perfis p ON u.permissao_id = p.id WHERE u.email = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            $param_email = $email;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $nome, $email, $hashed_senha, $permissao, $status);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($senha, $hashed_senha)){
                            // Verifica o status do usuário
                            if($status == 'aprovado'){
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["nome"] = $nome;
                                $_SESSION["permissao"] = $permissao;
                                
                                // Verificar se o usuário está usando uma senha temporária
                                $sql_senha_temp = "SELECT senha_temporaria FROM usuarios WHERE id = ?";
                                if($stmt_senha_temp = mysqli_prepare($link, $sql_senha_temp)){
                                    mysqli_stmt_bind_param($stmt_senha_temp, "i", $id);
                                    mysqli_stmt_execute($stmt_senha_temp);
                                    mysqli_stmt_bind_result($stmt_senha_temp, $senha_temporaria);
                                    mysqli_stmt_fetch($stmt_senha_temp);
                                    mysqli_stmt_close($stmt_senha_temp);
                                    
                                    if($senha_temporaria == 1){
                                        // Redirecionar para a página de edição de perfil
                                        header("location: usuario_perfil.php?senha_temporaria=1");
                                    } else{
                                        // Redirecionar para a página inicial
                                        header("location: index.php");
                                    }
                                }
                            } elseif($status == 'pendente'){
                                $login_err = "Sua conta está pendente de aprovação.";
                            } else {
                                $login_err = "Sua conta foi rejeitada ou desativada.";
                            }
                        } else{
                            $login_err = "Email ou senha inválidos.";
                        }
                    }
                } else{
                    $login_err = "Email ou senha inválidos.";
                }
            } else{
                echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($link);
}
?>
 
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <h2>Login</h2>
        <p>Por favor, preencha os campos para fazer o login.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <span class="invalid-feedback"><?php echo $email_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" class="<?php echo (!empty($senha_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $senha_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p><a href="esqueceu_senha.php">Esqueceu sua senha?</a></p>
            <p>Não tem uma conta? <a href="registro.php">Registre-se agora</a>.</p>
        </form>
    </div>
</body>
</html>