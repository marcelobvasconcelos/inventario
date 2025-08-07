<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$total_locais = 0;
$sql_count = "";
$sql_fetch = "";
$params = [];
$param_types = "";

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'aprovado'; // Default to 'aprovado'

$where_clause = "";

if ($_SESSION['permissao'] == 'Visualizador') {
    $usuario_id = $_SESSION['id'];
    $where_clause = " WHERE i.responsavel_id = ? AND l.status = 'aprovado'";
    $sql_count = "SELECT COUNT(DISTINCT l.id) FROM locais l JOIN itens i ON l.id = i.local_id" . $where_clause;
    $sql_fetch = "SELECT DISTINCT l.id, l.nome, l.status FROM locais l JOIN itens i ON l.id = i.local_id" . $where_clause;
    $params[] = $usuario_id;
    $param_types = "i";
} else { // Administrador e Gestor podem ver todos os locais aprovados por padrão, ou filtrar
    $sql_count = "SELECT COUNT(*) FROM locais";
    $sql_fetch = "SELECT id, nome, status, solicitado_por FROM locais";

    if ($status_filter != 'todos') {
        $where_clause = " WHERE status = ?";
        $params[] = $status_filter;
        $param_types = "s";
    }
    $sql_count .= $where_clause;
    $sql_fetch .= $where_clause;
}

// Adiciona limites
$sql_fetch .= " LIMIT ? OFFSET ?";

// Consulta para contagem total
if($stmt_count = mysqli_prepare($link, $sql_count)){
    if (!empty($params)) {
        $refs = [];
        foreach($params as $key => $value)
            $refs[$key] = &$params[$key];
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_count, $param_types], $refs));
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_locais = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
}

$total_paginas = ceil($total_locais / $itens_por_pagina);

// Consulta para os locais da página atual
if($stmt = mysqli_prepare($link, $sql_fetch)){
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

<h2>Locais de Armazenamento</h2>
<div class="items-header-controls">
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
    <a href="local_add.php" class="btn-custom">Adicionar Novo Local</a>
    <div class="filter-status">
        <label for="status_filter">Filtrar por Status:</label>
        <select id="status_filter" onchange="window.location.href='locais.php?status=' + this.value">
            <option value="aprovado" <?php echo ($status_filter == 'aprovado') ? 'selected' : ''; ?>>Aprovados</option>
            <option value="pendente" <?php echo ($status_filter == 'pendente') ? 'selected' : ''; ?>>Pendentes</option>
            <option value="rejeitado" <?php echo ($status_filter == 'rejeitado') ? 'selected' : ''; ?>>Rejeitados</option>
            <option value="todos" <?php echo ($status_filter == 'todos') ? 'selected' : ''; ?>>Todos</option>
        </select>
    </div>
    <?php endif; ?>
</div>

<table>
    <thead>
        <tr>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="nome">Nome <span class="sort-arrow"></span></th>
            <th>Status</th>
            <?php if($_SESSION["permissao"] == 'Administrador'): ?>
            <th>Solicitado Por</th>
            <th>Ações</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><a href="local_itens.php?id=<?php echo $row['id']; ?>"><?php echo $row['nome']; ?></a></td>
                <td>
                    <?php
                        if ($row['status'] == 'aprovado') {
                            echo '<i class="fas fa-check-circle" title="Aprovado"></i> Aprovado';
                        } elseif ($row['status'] == 'pendente') {
                            echo '<i class="fas fa-hourglass-half" title="Pendente"></i> Pendente';
                        } elseif ($row['status'] == 'rejeitado') {
                            echo '<i class="fas fa-times-circle" title="Rejeitado"></i> Rejeitado';
                        }
                    ?>
                </td>
                <?php if($_SESSION["permissao"] == 'Administrador'): ?>
                <td>
                    <?php
                        if ($row['solicitado_por']) {
                            $sql_solicitante = "SELECT nome FROM usuarios WHERE id = ?";
                            if($stmt_solicitante = mysqli_prepare($link, $sql_solicitante)){
                                mysqli_stmt_bind_param($stmt_solicitante, "i", $row['solicitado_por']);
                                mysqli_stmt_execute($stmt_solicitante);
                                mysqli_stmt_bind_result($stmt_solicitante, $solicitante_nome);
                                mysqli_stmt_fetch($stmt_solicitante);
                                echo htmlspecialchars($solicitante_nome);
                                mysqli_stmt_close($stmt_solicitante);
                            } else {
                                echo "Erro";
                            }
                        } else {
                            echo "N/A";
                        }
                    ?>
                </td>
                <td>
                    <?php if ($row['status'] == 'pendente'): ?>
                        <a href="local_approve.php?id=<?php echo $row['id']; ?>" title="Aprovar" onclick="return confirm('Tem certeza que deseja aprovar este local?');"><i class="fas fa-check-circle"></i></a>
                        <a href="local_reject.php?id=<?php echo $row['id']; ?>" title="Rejeitar" onclick="return confirm('Tem certeza que deseja rejeitar este local?');"><i class="fas fa-times-circle"></i></a>
                    <?php else: ?>
                        <a href="local_edit.php?id=<?php echo $row['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="local_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este local?');"><i class="fas fa-trash"></i></a>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo ($_SESSION["permissao"] == 'Administrador') ? '5' : '3'; ?>">Nenhum local encontrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($total_paginas > 1): ?>
        <?php if ($pagina_atual > 1): ?>
            <a href="?pagina=<?php echo $pagina_atual - 1; ?>&status=<?php echo $status_filter; ?>">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?php echo $i; ?>&status=<?php echo $status_filter; ?>" class="<?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($pagina_atual < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina_atual + 1; ?>&status=<?php echo $status_filter; ?>">Próxima</a>
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