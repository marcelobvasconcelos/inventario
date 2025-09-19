<?php
// Definir o diretório base para facilitar os includes
$base_path = dirname(__DIR__);

require_once $base_path . '/includes/header.php';
require_once $base_path . '/config/db.php';
require_once 'config.php';

// --- Controle de Acesso ---
$allowed_roles = ['Administrador', 'Almoxarife', 'Visualizador', 'Gestor'];
if (!isset($_SESSION['permissao']) || !in_array($_SESSION['permissao'], $allowed_roles)) {
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para acessar este módulo.</div>";
    require_once $base_path . '/includes/footer.php';
    exit;
}

// Define se o usuário tem visão privilegiada
$is_privileged_user = in_array($_SESSION['permissao'], ['Administrador', 'Almoxarife']);

// --- Coleta de Dados para o Dashboard ---

// 1. Total de materiais cadastrados
$total_materiais = $pdo->query("SELECT COUNT(*) FROM almoxarifado_materiais")->fetchColumn();

// 2. Total de materiais sem estoque
$materiais_sem_estoque = $pdo->query("SELECT COUNT(*) FROM almoxarifado_materiais WHERE estoque_atual <= 0")->fetchColumn();

// 3. Total de materiais com estoque baixo (menos de 5 unidades)
$materiais_estoque_baixo = $pdo->query("SELECT COUNT(*) FROM almoxarifado_materiais WHERE estoque_atual > 0 AND estoque_atual < 5")->fetchColumn();

// 4. Valor total do estoque
$valor_total_estoque = $pdo->query("SELECT SUM(estoque_atual * valor_unitario) FROM almoxarifado_materiais")->fetchColumn();

// 5. Total de requisições pendentes
$total_requisicoes_pendentes = $pdo->query("SELECT COUNT(*) FROM almoxarifado_requisicoes WHERE status = 'pendente'")->fetchColumn();

// 6. Total de requisições aprovadas no mês
$data_inicio_mes = date('Y-m-01');
$total_requisicoes_aprovadas_mes = $pdo->prepare("SELECT COUNT(*) FROM almoxarifado_requisicoes WHERE status = 'aprovada' AND data_requisicao >= ?");
$total_requisicoes_aprovadas_mes->execute([$data_inicio_mes]);
$total_requisicoes_aprovadas_mes = $total_requisicoes_aprovadas_mes->fetchColumn();

// 7. 5 materiais mais requisitados no mês
$materiais_mais_requisitados = [];
if ($is_privileged_user) {
    // Detectar nome da coluna
    $sql_check_column = "SHOW COLUMNS FROM almoxarifado_requisicoes_itens";
    $stmt_check = $pdo->prepare($sql_check_column);
    $stmt_check->execute();
    $columns = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
    
    $column_name = 'produto_id'; // padrão
    foreach ($columns as $col) {
        if ($col['Field'] == 'material_id') {
            $column_name = 'material_id';
            break;
        } elseif ($col['Field'] == 'produto_id') {
            $column_name = 'produto_id';
            break;
        }
    }
    
    $sql_mais_requisitados = "
        SELECT 
            m.nome,
            SUM(ri.quantidade_solicitada) as total_quantidade
        FROM almoxarifado_requisicoes_itens ri
        JOIN almoxarifado_requisicoes r ON ri.requisicao_id = r.id
        JOIN almoxarifado_materiais m ON ri.$column_name = m.id
        WHERE r.data_requisicao >= ?
        GROUP BY m.id, m.nome
        ORDER BY total_quantidade DESC
        LIMIT 5
    ";
    $stmt_mais_requisitados = $pdo->prepare($sql_mais_requisitados);
    $stmt_mais_requisitados->execute([$data_inicio_mes]);
    $materiais_mais_requisitados = $stmt_mais_requisitados->fetchAll(PDO::FETCH_ASSOC);
}

// 8. Últimas 5 requisições
$ultimas_requisicoes = [];
$sql_ultimas_requisicoes = "
    SELECT 
        r.id,
        u.nome as usuario_nome,
        r.data_requisicao,
        r.status
    FROM almoxarifado_requisicoes r
    JOIN usuarios u ON r.usuario_id = u.id
    ORDER BY r.data_requisicao DESC
    LIMIT 5
";
$ultimas_requisicoes = $pdo->query($sql_ultimas_requisicoes)->fetchAll(PDO::FETCH_ASSOC);

