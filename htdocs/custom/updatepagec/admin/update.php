<?php
// htdocs/custom/updatepagec/admin/update.php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/updatepagec/lib/updatepagec.lib.php';

$langs->load("updatepagec@updatepagec");

// Sécurité
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$error = 0;
$errors = array();

// Traitement des actions
if ($action == 'update') {
    $updatepagec = new UpdatePagec($db);
    $result = $updatepagec->launchUpdate();
    
    if ($result['success']) {
        setEventMessages($langs->trans("UpdateLaunchedSuccessfully"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("UpdateFailed"), $result['errors'], 'errors');
    }
}

// Récupération des logs
$logfile = DOL_DATA_ROOT . '/updatepagec.log';
$logs = '';
if (file_exists($logfile)) {
    $logs = file_get_contents($logfile);
}

// Affichage de la page
llxHeader('', $langs->trans("UpdatePagecSetup"));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans("UpdatePagecSetup"), $linkback, 'title_setup');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("UpdatePagecConfiguration") . '</td>';
print '</tr>';

// Section de mise à jour
print '<tr class="oddeven">';
print '<td>';
print '<form method="post" action="">';
print '<input type="hidden" name="action" value="update">';
print '<div class="info">' . $langs->trans("UpdatePagecDescription") . '</div>';
print '<br>';
print '<input type="submit" class="button button-primary" value="' . $langs->trans("LaunchUpdate") . '" onclick="return confirm(\'' . $langs->trans("ConfirmUpdate") . '\')">';
print '</form>';
print '</td>';
print '</tr>';

// Section des logs
if ($logs) {
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans("UpdateLogs") . '</td>';
    print '</tr>';
    print '<tr class="oddeven">';
    print '<td>';
    print '<pre style="max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
    print htmlspecialchars($logs);
    print '</pre>';
    print '</td>';
    print '</tr>';
}

print '</table>';
print '</div>';

llxFooter();
$db->close();
?>
