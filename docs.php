<?php
require_once 'includes/header.php';
require_once 'vendor/autoload.php';

// Define o manual padrão
$page = 'USER_MANUAL';

// Verifica se uma página específica foi solicitada via GET
if (isset($_GET['page']) && !empty($_GET['page'])) {
    // Medida de segurança: remove caracteres inválidos para nomes de arquivo
    $requested_page = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['page']);
    $potential_path = __DIR__ . '/docs/' . $requested_page . '.md';

    // Verifica se o arquivo solicitado realmente existe para evitar erros
    if (file_exists($potential_path)) {
        $page = $requested_page;
    }
}

// Constrói o caminho final do arquivo de forma segura
$manual_path = __DIR__ . '/docs/' . $page . '.md';

// Verifica se o arquivo existe antes de tentar lê-lo
if (file_exists($manual_path)) {
    $manual_content = file_get_contents($manual_path);
} else {
    // Se o arquivo não for encontrado, define um conteúdo de erro claro
    $manual_content = "# Manual não encontrado\nO arquivo `" . htmlspecialchars($page) . ".md` não pôde ser localizado.";
}

$parsedown = new Parsedown();
$parsedown->setSafeMode(false); // Permite a renderização de HTML, como os ícones

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
// Decodifica as entidades HTML para garantir que os ícones (<i>) sejam renderizados
$html_content = html_entity_decode($html_content);

?>

<main>
    <div id="markdown-content" class="documentacao-container">
        <?php echo $html_content; ?>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>