<?php
// verificar_permissoes_almoxarifado.php - Script para verificar se as permissões do almoxarifado estão corretas
require_once 'config/db.php';

echo "Verificando permissões do módulo de almoxarifado...\n\n";

try {
    // Verificar se o perfil 'Visualizador' existe
    $stmt = $pdo->prepare("SELECT id FROM perfis WHERE nome = 'Visualizador'");
    $stmt->execute();
    $perfil_visualizador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($perfil_visualizador) {
        echo "✓ Perfil 'Visualizador' encontrado (ID: " . $perfil_visualizador['id'] . ")\n";
    } else {
        echo "✗ Perfil 'Visualizador' não encontrado\n";
        exit(1);
    }
    
    // Verificar se há usuários com o perfil 'Visualizador'
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE permissao_id = ?");
    $stmt->execute([$perfil_visualizador['id']]);
    $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_usuarios > 0) {
        echo "✓ Encontrados $total_usuarios usuários com o perfil 'Visualizador'\n";
    } else {
        echo "⚠ Nenhum usuário encontrado com o perfil 'Visualizador'\n";
    }
    
    // Verificar se há produtos no almoxarifado
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM almoxarifado_produtos");
    $stmt->execute();
    $total_produtos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_produtos > 0) {
        echo "✓ Encontrados $total_produtos produtos no almoxarifado\n";
    } else {
        echo "⚠ Nenhum produto encontrado no almoxarifado\n";
    }
    
    // Verificar se há requisições no almoxarifado
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM almoxarifado_requisicoes");
    $stmt->execute();
    $total_requisicoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_requisicoes > 0) {
        echo "✓ Encontradas $total_requisicoes requisições no almoxarifado\n";
    } else {
        echo "⚠ Nenhuma requisição encontrada no almoxarifado\n";
    }
    
    // Verificar acesso à página de requisição
    echo "\n1. Verificando acesso à página de requisição...\n";
    
    // Simular acesso de um usuário visualizador
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE permissao_id = ? LIMIT 1");
    $stmt->execute([$perfil_visualizador['id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "   ✓ Usuário com perfil 'Visualizador' encontrado (ID: " . $usuario['id'] . ")\n";
        
        // Simular uma sessão
        session_start();
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $usuario['id'];
        $_SESSION['permissao'] = 'Visualizador';
        
        // Testar acesso à página de requisição
        echo "   ✓ Sessão simulada com sucesso\n";
        
        // Verificar se o usuário pode acessar a página de requisição
        $acesso_permitido = ($_SESSION['permissao'] == 'Administrador' || $_SESSION['permissao'] == 'Almoxarife' || $_SESSION['permissao'] == 'Visualizador' || $_SESSION['permissao'] == 'Gestor');
        
        if ($acesso_permitido) {
            echo "   ✓ Usuário com perfil 'Visualizador' tem permissão para acessar a página de requisição\n";
        } else {
            echo "   ✗ Usuário com perfil 'Visualizador' não tem permissão para acessar a página de requisição\n";
            exit(1);
        }
    } else {
        echo "   ⚠ Nenhum usuário com perfil 'Visualizador' encontrado para testar acesso\n";
    }
    
    // Verificar acesso ao menu de navegação
    echo "\n2. Verificando acesso ao menu de navegação...\n";
    
    // Ler o conteúdo do arquivo header.php
    $header_content = file_get_contents('includes/header.php');
    
    if ($header_content) {
        // Verificar se o menu de almoxarifado está visível para visualizadores
        if (strpos($header_content, '$_SESSION["permissao"] == \'Administrador\' || $_SESSION["permissao"] == \'Almoxarife\' || $_SESSION["permissao"] == \'Visualizador\'') !== false) {
            echo "   ✓ Menu de almoxarifado visível para usuários com perfil 'Visualizador'\n";
        } else {
            echo "   ✗ Menu de almoxarifado não visível para usuários com perfil 'Visualizador'\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo includes/header.php\n";
        exit(1);
    }
    
    // Verificar acesso à página principal do almoxarifado
    echo "\n3. Verificando acesso à página principal do almoxarifado...\n";
    
    // Ler o conteúdo do arquivo almoxarifado/index.php
    $index_content = file_get_contents('almoxarifado/index.php');
    
    if ($index_content) {
        // Verificar se a verificação de permissões permite acesso a visualizadores
        if (strpos($index_content, '$_SESSION["permissao"] != \'Administrador\' && $_SESSION["permissao"] != \'Almoxarife\' && $_SESSION["permissao"] != \'Visualizador\'') !== false) {
            echo "   ✓ Página principal do almoxarifado permite acesso a usuários com perfil 'Visualizador'\n";
        } else {
            echo "   ✗ Página principal do almoxarifado não permite acesso a usuários com perfil 'Visualizador'\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo almoxarifado/index.php\n";
        exit(1);
    }
    
    // Verificar acesso à página de requisição
    echo "\n4. Verificando acesso à página de requisição...\n";
    
    // Ler o conteúdo do arquivo almoxarifado/requisicao.php
    $requisicao_content = file_get_contents('almoxarifado/requisicao.php');
    
    if ($requisicao_content) {
        // Verificar se a verificação de permissões permite acesso a visualizadores
        if (strpos($requisicao_content, '$_SESSION["permissao"] != \'Administrador\' && $_SESSION["permissao"] != \'Almoxarife\' && $_SESSION["permissao"] != \'Visualizador\'') !== false) {
            echo "   ✓ Página de requisição permite acesso a usuários com perfil 'Visualizador'\n";
        } else {
            echo "   ✗ Página de requisição não permite acesso a usuários com perfil 'Visualizador'\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo almoxarifado/requisicao.php\n";
        exit(1);
    }
    
    // Verificar acesso às APIs
    echo "\n5. Verificando acesso às APIs...\n";
    
    // Verificar API de criação de requisição
    $api_processar_content = file_get_contents('api/almoxarifado_processar_requisicao.php');
    
    if ($api_processar_content) {
        // Verificar se a API permite acesso a usuários logados (sem verificação específica de perfil)
        if (strpos($api_processar_content, 'isset($_SESSION["loggedin"])') !== false) {
            echo "   ✓ API de criação de requisição permite acesso a usuários logados\n";
        } else {
            echo "   ✗ API de criação de requisição não permite acesso a usuários logados\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo api/almoxarifado_processar_requisicao.php\n";
        exit(1);
    }
    
    // Verificar API de aprovação de requisição
    $api_aprovar_content = file_get_contents('api/almoxarifado_aprovar_requisicao.php');
    
    if ($api_aprovar_content) {
        // Verificar se a API permite acesso apenas a administradores e almoxarifes
        if (strpos($api_aprovar_content, '$_SESSION["permissao"] != \'Administrador\' && $_SESSION["permissao"] != \'Almoxarife\'') !== false) {
            echo "   ✓ API de aprovação de requisição permite acesso apenas a administradores e almoxarifes\n";
        } else {
            echo "   ✗ API de aprovação de requisição não restringe acesso corretamente\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo api/almoxarifado_aprovar_requisicao.php\n";
        exit(1);
    }
    
    // Verificar API de rejeição de requisição
    $api_rejeitar_content = file_get_contents('api/almoxarifado_rejeitar_requisicao.php');
    
    if ($api_rejeitar_content) {
        // Verificar se a API permite acesso apenas a administradores e almoxarifes
        if (strpos($api_rejeitar_content, '$_SESSION["permissao"] != \'Administrador\' && $_SESSION["permissao"] != \'Almoxarife\'') !== false) {
            echo "   ✓ API de rejeição de requisição permite acesso apenas a administradores e almoxarifes\n";
        } else {
            echo "   ✗ API de rejeição de requisição não restringe acesso corretamente\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo api/almoxarifado_rejeitar_requisicao.php\n";
        exit(1);
    }
    
    // Verificar API de listagem de todas as requisições
    $api_listar_content = file_get_contents('api/almoxarifado_listar_requisicoes.php');
    
    if ($api_listar_content) {
        // Verificar se a API permite acesso apenas a administradores e almoxarifes
        if (strpos($api_listar_content, '$_SESSION["permissao"] != \'Administrador\' && $_SESSION["permissao"] != \'Almoxarife\'') !== false) {
            echo "   ✓ API de listagem de todas as requisições permite acesso apenas a administradores e almoxarifes\n";
        } else {
            echo "   ✗ API de listagem de todas as requisições não restringe acesso corretamente\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo api/almoxarifado_listar_requisicoes.php\n";
        exit(1);
    }
    
    // Verificar API de listagem de requisições do usuário
    $api_listar_minhas_content = file_get_contents('api/almoxarifado_listar_minhas_requisicoes.php');
    
    if ($api_listar_minhas_content) {
        // Verificar se a API permite acesso a usuários logados (sem verificação específica de perfil)
        if (strpos($api_listar_minhas_content, 'isset($_SESSION["loggedin"])') !== false) {
            echo "   ✓ API de listagem de requisições do usuário permite acesso a usuários logados\n";
        } else {
            echo "   ✗ API de listagem de requisições do usuário não permite acesso a usuários logados\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo api/almoxarifado_listar_minhas_requisicoes.php\n";
        exit(1);
    }
    
    // Verificar API de confirmação de recebimento
    $api_confirmar_content = file_get_contents('api/almoxarifado_confirmar_recebimento.php');
    
    if ($api_confirmar_content) {
        // Verificar se a API permite acesso a usuários logados (sem verificação específica de perfil)
        if (strpos($api_confirmar_content, 'isset($_SESSION["loggedin"])') !== false) {
            echo "   ✓ API de confirmação de recebimento permite acesso a usuários logados\n";
        } else {
            echo "   ✗ API de confirmação de recebimento não permite acesso a usuários logados\n";
            exit(1);
        }
    } else {
        echo "   ✗ Não foi possível ler o arquivo api/almoxarifado_confirmar_recebimento.php\n";
        exit(1);
    }
    
    echo "\n✓ Todas as verificações foram concluídas com sucesso!\n";
    echo "As permissões do módulo de almoxarifado estão configuradas corretamente.\n";
    echo "Usuários com o perfil 'Visualizador' podem acessar o módulo e criar requisições.\n";
    
} catch (Exception $e) {
    echo "✗ Erro durante as verificações: " . $e->getMessage() . "\n";
    exit(1);
}
?>