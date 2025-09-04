<?php
// atualizar_bd_almoxarifado_notificacoes.php - Script para atualizar o banco de dados
require_once 'config/db.php';

// Verificar permissões - apenas administradores
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["permissao"] != 'Administrador') {
    header("location: login.php");
    exit;
}

echo "<h2>Atualizando Banco de Dados - Sistema de Notificações do Almoxarifado</h2>";

// Ler o arquivo SQL
$sql_file = 'almoxarifado/create_notificacoes_requisicoes_table.sql';
if (!file_exists($sql_file)) {
    die("Arquivo SQL não encontrado: " . $sql_file);
}

$sql_content = file_get_contents($sql_file);
if ($sql_content === false) {
    die("Erro ao ler o arquivo SQL.");
}

// Dividir o conteúdo em comandos individuais
$queries = explode(';', $sql_content);
$success_count = 0;
$error_count = 0;

foreach ($queries as $query) {
    $query = trim($query);
    // Ignorar linhas vazias e comentários
    if (empty($query) || strpos($query, '--') === 0) {
        continue;
    }
    
    echo "<p>Executando: <code>" . htmlspecialchars(substr($query, 0, 100)) . "...</code></p>";
    
    if (mysqli_query($link, $query)) {
        echo "<p style='color: green;'>Sucesso!</p>";
        $success_count++;
    } else {
        echo "<p style='color: red;'>Erro: " . mysqli_error($link) . "</p>";
        $error_count++;
    }
}

echo "<h3>Resultado:</h3>";
echo "<p style='color: green;'>Consultas executadas com sucesso: " . $success_count . "</p>";
echo "<p style='color: " . ($error_count > 0 ? "red" : "green") . ";'>Erros: " . $error_count . "</p>";

if ($error_count == 0) {
    echo "<p style='color: green; font-weight: bold;'>Atualização concluída com sucesso!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>Houve erros durante a atualização. Verifique as mensagens acima.</p>";
}

mysqli_close($link);
?>