<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SESSION["permissao"] != 'Administrador'){
    echo "Acesso negado.";
    exit;
}

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Lógica para aprovar/rejeitar usuários
if(isset($_GET['acao']) && isset($_GET['id'])){
    $acao = $_GET['acao'];
    $usuario_id = $_GET['id'];

    if($acao == 'aprovar'){
        $novo_status = 'aprovado';
    } elseif($acao == 'rejeitar'){
        $novo_status = 'rejeitado';
    } elseif($acao == 'pendente'){
        $novo_status = 'pendente';
    }

    if(isset($novo_status)){
        $update_sql = "UPDATE usuarios SET status = ? WHERE id = ?";
        if($stmt = mysqli_prepare($link, $update_sql)){
            $refs = [];
            $params_update = ["si", $novo_status, $usuario_id];
            foreach($params_update as $key => $value)
                $refs[$key] = &$params_update[$key];
            call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
            if(mysqli_stmt_execute($stmt)){
                header("location: usuarios.php?pagina=" . $pagina_atual); // Redireciona para limpar a URL e manter a página
                exit();
            } else {
                echo "Erro ao atualizar o status do usuário.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Consulta para contagem total de usuários
$sql_count = "SELECT COUNT(*) FROM usuarios u JOIN perfis p ON u.permissao_id = p.id";
$result_count = mysqli_query($link, $sql_count);
$total_usuarios = mysqli_fetch_row($result_count)[0];
mysqli_free_result($result_count);

$total_paginas = ceil($total_usuarios / $itens_por_pagina);

// Consulta para os usuários da página atual
$sql = "SELECT u.id, u.nome, u.email, p.nome as perfil_nome, u.status 
        FROM usuarios u
        JOIN perfis p ON u.permissao_id = p.id
        ORDER BY u.nome ASC LIMIT ? OFFSET ?";

if($stmt = mysqli_prepare($link, $sql)){
    $refs = [];
    $params_main = ["ii", $itens_por_pagina, $offset];
    foreach($params_main as $key => $value)
        $refs[$key] = &$params_main[$key];
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}
?>

<h2>Gerenciar Usuários</h2>
<a href="usuario_add.php" class="btn-custom">Adicionar Novo Usuário</a>

<table class="table-striped table-hover">
    <thead>
        <tr>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="nome">Nome <span class="sort-arrow"></span></th>
            <th data-column="email">Email <span class="sort-arrow"></span></th>
            <th data-column="perfil_nome">Permissão <span class="sort-arrow"></span></th>
            <th data-column="status">Status <span class="sort-arrow"></span></th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><a href="usuario_itens.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['nome']); ?></a></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['perfil_nome']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($row['status'])); ?></td>
                <td>
                    <?php if($row['status'] == 'pendente'): ?>
                        <a href="usuarios.php?acao=aprovar&id=<?php echo $row['id']; ?>&pagina=<?php echo $pagina_atual; ?>" class="btn btn-aprovar">Aprovar</a>
                        <a href="usuarios.php?acao=rejeitar&id=<?php echo $row['id']; ?>&pagina=<?php echo $pagina_atual; ?>" class="btn btn-rejeitar">Rejeitar</a>
                    <?php else: ?>
                        <a href="usuario_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-editar">Editar</a>
                        <a href="usuario_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Excluir</a>
                        <a href="usuarios.php?acao=pendente&id=<?php echo $row['id']; ?>&pagina=<?php echo $pagina_atual; ?>" class="btn btn-pendente">Pendente</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">Nenhum usuário encontrado.</td>
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