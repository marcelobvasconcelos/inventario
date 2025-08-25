<?php
require_once 'includes/header.php';
require_once 'config/db.php';

if($_SESSION["permissao"] != 'Administrador'){
    echo "Acesso negado.";
    exit;
}

// Variável para armazenar a senha temporária gerada
$senha_temporaria_gerada = "";

// Processar ação de gerar senha temporária
if(isset($_POST['gerar_senha_temporaria']) && isset($_POST['usuario_id'])){
    $usuario_id = $_POST['usuario_id'];
    
    // Gerar uma senha temporária aleatória
    $senha_temporaria = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
    
    // Atualizar a senha do usuário e marcar como senha temporária
    $sql_update = "UPDATE usuarios SET senha = ?, senha_temporaria = 1 WHERE id = ?";
    if($stmt_update = mysqli_prepare($link, $sql_update)){
        mysqli_stmt_bind_param($stmt_update, "si", $senha_hash, $usuario_id);
        if(mysqli_stmt_execute($stmt_update)){
            // Armazenar a senha temporária para exibir
            $senha_temporaria_gerada = $senha_temporaria;
        } else {
            $mensagem = "Erro ao gerar senha temporária.";
        }
        mysqli_stmt_close($stmt_update);
    }
}

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
                header("location: usuarios.php"); // Redireciona para limpar a URL
                exit();
            } else {
                echo "Erro ao atualizar o status do usuário.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Consulta para contagem total de usuários
$sql_count = "SELECT COUNT(*) FROM usuarios u JOIN perfis p ON u.permissao_id = p.id";
$result_count = mysqli_query($link, $sql_count);
$total_usuarios = mysqli_fetch_row($result_count)[0];
mysqli_free_result($result_count);

$total_paginas = ceil($total_usuarios / $itens_por_pagina);

// Consulta para os usuários da página atual, incluindo informações sobre solicitações de senha
$sql = "SELECT u.id, u.nome, u.email, p.nome as perfil_nome, u.status,
               (SELECT COUNT(*) FROM solicitacoes_senha WHERE usuario_id = u.id AND status = 'pendente') as solicitacoes_pendentes
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

<style>
.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: .25rem;
}

.alert-success strong {
    font-size: 1.2em;
}

.alert-success span {
    display: block;
    margin: 10px 0;
    font-size: 1.5em;
    font-weight: bold;
    color: #d9534f;
    font-family: 'Courier New', monospace;
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border: 1px dashed #ccc;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
    line-height: 1.5;
    border-radius: 3px;
}

.btn-aprovar, .btn-rejeitar, .btn-editar, .btn-excluir, .btn-pendente, .btn-warning {
    margin: 2px;
}
</style>

<?php if(!empty($senha_temporaria_gerada)): ?>
    <div class="alert alert-success">
        <strong>Senha temporária gerada com sucesso!</strong><br>
        <span><?php echo $senha_temporaria_gerada; ?></span><br>
        <small>Copie esta senha e envie para o usuário. Ela será válida apenas para o primeiro acesso.</small>
    </div>
<?php endif; ?>

<table class="table-striped table-hover">
    <thead>
        <tr>
            <th data-column="id">ID <span class="sort-arrow"></span></th>
            <th data-column="nome">Nome <span class="sort-arrow"></span></th>
            <th data-column="email">Email <span class="sort-arrow"></span></th>
            <th data-column="perfil_nome">Permissão <span class="sort-arrow"></span></th>
            <th data-column="status">Status <span class="sort-arrow"></span></th>
            <th>Solicitações de Senha</th>
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
                    <?php if($row['solicitacoes_pendentes'] > 0): ?>
                        <span class="badge badge-warning"><?php echo $row['solicitacoes_pendentes']; ?> pendente(s)</span>
                    <?php else: ?>
                        <span class="badge badge-success">Nenhuma</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($row['status'] == 'pendente'): ?>
                        <a href="usuarios.php?acao=aprovar&id=<?php echo $row['id']; ?>" class="btn btn-aprovar btn-sm" title="Aprovar">
                            <i class="fas fa-check"></i>
                        </a>
                        <a href="usuarios.php?acao=rejeitar&id=<?php echo $row['id']; ?>" class="btn btn-rejeitar btn-sm" title="Rejeitar">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php else: ?>
                        <a href="usuario_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-editar btn-sm" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="usuario_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-excluir btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                            <i class="fas fa-trash"></i>
                        </a>
                        <a href="usuarios.php?acao=pendente&id=<?php echo $row['id']; ?>" class="btn btn-pendente btn-sm" title="Marcar como Pendente">
                            <i class="fas fa-clock"></i>
                        </a>
                        
                        <!-- Botão para gerar senha temporária -->
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="usuario_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="gerar_senha_temporaria" class="btn btn-warning btn-sm" title="Gerar Senha Temporária"
                                    <?php echo ($row['solicitacoes_pendentes'] > 0) ? '' : 'disabled'; ?>
                                    onclick="return confirm('Tem certeza que deseja gerar uma senha temporária para este usuário?');">
                                <i class="fas fa-key"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Nenhum usuário encontrado.</td>
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