<?php
require_once 'includes/header.php';
?>

<h2>Teste de Botão Verde</h2>

<div style="padding: 20px;">
    <p>Botão padrão:</p>
    <a href="#" class="btn-custom">Botão Verde</a>
    
    <p style="margin-top: 20px;">Botão dentro de um card:</p>
    <div class="card" style="width: 300px; padding: 20px;">
        <a href="#" class="btn-custom">Botão no Card</a>
    </div>
    
    <p style="margin-top: 20px;">Botão no módulo almoxarifado:</p>
    <div class="almoxarifado">
        <a href="#" class="btn-custom">Botão Almoxarifado</a>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>