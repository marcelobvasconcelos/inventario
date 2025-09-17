<?php
require_once "config/db.php";
$nome = $email = $senha = "";
$nome_err = $email_err = $senha_err = "";
$registro_sucesso = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Valida nome
    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira seu nome.";
    } else {
        $nome = trim($_POST["nome"]);
    }

    // Valida email
    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, insira um email.";
    } else {
        // Verifica se o email já existe
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "Este email já está em uso.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Valida senha
    if(empty(trim($_POST["senha"]))){
        $senha_err = "Por favor, insira uma senha.";     
    } elseif(strlen(trim($_POST["senha"])) < 6){
        $senha_err = "A senha deve ter no mínimo 6 caracteres.";
    } else {
        $senha = trim($_POST["senha"]);
    }

    // Se não houver erros, insere no banco de dados
    if(empty($nome_err) && empty($email_err) && empty($senha_err)){
        // Obter o ID do perfil 'Visualizador'
        $permissao_id = null;
        $sql_perfil = "SELECT id FROM perfis WHERE nome = 'Visualizador'";
        if($result_perfil = mysqli_query($link, $sql_perfil)){
            $row_perfil = mysqli_fetch_assoc($result_perfil);
            if($row_perfil) {
                $permissao_id = $row_perfil['id'];
            } else {
                // Fallback caso o perfil 'Visualizador' não exista
                $permissao_id = 3; // Assumindo que 3 é o ID do Visualizador
            }
        }

        $sql = "INSERT INTO usuarios (nome, email, senha, permissao_id, status) VALUES (?, ?, ?, ?, 'pendente')";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssi", $param_nome, $param_email, $param_senha, $permissao_id);
            
            $param_nome = $nome;
            $param_email = $email;
            $param_senha = password_hash($senha, PASSWORD_DEFAULT);
            
            if(mysqli_stmt_execute($stmt)){
                $registro_sucesso = "Cadastro realizado com sucesso! Sua conta está pendente de aprovação por um administrador.";
                // Limpa os campos após o sucesso
                $_POST = array();
                $nome = $email = $senha = "";
            } else {
                echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar</title>
    <link rel="stylesheet" href="/inventario/css/style.css">
</head>
<body>
    <div class="registro-wrapper">
        <h2>Registrar Nova Conta</h2>
        <p>Preencha o formulário para criar uma conta. Sua conta precisará ser aprovada por um administrador.</p>

        <?php 
        if(!empty($registro_sucesso)){
            echo '<div class="alert alert-success">' . $registro_sucesso . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" name="nome" class="<?php echo (!empty($nome_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nome; ?>">
                <span class="invalid-feedback"><?php echo $nome_err; ?></span>
            </div>
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
                <input type="submit" class="btn btn-primary" value="Registrar">
            </div>
            <p>Já tem uma conta? <a href="login.php">Faça o login aqui</a>.</p>
        </form>
    </div>
</body>
</html>
