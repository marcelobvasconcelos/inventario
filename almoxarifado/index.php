<?php
// almoxarifado/index.php - Página principal do almoxarifado
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Verificar se o usuário tem permissão de administrador, almoxarife, visualizador ou gestor
if ($_SESSION["permissao"] != 'Administrador' && $_SESSION["permissao"] != 'Almoxarife' && $_SESSION["permissao"] != 'Visualizador' && $_SESSION["permissao"] != 'Gestor') {
    header("location: ../dashboard.php");
    exit;
}

// Configurações de paginação
$itens_por_pagina = 10;
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
    <h2>Estoque do Almoxarifado</h2>
    <?php if($_SESSION["permissao"] == 'Administrador' || $_SESSION["permissao"] == 'Almoxarife'): ?>
        <a href="add_produto.php" class="btn-custom">Adicionar Produto</a>
    <?php endif; ?>
    <?php if($_SESSION["permissao"] == 'Administrador' || $_SESSION["permissao"] == 'Almoxarife' || $_SESSION["permissao"] == 'Visualizador'): ?>
        <a href="requisicao.php" class="btn-custom">Nova Requisição</a>
        <a href="minhas_notificacoes.php" class="btn-custom">Minhas Notificações</a>
    <?php endif; ?>
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
        <a href="admin_notificacoes.php" class="btn-custom">Gerenciar Requisições</a>
        <a href="empenhos/index.php" class="btn-custom">Gerenciar Empenhos</a>
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
            <?php if($_SESSION["permissao"] == 'Administrador' || $_SESSION["permissao"] == 'Almoxarife'): ?>
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
                <?php if($_SESSION["permissao"] == 'Administrador' || $_SESSION["permissao"] == 'Almoxarife'): ?>
                    <td>
                        <a href="add_produto.php?id=<?php echo $row['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo ($_SESSION["permissao"] == 'Administrador' || $_SESSION["permissao"] == 'Almoxarife') ? '8' : '7'; ?>">Nenhum produto encontrado.</td>
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

<?php if($_SESSION["permissao"] == 'Administrador' || $_SESSION["permissao"] == 'Almoxarife' || $_SESSION["permissao"] == 'Visualizador'): ?>
<div class="almoxarifado-header" style="margin-top: 30px;">
    <h2>Minhas Requisições</h2>
</div>

<?php
// Buscar requisições do usuário logado
$sql_requisicoes = "
    SELECT 
        r.id, 
        r.codigo_requisicao,
        r.data_requisicao, 
        r.status,
        l.nome as local_nome
    FROM almoxarifado_requisicoes r
    LEFT JOIN locais l ON r.local_id = l.id
    WHERE r.usuario_id = ?
    ORDER BY r.data_requisicao DESC
    LIMIT 10
";

$requisicoes = [];
if($stmt_requisicoes = mysqli_prepare($link, $sql_requisicoes)){
    mysqli_stmt_bind_param($stmt_requisicoes, "i", $_SESSION['id']);
    if(mysqli_stmt_execute($stmt_requisicoes)){
        $result_requisicoes = mysqli_stmt_get_result($stmt_requisicoes);
        while($row = mysqli_fetch_assoc($result_requisicoes)){
            $requisicoes[] = $row;
        }
    }
    mysqli_stmt_close($stmt_requisicoes);
}
?>

<table class="almoxarifado-table">
    <thead>
        <tr>
            <th>Código</th>
            <th>Data</th>
            <th>Local</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($requisicoes) > 0): ?>
            <?php foreach($requisicoes as $req): ?>
            <tr>
                <td><?php echo 'REQ-' . str_pad($req['id'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($req['data_requisicao'])); ?></td>
                <td><?php echo htmlspecialchars($req['local_nome'] ?? 'Não especificado'); ?></td>
                <td>
                    <?php 
                        $status = $req['status'];
                        $status_class = '';
                        switch($status) {
                            case 'pendente':
                                $status_class = 'badge-warning';
                                break;
                            case 'aprovada':
                                $status_class = 'badge-success';
                                break;
                            case 'rejeitada':
                                $status_class = 'badge-danger';
                                break;
                            case 'concluida':
                                $status_class = 'badge-info';
                                break;
                            default:
                                $status_class = 'badge-secondary';
                        }
                    ?>
                    <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                </td>
                <td>
                    <a href="detalhes_requisicao.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-primary">Ver Detalhes</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Nenhuma requisição encontrada.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>

<?php
mysqli_close($link);
require_once '../includes/footer.php';
?>