// 9. Movimentações recentes (últimas 5 entradas/saídas)
$movimentacoes_recentes = [];
if ($is_privileged_user) {
    $sql_movimentacoes = "
        SELECT 
            m.nome as material_nome,
            mov.tipo,
            mov.quantidade,
            mov.data_movimentacao,
            u.nome as usuario_nome
        FROM almoxarifado_movimentacoes mov
        JOIN almoxarifado_materiais m ON mov.material_id = m.id
        JOIN usuarios u ON mov.usuario_id = u.id
        ORDER BY mov.data_movimentacao DESC
        LIMIT 5
    ";
    $movimentacoes_recentes = $pdo->query($sql_movimentacoes)->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="page-header-sticky">
    <div class="almoxarifado-header">
        <h2>Dashboard do Almoxarifado</h2>
        <?php require_once 'menu_almoxarifado.php'; ?>
    </div>
</div>

<div class="dashboard-container">
    <!-- Linha 1: Cards de Resumo -->
    <div class="dashboard-row">
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Total de Materiais</h3>
            </div>
            <div class="card-body">
                <div class="card-value"><?php echo $total_materiais; ?></div>
                <div class="card-icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Sem Estoque</h3>
            </div>
            <div class="card-body">
                <div class="card-value"><?php echo $materiais_sem_estoque; ?></div>
                <div class="card-icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Estoque Baixo</h3>
            </div>
            <div class="card-body">
                <div class="card-value"><?php echo $materiais_estoque_baixo; ?></div>
                <div class="card-icon"><i class="fas fa-sort-amount-down"></i></div>
            </div>
        </div>
        
        <?php if ($is_privileged_user): ?>
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Valor Total do Estoque</h3>
            </div>
            <div class="card-body">
                <div class="card-value">R$ <?php echo number_format($valor_total_estoque, 2, ',', '.'); ?></div>
                <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Linha 2: Cards de Requisições -->
    <div class="dashboard-row">
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Requisições Pendentes</h3>
            </div>
            <div class="card-body">
                <div class="card-value"><?php echo $total_requisicoes_pendentes; ?></div>
                <div class="card-icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Requisições Aprovadas (Mês)</h3>
            </div>
            <div class="card-body">
                <div class="card-value"><?php echo $total_requisicoes_aprovadas_mes; ?></div>
                <div class="card-icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>
    
    <!-- Linha 3: Tabelas de Dados -->
    <div class="dashboard-tables-section">
        <div class="tables-row">
            <!-- Coluna Esquerda: Tabelas Menores -->
            <div class="tables-column-left">
                <?php if ($is_privileged_user && !empty($materiais_mais_requisitados)): ?>
                <div class="dashboard-table-container">
                    <h3 class="table-title">Materiais Mais Requisitados (Mês)</h3>
                    <div class="table-responsive">
                        <table class="almoxarifado-table">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-center">Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($materiais_mais_requisitados as $material): ?>
                                <tr>
                                    <td class="material-name"><?php echo htmlspecialchars($material['nome']); ?></td>
                                    <td class="text-center"><?php echo $material['total_quantidade']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="dashboard-table-container">
                    <h3 class="table-title">Últimas Requisições</h3>
                    <div class="table-responsive">
                        <table class="almoxarifado-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Usuário</th>
                                    <th>Data</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($ultimas_requisicoes as $requisicao): ?>
                                <tr>
                                    <td class="req-code"><?php echo 'REQ-' . str_pad($requisicao['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td class="user-name"><?php echo htmlspecialchars($requisicao['usuario_nome']); ?></td>
                                    <td class="req-date"><?php echo date('d/m/Y', strtotime($requisicao['data_requisicao'])); ?></td>
                                    <td class="text-center">
                                        <?php 
                                            $status = $requisicao['status'];
                                            $status_class = '';
                                            switch($status) {
                                                case 'pendente': $status_class = 'badge-warning'; break;
                                                case 'aprovada': $status_class = 'badge-success'; break;
                                                case 'rejeitada': $status_class = 'badge-danger'; break;
                                                case 'concluida': $status_class = 'badge-info'; break;
                                                default: $status_class = 'badge-secondary';
                                            }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Coluna Direita: Tabela Maior -->
            <?php if ($is_privileged_user && !empty($movimentacoes_recentes)): ?>
            <div class="tables-column-right">
                <div class="dashboard-table-container">
                    <h3 class="table-title">Movimentações Recentes</h3>
                    <div class="table-responsive">
                        <table class="almoxarifado-table">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Quantidade</th>
                                    <th>Data</th>
                                    <th>Usuário</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($movimentacoes_recentes as $mov): ?>
                                <tr>
                                    <td class="material-name"><?php echo htmlspecialchars($mov['material_nome']); ?></td>
                                    <td class="text-center">
                                        <?php if($mov['tipo'] == 'entrada'): ?>
                                            <span class="badge badge-success">Entrada</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Saída</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo $mov['quantidade']; ?></td>
                                    <td class="mov-date"><?php echo date('d/m/Y H:i', strtotime($mov['data_movimentacao'])); ?></td>
                                    <td class="user-name"><?php echo htmlspecialchars($mov['usuario_nome']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 20px 0;
}

.dashboard-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px 20px -10px;
}

.dashboard-card {
    flex: 1;
    min-width: 250px;
    margin: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    background: #fff;
}

.card-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.card-body {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-value {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
}

.card-icon {
    font-size: 32px;
    color: #6c757d;
}

/* Nova seção de tabelas */
.dashboard-tables-section {
    margin-top: 20px;
}

.tables-row {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.tables-column-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-width: 0;
}

.tables-column-right {
    flex: 1.5;
    min-width: 0;
}

.dashboard-table-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    min-width: 0;
}

.table-title {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    text-align: center;
    font-size: 1.1em;
    font-weight: 600;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
}

.table-responsive {
    overflow-x: auto;
    margin: -5px;
    padding: 5px;
}

/* Estilos específicos das colunas */
.material-name {
    max-width: 200px;
    word-wrap: break-word;
    white-space: normal;
}

.user-name {
    max-width: 120px;
    word-wrap: break-word;
    white-space: normal;
}

.req-code {
    font-family: monospace;
    font-size: 0.9em;
    white-space: nowrap;
}

.req-date, .mov-date {
    white-space: nowrap;
    font-size: 0.9em;
}

.text-center {
    text-align: center;
}

/* Responsividade */
@media (max-width: 1200px) {
    .tables-row {
        flex-direction: column;
        gap: 20px;
    }
    
    .tables-column-left,
    .tables-column-right {
        flex: none;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .dashboard-row {
        flex-direction: column;
    }
    
    .dashboard-card,
    .dashboard-table-container {
        min-width: 100%;
    }
    
    .dashboard-table-container {
        padding: 15px;
    }
    
    .table-title {
        font-size: 1em;
    }
    
    .material-name,
    .user-name {
        max-width: none;
    }
}
</style>

<?php
require_once $base_path . '/includes/footer.php';
?>