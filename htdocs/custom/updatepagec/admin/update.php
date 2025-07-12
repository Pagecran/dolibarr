<?php
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/updatepagec/lib/updatepagec.lib.php';

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

// Traitement de la restauration
if ($action == 'restore') {
    $updatepagec = new UpdatePagec($db);
    $restore_result = $updatepagec->restoreBackup();
    
    if ($restore_result['success']) {
        setEventMessages("Restauration effectuée avec succès", null, 'mesgs');
    } else {
        setEventMessages("Échec de la restauration", $restore_result['errors'], 'errors');
    }
}

// Récupération des logs
$updatepagec = new UpdatePagec($db);
$logs = $updatepagec->getLogs(50); // 50 dernières lignes

// Vérification de l'existence des backups
$backup_files = $updatepagec->getBackupFiles();

// Affichage de la page
llxHeader('', $langs->trans("UpdatePagecSetup"));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans("UpdatePagecSetup"), $linkback, 'title_setup');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Section de mise à jour
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("UpdatePagecConfiguration") . '</td>';
print '</tr>';

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

// Section de restauration (si des backups existent)
if (!empty($backup_files)) {
    print '<tr class="liste_titre">';
    print '<td>Restauration des données</td>';
    print '</tr>';
    print '<tr class="oddeven">';
    print '<td>';
    print '<div class="warning">';
    print '<strong>Attention :</strong> La restauration peut écraser des données récentes.';
    print '</div>';
    print '<br>';
    print '<strong>Fichiers de backup disponibles :</strong><br>';
    foreach ($backup_files as $file) {
        print '- ' . basename($file) . ' (' . date('Y-m-d H:i:s', filemtime($file)) . ')<br>';
    }
    print '<br>';
    print '<form method="post" action="">';
    print '<input type="hidden" name="action" value="restore">';
    print '<input type="submit" class="button button-warning" value="Restaurer les données" onclick="return confirm(\'Êtes-vous sûr de vouloir restaurer les données ? Cela peut écraser des données récentes.\')">';
    print '</form>';
    print '</td>';
    print '</tr>';
}

// Section des logs
if ($logs) {
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans("UpdateLogs") . '</td>';
    print '</tr>';
    print '<tr class="oddeven">';
    print '<td>';
    print '<pre style="max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
    print $updatepagec->formatOutput($logs);
    print '</pre>';
    print '</td>';
    print '</tr>';
}

// Section des informations système
print '<tr class="liste_titre">';
print '<td>Informations système</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>';
$sysinfo = $updatepagec->getSystemInfo();
print '<table class="noborder centpercent">';
print '<tr><td>Script de mise à jour:</td><td>' . $sysinfo['script_path'] . ' (' . ($sysinfo['script_exists'] ? 'Existe' : 'N\'existe pas') . ', ' . ($sysinfo['script_executable'] ? 'Exécutable' : 'Non exécutable') . ')</td></tr>';
print '<tr><td>Fichier de logs:</td><td>' . $sysinfo['log_file'] . ' (' . ($sysinfo['log_writable'] ? 'Écriture autorisée' : 'Écriture interdite') . ')</td></tr>';
print '<tr><td>Utilisateur:</td><td>' . $sysinfo['user'] . ' (' . ($sysinfo['user_admin'] ? 'Admin' : 'Non admin') . ')</td></tr>';
print '<tr><td>Version Dolibarr:</td><td>' . $sysinfo['dolibarr_version'] . '</td></tr>';
print '<tr><td>Version PHP:</td><td>' . $sysinfo['php_version'] . '</td></tr>';
print '</table>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

llxFooter();
$db->close();
?>
