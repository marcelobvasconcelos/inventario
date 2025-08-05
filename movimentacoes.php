<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Se for admin, mostra tudo. Se for usuário, mostra apenas as movimentações dos seus itens.
if ($_SESSION['permissao'] == 'admin') {
    $sql = "SELECT m.id, i.nome AS item, lo.nome AS origem, ld.nome AS destino, u.nome AS usuario, m.data_movimentacao FROM movimentacoes m JOIN itens i ON m.item_id = i.id JOIN locais lo ON m.local_origem_id = lo.id JOIN locais ld ON m.local_destino_id = ld.id JOIN usuarios u ON m.usuario_id = u.id ORDER BY m.data_movimentacao DESC";
} else {
    $usuario_id = $_SESSION['id'];
    $sql = "SELECT m.id, i.nome AS item, lo.nome AS origem, ld.nome AS destino, u.nome AS usuario, m.data_movimentacao FROM movimentacoes m JOIN itens i ON m.item_id = i.id JOIN locais lo ON m.local_origem_id = lo.id JOIN locais ld ON m.local_destino_id = ld.id JOIN usuarios u ON m.usuario_id = u.id WHERE i.responsavel_id = ? ORDER BY m.data_movimentacao DESC";
}

if($stmt = mysqli_prepare($link, $sql)){
    if ($_SESSION['permissao'] != 'admin') {
        mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

?>

<h2>Movimentações de Itens</h2>
<?php if($_SESSION['permissao'] == 'admin'): // Mostra o botão apenas para admins ?>
    <a href="movimentacao_add.php">Registrar Nova Movimentação</a>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Local de Origem</th>
            <th>Local de Destino</th>
            <th>Usuário</th>
            <th>Data</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['item']; ?></td>
            <td><?php echo $row['origem']; ?></td>
            <td><?php echo $row['destino']; ?></td>
            <td><?php echo $row['usuario']; ?></td>
            <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_movimentacao'])); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>