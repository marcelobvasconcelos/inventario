<?php
// Script para verificar a estrutura da tabela itens
require_once 'config/db.php';

// Verificar a estrutura da coluna 'estado'
$sql = "SHOW COLUMNS FROM itens LIKE 'estado'";
$result = mysqli_query($link, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "Estrutura da coluna 'estado':
";
    echo "Tipo: " . $row['Type'] . "
";
    echo "Null: " . $row['Null'] . "
";
    echo "Key: " . $row['Key'] . "
";
    echo "Default: " . $row['Default'] . "
";
    echo "Extra: " . $row['Extra'] . "
";
} else {
    echo "Não foi possível obter informações sobre a coluna 'estado'.
";
}

// Verificar os valores permitidos para o ENUM (se for ENUM)
$sql_enum = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'inventario_db' AND TABLE_NAME = 'itens' AND COLUMN_NAME = 'estado'";
$result_enum = mysqli_query($link, $sql_enum);

if ($result_enum && mysqli_num_rows($result_enum) > 0) {
    $row_enum = mysqli_fetch_assoc($result_enum);
    echo "
Valores permitidos para ENUM:
";
    echo $row_enum['COLUMN_TYPE'] . "
";
}

mysqli_close($link);
?>