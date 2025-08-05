<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$sql = "SELECT id, nome FROM locais ORDER BY nome ASC";
$result = mysqli_query($link, $sql);
?>

<h2>Locais de Armazenamento</h2>
<a href="local_add.php">Adicionar Novo Local</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['nome']; ?></td>
            <td>
                <a href="local_edit.php?id=<?php echo $row['id']; ?>">Editar</a>
                <a href="local_delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este local?');">Excluir</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>