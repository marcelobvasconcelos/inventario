<?php
// Inicia a sessão PHP e inclui a conexão com o banco de dados
require_once 'config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["id"])) {
    header("location: login.php");
    exit;
}

$usuario_logado_id = $_SESSION['id'];

if (isset($_POST['is_ajax']) && $_POST['is_ajax'] == 'true') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    try {
        // ... (toda a lógica de processamento AJAX que já existe) ...
    } catch (Exception $e) {
        $response['message'] = "Erro: " . $e->getMessage();
    }
    echo json_encode($response);
    exit;
}

// Lógica para buscar notificações para exibição
$notificacoes = []; // Array para armazenar os dados processados
// ... (código de busca de notificações existente) ...

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <!-- Conteúdo da página -->
    <?php foreach ($notificacoes as $notificacao): ?>
        <!-- ... (código de exibição da notificação) ... -->
        <div class="chat-container">
            <?php if (!empty($historico_respostas)): ?>
                <?php foreach ($historico_respostas as $msg): ?>
                    <div class="chat-bubble">
                        <!-- ... -->
                        &bull; <?php echo date('d/m/Y H:i', strtotime($msg['data_resposta'])); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <!-- ... (resto do código de exibição) ... -->
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-action-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            // ... (resto da lógica do form) ...
            fetch('notificacoes_usuario.php', { method: 'POST', body: formData })
            .then(response => response.text()) // Obter a resposta como texto
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    // Lógica de sucesso original
                } catch (e) {
                    const messageMatch = text.match(/"message":"([^"]*)"/);
                    if (messageMatch && messageMatch[1]) {
                        // Lógica de sucesso extraindo a mensagem
                    } else {
                        console.error('Não foi possível extrair a mensagem da resposta:', text);
                    }
                }
            })
            .catch(error => {
                console.error('Erro de rede:', error);
            });
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
