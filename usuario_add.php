<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SESSION["permissao"] != 'Administrador'){
    echo "Acesso negado.";
    exit;
}

// Obter perfis disponíveis
$perfis_sql = "SELECT id, nome FROM perfis ORDER BY nome ASC";
$perfis_result = mysqli_query($link, $perfis_sql);

$nome = $email = $permissao_id = "";
$nome_err = $email_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validar nome
    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira um nome.";
    } else{
        $nome = trim($_POST["nome"]);
    }
    
    // Validar email
    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, insira um email.";
    } else{
        // Verificar se o email já existe
        $sql_check = "SELECT id FROM usuarios WHERE email = ?";
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            mysqli_stmt_bind_param($stmt_check, "s", $email);
            if(mysqli_stmt_execute($stmt_check)){
                mysqli_stmt_store_result($stmt_check);
                if(mysqli_stmt_num_rows($stmt_check) > 0){
                    $email_err = "Este email já está cadastrado.";
                } else{
                    $email = trim($_POST["email"]);
                }
            }
            mysqli_stmt_close($stmt_check);
        }
    }
    
    $permissao_id = $_POST["permissao_id"];
    
    // Verificar se não há erros antes de inserir no banco de dados
    if(empty($nome_err) && empty($email_err)){
        // Gerar uma senha temporária aleatória
        $senha_temporaria = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nome, email, senha, permissao_id, senha_temporaria) VALUES (?, ?, ?, ?, 1)";
        
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssi", $nome, $email, $senha_hash, $permissao_id);
            
            if(mysqli_stmt_execute($stmt)){
                // Mostrar a senha temporária gerada
                echo "<div class='alert alert-success'>";
                echo "<strong>Usuário criado com sucesso!</strong><br>";
                echo "Senha temporária gerada: <strong>" . $senha_temporaria . "</strong><br>";
                echo "Informe esta senha ao usuário. Ele deverá alterá-la no primeiro acesso.";
                echo "</div>";
                echo "<a href='usuarios.php' class='btn-custom'>Voltar para Usuários</a>";
                require_once 'includes/footer.php';
                exit;
            } else{
                echo "Oops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<h2>Adicionar Novo Usuário</h2>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
                <option value="<?php echo $perfil['id']; ?>" <?php echo (isset($permissao_id) && $perfil['id'] == $permissao_id) ? 'selected' : ''; ?>><?php echo $perfil['nome']; ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <input type="submit" value="Adicionar" class="btn-custom">
        <a href="usuarios.php" class="btn-custom">Cancelar</a>
    </div>
</form>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>