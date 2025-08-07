<?php
require_once 'includes/header.php';
require_once 'vendor/autoload.php';

use Parsedown;

$manual_path = 'docs/USER_MANUAL.md';
$manual_content = file_get_contents($manual_path);

$parsedown = new Parsedown();

$filtered_content = [];
$lines = explode("\n", $manual_content);
$current_section_allowed = true;

foreach ($lines as $line) {
    // Verifica se a linha é um cabeçalho de seção com marcador de permissão
    if (preg_match('/^## \[(ADMINISTRADOR|GESTOR|VISUALIZADOR)\] (.*)$/', $line, $matches)) {
        $required_permission = $matches[1];
        $section_title = $matches[2];

        // Verifica se o usuário tem a permissão necessária para esta seção
        if ($_SESSION['permissao'] == 'Administrador') {
            $current_section_allowed = true;
        } elseif ($_SESSION['permissao'] == 'Gestor' && ($required_permission == 'GESTOR' || $required_permission == 'VISUALIZADOR')) {
            $current_section_allowed = true;
        } elseif ($_SESSION['permissao'] == 'Visualizador' && $required_permission == 'VISUALIZADOR') {
            $current_section_allowed = true;
        } else {
            $current_section_allowed = false;
        }
        
        // Adiciona o título da seção (sem o marcador de permissão) se a seção for permitida
        if ($current_section_allowed) {
            $filtered_content[] = "## " . $section_title;
        }

    } else if (preg_match('/^## (.*)$/', $line)) {
        // Se for um cabeçalho de seção sem marcador de permissão, é sempre permitido
        $current_section_allowed = true;
        $filtered_content[] = $line;
    } else {
        // Adiciona a linha ao conteúdo filtrado se a seção atual for permitida
        if ($current_section_allowed) {
            $filtered_content[] = $line;
        }
    }
}

$final_markdown = implode("\n", $filtered_content);
$html_content = $parsedown->text($final_markdown);

?>

<main>
    <div id="markdown-content" class="documentacao-container">
        <?php echo $html_content; ?>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>