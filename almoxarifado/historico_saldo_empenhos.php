<?php
// historico_saldo_empenhos.php - Página para visualizar histórico de alterações de saldo dos empenhos
require_once '../includes/header.php';
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once '../includes/footer.php';
    exit;
}

// Configurações de paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Buscar histórico de alterações
$sql_count = "SELECT COUNT(*) as total FROM empenhos_saldo_historico";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute();
$total_registros = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta para o histórico da página atual
$sql_historico = "SELECT h.*, nf.fornecedor, u.nome as usuario_nome 
                  FROM empenhos_saldo_historico h
                  LEFT JOIN notas_fiscais nf ON h.empenho_numero COLLATE utf8mb4_general_ci = nf.empenho_numero COLLATE utf8mb4_general_ci
                  JOIN usuarios u ON h.usuario_id = u.id
                  ORDER BY h.data_alteracao DESC
                  LIMIT " . (int)$offset . ", " . (int)$itens_por_pagina;
$stmt_historico = $pdo->prepare($sql_historico);
$stmt_historico->execute();
$historico = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Histórico de Alterações de Saldo dos Empenhos</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <?php if(empty($historico)): ?>
        <div class="alert alert-info">Nenhum registro de histórico encontrado.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3>Registros de Histórico</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Empenho</th>
                                <th>Fornecedor</th>
                                <th>Saldo Anterior</th>
                                <th>Saldo Novo</th>
                                <th>Valor Alteração</th>
                                <th>Tipo</th>
                                <th>Usuário</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($historico as $registro): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($registro['data_alteracao'])); ?></td>
                                    <td><?php echo htmlspecialchars($registro['empenho_numero']); ?></td>
                                    <td><?php echo htmlspecialchars($registro['fornecedor'] ?? 'N/A'); ?></td>
                                    <td>R$ <?php echo number_format($registro['saldo_anterior'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($registro['saldo_novo'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php if($registro['valor_alteracao'] > 0): ?>
                                            <span class="text-success">
                                                +R$ <?php echo number_format($registro['valor_alteracao'], 2, ',', '.'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-danger">
                                                R$ <?php echo number_format($registro['valor_alteracao'], 2, ',', '.'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo ($registro['tipo_alteracao'] == 'entrada') ? 'success' : 
                                                 (($registro['tipo_alteracao'] == 'saida') ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($registro['tipo_alteracao']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($registro['usuario_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($registro['descricao'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($total_paginas > 1): ?>
                <nav aria-label="Navegação de página">
                    <ul class="pagination justify-content-center">
                        <?php if($pagina_atual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?php echo $pagina_atual - 1; ?>">Anterior</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($pagina_atual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?php echo $pagina_atual + 1; ?>">Próximo</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
}

.card-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    font-weight: 600;
}

.card-header h3 {
    margin: 0;
    font-size: 1.2em;
}

.pagination .page-link {
    color: #28a745;
}

.pagination .page-item.active .page-link {
    background-color: #28a745;
    border-color: #28a745;
}
</style>

<?php
require_once '../includes/footer.php';
?>