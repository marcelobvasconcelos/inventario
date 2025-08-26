<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Verificar se o usuário está logado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Obter estatísticas do sistema
try {
    // Contar itens totais
    $stmt_itens = $pdo->prepare("SELECT COUNT(*) as total FROM itens WHERE estado != 'Excluido'");
    $stmt_itens->execute();
    $total_itens = $stmt_itens->fetchColumn();
    
    // Contar locais
    $stmt_locais = $pdo->prepare("SELECT COUNT(*) as total FROM locais");
    $stmt_locais->execute();
    $total_locais = $stmt_locais->fetchColumn();
    
    // Contar usuários
    $stmt_usuarios = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE nome != 'Lixeira'");
    $stmt_usuarios->execute();
    $total_usuarios = $stmt_usuarios->fetchColumn();
    
    // Contar movimentações
    $stmt_movimentacoes = $pdo->prepare("SELECT COUNT(*) as total FROM movimentacoes");
    $stmt_movimentacoes->execute();
    $total_movimentacoes = $stmt_movimentacoes->fetchColumn();
    
    // Contar notificações pendentes (apenas para usuários não administradores)
    $notif_count = 0;
    if ($_SESSION["permissao"] != 'Administrador') {
        $stmt_notif = $pdo->prepare("SELECT COUNT(*) as total FROM itens WHERE responsavel_id = ? AND status_confirmacao = 'Pendente'");
        $stmt_notif->execute([$_SESSION['id']]);
        $notif_count = $stmt_notif->fetchColumn();
    } else {
        // Para administradores, contar notificações de movimentação pendentes
        $stmt_notif = $pdo->prepare("SELECT COUNT(*) as total FROM notificacoes_movimentacao WHERE status_confirmacao = 'Pendente'");
        $stmt_notif->execute();
        $notif_count = $stmt_notif->fetchColumn();
    }
    
    // Contar itens por estado
    $stmt_estados = $pdo->prepare("SELECT estado, COUNT(*) as total FROM itens WHERE estado != 'Excluido' GROUP BY estado");
    $stmt_estados->execute();
    $itens_por_estado = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar itens por local (top 5)
    $stmt_top_locais = $pdo->prepare("SELECT l.nome, COUNT(i.id) as total FROM locais l JOIN itens i ON l.id = i.local_id WHERE i.estado != 'Excluido' GROUP BY l.id, l.nome ORDER BY total DESC LIMIT 5");
    $stmt_top_locais->execute();
    $top_locais = $stmt_top_locais->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erro ao carregar estatísticas: " . $e->getMessage() . "</div>";
    exit;
}
?>

<h2>Dashboard do Sistema de Inventário</h2>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_itens; ?></h3>
            <p>Itens Totais</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_locais; ?></h3>
            <p>Locais</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_usuarios; ?></h3>
            <p>Usuários</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_movimentacoes; ?></h3>
            <p>Movimentações</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-danger">
            <i class="fas fa-bell"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $notif_count; ?></h3>
            <p>Notificações Pendentes</p>
        </div>
    </div>
</div>

<div class="dashboard-content">
    <div class="dashboard-section">
        <h3><i class="fas fa-chart-bar"></i> Itens por Estado</h3>
        <div class="chart-container">
            <canvas id="itensPorEstadoChart"></canvas>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h3><i class="fas fa-map-marked-alt"></i> Top 5 Locais com Mais Itens</h3>
        <div class="chart-container">
            <canvas id="topLocaisChart"></canvas>
        </div>
    </div>
</div>

