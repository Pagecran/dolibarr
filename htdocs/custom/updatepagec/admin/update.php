<?php
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$action = GETPOST('action', 'aZ09');

if ($action == 'update') {
    $script_path = DOL_DOCUMENT_ROOT . '/dolibarr_pagec_proxmox.sh';
    
    if (file_exists($script_path)) {
        $output = shell_exec("bash $script_path 2>&1");
        $success = true;
    } else {
        $output = "Script non trouvé : $script_path";
        $success = false;
    }
}

llxHeader('', $langs->trans("UpdatePagec"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("UpdatePagec"), $linkback, 'title_setup');

if ($action == 'update') {
    print '<div class="info">';
    print '<strong>Résultat de la mise à jour :</strong><br>';
    print '<pre>' . htmlspecialchars($output) . '</pre>';
    print '</div>';
}

print '<form method="POST">';
print '<input type="hidden" name="action" value="update">';
print '<input type="submit" class="button" value="Lancer la mise à jour">';
print '</form>';

llxFooter();
?>
