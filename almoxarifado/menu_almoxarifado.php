<?php
// Componente de menu reutilizável para o módulo de almoxarifado.
// A página que incluir este arquivo deve definir as variáveis de permissão.
?>
<a href="index.php" class="btn-custom"><i class="fas fa-home"></i> Início</a>
<a href="dashboard.php" class="btn-custom"><i class="fas fa-chart-line"></i> Dashboard</a>
<a href="estatisticas.php" class="btn-custom"><i class="fas fa-chart-bar"></i> Estatísticas</a>
<a href="requisicao.php" class="btn-custom"><i class="fas fa-plus"></i> Nova Requisição</a>
<a href="notificacoes.php" class="btn-custom"><i class="fas fa-bell"></i> Minhas Notificações</a>
<?php if (isset($_SESSION["permissao"]) && $_SESSION["permissao"] == 'Administrador'): ?>
    <a href="admin_notificacoes.php" class="btn-custom"><i class="fas fa-tasks"></i> Gerenciar Requisições</a>
    <a href="empenhos_index.php" class="btn-custom"><i class="fas fa-file-invoice-dollar"></i> Gestão Financeira</a>
    <a href="entrada_material.php" class="btn-custom"><i class="fas fa-arrow-down"></i> Registrar Entrada de Material</a>
    <a href="historico_saldo_empenhos.php" class="btn-custom"><i class="fas fa-history"></i> Histórico de Empenhos</a>
<?php endif; ?>