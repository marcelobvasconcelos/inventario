<?php
require_once 'includes/header.php';
require_once 'config/db.php'; // Conexão com o banco para a consulta

// Buscar últimas movimentações
//comentário
if ($_SESSION['permissao'] == 'Administrador') {
    $sql = "SELECT 
                m.data_movimentacao, 
                i.nome as item_nome, 
                lo.nome as local_origem, 
                ld.nome as local_destino, 
                u.nome as usuario_nome 
            FROM movimentacoes m
            JOIN itens i ON m.item_id = i.id
            JOIN locais lo ON m.local_origem_id = lo.id
            JOIN locais ld ON m.local_destino_id = ld.id
            JOIN usuarios u ON m.usuario_id = u.id
            ORDER BY m.data_movimentacao DESC
            LIMIT 10";
} else {
    $usuario_id = $_SESSION['id'];
    $sql = "SELECT 
                m.data_movimentacao, 
                i.nome as item_nome, 
                lo.nome as local_origem, 
                ld.nome as local_destino, 
                u.nome as usuario_nome 
            FROM movimentacoes m
            JOIN itens i ON m.item_id = i.id
            JOIN locais lo ON m.local_origem_id = lo.id
            JOIN locais ld ON m.local_destino_id = ld.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE i.responsavel_id = ?
            ORDER BY m.data_movimentacao DESC
            LIMIT 10";
}

if($stmt = mysqli_prepare($link, $sql)){
    if ($_SESSION['permissao'] != 'Administrador') {
        mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    }
    mysqli_stmt_execute($stmt);
    $movimentacoes = mysqli_stmt_get_result($stmt);
}

?>

<h2>Bem-vindo ao Sistema de Inventário</h2>
<p>Selecione uma das opções no menu acima ou use os atalhos abaixo para começar.</p>

<div class="atalhos-container">
    <a href="itens.php" class="atalho-item">
        <i class="fas fa-box"></i>
        <span>Itens</span>
    </a>
    <a href="locais.php" class="atalho-item">
        <i class="fas fa-map-marker-alt"></i>
        <span>Locais</span>
    </a>
    <a href="movimentacoes.php" class="atalho-item">
        <i class="fas fa-exchange-alt"></i>
        <span>Movimentações</span>
    </a>
    <a href="almoxarifado/index.php" class="atalho-item">
        <i class="fas fa-warehouse"></i>
        <span>Almoxarifado</span>
    </a>
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
    <a href="usuarios.php" class="atalho-item">
        <i class="fas fa-users"></i>
        <span>Usuários</span>
    </a>
    <?php endif; ?>
</div>

<div class="atividades-recentes">
    <h3>Últimas Atividades</h3>
    <?php if(mysqli_num_rows($movimentacoes) > 0): ?>
        <table class="table-striped table-hover">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Item</th>
                    <th>De</th>
                    <th>Para</th>
                    <th>Realizado por</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($movimentacoes)): ?>
                    <tr>
                        <td><?php echo date("d/m/Y H:i", strtotime($row['data_movimentacao'])); ?></td>
                        <td><?php echo $row['item_nome']; ?></td>
                        <td><?php echo $row['local_origem']; ?></td>
                        <td><?php echo $row['local_destino']; ?></td>
                        <td><?php echo $row['usuario_nome']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma movimentação registrada ainda.</p>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>