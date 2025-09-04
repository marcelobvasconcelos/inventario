<?php
// verificar_usuario_visualizador.php - Script para verificar se o usuário tem o perfil "Visualizador"
session_start();

echo "Verificando sessão do usuário...\n\n";

// Verificar se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "Usuário não está logado.\n";
    exit(1);
}

// Exibir informações da sessão
echo "Informações da sessão:\n";
echo "- ID do usuário: " . (isset($_SESSION["id"]) ? $_SESSION["id"] : "Não definido") . "\n";
echo "- Nome do usuário: " . (isset($_SESSION["nome"]) ? $_SESSION["nome"] : "Não definido") . "\n";
echo "- Perfil do usuário: " . (isset($_SESSION["permissao"]) ? $_SESSION["permissao"] : "Não definido") . "\n";

// Verificar se o perfil é "Visualizador"
if (isset($_SESSION["permissao"]) && $_SESSION["permissao"] == "Visualizador") {
    echo "\n✓ O usuário tem o perfil 'Visualizador'.\n";
    echo "O menu de almoxarifado deveria estar visível para este usuário.\n";
} else {
    echo "\n✗ O usuário não tem o perfil 'Visualizador'.\n";
    echo "Perfil atual: " . (isset($_SESSION["permissao"]) ? $_SESSION["permissao"] : "Não definido") . "\n";
}

// Verificar se a variável de sessão está correta
echo "\nVerificando variáveis de sessão:\n";
foreach ($_SESSION as $key => $value) {
    if (is_string($value) || is_numeric($value)) {
        echo "- $key: $value\n";
    }
}

// Verificar se a constante SESSION está definida
echo "\nVerificando constantes definidas:\n";
$constants = get_defined_constants(true);
$user_constants = isset($constants['user']) ? $constants['user'] : [];
foreach ($user_constants as $name => $value) {
    echo "- $name: $value\n";
}
?>