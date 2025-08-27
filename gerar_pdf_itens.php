<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['permissao'] != 'Administrador') {
    die("Acesso negado.");
}

require 'config/db.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Buscar configurações (logo)
$stmt_config = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('logo_path')");
$configuracoes = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
$logo_path = $configuracoes['logo_path'] ?? '';

// Receber dados do POST
$search_by = $_POST['search_by'] ?? '';
$search_query = $_POST['search_query'] ?? '';
$cabecalho_pdf = $_POST['cabecalho_pdf'] ?? 'Relatório de Inventário';
$selected_item_ids = $_POST['selected_item_ids'] ?? []; // IDs dos itens selecionados

// Montar a consulta SQL com base nos filtros (sem paginação)
$sql_base = "SELECT i.id, i.nome, i.patrimonio_novo, i.patrimonio_secundario, l.nome AS local, u.nome AS responsavel, i.estado FROM itens i JOIN locais l ON i.local_id = l.id JOIN usuarios u ON i.responsavel_id = u.id";
$where_clause = " WHERE i.estado != 'Excluido'"; // Sempre excluir itens marcados como 'Excluido'
$params = [];

// Se houver itens selecionados, filtrar por esses IDs
if (!empty($selected_item_ids)) {
    // Converter para inteiros para segurança
    $selected_item_ids = array_map('intval', $selected_item_ids);
    
    // Criar placeholders para os IDs
    $placeholders = str_repeat('?,', count($selected_item_ids) - 1) . '?';
    
    // Adicionar condição WHERE para os IDs selecionados
    $where_clause .= " AND i.id IN ($placeholders)";
    $params = array_merge($params, $selected_item_ids);
} else {
    // Se não houver itens selecionados, aplicar os filtros de pesquisa normais
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $field_map = [
            'id' => 'i.id',
            'patrimonio_novo' => 'i.patrimonio_novo',
            'patrimonio_secundario' => 'i.patrimonio_secundario',
            'local' => 'l.nome',
            'responsavel' => 'u.nome'
        ];

        if (array_key_exists($search_by, $field_map)) {
            $where_clause .= " AND " . $field_map[$search_by] . " LIKE ?";
            $params[] = $search_term;
        } else {
            $where_clause .= " AND i.nome LIKE ?"; // Fallback para nome
            $params[] = $search_term;
        }
    }
}

$sql = $sql_base . $where_clause . " ORDER BY i.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Montar o HTML para o PDF
$html = '<!DOCTYPE html>';
$html .= '<html lang="pt-br">';
$html .= '<head>';
$html .= '<meta charset="UTF-8">';
$html .= '<title>Relatório de Itens</title>';
$html .= '<style>';
$html .= 'body { font-family: sans-serif; }';
$html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
$html .= 'th, td { border: 1px solid #dddddd; padding: 8px; text-align: left; }';
$html .= 'th { background-color: #f2f2f2; }';
$html .= '.header { text-align: center; margin-bottom: 20px; }';
$html .= '.header img { max-height: 80px; max-width: 200px; }';
$html .= '.cabecalho-texto { margin-top: 15px; text-align: justify; }';
$html .= '</style>';
$html .= '</head>';
$html .= '<body>';

// Cabeçalho com Logo
$html .= '<div class="header">';
if (!empty($logo_path) && file_exists($logo_path)) {
    // Para o DomPDF ler a imagem, é melhor usar o caminho absoluto ou converter para base64
    $tipo_imagem = pathinfo($logo_path, PATHINFO_EXTENSION);
    $data_imagem = file_get_contents($logo_path);
    $imagem_base64 = 'data:image/' . $tipo_imagem . ';base64,' . base64_encode($data_imagem);
    $html .= '<img src="' . $imagem_base64 . '" alt="Logo">';
}
$html .= '</div>';

// Texto do Cabeçalho personalizado
if (!empty($cabecalho_pdf)) {
    $html .= '<div class="cabecalho-texto">';
    // Usar htmlentities com UTF-8 para melhor tratamento de caracteres especiais
    $html .= nl2br(htmlentities($cabecalho_pdf, ENT_QUOTES, 'UTF-8'));
    $html .= '</div>';
}

// Tabela de Itens
$html .= '<table>';
$html .= '<thead>';
$html .= '<tr><th>ID</th><th>Nome</th><th>Patrimônio</th><th>Pat. Secundário</th><th>Local</th><th>Responsável</th><th>Estado</th></tr>';
$html .= '</thead>';
$html .= '<tbody>';

if (count($itens) > 0) {
    foreach ($itens as $item) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['nome']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['patrimonio_novo']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['patrimonio_secundario']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['local']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['responsavel']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['estado']) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="7">Nenhum item encontrado para os critérios de pesquisa.</td></tr>';
}

$html .= '</tbody>';
$html .= '</table>';
$html .= '</body>';
$html .= '</html>';

// Configurar e renderizar o PDF
$options = new Options();
$options->set('isRemoteEnabled', true); // Necessário para carregar imagens
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Formato paisagem para caber mais colunas
$dompdf->render();

// Enviar o PDF para o navegador
$dompdf->stream("relatorio_inventario.pdf", ["Attachment" => 0]); // 0 para abrir no navegador, 1 para forçar download

?>
