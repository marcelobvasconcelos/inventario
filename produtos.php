<?php
// produtos.php - Listagem de produtos do almoxarifado
require_once 'includes/header.php';
require_once 'config/db.php';

// Verificar permissões
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Variáveis de pesquisa
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// SQL base para contagem total
$sql_count_base = "SELECT COUNT(*) FROM almoxarifado_produtos";
$sql_base = "SELECT * FROM almoxarifado_produtos";

$where_clause = "";
$params = [];
$param_types = "";

// Adiciona condição de pesquisa, se houver
if (!empty($search_query)) {
    $where_clause = " WHERE nome LIKE ? OR descricao LIKE ?";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $param_types = "ss";
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
    $total_produtos = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
}

$total_paginas = ceil($total_produtos / $itens_por_pagina);

// Consulta para os produtos da página atual
$sql = $sql_base . $where_clause . " ORDER BY nome ASC LIMIT ? OFFSET ?";

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

<div class="almoxarifado-header">
    <h2>Produtos do Almoxarifado</h2>
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
        <a href="produto_add.php" class="btn-custom">Adicionar Novo Produto</a>
    <?php endif; ?>
</div>

<div class="controls-container">
    <div class="search-form">
        <form action="" method="GET">
            <div class="search-input">
                <input type="text" name="search_query" placeholder="Pesquisar produtos..." value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
                <input type="submit" value="Pesquisar" class="btn-custom">
            </div>
        </form>
    </div>
</div>

<table class="almoxarifado-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Unidade</th>
            <th>Estoque Atual</th>
            <th>Estoque Mínimo</th>
            <th>Status</th>
            <?php if($_SESSION["permissao"] == 'Administrador'): ?>
                <th>Ações</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['nome']); ?></td>
                <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                <td><?php echo htmlspecialchars($row['unidade_medida']); ?></td>
                <td><?php echo $row['estoque_atual']; ?></td>
                <td><?php echo $row['estoque_minimo']; ?></td>
                <td>
                    <?php 
                        if ($row['estoque_atual'] <= $row['estoque_minimo']) {
                            echo '<span class="badge badge-danger">Estoque Baixo</span>';
                        } else {
                            echo '<span class="badge badge-success">Normal</span>';
                        }
                    ?>
                </td>
                <?php if($_SESSION["permissao"] == 'Administrador'): ?>
                    <td>
                        <a href="produto_edit.php?id=<?php echo $row['id']; ?>" title="Editar" class="action-icon edit-icon"><i class="fas fa-edit"></i></a>
                        <a href="produto_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" class="action-icon delete-icon" onclick="return confirm('Tem certeza que deseja excluir este produto?');"><i class="fas fa-trash"></i></a>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo ($_SESSION["permissao"] == 'Administrador') ? '8' : '7'; ?>">Nenhum produto encontrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($total_paginas > 1): ?>
        <?php 
        // Constrói os parâmetros para manter a pesquisa na paginação
        $query_params = [];
        if (!empty($search_query)) {
            $query_params['search_query'] = $search_query;
        }
        
        $base_url = '?' . http_build_query($query_params);
        ?>
        
        <?php if ($pagina_atual > 1): ?>
            <a href="<?php echo $base_url . '&pagina=' . ($pagina_atual - 1); ?>">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="<?php echo $base_url . '&pagina=' . $i; ?>" class="<?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($pagina_atual < $total_paginas): ?>
            <a href="<?php echo $base_url . '&pagina=' . ($pagina_atual + 1); ?>">Próxima</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>