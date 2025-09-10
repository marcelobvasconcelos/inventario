<?php
// Componente de menu reutilizável para o módulo de almoxarifado.
// A página que incluir este arquivo deve definir as variáveis de permissão.
?>
<a href="dashboard.php" class="btn-custom"><i class="fas fa-chart-line"></i> Dashboard</a>
<a href="requisicao.php" class="btn-custom"><i class="fas fa-plus"></i> Nova Requisição</a>
<a href="notificacoes.php" class="btn-custom"><i class="fas fa-bell"></i> Minhas Notificações</a>
<?php if (isset($is_privileged_user) && $is_privileged_user): ?>
    <a href="material_add.php" class="btn-custom">Adicionar Material</a>
<?php endif; ?>
<?php if (isset($_SESSION["permissao"]) && $_SESSION["permissao"] == 'Administrador'): ?>
    <a href="admin_notificacoes.php" class="btn-custom">Gerenciar Requisições</a>
    <a href="empenhos_index.php" class="btn-custom">Gerenciar Empenhos</a>
<?php endif; ?>