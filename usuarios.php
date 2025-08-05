<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SESSION["permissao"] != 'admin'){
    echo "Acesso negado.";
    exit;
}

// Lógica para aprovar/rejeitar usuários
if(isset($_GET['acao']) && isset($_GET['id'])){
    $acao = $_GET['acao'];
    $usuario_id = $_GET['id'];

    if($acao == 'aprovar'){
        $novo_status = 'aprovado';
    } elseif($acao == 'rejeitar'){
        $novo_status = 'rejeitado';
    }

    if(isset($novo_status)){
        $update_sql = "UPDATE usuarios SET status = ? WHERE id = ?";
        if($stmt = mysqli_prepare($link, $update_sql)){
            mysqli_stmt_bind_param($stmt, "si", $novo_status, $usuario_id);
            if(mysqli_stmt_execute($stmt)){
                header("location: usuarios.php"); // Redireciona para limpar a URL
                exit();
            } else {
                echo "Erro ao atualizar o status do usuário.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$sql = "SELECT id, nome, email, permissao, status FROM usuarios ORDER BY nome ASC";
$result = mysqli_query($link, $sql);
?>

<h2>Gerenciar Usuários</h2>
<a href="usuario_add.php">Adicionar Novo Usuário</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Permissão</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['nome']; ?></td>
            <td><?php echo $row['email']; ?></td>
            <td><?php echo $row['permissao']; ?></td>
            <td><?php echo ucfirst($row['status']); ?></td>
            <td>
                <?php if($row['status'] == 'pendente'): ?>
                    <a href="usuarios.php?acao=aprovar&id=<?php echo $row['id']; ?>" class="btn-aprovar">Aprovar</a>
                    <a href="usuarios.php?acao=rejeitar&id=<?php echo $row['id']; ?>" class="btn-rejeitar">Rejeitar</a>
                <?php else: ?>
                    <a href="usuario_edit.php?id=<?php echo $row['id']; ?>">Editar</a>
                    <a href="usuario_delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Excluir</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>