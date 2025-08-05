<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Validação da sessão e permissão
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Validação do ID do usuário
if(!isset($_GET['id']) || empty(trim($_GET['id'])) || !ctype_digit($_GET['id'])){
    echo "<main><h2>ID de usuário inválido.</h2></main>";
    require_once 'includes/footer.php';
    exit;
}

$usuario_id = $_GET['id'];

// Buscar nome do usuário
$user_sql = "SELECT nome FROM usuarios WHERE id = ?";
$user_nome = "Usuário não encontrado";
if($stmt_user = mysqli_prepare($link, $user_sql)){
    mysqli_stmt_bind_param($stmt_user, "i", $usuario_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    if($user_row = mysqli_fetch_assoc($result_user)){
        $user_nome = $user_row['nome'];
    }
    mysqli_stmt_close($stmt_user);
}

// Buscar itens do usuário
$sql = "SELECT i.id, i.nome, i.patrimonio_novo, l.nome as local_nome, i.estado 
        FROM itens i 
        JOIN locais l ON i.local_id = l.id 
        WHERE i.responsavel_id = ? 
        ORDER BY i.nome ASC";

$itens = [];
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $itens[] = $row;
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>

<main>
    <h2>Itens Sob Responsabilidade de <?php echo htmlspecialchars($user_nome); ?></h2>

    <?php if(!empty($itens)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome do Item</th>
                <th>Patrimônio</th>
                <th>Local</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($itens as $item): ?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['nome']); ?></td>
                <td><?php echo htmlspecialchars($item['patrimonio_novo']); ?></td>
                <td><?php echo htmlspecialchars($item['local_nome']); ?></td>
                <td><?php echo htmlspecialchars($item['estado']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>Este usuário não possui nenhum item sob sua responsabilidade no momento.</p>
    <?php endif; ?>

    <br>
    <a href="usuarios.php" class="btn btn-editar">Voltar para Usuários</a>
</main>

<?php
require_once 'includes/footer.php';
?>
