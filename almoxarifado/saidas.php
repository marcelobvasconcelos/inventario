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

// SQL base para contagem total de saídas
$sql_count_base = "SELECT COUNT(*) FROM almoxarifado_saidas s JOIN almoxarifado_materiais m ON s.material_id = m.id JOIN usuarios u ON s.usuario_id = u.id";
$sql_base = "SELECT s.*, m.nome as material_nome, m.codigo as material_codigo, u.nome as usuario_nome";

$where_clause = "";
$params = [];
$param_types = "";

// Lógica de pesquisa
if (!empty($search_query)) {
    $where_clause = " WHERE (m.codigo LIKE ? OR m.nome LIKE ? OR s.setor_destino LIKE ?)";
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
    $total_saidas = mysqli_fetch_row($result_count)[0];
    mysqli_stmt_close($stmt_count);
} else {
    $total_saidas = 0;
}

$total_paginas = ceil($total_saidas / $itens_por_pagina);

// Consulta para as saídas da página atual
$sql = $sql_base . " FROM almoxarifado_saidas s JOIN almoxarifado_materiais m ON s.material_id = m.id JOIN usuarios u ON s.usuario_id = u.id" . $where_clause . " ORDER BY s.data_saida DESC, s.id DESC LIMIT ? OFFSET ?";

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

<h2>Saídas do Almoxarifado</h2>

<div class="controls-container">
    <div class="actions-buttons">
        <a href="saida_add.php" class="btn-custom">Registrar Nova Saída</a>
    </div>

    <div class="search-form">
        <form action="" method="GET">
            <div class="search-input">
                <input type="text" name="search" placeholder="Buscar por código, nome do material ou setor..." value="<?php echo htmlspecialchars($search_query); ?>">
                <input type="submit" value="Buscar">
                <?php if(!empty($search_query)): ?>
                    <a href="saidas.php" class="btn-custom">Limpar Busca</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<table class="table-striped table-hover">
    <thead>
        <tr>
            <th>Data</th>
            <th>Material</th>
            <th>Quantidade</th>
            <th>Setor Destino</th>
            <th>Responsável</th>
            <th>Registrado por</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo date("d/m/Y", strtotime($row['data_saida'])); ?></td>
                <td><?php echo htmlspecialchars($row['material_codigo'] . ' - ' . $row['material_nome']); ?></td>
                <td><?php echo formatar_quantidade($row['quantidade']); ?></td>
                <td><?php echo htmlspecialchars($row['setor_destino']); ?></td>
                <td><?php echo htmlspecialchars($row['responsavel_saida']); ?></td>
                <td><?php echo htmlspecialchars($row['usuario_nome']); ?></td>
                <td>
                    <a href="saida_edit.php?id=<?php echo $row['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                    <a href="saida_delete.php?id=<?php echo $row['id']; ?>" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta saída?');"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Nenhuma saída encontrada.</td>
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