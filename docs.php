<?php
require_once 'includes/header.php';
?>

<main>
    <div id="markdown-content" class="documentacao-container">
        <!-- Conteúdo do Markdown será carregado aqui pelo JavaScript -->
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('docs/USER_MANUAL.md')
        .then(response => response.text())
        .then(markdown => {
            document.getElementById('markdown-content').innerHTML = marked.parse(markdown);
        })
        .catch(error => console.error('Erro ao carregar o manual:', error));
});
</script>

<?php
require_once 'includes/footer.php';
?>