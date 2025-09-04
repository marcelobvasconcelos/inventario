<?php
// Script para verificar se as páginas do módulo de empenhos estão sendo carregadas corretamente

// Iniciar a sessão
session_start();

// Definir permissões de administrador para teste
$_SESSION["permissao"] = "Administrador";
$_SESSION["loggedin"] = true;
$_SESSION["id"] = 1;
$_SESSION["nome"] = "Administrador Teste";

// Incluir os arquivos necessários
require_once 'C:\\xampp\\htdocs\\inventario\\config\\db.php';
require_once 'C:\\xampp\\htdocs\\inventario\\includes\\header.php';

// Testar o carregamento da página de categorias
ob_start();
include 'categoria_add.php';
$content = ob_get_contents();
ob_end_clean();

// Verificar se a página contém o título esperado
if (strpos($content, 'Gerenciamento de Categorias') !== false) {
    echo "Página de categorias: OK\n";
} else {
    echo "Página de categorias: ERRO\n";
}

// Testar o carregamento da página de empenhos
ob_start();
include 'empenho_add.php';
$content = ob_get_contents();
ob_end_clean();

// Verificar se a página contém o título esperado
if (strpos($content, 'Gerenciamento de Empenhos') !== false) {
    echo "Página de empenhos: OK\n";
} else {
    echo "Página de empenhos: ERRO\n";
}

// Testar o carregamento da página de notas fiscais
ob_start();
include 'nota_fiscal_add.php';
$content = ob_get_contents();
ob_end_clean();

// Verificar se a página contém o título esperado
if (strpos($content, 'Gerenciamento de Notas Fiscais') !== false) {
    echo "Página de notas fiscais: OK\n";
} else {
    echo "Página de notas fiscais: ERRO\n";
}

// Testar o carregamento da página de materiais
ob_start();
include 'material_add.php';
$content = ob_get_contents();
ob_end_clean();

// Verificar se a página contém o título esperado
if (strpos($content, 'Gerenciamento de Materiais') !== false) {
    echo "Página de materiais: OK\n";
} else {
    echo "Página de materiais: ERRO\n";
}

echo "Teste de carregamento de páginas concluído.\n";
?>