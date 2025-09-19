<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../config.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$chart_type = $input['chart_type'] ?? 'bar';
$period = $input['period'] ?? '30days';
$data_type = $input['data_type'] ?? 'movimentacao';
$start_date = $input['start_date'] ?? null;
$end_date = $input['end_date'] ?? null;
$items = $input['items'] ?? [];

// Calcular intervalo de datas
$date_range = getDateRange($period, $start_date, $end_date);

// Buscar dados baseado no tipo
try {
    $chartData = getChartData($pdo, $data_type, $date_range, $items);
    echo json_encode(['success' => true, 'chartData' => $chartData]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getDateRange($period, $start_date, $end_date) {
    $now = new DateTime();
    $end = clone $now;

    switch ($period) {
        case '7days':
            $start = clone $now;
            $start->modify('-7 days');
            break;
        case '30days':
            $start = clone $now;
            $start->modify('-30 days');
            break;
        case 'quarter':
            $start = clone $now;
            $start->modify('-3 months');
            break;
        case 'year':
            $start = clone $now;
            $start->modify('-1 year');
            break;
        case 'custom':
            if (!$start_date || !$end_date) {
                throw new Exception('Datas personalizadas são obrigatórias');
            }
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            break;
        default:
            $start = clone $now;
            $start->modify('-30 days');
    }

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d')
    ];
}

function getChartData($pdo, $data_type, $date_range, $items) {
    switch ($data_type) {
        case 'movimentacao':
            return getMovimentacaoData($pdo, $date_range);
        case 'valor_estoque':
            return getValorEstoqueData($pdo, $date_range);
        case 'mais_requisitados':
            return getMaisRequisitadosData($pdo, $date_range);
        case 'estoque_baixo':
            return getEstoqueBaixoData($pdo);
        case 'requisicoes_status':
            return getRequisicoesStatusData($pdo, $date_range);
        case 'itens_especificos':
            return getItensEspecificosData($pdo, $date_range, $items);
        default:
            throw new Exception('Tipo de dados inválido');
    }
}

function getMovimentacaoData($pdo, $date_range) {
    $sql = "
        SELECT
            DATE(data_movimentacao) as data,
            tipo,
            SUM(quantidade) as total
        FROM almoxarifado_movimentacoes
        WHERE DATE(data_movimentacao) BETWEEN ? AND ?
        GROUP BY DATE(data_movimentacao), tipo
        ORDER BY data
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_range['start'], $date_range['end']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dataMap = [];
    foreach ($results as $row) {
        $data = $row['data'];
        if (!isset($dataMap[$data])) {
            $dataMap[$data] = ['entrada' => 0, 'saida' => 0];
        }
        $dataMap[$data][$row['tipo']] = (int)$row['total'];
    }

    $labels = array_keys($dataMap);
    $entradas = array_column($dataMap, 'entrada');
    $saidas = array_column($dataMap, 'saida');

    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Entradas',
                'data' => $entradas,
                'backgroundColor' => 'rgba(40, 167, 69, 0.5)',
                'borderColor' => 'rgba(40, 167, 69, 1)',
                'borderWidth' => 1
            ],
            [
                'label' => 'Saídas',
                'data' => $saidas,
                'backgroundColor' => 'rgba(220, 53, 69, 0.5)',
                'borderColor' => 'rgba(220, 53, 69, 1)',
                'borderWidth' => 1
            ]
        ]
    ];
}

function getValorEstoqueData($pdo, $date_range) {
    // Para valor do estoque, mostrar o valor total atual
    // Como é um valor único, usar gráfico de pizza ou barra simples
    $sql = "SELECT SUM(estoque_atual * valor_unitario) as valor_total FROM almoxarifado_materiais";
    $valor_total = $pdo->query($sql)->fetchColumn();

    return [
        'labels' => ['Valor Total do Estoque'],
        'datasets' => [
            [
                'label' => 'Valor (R$)',
                'data' => [$valor_total],
                'backgroundColor' => ['rgba(0, 123, 255, 0.5)'],
                'borderColor' => ['rgba(0, 123, 255, 1)'],
                'borderWidth' => 1
            ]
        ]
    ];
}