<div class="atalhos-container">
    <div class="atalho-item" onclick="window.location.href='itens.php'">
        <i class="fas fa-boxes fa-2x"></i>
        <h3>Itens</h3>
        <p>Gerenciar itens do inventário</p>
    </div>
    
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
    <div class="atalho-item" onclick="window.location.href='itens_excluidos.php'">
        <i class="fas fa-trash-alt fa-2x"></i>
        <h3>Lixeira</h3>
        <p>Itens excluídos</p>
    </div>
    <?php endif; ?>
    
    <div class="atalho-item" onclick="window.location.href='locais.php'">
        <i class="fas fa-map-marker-alt fa-2x"></i>
        <h3>Locais</h3>
        <p>Gerenciar locais de armazenamento</p>
    </div>
    
    <div class="atalho-item" onclick="window.location.href='movimentacoes.php'">
        <i class="fas fa-exchange-alt fa-2x"></i>
        <h3>Movimentações</h3>
        <p>Histórico de movimentações</p>
    </div>
    
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
    <div class="atalho-item" onclick="window.location.href='usuarios.php'">
        <i class="fas fa-users fa-2x"></i>
        <h3>Usuários</h3>
        <p>Gerenciar usuários do sistema</p>
    </div>
    <?php endif; ?>
    
    <div class="atalho-item" onclick="window.location.href='notificacoes_usuario.php'">
        <i class="fas fa-bell fa-2x"></i>
        <h3>Minhas Notificações</h3>
        <p>Ver notificações pendentes</p>
    </div>
    
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
    <div class="atalho-item" onclick="window.location.href='notificacoes_admin.php'">
        <i class="fas fa-tasks fa-2x"></i>
        <h3>Gerenciar Notificações</h3>
        <p>Administrar todas as notificações</p>
    </div>
    <?php endif; ?>
    
    <?php if($_SESSION["permissao"] == 'Administrador' || $_SESSION["permissao"] == 'Gestor'): ?>
    <div class="atalho-item" onclick="window.location.href='item_add.php'">
        <i class="fas fa-plus-circle fa-2x"></i>
        <h3>Adicionar Item</h3>
        <p>Cadastrar novo item</p>
    </div>
    <?php endif; ?>
    
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
    <div class="atalho-item" onclick="window.location.href='movimentacao_add.php'">
        <i class="fas fa-arrow-right fa-2x"></i>
        <h3>Movimentar Itens</h3>
        <p>Registrar nova movimentação</p>
    </div>
    <?php endif; ?>
    
    <div class="atalho-item" onclick="window.location.href='usuario_perfil.php'">
        <i class="fas fa-user fa-2x"></i>
        <h3>Meu Perfil</h3>
        <p>Editar perfil e senha</p>
    </div>
    
    <?php if($_SESSION["permissao"] == 'Administrador'): ?>
    <div class="atalho-item" onclick="window.location.href='configuracoes_pdf.php'">
        <i class="fas fa-file-pdf fa-2x"></i>
        <h3>Configurações PDF</h3>
        <p>Configurar relatórios</p>
    </div>
    <?php endif; ?>
    
    <div class="atalho-item" onclick="window.location.href='docs.php'">
        <i class="fas fa-question-circle fa-2x"></i>
        <h3>Ajuda</h3>
        <p>Manual do usuário</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de itens por estado
    const estadoCtx = document.getElementById('itensPorEstadoChart').getContext('2d');
    const itensPorEstadoChart = new Chart(estadoCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php 
                foreach($itens_por_estado as $estado) {
                    echo "'" . htmlspecialchars($estado['estado']) . "',";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach($itens_por_estado as $estado) {
                        echo $estado['total'] . ",";
                    }
                    ?>
                ],
                backgroundColor: [
                    '#007bff',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#17a2b8'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw;
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Gráfico de top locais
    const locaisCtx = document.getElementById('topLocaisChart').getContext('2d');
    const topLocaisChart = new Chart(locaisCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach($top_locais as $local) {
                    echo "'" . htmlspecialchars($local['nome']) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Número de Itens',
                data: [
                    <?php 
                    foreach($top_locais as $local) {
                        echo $local['total'] . ",";
                    }
                    ?>
                ],
                backgroundColor: '#007bff',
                borderColor: '#0056b3',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>