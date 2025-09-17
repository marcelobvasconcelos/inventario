<?php
// material_detalhes.php - Página para visualizar detalhes de um material
require_once '../config/db.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    header('Location: ../index.php');
    exit;
}

// Verificar se foi passado um ID de material
if(!isset($_GET['id']) || empty($_GET['id'])){
    header("Location: index.php");
    exit;
}

$material_id = (int)$_GET['id'];

// Buscar dados do material
$sql_material = "SELECT * FROM almoxarifado_materiais WHERE id = ?";
$stmt_material = $pdo->prepare($sql_material);
$stmt_material->execute([$material_id]);
$material = $stmt_material->fetch(PDO::FETCH_ASSOC);

if(!$material){
    header("Location: index.php");
    exit;
}

// Buscar todas as entradas do material
$sql_entradas = "SELECT * FROM almoxarifado_entradas WHERE material_id = ? ORDER BY data_entrada DESC, id DESC";
$stmt_entradas = $pdo->prepare($sql_entradas);
$stmt_entradas->execute([$material_id]);
$entradas = $stmt_entradas->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as saídas do material
$sql_saidas = "SELECT * FROM almoxarifado_saidas WHERE material_id = ? ORDER BY data_saida DESC, id DESC";
$stmt_saidas = $pdo->prepare($sql_saidas);
$stmt_saidas->execute([$material_id]);
$saidas = $stmt_saidas->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as movimentações do material
$sql_movimentacoes = "SELECT * FROM almoxarifado_movimentacoes WHERE material_id = ? ORDER BY data_movimentacao DESC, id DESC";
$stmt_movimentacoes = $pdo->prepare($sql_movimentacoes);
$stmt_movimentacoes->execute([$material_id]);
$movimentacoes = $stmt_movimentacoes->fetchAll(PDO::FETCH_ASSOC);

// Buscar em quais empenhos e notas fiscais o material entrou
$sql_empenhos_notas = "SELECT DISTINCT nf.empenho_numero, nf.nota_numero, nf.nota_valor, nf.fornecedor, nf.cnpj,
                              ei.valor as valor_empenho, ei.saldo, ei.status as status_empenho
                        FROM almoxarifado_entradas ae
                        JOIN notas_fiscais nf ON ae.nota_fiscal = nf.nota_numero
                        JOIN empenhos_insumos ei ON nf.empenho_numero = ei.numero
                        WHERE ae.material_id = ?
                        ORDER BY nf.empenho_numero, nf.nota_numero";
$stmt_empenhos_notas = $pdo->prepare($sql_empenhos_notas);
$stmt_empenhos_notas->execute([$material_id]);
$empenhos_notas = $stmt_empenhos_notas->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os empenhos relacionados ao material (mesmo sem notas fiscais)
$sql_todos_empenhos = "SELECT DISTINCT ei.numero, nf.fornecedor, nf.cnpj, ei.valor as valor_empenho,
                              ei.saldo, ei.status as status_empenho, ei.data_emissao
                        FROM empenhos_insumos ei
                        JOIN notas_fiscais nf ON ei.numero = nf.empenho_numero
                        JOIN almoxarifado_entradas ae ON nf.nota_numero = ae.nota_fiscal
                        WHERE ae.material_id = ?
                        ORDER BY ei.numero";
$stmt_todos_empenhos = $pdo->prepare($sql_todos_empenhos);
$stmt_todos_empenhos->execute([$material_id]);
$todos_empenhos = $stmt_todos_empenhos->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas de uso
$sql_estatisticas = "SELECT
    COUNT(DISTINCT ae.id) as total_entradas,
    SUM(ae.quantidade) as quantidade_total_entrada,
    COUNT(DISTINCT asai.id) as total_saidas,
    SUM(asai.quantidade) as quantidade_total_saida,
    AVG(ae.valor_unitario) as valor_medio_entrada,
    MIN(ae.data_entrada) as primeira_entrada,
    MAX(ae.data_entrada) as ultima_entrada,
    MIN(asai.data_saida) as primeira_saida,
    MAX(asai.data_saida) as ultima_saida
FROM almoxarifado_materiais am
LEFT JOIN almoxarifado_entradas ae ON am.id = ae.material_id
LEFT JOIN almoxarifado_saidas asai ON am.id = asai.material_id
WHERE am.id = ?";
$stmt_estatisticas = $pdo->prepare($sql_estatisticas);
$stmt_estatisticas->execute([$material_id]);
$estatisticas = $stmt_estatisticas->fetch(PDO::FETCH_ASSOC);

