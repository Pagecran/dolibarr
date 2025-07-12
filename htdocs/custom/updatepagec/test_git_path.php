<?php
require '../../../main.inc.php';

echo "DOL_DOCUMENT_ROOT: " . DOL_DOCUMENT_ROOT . "\n";
echo "dirname(DOL_DOCUMENT_ROOT): " . dirname(DOL_DOCUMENT_ROOT) . "\n";
echo "Git existe: " . (is_dir(dirname(DOL_DOCUMENT_ROOT) . '/.git') ? 'Oui' : 'Non') . "\n";

if (is_dir(dirname(DOL_DOCUMENT_ROOT) . '/.git')) {
    $git_status = shell_exec("cd " . escapeshellarg(dirname(DOL_DOCUMENT_ROOT)) . " && git status --porcelain 2>&1");
    echo "État Git:\n" . $git_status;
}
?> 