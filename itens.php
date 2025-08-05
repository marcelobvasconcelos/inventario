<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Se for admin, mostra tudo. Se for usuário, mostra apenas os seus itens.
if ($_SESSION['permissao'] == 'admin') {
    $sql = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.nome AS local, u.nome AS responsavel, i.estado FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id ORDER BY i.id DESC";
} else {
    $usuario_id = $_SESSION['id'];
    $sql = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.nome AS local, u.nome AS responsavel, i.estado FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id WHERE i.responsavel_id = ? ORDER BY i.id DESC";
}

if($stmt = mysqli_prepare($link, $sql)){
    if ($_SESSION['permissao'] != 'admin') {
        mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

?>

<h2>Itens do Inventário</h2>
<?php if($_SESSION['permissao'] == 'admin'): // Mostra o botão apenas para admins ?>
    <a href="item_add.php">Adicionar Novo Item</a>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Patrimônio</th>
            <th>Patrimônio Secundário</th>
            <th>Local</th>
            <th>Responsável</th>
            <th>Estado</th>
            <?php if($_SESSION['permissao'] == 'admin'): ?>
                <th>Ações</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['nome']; ?></td>
            <td><?php echo $row['patrimonio_novo']; ?></td>
            <td><?php echo $row['patrimonio_secundario']; ?></td>
            <td><?php echo $row['local']; ?></td>
            <td><?php echo $row['responsavel']; ?></td>
            <td><?php echo $row['estado']; ?></td>
            <?php if($_SESSION['permissao'] == 'admin'): ?>
                <td>
                    <a href="item_edit.php?id=<?php echo $row['id']; ?>">Editar</a>
                    <a href="item_delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este item?');">Excluir</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>