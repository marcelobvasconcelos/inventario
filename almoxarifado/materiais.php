<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(__DIR__);

require_once $base_path . '/includes/header.php';
require_once $base_path . '/config/db.php';
require_once 'config.php';

// Verificar permissão de acesso
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para acessar este módulo.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Configurações de paginação
$itens_por_pagina = ALMOXARIFADO_ITENS_POR_PAGINA;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Variáveis de pesquisa
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// SQL base para contagem total de materiais
$sql_count_base = "SELECT COUNT(*) FROM almoxarifado_materiais";
$sql_base = "SELECT * FROM almoxarifado_materiais";

$where_clause = "";
$params = [];
$param_types = "";

// Lógica de pesquisa
if (!empty($search_query)) {
    $where_clause = " WHERE (codigo LIKE ? OR nome LIKE ? OR categoria LIKE ?)";
    $search_term = '%' . $search_query . '%';
    $params = [$search_term, $search_term, $search_term];
    $param_types = "sss";
}

// Consulta para contagem total
$sql_count = $sql_count_base . $where_clause;
if($stmt_count = mysqli_prepare($link, $sql_count)){
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_count, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_materiais = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
} else {
    $total_materiais = 0;
}

$total_paginas = ceil($total_materiais / $itens_por_pagina);

// Consulta para os materiais da página atual
$sql = $sql_base . $where_clause . " ORDER BY nome ASC LIMIT ? OFFSET ?";

if($stmt = mysqli_prepare($link, $sql)){
    $bind_params = array_merge($params, [$itens_por_pagina, $offset]);
    $bind_types = $param_types . "ii";
    mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}
?>

<h2>Materiais do Almoxarifado</h2>

<div class="controls-container">
    <div class="actions-buttons">
        <a href="material_add.php" class="btn-custom">Adicionar Novo Material</a>
    </div>

    <div class="search-form">
        <form action="" method="GET">
            <div class="search-input">
                <input type="text" name="search" placeholder="Buscar por código, nome ou categoria..." value="<?php echo htmlspecialchars($search_query); ?>">
                <input type="submit" value="Buscar">
                <?php if(!empty($search_query)): ?>
                    <a href="materiais.php" class="btn-custom">Limpar Busca</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<table class="table-striped table-hover">
    <thead>
        <tr>
            <th>Código</th>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Unidade</th>
            <th>Estoque Mínimo</th>
            <th>Estoque Atual</th>
            <th>Valor Unitário</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                <td><?php echo htmlspecialchars($row['nome']); ?></td>
                <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                <td><?php echo htmlspecialchars($row['unidade_medida']); ?></td>
                <td><?php echo formatar_quantidade($row['estoque_minimo']); ?></td>
                <td><?php echo formatar_quantidade($row['estoque_atual']); ?></td>
                <td><?php echo formatar_valor($row['valor_unitario']); ?></td>
                <td>
                    <?php if ($row['status'] == 'ativo'): ?>
                        <span class="badge badge-success">Ativo</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inativo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="material_edit.php?id=<?php echo $row['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                    <a href="material_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este material?');"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">Nenhum material encontrado.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($total_paginas > 1): ?>
        <?php if ($pagina_atual > 1): ?>
            <a href="?pagina=<?php echo $pagina_atual - 1; ?>&search=<?php echo urlencode($search_query); ?>">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>" class="<?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($pagina_atual < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina_atual + 1; ?>&search=<?php echo urlencode($search_query); ?>">Próxima</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
mysqli_close($link);
require_once $base_path . '/includes/footer.php';
?>