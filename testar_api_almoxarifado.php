<?php
// testar_api_almoxarifado.php - Script para testar a API de almoxarifado
require_once 'config/db.php';

// Iniciar sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simular um usuário logado (usuário com ID 1)
$_SESSION['id'] = 1;
$_SESSION['loggedin'] = true;

// Incluir a API
include 'api/almoxarifado_listar_minhas_requisicoes.php';
?>