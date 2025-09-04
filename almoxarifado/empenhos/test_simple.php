<?php
// Script simplificado para verificar se os arquivos do módulo de empenhos estão sendo carregados corretamente

// Definir variáveis de sessão necessárias
$_SESSION["permissao"] = "Administrador";
$_SESSION["loggedin"] = true;
$_SESSION["id"] = 1;
$_SESSION["nome"] = "Administrador Teste";

// Definir a variável REQUEST_URI para evitar warnings
$_SERVER["REQUEST_URI"] = "/inventario/empenhos/";

// Iniciar a sessão
session_start();

// Incluir os arquivos necessários
require_once 'C:/xampp/htdocs/inventario/config/db.php';

// Testar o carregamento da página de categorias
echo "Testando carregamento da página de categorias...\n";
try {
    ob_start();
    include 'categoria_add.php';
    $content = ob_get_contents();
    ob_end_clean();
    
    // Verificar se a página contém o título esperado
    if (strpos($content, 'Gerenciamento de Categorias') !== false) {
        echo "Página de categorias: OK\n";
    } else {
        echo "Página de categorias: ERRO - Título não encontrado\n";
    }
} catch (Exception $e) {
    echo "Página de categorias: ERRO - " . $e->getMessage() . "\n";
}

echo "Teste concluído.\n";
?>