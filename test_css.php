<?php
// test_css.php - Testa o carregamento dos arquivos CSS
header('Content-Type: text/plain');

echo "Testando carregamento de arquivos CSS dos temas:\n\n";

$temas = ['padrao', 'azul', 'verde', 'roxo'];

foreach ($temas as $tema) {
    $arquivo = "css/tema_{$tema}.css";
    echo "Tema: {$tema}\n";
    echo "Arquivo: {$arquivo}\n";
    
    if (file_exists($arquivo)) {
        echo "Status: Arquivo encontrado\n";
        $conteudo = file_get_contents($arquivo);
        if ($conteudo !== false) {
            echo "Tamanho: " . strlen($conteudo) . " bytes\n";
            echo "Primeiras linhas:\n" . substr($conteudo, 0, 200) . "\n";
        } else {
            echo "Status: Erro ao ler o arquivo\n";
        }
    } else {
        echo "Status: Arquivo NÃO encontrado\n";
    }
    
    echo str_repeat("-", 50) . "\n";
}
?>