function getMaisRequisitadosData($pdo, $date_range) {
    // Detectar nome da coluna automaticamente
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
    
    $sql = "
        SELECT
            m.nome,
            SUM(ri.quantidade_solicitada) as total
        FROM almoxarifado_requisicoes_itens ri
        JOIN almoxarifado_requisicoes r ON ri.requisicao_id = r.id
        JOIN almoxarifado_materiais m ON ri.$column_name = m.id
        WHERE DATE(r.data_requisicao) BETWEEN ? AND ?
        GROUP BY m.id, m.nome
        HAVING total > 0
        ORDER BY total DESC
        LIMIT 5
    ";
    
    // Se não houver dados no período, buscar os últimos 90 dias
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_range['start'], $date_range['end']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não encontrou resultados, expandir período
    if (empty($results)) {
        $sql_fallback = "
            SELECT
                m.nome,
                SUM(ri.quantidade_solicitada) as total
            FROM almoxarifado_requisicoes_itens ri
            JOIN almoxarifado_requisicoes r ON ri.requisicao_id = r.id
            JOIN almoxarifado_materiais m ON ri.$column_name = m.id
            WHERE r.data_requisicao >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY m.id, m.nome
            HAVING total > 0
            ORDER BY total DESC
            LIMIT 5
        ";
        $stmt = $pdo->prepare($sql_fallback);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $labels = array_column($results, 'nome');
    $data = array_column($results, 'total');

    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Quantidade Requisitada',
                'data' => $data,
                'backgroundColor' => 'rgba(255, 193, 7, 0.5)',
                'borderColor' => 'rgba(255, 193, 7, 1)',
                'borderWidth' => 1
            ]
        ]
    ];
}

function getEstoqueBaixoData($pdo) {
    $sql = "
        SELECT nome, estoque_atual, estoque_minimo
        FROM almoxarifado_materiais
        WHERE estoque_atual < estoque_minimo AND estoque_atual > 0
        ORDER BY (estoque_minimo - estoque_atual) DESC
        LIMIT 10
    ";

    $results = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($results, 'nome');
    $data = array_column($results, 'estoque_atual');

    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Estoque Atual',
                'data' => $data,
                'backgroundColor' => 'rgba(255, 193, 7, 0.5)',
                'borderColor' => 'rgba(255, 193, 7, 1)',
                'borderWidth' => 1
            ]
        ]
    ];
}

function getRequisicoesStatusData($pdo, $date_range) {
    $sql = "
        SELECT status, COUNT(*) as total
        FROM almoxarifado_requisicoes
        WHERE DATE(data_requisicao) BETWEEN ? AND ?
        GROUP BY status
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_range['start'], $date_range['end']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_map(function($row) {
        return ucfirst($row['status']);
    }, $results);
    $data = array_column($results, 'total');

    $colors = [
        'pendente' => 'rgba(255, 193, 7, 0.5)',
        'aprovada' => 'rgba(40, 167, 69, 0.5)',
        'rejeitada' => 'rgba(220, 53, 69, 0.5)',
        'concluida' => 'rgba(0, 123, 255, 0.5)'
    ];

    $backgroundColors = array_map(function($row) use ($colors) {
        return $colors[$row['status']] ?? 'rgba(108, 117, 125, 0.5)';
    }, $results);

    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Número de Requisições',
                'data' => $data,
                'backgroundColor' => $backgroundColors,
                'borderWidth' => 1
            ]
        ]
    ];
}

function getItensEspecificosData($pdo, $date_range, $items) {
    if (empty($items)) {
        throw new Exception('Nenhum item selecionado');
    }

    // Para itens específicos, mostrar movimentação ou estoque
    // Por simplicidade, mostrar estoque atual
    $placeholders = str_repeat('?,', count($items) - 1) . '?';
    $sql = "
        SELECT nome, estoque_atual, valor_unitario
        FROM almoxarifado_materiais
        WHERE id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($items);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($results, 'nome');
    $data = array_map(function($row) {
        return $row['estoque_atual'] * $row['valor_unitario'];
    }, $results);

    return [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Valor em Estoque (R$)',
                'data' => $data,
                'backgroundColor' => 'rgba(0, 123, 255, 0.5)',
                'borderColor' => 'rgba(0, 123, 255, 1)',
                'borderWidth' => 1
            ]
        ]
    ];
}
?>