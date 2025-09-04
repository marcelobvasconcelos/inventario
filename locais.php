<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Variáveis de pesquisa
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

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
    
    // Adiciona condição de pesquisa, se houver
    if (!empty($search_query)) {
        $where_clause .= " AND l.nome LIKE ?";
        $params[] = '%' . $search_query . '%';
        $param_types .= "s";
    }
    
    $sql_count = "SELECT COUNT(DISTINCT l.id) FROM locais l JOIN itens i ON l.id = i.local_id" . $where_clause;
    $sql_fetch = "SELECT DISTINCT l.id, l.nome, l.status FROM locais l JOIN itens i ON l.id = i.local_id" . $where_clause;
    $params = array_merge([$usuario_id], $params);
    $param_types = "i" . $param_types;
} else { // Administrador e Gestor podem ver todos os locais aprovados por padrão, ou filtrar
    $sql_count = "SELECT COUNT(*) FROM locais";
    $sql_fetch = "SELECT id, nome, status, solicitado_por FROM locais";

    // Adiciona condições de filtro
    $conditions = [];
    if ($status_filter != 'todos') {
        $conditions[] = "status = ?";
        $params[] = $status_filter;
        $param_types = "s";
    }
    
    // Adiciona condição de pesquisa, se houver
    if (!empty($search_query)) {
        $conditions[] = "nome LIKE ?";
        $params[] = '%' . $search_query . '%';
        $param_types .= "s";
    }
    
    if (!empty($conditions)) {
        $where_clause = " WHERE " . implode(" AND ", $conditions);
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
<div class="controls-container">
    <div class="main-actions">
        <?php if($_SESSION["permissao"] == 'Administrador'): ?>
        <a href="local_add.php" class="btn-custom">Adicionar Novo Local</a>
        <?php endif; ?>
    </div>
    
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
    <div class="search-form">
        <form action="" method="GET">
            <div class="search-input">
                <input type="text" name="search_query" placeholder="Pesquisar por nome do setor..." value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
            </div>
        </form>
    </div>
    
    <div class="filter-status">
        <form action="" method="GET">
            <!-- Manter o valor da pesquisa, se existir -->
            <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
            <?php endif; ?>
            
            <label for="status_filter">Filtrar por Status:</label>
            <select id="status_filter" name="status" onchange="this.form.submit()">
                <option value="aprovado" <?php echo ($status_filter == 'aprovado') ? 'selected' : ''; ?>>Aprovados</option>
                <option value="pendente" <?php echo ($status_filter == 'pendente') ? 'selected' : ''; ?>>Pendentes</option>
                <option value="rejeitado" <?php echo ($status_filter == 'rejeitado') ? 'selected' : ''; ?>>Rejeitados</option>
                <option value="todos" <?php echo ($status_filter == 'todos') ? 'selected' : ''; ?>>Todos</option>
            </select>
        </form>
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
        <?php 
        // Constrói os parâmetros para manter a pesquisa e filtros na paginação
        $query_params = [];
        if (!empty($search_query)) {
            $query_params['search_query'] = $search_query;
        }
        if ($status_filter != 'aprovado') { // 'aprovado' é o padrão, então só adiciona se for diferente
            $query_params['status'] = $status_filter;
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;

    const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
        v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

    // --- Lógica de ordenação de colunas ---
    const sortableHeaders = document.querySelectorAll('th[data-column]');
    let currentSortColumn = null;
    let currentSortDirection = 'asc';
    
    // Função para obter o valor da célula
    function getCellValue(tr, idx) {
        const cell = tr.children[idx];
        // Se for um link, retorna o texto do link
        const link = cell.querySelector('a');
        if (link) {
            return link.textContent || link.innerText || '';
        }
        // Se for um elemento com classe icon, retorna o texto após o ícone
        const icon = cell.querySelector('i');
        if (icon) {
            return cell.textContent.replace(icon.outerHTML, '').trim();
        }
        // Caso contrário, retorna o conteúdo da célula
        return cell.textContent || cell.innerText || '';
    }
    
    // Função de comparação
    function comparer(idx, asc) {
        return function(a, b) {
            const v1 = getCellValue(asc ? a : b, idx);
            const v2 = getCellValue(asc ? b : a, idx);
            
            // Verificar se são números
            const num1 = parseFloat(v1);
            const num2 = parseFloat(v2);
            
            // Se ambos forem números válidos, comparar como números
            if (!isNaN(num1) && !isNaN(num2)) {
                return num1 - num2;
            }
            
            // Caso contrário, comparar como strings
            return v1.toString().localeCompare(v2.toString());
        };
    }
    
    sortableHeaders.forEach(header => {
        // Adicionar seta padrão para todas as colunas
        const arrow = header.querySelector('.sort-arrow');
        if (arrow) {
            arrow.classList.add('none');
        }
        
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            const table = header.closest('table');
            const tbody = table.querySelector('tbody');
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            
            // Remover classes de ordenação de todas as setas
            document.querySelectorAll('.sort-arrow').forEach(arrow => {
                arrow.classList.remove('up', 'down');
                arrow.classList.add('none');
            });
            
            // Determinar a direção da ordenação
            if (currentSortColumn === columnIndex) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortDirection = 'asc';
                currentSortColumn = columnIndex;
            }
            
            // Atualizar a seta da coluna clicada
            const clickedArrow = header.querySelector('.sort-arrow');
            if (clickedArrow) {
                clickedArrow.classList.remove('none');
                if (currentSortDirection === 'asc') {
                    clickedArrow.classList.add('up');
                } else {
                    clickedArrow.classList.add('down');
                }
            }
            
            // Ordenar as linhas
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(comparer(columnIndex, currentSortDirection === 'asc'));
            
            // Reordenar as linhas no tbody
            rows.forEach(row => tbody.appendChild(row));
        });
    });
    
    // Implementação da pesquisa em tempo real
    const searchInput = document.querySelector('input[name="search_query"]');
    if (searchInput) {
        let timeout = null;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const searchTerm = this.value;
            
            // Aguarda 300ms após o usuário parar de digitar antes de enviar a requisição
            timeout = setTimeout(function() {
                if (searchTerm.length >= 3) {
                    // Aqui você poderia implementar uma pesquisa em tempo real via AJAX
                    // Por enquanto, vamos apenas submeter o formulário automaticamente
                    searchInput.form.submit();
                } else if (searchTerm.length === 0) {
                    // Se o campo estiver vazio, submete o formulário para limpar a pesquisa
                    searchInput.form.submit();
                }
            }, 300);
        });
    }
});
</script>

<?php
mysqli_close($link);
require_once 'includes/footer.php';
?>