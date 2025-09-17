<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'inventario');
define('DB_PASSWORD', 'fA9-A@BLn_PiHsR0');
define('DB_NAME', 'inventario_db');

/* Conexão MySQL com PDO */
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    // Definir o modo de erro do PDO para exceção
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Definir o modo de fetch padrão
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERRO: Não foi possível conectar ao banco de dados. " . $e->getMessage());
}

// Manter a conexão mysqli para compatibilidade com arquivos antigos, se necessário
// Se todos os arquivos forem migrados para PDO, esta parte pode ser removida.
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($link === false){
    die("ERRO: Não foi possível conectar com mysqli. " . mysqli_connect_error());
}

?>