// Buscar requisições pendentes para este material
$sql_requisicoes_pendentes = "SELECT COUNT(*) as total_pendentes,
                                    SUM(CASE WHEN ar.status = 'pendente' THEN ari.quantidade_solicitada ELSE 0 END) as quantidade_pendente
                             FROM almoxarifado_requisicoes ar
                             JOIN almoxarifado_requisicoes_itens ari ON ar.id = ari.requisicao_id
                             WHERE ari.produto_id = ? AND ar.status IN ('pendente', 'aprovada')";
$stmt_requisicoes_pendentes = $pdo->prepare($sql_requisicoes_pendentes);
$stmt_requisicoes_pendentes->execute([$material_id]);
$requisicoes_pendentes = $stmt_requisicoes_pendentes->fetch(PDO::FETCH_ASSOC);

// Calcular valor total do estoque
$valor_total_estoque = $material['estoque_atual'] * $material['valor_unitario'];

// Calcular quantidade disponível para requisição
$quantidade_disponivel = $material['estoque_atual'] - ($requisicoes_pendentes['quantidade_pendente'] ?? 0);

// Determinar status do estoque
if ($material['estoque_atual'] <= 0) {
    $status_estoque = 'Sem estoque';
    $status_class = 'danger';
} elseif ($material['estoque_atual'] < $material['estoque_minimo']) {
    $status_estoque = 'Estoque baixo';
    $status_class = 'warning';
} else {
    $status_estoque = 'Estoque normal';
    $status_class = 'success';
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="almoxarifado-header">
        <h2>Detalhes do Material</h2>
        <?php
        $is_privileged_user = true;
        require_once 'menu_almoxarifado.php';
        ?>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Informações Gerais</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Código:</strong> <?php echo htmlspecialchars($material['codigo']); ?></p>
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($material['nome']); ?></p>
                    <p><strong>Descrição:</strong> <?php echo htmlspecialchars($material['descricao'] ?? 'Não informado'); ?></p>
                    <p><strong>Categoria:</strong> <?php echo htmlspecialchars($material['categoria']); ?></p>
                    <p><strong>Unidade de Medida:</strong> <?php echo htmlspecialchars($material['unidade_medida']); ?></p>
                    <p><strong>Nota Fiscal Vinculada:</strong> 
                        <?php if (!empty($material['nota_fiscal'])): ?>
                            <span class="badge badge-info"><?php echo htmlspecialchars($material['nota_fiscal']); ?></span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Nenhuma</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Data de Criação:</strong> 
                        <?php echo !empty($material['data_criacao']) ? date('d/m/Y H:i:s', strtotime($material['data_criacao'])) : 'N/A'; ?>
                    </p>
                    <p><strong>Usuário de Criação:</strong> 
                        <?php 
                        // Buscar nome do usuário de criação
                        if (!empty($material['usuario_criacao'])) {
                            $sql_usuario = "SELECT nome FROM usuarios WHERE id = ?";
                            $stmt_usuario = $pdo->prepare($sql_usuario);
                            $stmt_usuario->execute([$material['usuario_criacao']]);
                            $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
                            echo $usuario ? htmlspecialchars($usuario['nome']) : 'N/A';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status do Estoque:</strong>
                        <span class="badge badge-<?php echo $status_class; ?> ml-2">
                            <?php echo $status_estoque; ?>
                        </span>
                    </p>
                    <p><strong>Estoque Mínimo:</strong> <?php echo number_format($material['estoque_minimo'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></p>
                    <p><strong>Estoque Atual:</strong> <?php echo number_format($material['estoque_atual'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></p>
                    <p><strong>Quantidade Disponível:</strong> <?php echo number_format($quantidade_disponivel, 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></p>
                    <p><strong>Valor Unitário Médio:</strong> R$ <?php echo number_format($material['valor_unitario'], 2, ',', '.'); ?></p>
                    <p><strong>Valor Total do Estoque:</strong> R$ <?php echo number_format($valor_total_estoque, 2, ',', '.'); ?></p>
                </div>
            </div>

            <?php if($requisicoes_pendentes['total_pendentes'] > 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Requisições Pendentes:</strong>
                        <?php echo $requisicoes_pendentes['total_pendentes']; ?> requisição(ões) pendente(s) totalizando
                        <?php echo number_format($requisicoes_pendentes['quantidade_pendente'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estatísticas de Uso -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>Estatísticas de Uso</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h4><?php echo number_format($estatisticas['total_entradas'] ?? 0); ?></h4>
                        <p class="text-muted">Total de Entradas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4><?php echo number_format($estatisticas['quantidade_total_entrada'] ?? 0, 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></h4>
                        <p class="text-muted">Quantidade Total Entrada</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4><?php echo number_format($estatisticas['total_saidas'] ?? 0); ?></h4>
                        <p class="text-muted">Total de Saídas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4><?php echo number_format($estatisticas['quantidade_total_saida'] ?? 0, 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></h4>
                        <p class="text-muted">Quantidade Total Saída</p>
                    </div>
                </div>
            </div>

            <?php if($estatisticas['primeira_entrada'] || $estatisticas['ultima_entrada']): ?>
            <div class="row mt-3">
                <div class="col-md-6">
                    <p><strong>Primeira Entrada:</strong>
                        <?php echo $estatisticas['primeira_entrada'] ? date('d/m/Y', strtotime($estatisticas['primeira_entrada'])) : 'N/A'; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Última Entrada:</strong>
                        <?php echo $estatisticas['ultima_entrada'] ? date('d/m/Y', strtotime($estatisticas['ultima_entrada'])) : 'N/A'; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Todos os Empenhos Relacionados -->
    <?php if(!empty($todos_empenhos)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Todos os Empenhos Relacionados</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Número do Empenho</th>
                                <th>Data Emissão</th>
                                <th>Fornecedor</th>
                                <th>CNPJ</th>
                                <th>Valor Total</th>
                                <th>Saldo Restante</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($todos_empenhos as $empenho): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($empenho['numero']); ?></td>
                                    <td><?php echo $empenho['data_emissao'] ? date('d/m/Y', strtotime($empenho['data_emissao'])) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($empenho['fornecedor']); ?></td>
                                    <td><?php echo htmlspecialchars($empenho['cnpj']); ?></td>
                                    <td>R$ <?php echo number_format($empenho['valor_empenho'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($empenho['saldo'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($empenho['status_empenho'] == 'Aberto') ? 'success' : 'secondary'; ?>">
                                            <?php echo htmlspecialchars($empenho['status_empenho']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notas Fiscais Detalhadas -->
    <?php if(!empty($empenhos_notas)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Notas Fiscais e Entradas Associadas</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Empenho</th>
                                <th>Nota Fiscal</th>
                                <th>Data Emissão</th>
                                <th>Valor da Nota</th>
                                <th>Fornecedor</th>
                                <th>CNPJ</th>
                                <th>Status Empenho</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($empenhos_notas as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['empenho_numero']); ?></td>
                                    <td><?php echo htmlspecialchars($item['nota_numero']); ?></td>
                                    <td><?php echo date('d/m/Y'); // Nota fiscal não tem data, usar data atual como referência ?></td>
                                    <td>R$ <?php echo number_format($item['nota_valor'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($item['fornecedor']); ?></td>
                                    <td><?php echo htmlspecialchars($item['cnpj']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($item['status_empenho'] == 'Aberto') ? 'success' : 'secondary'; ?>">
                                            <?php echo htmlspecialchars($item['status_empenho']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($entradas)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Histórico de Entradas Detalhado</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Data Entrada</th>
                                <th>Nota Fiscal</th>
                                <th>Empenho</th>
                                <th>Quantidade</th>
                                <th>Valor Unitário</th>
                                <th>Valor Total</th>
                                <th>Fornecedor</th>
                                <th>CNPJ</th>
                                <th>Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Buscar informações completas das entradas com JOIN
                            $sql_entradas_completas = "SELECT ae.*, nf.empenho_numero, nf.fornecedor, nf.cnpj, u.nome as usuario_nome
                                                      FROM almoxarifado_entradas ae
                                                      LEFT JOIN notas_fiscais nf ON ae.nota_fiscal = nf.nota_numero
                                                      LEFT JOIN usuarios u ON ae.usuario_id = u.id
                                                      WHERE ae.material_id = ?
                                                      ORDER BY ae.data_entrada DESC, ae.id DESC";
                            $stmt_entradas_completas = $pdo->prepare($sql_entradas_completas);
                            $stmt_entradas_completas->execute([$material_id]);
                            $entradas_completas = $stmt_entradas_completas->fetchAll(PDO::FETCH_ASSOC);

                            foreach($entradas_completas as $entrada): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($entrada['data_entrada'])); ?></td>
                                    <td><?php echo htmlspecialchars($entrada['nota_fiscal'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entrada['empenho_numero'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($entrada['quantidade'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                    <td>R$ <?php echo number_format($entrada['valor_unitario'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($entrada['quantidade'] * $entrada['valor_unitario'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($entrada['fornecedor'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entrada['cnpj'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entrada['usuario_nome'] ?? 'Sistema'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($saidas)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Histórico de Saídas Detalhado</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Data Saída</th>
                                <th>Quantidade</th>
                                <th>Valor Unitário Médio</th>
                                <th>Valor Total</th>
                                <th>Setor/Local Destino</th>
                                <th>Responsável</th>
                                <th>Usuário</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Buscar informações completas das saídas
                            $sql_saidas_completas = "SELECT asai.*, u.nome as usuario_nome
                                                    FROM almoxarifado_saidas asai
                                                    LEFT JOIN usuarios u ON asai.usuario_id = u.id
                                                    WHERE asai.material_id = ?
                                                    ORDER BY asai.data_saida DESC, asai.id DESC";
                            $stmt_saidas_completas = $pdo->prepare($sql_saidas_completas);
                            $stmt_saidas_completas->execute([$material_id]);
                            $saidas_completas = $stmt_saidas_completas->fetchAll(PDO::FETCH_ASSOC);

                            foreach($saidas_completas as $saida): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($saida['data_saida'])); ?></td>
                                    <td><?php echo number_format($saida['quantidade'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                    <td>R$ <?php echo number_format($material['valor_unitario'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($saida['quantidade'] * $material['valor_unitario'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($saida['setor_destino'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($saida['responsavel_saida'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($saida['usuario_nome'] ?? 'Sistema'); ?></td>
                                    <td><?php echo htmlspecialchars($saida['observacao'] ?? 'Saída padrão'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Requisições Pendentes -->
    <?php
    $sql_requisicoes_detalhes = "SELECT ar.id, ar.data_requisicao, ar.status, ari.quantidade_solicitada,
                                        u.nome as usuario_nome, ar.justificativa
                                 FROM almoxarifado_requisicoes ar
                                 JOIN almoxarifado_requisicoes_itens ari ON ar.id = ari.requisicao_id
                                 JOIN usuarios u ON ar.usuario_id = u.id
                                 WHERE ari.produto_id = ? AND ar.status IN ('pendente', 'aprovada')
                                 ORDER BY ar.data_requisicao DESC";
    $stmt_requisicoes_detalhes = $pdo->prepare($sql_requisicoes_detalhes);
    $stmt_requisicoes_detalhes->execute([$material_id]);
    $requisicoes_detalhes = $stmt_requisicoes_detalhes->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if(!empty($requisicoes_detalhes)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Requisições Pendentes</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Data Requisição</th>
                                <th>Usuário</th>
                                <th>Quantidade Solicitada</th>
                                <th>Status</th>
                                <th>Justificativa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requisicoes_detalhes as $requisicao): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($requisicao['data_requisicao'])); ?></td>
                                    <td><?php echo htmlspecialchars($requisicao['usuario_nome']); ?></td>
                                    <td><?php echo number_format($requisicao['quantidade_solicitada'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($requisicao['status'] == 'aprovada') ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($requisicao['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($requisicao['justificativa'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if(!empty($movimentacoes)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Histórico Completo de Movimentações</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Tipo</th>
                                <th>Quantidade</th>
                                <th>Saldo Anterior</th>
                                <th>Saldo Atual</th>
                                <th>Referência</th>
                                <th>Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Buscar movimentações com informações do usuário
                            $sql_movimentacoes_completas = "SELECT am.*, u.nome as usuario_nome
                                                           FROM almoxarifado_movimentacoes am
                                                           LEFT JOIN usuarios u ON am.usuario_id = u.id
                                                           WHERE am.material_id = ?
                                                           ORDER BY am.data_movimentacao DESC, am.id DESC";
                            $stmt_movimentacoes_completas = $pdo->prepare($sql_movimentacoes_completas);
                            $stmt_movimentacoes_completas->execute([$material_id]);
                            $movimentacoes_completas = $stmt_movimentacoes_completas->fetchAll(PDO::FETCH_ASSOC);

                            foreach($movimentacoes_completas as $movimentacao): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($movimentacao['data_movimentacao'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($movimentacao['tipo'] == 'entrada') ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($movimentacao['tipo']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($movimentacao['quantidade'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                    <td><?php echo number_format($movimentacao['saldo_anterior'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                    <td><?php echo number_format($movimentacao['saldo_atual'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                    <td><?php echo htmlspecialchars($movimentacao['referencia_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($movimentacao['usuario_nome'] ?? 'Sistema'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Histórico de Requisições Atendidas -->
    <?php
    $sql_requisicoes_atendidas = "SELECT ar.id, ar.data_requisicao, ari.quantidade_entregue,
                                        u.nome as usuario_nome, ar.status
                                 FROM almoxarifado_requisicoes ar
                                 JOIN almoxarifado_requisicoes_itens ari ON ar.id = ari.requisicao_id
                                 JOIN usuarios u ON ar.usuario_id = u.id
                                 WHERE ari.produto_id = ? AND ari.quantidade_entregue > 0
                                 ORDER BY ar.data_requisicao DESC";
    $stmt_requisicoes_atendidas = $pdo->prepare($sql_requisicoes_atendidas);
    $stmt_requisicoes_atendidas->execute([$material_id]);
    $requisicoes_atendidas = $stmt_requisicoes_atendidas->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if(!empty($requisicoes_atendidas)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3>Histórico de Requisições Atendidas</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Data da Requisição</th>
                                <th>Usuário</th>
                                <th>Quantidade Fornecida</th>
                                <th>Status da Requisição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requisicoes_atendidas as $requisicao): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($requisicao['data_requisicao'])); ?></td>
                                    <td><?php echo htmlspecialchars($requisicao['usuario_nome']); ?></td>
                                    <td><?php echo number_format($requisicao['quantidade_entregue'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($requisicao['status'] == 'concluida') ? 'success' : 'info'; ?>">
                                            <?php echo ucfirst($requisicao['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Resumo Executivo -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>Resumo Executivo</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Status Atual do Material</h5>
                    <ul class="list-unstyled">
                        <li><strong>Status do Estoque:</strong>
                            <span class="badge badge-<?php echo $status_class; ?> ml-2"><?php echo $status_estoque; ?></span>
                        </li>
                        <li><strong>Estoque Atual:</strong> <?php echo number_format($material['estoque_atual'], 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></li>
                        <li><strong>Quantidade Disponível:</strong> <?php echo number_format($quantidade_disponivel, 2, ',', '.'); ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></li>
                        <li><strong>Valor Total do Estoque:</strong> R$ <?php echo number_format($valor_total_estoque, 2, ',', '.'); ?></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Atividade Recente</h5>
                    <ul class="list-unstyled">
                        <li><strong>Total de Entradas:</strong> <?php echo number_format($estatisticas['total_entradas'] ?? 0); ?></li>
                        <li><strong>Total de Saídas:</strong> <?php echo number_format($estatisticas['total_saidas'] ?? 0); ?></li>
                        <li><strong>Requisições Pendentes:</strong> <?php echo $requisicoes_pendentes['total_pendentes'] ?? 0; ?></li>
                        <li><strong>Requisições Atendidas:</strong> <?php echo count($requisicoes_atendidas); ?></li>
                        <li><strong>Última Movimentação:</strong>
                            <?php echo !empty($movimentacoes_completas) ? date('d/m/Y', strtotime($movimentacoes_completas[0]['data_movimentacao'])) : 'Nenhuma'; ?>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if(!empty($todos_empenhos)): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <h5>Fornecedores Associados</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fornecedor</th>
                                    <th>CNPJ</th>
                                    <th>Empenhos</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $fornecedores_unicos = [];
                                foreach($todos_empenhos as $empenho) {
                                    $key = $empenho['fornecedor'] . '|' . $empenho['cnpj'];
                                    if (!isset($fornecedores_unicos[$key])) {
                                        $fornecedores_unicos[$key] = [
                                            'fornecedor' => $empenho['fornecedor'],
                                            'cnpj' => $empenho['cnpj'],
                                            'empenhos' => 1,
                                            'status' => $empenho['status_empenho']
                                        ];
                                    } else {
                                        $fornecedores_unicos[$key]['empenhos']++;
                                    }
                                }
                                foreach($fornecedores_unicos as $fornecedor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fornecedor['fornecedor']); ?></td>
                                        <td><?php echo htmlspecialchars($fornecedor['cnpj']); ?></td>
                                        <td><?php echo $fornecedor['empenhos']; ?> empenho(s)</td>
                                        <td>
                                            <span class="badge badge-<?php echo ($fornecedor['status'] == 'Aberto') ? 'success' : 'secondary'; ?>">
                                                <?php echo htmlspecialchars($fornecedor['status']); ?>
                                            </span>
                                        </td>
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
.stats-card {
    text-align: center;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.stats-number {
    font-size: 2em;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 5px;
}

.stats-label {
    color: #6c757d;
    font-size: 0.9em;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-indicator.success { background-color: #28a745; }
.status-indicator.warning { background-color: #ffc107; }
.status-indicator.danger { background-color: #dc3545; }
.status-indicator.secondary { background-color: #6c757d; }

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
</style>

<?php
require_once '../includes/footer.php';
?>