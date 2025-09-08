<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Verifica se o usuário está logado e é administrador
if (!isset($_SESSION['id']) || $_SESSION['permissao'] != 'Administrador') {
    header('Location: ../index.php');
    exit();
}

// Configurações de paginação
$itens_por_pagina = 60;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Variáveis de pesquisa
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : '';
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// SQL base para contagem total de itens
$sql_count_base = "SELECT COUNT(*) FROM almoxarifado_materiais am JOIN locais l ON am.local_id = l.id JOIN usuarios u ON am.responsavel_id = u.id";
$sql_base = "SELECT am.id, am.nome, l.id as local_id, l.nome AS local, u.nome AS responsavel, am.estado, am.responsavel_id, am.status_confirmacao FROM almoxarifado_materiais am JOIN locais l ON am.local_id = l.id JOIN usuarios u ON am.responsavel_id = u.id";

$where_clause = "";
$params = [];
$param_types = "";

// Lógica de pesquisa para administradores
if (!empty($search_query)) {
    $search_term = '%' . $search_query . '%';
    switch ($search_by) {
        case 'id':
            $where_clause .= " WHERE am.id LIKE ?";
            $params[] = $search_term;
            $param_types .= "s";
            break;
        case 'nome':
            $where_clause .= " WHERE am.nome LIKE ?";
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
            $where_clause .= " WHERE am.nome LIKE ?";
            $params[] = $search_term;
            $param_types .= "s";
            break;
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
$sql = $sql_base . $where_clause . " ORDER BY am.id DESC LIMIT ? OFFSET ?";

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

<h2>Itens do Almoxarifado</h2>
<div class="controls-container">
    <div class="actions-buttons">
        <a href="item_add.php" class="btn-custom"><i class="fas fa-plus"></i> Adicionar Novo Item</a>
    </div>

    <div class="search-form">
        <form action="" method="GET">
            <div class="search-criteria">
                <label for="search_by">Pesquisar por:</label>
                <select name="search_by" id="search_by">
                    <option value="id" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'id') ? 'selected' : ''; ?>>ID</option>
                    <option value="nome" <?php echo (isset($_GET['search_by']) && $_GET['search_by'] == 'nome') ? 'selected' : ''; ?>>Nome</option>
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
</div>

<table class="table-striped table-hover">
    <thead>
        <tr>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="nome">Nome <span class="sort-arrow"></span></th>
            <th data-column="local">Local <span class="sort-arrow"></span></th>
            <th data-column="responsavel">Responsável <span class="sort-arrow"></span></th>
            <th data-column="estado">Estado <span class="sort-arrow"></span></th>
            <th>Status Confirmação</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><a href="item_details.php?id=<?php echo $row['id']; ?>"><?php echo $row['nome']; ?></a></td>
                <td><a href="../local_itens.php?id=<?php echo $row['local_id']; ?>"><?php echo $row['local']; ?></a></td>
                <td><?php echo $row['responsavel']; ?></td>
                <td><?php echo $row['estado']; ?></td>
                <td>
                    <?php
                        $status_confirmacao = $row['status_confirmacao'];
                        $badge_class = '';
                        if ($status_confirmacao == 'Pendente') {
                            $badge_class = 'badge-warning';
                        } elseif ($status_confirmacao == 'Confirmado') {
                            $badge_class = 'badge-success';
                        } elseif ($status_confirmacao == 'Nao Confirmado') {
                            $badge_class = 'badge-danger';
                        }
                    ?>
                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($status_confirmacao); ?></span>
                </td>
                <td>
                    <a href="item_edit.php?id=<?php echo $row['id']; ?>" title="Editar" class="btn-custom"><i class="fas fa-edit"></i></a>
                    <a href="item_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir este item?');"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Nenhum item encontrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($total_paginas > 1): ?>
        <ul class="pagination-list">
            <?php if ($pagina_atual > 1): ?>
                <li><a href="?pagina=1<?php echo !empty($search_query) ? '&search_by=' . $search_by . '&search_query=' . urlencode($search_query) : ''; ?>" title="Primeira"><i class="fas fa-angle-double-left"></i></a></li>
                <li><a href="?pagina=<?php echo $pagina_atual - 1; ?><?php echo !empty($search_query) ? '&search_by=' . $search_by . '&search_query=' . urlencode($search_query) : ''; ?>" title="Anterior"><i class="fas fa-angle-left"></i></a></li>
            <?php endif; ?>

            <?php
            // Lógica para mostrar páginas próximas
            $range = 2; // Quantidade de páginas antes e depois da atual
            $start = max(1, $pagina_atual - $range);
            $end = min($total_paginas, $pagina_atual + $range);

            // Mostrar "..." se necessário
            if ($start > 1) {
                echo '<li><a href="?pagina=1' . (!empty($search_query) ? '&search_by=' . $search_by . '&search_query=' . urlencode($search_query) : '') . '">1</a></li>';
                if ($start > 2) {
                    echo '<li><span>...</span></li>';
                }
            }

            for ($i = $start; $i <= $end; $i++) {
                $active_class = ($i == $pagina_atual) ? 'active' : '';
                echo '<li><a href="?pagina=' . $i . (!empty($search_query) ? '&search_by=' . $search_by . '&search_query=' . urlencode($search_query) : '') . '" class="' . $active_class . '">' . $i . '</a></li>';
            }

            // Mostrar "..." se necessário
            if ($end < $total_paginas) {
                if ($end < $total_paginas - 1) {
                    echo '<li><span>...</span></li>';
                }
                echo '<li><a href="?pagina=' . $total_paginas . (!empty($search_query) ? '&search_by=' . $search_by . '&search_query=' . urlencode($search_query) : '') . '">' . $total_paginas . '</a></li>';
            }
            ?>

            <?php if ($pagina_atual < $total_paginas): ?>
                <li><a href="?pagina=<?php echo $pagina_atual + 1; ?><?php echo !empty($search_query) ? '&search_by=' . $search_by . '&search_query=' . urlencode($search_query) : ''; ?>" title="Próxima"><i class="fas fa-angle-right"></i></a></li>
                <li><a href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($search_query) ? '&search_by=' . $search_by . '&search_query=' . urlencode($search_query) : ''; ?>" title="Última"><i class="fas fa-angle-double-right"></i></a></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>