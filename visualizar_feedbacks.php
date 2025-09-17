<?php
// visualizar_feedbacks.php - Script para visualizar feedbacks recebidos
require_once 'includes/header.php';

// Verificar permissões - apenas administradores podem acessar
if($_SESSION["permissao"] != 'Administrador'){
    echo "<div class='alert alert-danger'>Acesso negado. Você não tem permissão para executar esta ação.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Verificar se o arquivo de feedback existe
$arquivo = 'feedback_testes_sistema.csv';
if (!file_exists($arquivo)) {
    echo "<div class='alert alert-info'>Nenhum feedback recebido ainda.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Ler o arquivo CSV
$linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (count($linhas) <= 1) {
    echo "<div class='alert alert-info'>Nenhum feedback recebido ainda.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Processar cabeçalhos
$cabecalhos = str_getcsv($linhas[0]);

// Processar dados
$feedbacks = [];
for ($i = 1; $i < count($linhas); $i++) {
    $dados = str_getcsv($linhas[$i]);
    $feedback = [];
    for ($j = 0; $j < count($cabecalhos); $j++) {
        $feedback[$cabecalhos[$j]] = isset($dados[$j]) ? $dados[$j] : '';
    }
    $feedbacks[] = $feedback;
}

// Calcular estatísticas
$total_feedbacks = count($feedbacks);

// Estatísticas por perfil
$perfis = [];
foreach ($feedbacks as $feedback) {
    $perfil = $feedback['perfil_usuario'];
    if (!isset($perfis[$perfil])) {
        $perfis[$perfil] = 0;
    }
    $perfis[$perfil]++;
}

// Estatísticas de avaliação (escalas 1-10)
$avaliacoes = [
    'facilidade_uso' => [],
    'eficiencia_sistema' => [],
    'aparencia_sistema' => []
];

foreach ($feedbacks as $feedback) {
    foreach (['facilidade_uso', 'eficiencia_sistema', 'aparencia_sistema'] as $avaliacao) {
        if (!empty($feedback[$avaliacao]) && is_numeric($feedback[$avaliacao])) {
            $avaliacoes[$avaliacao][] = (int)$feedback[$avaliacao];
        }
    }
}

// Calcular médias
$medias = [];
foreach ($avaliacoes as $tipo => $valores) {
    if (count($valores) > 0) {
        $medias[$tipo] = round(array_sum($valores) / count($valores), 1);
    } else {
        $medias[$tipo] = 0;
    }
}

?>

<div class="container">
    <h1>📊 Relatório de Feedbacks Recebidos</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Estatísticas Gerais</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <h4><?php echo $total_feedbacks; ?></h4>
                        <p>Total de Feedbacks</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <h4><?php echo count($perfis); ?></h4>
                        <p>Perfis Distintos</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <h4><?php echo isset($medias['facilidade_uso']) ? $medias['facilidade_uso'] : 'N/A'; ?>/10</h4>
                        <p>Média de Facilidade de Uso</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Distribuição por Perfil</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($perfis as $perfil => $quantidade): ?>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h4><?php echo $quantidade; ?></h4>
                            <p><?php echo ucfirst($perfil); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Avaliações Médias</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <h4><?php echo isset($medias['facilidade_uso']) ? $medias['facilidade_uso'] : 'N/A'; ?>/10</h4>
                        <p>Facilidade de Uso</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <h4><?php echo isset($medias['eficiencia_sistema']) ? $medias['eficiencia_sistema'] : 'N/A'; ?>/10</h4>
                        <p>Eficiência do Sistema</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <h4><?php echo isset($medias['aparencia_sistema']) ? $medias['aparencia_sistema'] : 'N/A'; ?>/10</h4>
                        <p>Aparência do Sistema</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Feedbacks Detalhados</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Perfil</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Facilidade de Uso</th>
                            <th>Eficiência</th>
                            <th>Aparência</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($feedback['data_hora']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($feedback['perfil_usuario'])); ?></td>
                                <td><?php echo htmlspecialchars($feedback['nome_usuario'] ?? 'Anônimo'); ?></td>
                                <td><?php echo htmlspecialchars($feedback['email_usuario'] ?? 'Não informado'); ?></td>
                                <td><?php echo htmlspecialchars($feedback['facilidade_uso'] ?? 'N/A'); ?>/10</td>
                                <td><?php echo htmlspecialchars($feedback['eficiencia_sistema'] ?? 'N/A'); ?>/10</td>
                                <td><?php echo htmlspecialchars($feedback['aparencia_sistema'] ?? 'N/A'); ?>/10</td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="verDetalhes('<?php echo urlencode(json_encode($feedback)); ?>')">
                                        <i class="fas fa-eye"></i> Ver Detalhes
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalhes do feedback -->
<div class="modal fade" id="detalhesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Feedback</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detalhesModalBody">
                <!-- Conteúdo será preenchido dinamicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalhes(feedbackEncoded) {
    // Decodificar o feedback
    const feedbackJson = decodeURIComponent(feedbackEncoded);
    const feedback = JSON.parse(feedbackJson);
    
    // Construir o conteúdo do modal
    let conteudo = '<div class="container-fluid">';
    
    // Informações básicas
    conteudo += '<div class="row mb-3"><div class="col-12"><h5>Informações Básicas</h5></div></div>';
    conteudo += '<div class="row">';
    conteudo += '<div class="col-md-4"><strong>Perfil:</strong> ' + (feedback.perfil_usuario ? feedback.perfil_usuario.charAt(0).toUpperCase() + feedback.perfil_usuario.slice(1) : 'Não informado') + '</div>';
    conteudo += '<div class="col-md-4"><strong>Nome:</strong> ' + (feedback.nome_usuario || 'Anônimo') + '</div>';
    conteudo += '<div class="col-md-4"><strong>E-mail:</strong> ' + (feedback.email_usuario || 'Não informado') + '</div>';
    conteudo += '</div>';
    conteudo += '<div class="row mb-3">';
    conteudo += '<div class="col-md-4"><strong>Data/Hora:</strong> ' + feedback.data_hora + '</div>';
    conteudo += '<div class="col-md-4"><strong>Facilidade de Uso:</strong> ' + (feedback.facilidade_uso || 'N/A') + '/10</div>';
    conteudo += '<div class="col-md-4"><strong>Eficiência:</strong> ' + (feedback.eficiencia_sistema || 'N/A') + '/10</div>';
    conteudo += '</div>';
    conteudo += '<div class="row mb-4">';
    conteudo += '<div class="col-md-4"><strong>Aparência:</strong> ' + (feedback.aparencia_sistema || 'N/A') + '/10</div>';
    conteudo += '</div>';
    
    // Seção por seção
    const secoes = {
        'autenticacao_comentarios': 'Autenticação e Perfis',
        'legado_comentarios': 'Módulo Legado',
        'almoxarifado_comentarios': 'Módulo Almoxarifado',
        'integracao_comentarios': 'Integração entre Módulos',
        'ergonomia_comentarios': 'Ergonomia e Usabilidade',
        'seguranca_comentarios': 'Segurança',
        'performance_comentarios': 'Performance',
        'funcionalidades_adicionar': 'Funcionalidades para Adicionar',
        'funcionalidades_aprimorar': 'Funcionalidades para Aprimorar',
        'comentarios_finais': 'Comentários Finais',
        'problemas_encontrados': 'Problemas Encontrados',
        'sugestoes_especificas': 'Sugestões Específicas'
    };
    
    for (const [campo, titulo] of Object.entries(secoes)) {
        if (feedback[campo] && feedback[campo].trim() !== '') {
            conteudo += '<div class="row mb-3"><div class="col-12"><h5>' + titulo + '</h5>';
            conteudo += '<p>' + escapeHtml(feedback[campo]).replace(/\n/g, '<br>') + '</p></div></div>';
        }
    }
    
    conteudo += '</div>';
    
    // Preencher o modal
    document.getElementById('detalhesModalBody').innerHTML = conteudo;
    
    // Mostrar o modal
    $('#detalhesModal').modal('show');
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>

<style>
.stat-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-card h4 {
    color: #124a80;
    margin-bottom: 10px;
}

.table th {
    background-color: #eef2f7;
    font-weight: 600;
}
</style>

<?php
require_once 'includes/footer.php';
?>