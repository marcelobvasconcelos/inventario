<?php
// test_caminhos.php - Testa os caminhos dos arquivos
header('Content-Type: text/plain');

echo "Testando caminhos dos arquivos:\n\n";

// Caminho absoluto
$diretorio_atual = __DIR__;
echo "Diretório atual: {$diretorio_atual}\n";

// Caminho do arquivo CSS
$arquivo_css = $diretorio_atual . '/css/tema_verde.css';
echo "Caminho do arquivo CSS: {$arquivo_css}\n";

// Verifica se o arquivo existe
if (file_exists($arquivo_css)) {
    echo "Arquivo encontrado: SIM\n";
    
    // Tenta ler o arquivo
    $conteudo = file_get_contents($arquivo_css);
    if ($conteudo !== false) {
        echo "Leitura do arquivo: SUCESSO\n";
        echo "Tamanho: " . strlen($conteudo) . " bytes\n";
    } else {
        echo "Leitura do arquivo: FALHA\n";
    }
} else {
    echo "Arquivo encontrado: NÃO\n";
}

echo "\n";

// Testa caminho relativo
echo "Testando caminho relativo:\n";
$arquivo_css_rel = 'css/tema_verde.css';
echo "Caminho relativo: {$arquivo_css_rel}\n";

if (file_exists($arquivo_css_rel)) {
    echo "Arquivo encontrado (relativo): SIM\n";
} else {
    echo "Arquivo encontrado (relativo): NÃO\n";
}
?>