<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'inventario');
define('DB_PASSWORD', 'fA9*A@BLn_PiHsR0');
define('DB_NAME', 'inventario_db');

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($link === false){
    die("ERRO: Não foi possível conectar. " . mysqli_connect_error());
}
?>
