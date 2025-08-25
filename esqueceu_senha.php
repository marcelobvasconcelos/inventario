<?php
// esqueceu_senha.php - Página para solicitar recuperação de senha

require_once "config/db.php";

$nome_completo = $email = "";
$nome_completo_err = $email_err = $mensagem = "";
$solicitacao_enviada = false;

// Processamento do formulário
if($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['solicitacao_enviada'])){
    // Validação do nome completo
    if(empty(trim($_POST["nome_completo"]))){
        $nome_completo_err = "Por favor, insira seu nome completo.";
    } else{
        $nome_completo = trim($_POST["nome_completo"]);
    }
    
    // Validação do email
    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, insira seu email.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    // Verificar se não há erros antes de processar
    if(empty($nome_completo_err) && empty($email_err)){
        // Verificar se existe um usuário com esse email e nome
        $sql = "SELECT id, nome, email FROM usuarios WHERE email = ? AND nome = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ss", $param_email, $param_nome);
            
            $param_email = $email;
            $param_nome = $nome_completo;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $nome, $email_usuario);
                    if(mysqli_stmt_fetch($stmt)){
                        // Inserir solicitação na tabela de solicitações
                        $sql_insert = "INSERT INTO solicitacoes_senha (usuario_id, nome_completo, email) VALUES (?, ?, ?)";
                        
                        if($stmt_insert = mysqli_prepare($link, $sql_insert)){
                            mysqli_stmt_bind_param($stmt_insert, "iss", $id, $nome_completo, $email);
                            
                            if(mysqli_stmt_execute($stmt_insert)){
                                $mensagem = "Sua solicitação foi enviada com sucesso. Um administrador irá processar sua solicitação e enviar uma senha temporária para o seu email. Isso pode levar algumas horas.";
                                $solicitacao_enviada = true;
                            } else{
                                $mensagem = "Oops! Algo deu errado ao enviar sua solicitação. Por favor, tente novamente mais tarde.";
                            }
                            mysqli_stmt_close($stmt_insert);
                        }
                    }
                } else{
                    $mensagem = "Não foi encontrado nenhum usuário com esse email e nome completo.";
                }
            } else{
                $mensagem = "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <h2>Recuperar Senha</h2>
        
        <?php 
        if(!empty($mensagem)){
            echo '<div class="alert alert-info">' . $mensagem . '</div>';
        }
        ?>
        
        <?php if(!$solicitacao_enviada): ?>
            <p>Por favor, preencha os campos abaixo para solicitar a recuperação de sua senha.</p>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Nome Completo</label>
                    <input type="text" name="nome_completo" class="<?php echo (!empty($nome_completo_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nome_completo; ?>">
                    <span class="invalid-feedback"><?php echo $nome_completo_err; ?></span>
                </div>    
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Enviar Solicitação">
                </div>
                <p><a href="login.php">Voltar para o login</a></p>
            </form>
        <?php else: ?>
            <p><a href="login.php">Voltar para o login</a></p>
        <?php endif; ?>
    </div>
</body>
</html>