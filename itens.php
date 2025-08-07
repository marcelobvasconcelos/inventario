<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Variáveis de pesquisa
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : '';
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// SQL base para contagem total de itens
$sql_count_base = "SELECT COUNT(*) FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id";
$sql_base = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.id as local_id, l.nome AS local, u.nome AS responsavel, i.estado, i.responsavel_id FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id";

$where_clause = "";
$params = [];
$param_types = "";

// Se for admin, mostra tudo. Se for usuário, mostra apenas os seus itens.
if ($_SESSION['permissao'] != 'Administrador') {
    $where_clause = " WHERE i.responsavel_id = ?";
    $params[] = $_SESSION['id'];
    $param_types = "i";
} else { // Lógica de pesquisa para administradores
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        switch ($search_by) {
            case 'id':
                $where_clause .= " WHERE i.id LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'patrimonio_novo':
                $where_clause .= " WHERE i.patrimonio_novo LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'patrimonio_secundario':
                $where_clause .= " WHERE i.patrimonio_secundario LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'local':
                $where_clause .= " WHERE l.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            case 'responsavel':
                $where_clause .= " WHERE u.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
            default:
                // Pesquisa padrão por nome do item se nenhum critério válido for selecionado
                $where_clause .= " WHERE i.nome LIKE ?";
                $params[] = $search_term;
                $param_types .= "s";
                break;
        }
    }
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
    $total_itens = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
} else {
    $total_itens = 0; // Em caso de erro na contagem
}

$total_paginas = ceil($total_itens / $itens_por_pagina);

// Consulta para os itens da página atual
$sql = $sql_base . $where_clause . " ORDER BY i.id DESC LIMIT ? OFFSET ?";

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
    $result = false; // Em caso de erro na consulta principal
}

?>

<h2>Itens do Inventário</h2>
<div class="items-header-controls">
    <?php if($_SESSION['permissao'] == 'Administrador' || $_SESSION['permissao'] == 'Gestor'): // Mostra o botão apenas para admins e gestores ?>
        <a href="item_add.php" class="btn-custom">Adicionar Novo Item</a>
    <?php endif; ?>

    <?php if($_SESSION['permissao'] == 'Administrador'): ?>
    <div class="search-form">
        <form action="" method="GET">
            <div class="search-criteria">
                <label for="search_by">Pesquisar por:</label>
                <select name="search_by" id="search_by">
                    <option value="id" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'id') ? 'selected' : ''; ?>>ID</option>
                    <option value="patrimonio_novo" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'patrimonio_novo') ? 'selected' : ''; ?>>Patrimônio</option>
                    <option value="patrimonio_secundario" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'patrimonio_secundario') ? 'selected' : ''; ?>>Patrimônio Secundário</option>
                    <option value="local" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'local') ? 'selected' : ''; ?>>Local</option>
                    <option value="responsavel" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'responsavel') ? 'selected' : ''; ?>>Responsável</option>
                </select>
            </div>
            <div class="search-input">
                <input type="text" name="search_query" placeholder="Digite o termo de pesquisa" value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
                <input type="submit" value="Pesquisar">
                <?php if(isset($_GET['search_query'])): ?>
                    <a href="itens.php" class="btn-custom">Limpar Pesquisa</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<table>
    <thead>
        <tr>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="nome">Nome <span class="sort-arrow"></span></th>
            <th data-column="patrimonio_novo">Patrimônio <span class="sort-arrow"></span></th>
            <th data-column="patrimonio_secundario">Patrimônio Secundário <span class="sort-arrow"></span></th>
            <th data-column="local">Local <span class="sort-arrow"></span></th>
            <th data-column="responsavel">Responsável <span class="sort-arrow"></span></th>
            <th data-column="estado">Estado <span class="sort-arrow"></span></th>
            <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                <th>Ações</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><a href="item_details.php?id=<?php echo $row['id']; ?>"><?php echo $row['nome']; ?></a></td>
                <td><?php echo $row['patrimonio_novo']; ?></td>
                <td><?php echo $row['patrimonio_secundario']; ?></td>
                <td><a href="local_itens.php?id=<?php echo $row['local_id']; ?>"><?php echo $row['local']; ?></a></td>
                <td><?php echo $row['responsavel']; ?></td>
                <td><?php echo $row['estado']; ?></td>
                <td>
                    <!-- Debugging: Permissão do usuário logado: <?php echo $_SESSION['permissao']; ?>, ID do usuário logado: <?php echo $_SESSION['id']; ?>, Responsável do item: <?php echo $row['responsavel_id']; ?> -->
                    <?php if($_SESSION['permissao'] == 'Administrador' || ($_SESSION['permissao'] == 'Gestor' && $row['responsavel_id'] == $_SESSION['id'])): ?>
                        <a href="item_edit.php?id=<?php echo $row['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                        <?php if($_SESSION['permissao'] == 'Administrador'): ?>
                            <a href="item_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este item? Todas as movimentações relacionadas a ele também serão removidas. Esta ação não poderá ser desfeita!');"><i class="fas fa-trash"></i></a>
                        <?php else: ?>
                            <i class="fas fa-trash disabled-icon" title="Permissão negada para excluir"></i>
                        <?php endif; ?>
                    </td>
                <?php elseif($_SESSION['permissao'] == 'Visualizador'): ?>
                    <td>
                        <i class="fas fa-edit disabled-icon" title="Permissão negada para editar"></i>
                        <i class="fas fa-trash disabled-icon" title="Permissão negada para excluir"></i>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">Nenhum item encontrado.</td>
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