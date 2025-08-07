<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$total_movimentacoes = 0;
$sql_count_base = "SELECT COUNT(*) FROM movimentacoes m JOIN itens i ON m.item_id = i.id JOIN locais lo ON m.local_origem_id = lo.id JOIN locais ld ON m.local_destino_id = ld.id JOIN usuarios u_resp ON i.responsavel_id = u_resp.id LEFT JOIN usuarios u_ant ON i.usuario_anterior_id = u_ant.id";
$sql_fetch_base = "SELECT 
                m.id, 
                i.id AS item_id, 
                i.nome AS item, 
                lo.id AS origem_id, 
                lo.nome AS origem, 
                ld.id AS destino_id, 
                ld.nome AS destino, 
                u_ant.id AS usuario_origem_id, 
                u_ant.nome AS usuario_origem, 
                u_resp.id AS usuario_destino_id, 
                u_resp.nome AS usuario_destino, 
                m.data_movimentacao
            FROM 
                movimentacoes m
            JOIN itens i ON m.item_id = i.id
            JOIN locais lo ON m.local_origem_id = lo.id
            JOIN locais ld ON m.local_destino_id = ld.id
            JOIN usuarios u_resp ON i.responsavel_id = u_resp.id
            LEFT JOIN usuarios u_ant ON i.usuario_anterior_id = u_ant.id";

$where_clause = "";
$params = [];
$param_types = "";

// Se for admin, mostra tudo. Se for usuário, mostra apenas as movimentações relacionadas a ele.
if ($_SESSION['permissao'] != 'Administrador') {
    $usuario_id = $_SESSION['id'];
    $where_clause = " WHERE i.responsavel_id = ? OR i.usuario_anterior_id = ?";
    $params[] = $usuario_id;
    $params[] = $usuario_id;
    $param_types = "ii";
}

// Consulta para contagem total
$sql_count = $sql_count_base . $where_clause;
if($stmt_count = mysqli_prepare($link, $sql_count)){
    if (!empty($params)) {
        $refs = [];
        foreach($params as $key => $value)
            $refs[$key] = &$params[$key];
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_count, $param_types], $refs));
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_movimentacoes = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
}

$total_paginas = ceil($total_movimentacoes / $itens_por_pagina);

// Consulta para os itens da página atual
$sql = $sql_fetch_base . $where_clause . " ORDER BY m.data_movimentacao DESC LIMIT ? OFFSET ?";

if($stmt = mysqli_prepare($link, $sql)){
    $bind_params = [];
    $bind_types = $param_types . "ii";
    if (!empty($params)) {
        $bind_params = array_merge($params, [$itens_por_pagina, $offset]);
    } else {
        $bind_params = [$itens_por_pagina, $offset];
    }
    $refs = [];
    foreach($bind_params as $key => $value)
        $refs[$key] = &$bind_params[$key];
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $bind_types], $refs));
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}

?>

<h2>Movimentações de Itens</h2>
<?php if($_SESSION['permissao'] == 'Administrador'): // Mostra o botão apenas para admins ?>
    <a href="movimentacao_add.php" class="btn-custom">Registrar Nova Movimentação</a>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="item">Item <span class="sort-arrow"></span></th>
            <th data-column="origem">Local de Origem <span class="sort-arrow"></span></th>
            <th data-column="usuario_origem">Usuário de Origem <span class="sort-arrow"></span></th>
            <th data-column="destino">Local de Destino <span class="sort-arrow"></span></th>
            <th data-column="usuario_destino">Usuário de Destino <span class="sort-arrow"></span></th>
            <th data-column="data_movimentacao">Data <span class="sort-arrow"></span></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><a href="item_details.php?id=<?php echo $row['item_id']; ?>"><?php echo $row['item']; ?></a></td>
                <td><a href="local_itens.php?id=<?php echo $row['origem_id']; ?>"><?php echo $row['origem']; ?></a></td>
                <td>
                    <?php if($row['usuario_origem']): ?>
                        <a href="usuario_itens.php?id=<?php echo $row['usuario_origem_id']; ?>"><?php echo $row['usuario_origem']; ?></a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><a href="local_itens.php?id=<?php echo $row['destino_id']; ?>"><?php echo $row['destino']; ?></a></td>
                <td><a href="usuario_itens.php?id=<?php echo $row['usuario_destino_id']; ?>"><?php echo $row['usuario_destino']; ?></a></td>
                <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_movimentacao'])); ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Nenhuma movimentação encontrada.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($total_paginas > 1): ?>
        <?php if ($pagina_atual > 1): ?>
            <a href="?pagina=<?php echo $pagina_atual - 1; ?>">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?php echo $i; ?>" class="<?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($pagina_atual < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina_atual + 1; ?>">Próxima</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

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
