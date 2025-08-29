-- Arquivo de atualização para produção - Correção do item_edit.php
-- Data: 2025-08-29

/*
 * Nenhuma alteração no banco de dados é necessária para esta atualização.
 * A correção foi feita apenas no arquivo item_edit.php para resolver
 * o problema com mysqli_stmt_bind_param() que exigia passagem de 
 * parâmetros por referência.
 *
 * Arquivos modificados:
 * - item_edit.php
 */