<?php
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Buscar estatísticas
// Total de categorias
$sql_categorias = "SELECT COUNT(*) as total FROM almoxarifado_categorias";
$stmt_categorias = $pdo->prepare($sql_categorias);
$stmt_categorias->execute();
$total_categorias = $stmt_categorias->fetch(PDO::FETCH_ASSOC)['total'];

// Total de empenhos
$sql_empenhos = "SELECT COUNT(*) as total FROM empenhos_insumos";
$stmt_empenhos = $pdo->prepare($sql_empenhos);
$stmt_empenhos->execute();
$total_empenhos = $stmt_empenhos->fetch(PDO::FETCH_ASSOC)['total'];

// Total de notas fiscais
$sql_notas = "SELECT COUNT(*) as total FROM notas_fiscais";
$stmt_notas = $pdo->prepare($sql_notas);
$stmt_notas->execute();
$total_notas = $stmt_notas->fetch(PDO::FETCH_ASSOC)['total'];

// Total de materiais
$sql_materiais = "SELECT COUNT(*) as total FROM almoxarifado_materiais";
$stmt_materiais = $pdo->prepare($sql_materiais);
$stmt_materiais->execute();
$total_materiais = $stmt_materiais->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Módulo de Controle de Empenhos</h2>
        <?php
        $is_privileged_user = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <?php require_once 'menu_empenhos.php'; ?>

    <?php
    // Buscar todos os empenhos para listagem
    $sql_empenhos_lista = "SELECT * FROM empenhos_insumos ORDER BY data_emissao DESC";
    $stmt_empenhos_lista = $pdo->prepare($sql_empenhos_lista);
    $stmt_empenhos_lista->execute();
    $empenhos_lista = $stmt_empenhos_lista->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if(!empty($empenhos_lista)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h3>Lista de Empenhos</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Data de Emissão</th>
                            <th>Valor</th>
                            <th>Saldo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($empenhos_lista as $empenho): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($empenho['numero']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($empenho['data_emissao'])); ?></td>
                                <td>R$ <?php echo number_format($empenho['valor'] ?? 0, 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($empenho['saldo'] ?? 0, 2, ',', '.'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $empenho['status'] == 'Aberto' ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($empenho['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="empenho_edit.php?numero=<?php echo $empenho['numero']; ?>" class="btn btn-xs btn-warning" title="Editar Empenho">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="empenho_delete.php?numero=<?php echo $empenho['numero']; ?>" class="btn btn-xs btn-danger" title="Excluir Empenho" onclick="return confirm('Tem certeza que deseja excluir este empenho?')">
                                        <i class="fas fa-trash fa-xs"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Fluxo de Trabalho</h3>
        </div>
        <div class="card-body">
            <ol class="lista-numerada-alinhada">
                <li><strong>Cadastrar Categorias:</strong> Primeiro, cadastre as categorias de materiais no sistema para organizar os produtos.</li>
                <li><strong>Cadastrar Empenhos:</strong> Registre os empenhos recebidos com fornecedor, valor e data de emissão.</li>
                <li><strong>Cadastrar Notas Fiscais:</strong> Para cada empenho, registre as notas fiscais correspondentes. O valor da nota será descontado do saldo do empenho.</li>
                <li><strong>Cadastrar Materiais:</strong> Cadastre os materiais vinculando-os às categorias. Opcionalmente, vincule à nota fiscal para rastreabilidade.</li>
                <li><strong>Registrar Entradas:</strong> Use a funcionalidade "Entrada de Material" para dar entrada nos produtos físicos, vinculando-os às notas fiscais e atualizando o estoque.</li>
            </ol>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> <strong>Observações importantes:</strong>
                <ul class="mb-0 mt-2 lista-alinhada">
                    <li>Materiais podem ser cadastrados independentemente de notas fiscais, apenas com categoria</li>
                    <li>A entrada física dos materiais deve ser registrada separadamente para atualizar o estoque</li>
                    <li>O sistema controla automaticamente os saldos dos empenhos conforme as notas fiscais são cadastradas</li>
                    <li>Todas as movimentações de estoque são registradas para auditoria</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.lista-numerada-alinhada {
    text-align: left;
    padding-left: 20px;
}

.lista-numerada-alinhada li {
    text-align: left;
    margin-bottom: 8px;
    padding-left: 5px;
}

.lista-alinhada {
    text-align: left;
    padding-left: 20px;
}

.lista-alinhada li {
    text-align: left;
    margin-bottom: 5px;
    padding-left: 5px;
}
</style>

<?php
require_once '../includes/footer.php';
?>
