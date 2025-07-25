<?php
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Sécurité admin
if (!$user->admin) accessforbidden();

$langs->load("updatepagec_updater@updatepagec_updater");

$action = GETPOST('action', 'aZ09');
$result = '';

if ($action == 'gitpull') {
    $dolibarr_root = dirname(DOL_DOCUMENT_ROOT);
    $git_branch = 'Pagec';
    $cmd = "cd " . escapeshellarg($dolibarr_root) . " && git pull origin " . escapeshellarg($git_branch) . " 2>&1";
    $result = shell_exec($cmd);
}

llxHeader('', $langs->trans("UpdatePagecUpdaterAdmin"));

print load_fiche_titre($langs->trans("UpdatePagecUpdaterAdmin"), '', 'technic');

print '<form method="post" action="">';
print '<input type="hidden" name="action" value="gitpull">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="submit" class="button" value="Lancer git pull sur Dolibarr (branche Pagec)" onclick="return confirm(\'Confirmer la mise à jour du module updatePagec ?\')">';
print '</form>';

if ($result) {
    print '<h3>Résultat du git pull</h3>';
    print '<pre style="background:#f5f5f5; border:1px solid #ccc; padding:10px;">'.htmlspecialchars($result).'</pre>';
}

llxFooter();
$db->close(); 