<?php
require_once 'includes/header.php';
require_once 'config/db.php';

$local_id = $_GET['id'];

$sql_local = "SELECT nome FROM locais WHERE id = ?";
if($stmt_local = mysqli_prepare($link, $sql_local)){
    mysqli_stmt_bind_param($stmt_local, "i", $local_id);
    mysqli_stmt_execute($stmt_local);
    $result_local = mysqli_stmt_get_result($stmt_local);
    $local = mysqli_fetch_assoc($result_local);
    $local_nome = $local['nome'];
} else {
    $local_nome = "Local Desconhecido";
}

$sql_itens = "SELECT 
                i.id, i.nome, i.observacao AS descricao, i.patrimonio_novo AS numero_serie, i.data_cadastro AS data_compra, i.estado AS status, 
                l.nome as local_nome, u.nome as responsavel_nome, i.responsavel_id
              FROM itens i
              LEFT JOIN locais l ON i.local_id = l.id
              LEFT JOIN usuarios u ON i.responsavel_id = u.id
              WHERE i.local_id = ?";

if($stmt_itens = mysqli_prepare($link, $sql_itens)){
    mysqli_stmt_bind_param($stmt_itens, "i", $local_id);
    if(mysqli_stmt_execute($stmt_itens)){
        $itens = mysqli_stmt_get_result($stmt_itens);
    } else {
        echo "Erro ao executar a consulta de itens: " . mysqli_error($link);
        $itens = false; // Define $itens como false para evitar o erro mysqli_num_rows
    }
} else {
    echo "Erro ao preparar a consulta de itens: " . mysqli_error($link);
    $itens = false; // Define $itens como false para evitar o erro mysqli_num_rows
}

?>

<h2>Itens em <?php echo $local_nome; ?></h2>
<p><a href="locais.php" class="btn-custom">Voltar para Locais</a></p>

<?php if($itens && mysqli_num_rows($itens) > 0): ?>
    <table>
        <thead>
            <tr>
                <th data-column="id">ID <span class="sort-arrow"></span></th>
                <th data-column="nome">Nome <span class="sort-arrow"></span></th>
                <th data-column="descricao">Descrição <span class="sort-arrow"></span></th>
                <th data-column="numero_serie">Número de Série <span class="sort-arrow"></span></th>
                <th data-column="data_compra">Data de Compra <span class="sort-arrow"></span></th>
                <th data-column="status">Status <span class="sort-arrow"></span></th>
                <th data-column="responsavel_nome">Responsável <span class="sort-arrow"></span></th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($itens)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><a href="item_details.php?id=<?php echo $row['id']; ?>"><?php echo $row['nome']; ?></a></td>
                    <td><?php echo $row['descricao']; ?></td>
                    <td><?php echo $row['numero_serie']; ?></td>
                    <td><?php echo $row['data_compra']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['responsavel_nome']; ?></td>
                    <td>
                        <?php if($_SESSION['permissao'] == 'Administrador' || ($_SESSION['permissao'] == 'Gestor' && $row['responsavel_id'] == $_SESSION['id'])): ?>
                            <a href="item_edit.php?id=<?php echo $row['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                            <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                                <a href="item_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este item?');"><i class="fas fa-trash"></i></a>
                            <?php else: ?>
                                <i class="fas fa-trash disabled-icon" title="Permissão negada para excluir"></i>
                            <?php endif; ?>
                        <?php elseif($_SESSION['permissao'] == 'Visualizador'): ?>
                            <i class="fas fa-edit disabled-icon" title="Permissão negada para editar"></i>
                            <i class="fas fa-trash disabled-icon" title="Permissão negada para excluir"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Nenhum item encontrado neste local.</p>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;

    const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
        v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

    document.querySelectorAll('th[data-column]').forEach(th => {
        th.addEventListener('click', (() => {
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const column = Array.from(th.parentNode.children).indexOf(th);
            const currentIsAsc = th.classList.contains('asc');

            // Remove sorting classes from all headers
            document.querySelectorAll('th[data-column]').forEach(header => {
                header.classList.remove('asc', 'desc');
                header.querySelector('.sort-arrow').innerText = '';
            });

            // Add sorting class to the clicked header
            if (currentIsAsc) {
                th.classList.add('desc');
                th.querySelector('.sort-arrow').innerText = ' ↓'; // Down arrow
            } else {
                th.classList.add('asc');
                th.querySelector('.sort-arrow').innerText = ' ↑'; // Up arrow
            }

            Array.from(tbody.querySelectorAll('tr'))
                .sort(comparer(column, !currentIsAsc))
                .forEach(tr => tbody.appendChild(tr));
        }));
    });
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>