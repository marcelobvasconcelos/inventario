<?php
// Configurações específicas do módulo almoxarifado

// Definições de constantes para o módulo
define('ALMOXARIFADO_MODULE_PATH', __DIR__);
define('ALMOXARIFADO_BASE_URL', '/almoxarifado');

// Configurações de paginação
define('ALMOXARIFADO_ITENS_POR_PAGINA', 20);

// Tipos de movimentação
define('ALMOXARIFADO_ENTRADA', 'entrada');
define('ALMOXARIFADO_SAIDA', 'saida');

// Status de materiais
define('ALMOXARIFADO_MATERIAL_ATIVO', 'ativo');
define('ALMOXARIFADO_MATERIAL_INATIVO', 'inativo');

// Funções auxiliares do módulo
function formatar_quantidade($quantidade) {
    return number_format($quantidade, 2, ',', '.');
}

function formatar_valor($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function get_nome_usuario($usuario_id, $pdo) {
    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nome'] : 'Desconhecido';
}

function get_nome_material($material_id, $pdo) {
    $stmt = $pdo->prepare("SELECT nome FROM materiais WHERE id = ?");
    $stmt->execute([$material_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['nome'] : 'Desconhecido';
}

?>