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
    if (GETPOST('token') == newToken()) {
        $updatepagec = new UpdatePagec($db);
        $result = $updatepagec->launchUpdate();
        // Ne pas afficher de message de succès/échec, tout est dans les logs
    } else {
        setEventMessages("Erreur de sécurité : token CSRF invalide", null, 'errors');
    }
}

if ($action == 'clearlogs') {
    if (GETPOST('token') == newToken()) {
        $updatepagec = new UpdatePagec($db);
        $updatepagec->clearLogs();
        setEventMessages("Logs vidés", null, 'mesgs');
    } else {
        setEventMessages("Erreur de sécurité : token CSRF invalide", null, 'errors');
    }
}

if ($action == 'restorefile') {
    if (GETPOST('token') == newToken()) {
        $updatepagec = new UpdatePagec($db);
        $file = GETPOST('file');
        $type = GETPOST('filetype');
        $result = $updatepagec->restoreBackupFile($file, $type);
        if ($result['success']) {
            setEventMessages("Restauration effectuée depuis $file", null, 'mesgs');
        } else {
            setEventMessages("Erreur restauration : ".implode(' | ', $result['errors']), null, 'errors');
        }
    } else {
        setEventMessages("Erreur de sécurité : token CSRF invalide", null, 'errors');
    }
}

if ($action == 'uploadbackup') {
    if (GETPOST('token') == newToken()) {
        $updatepagec = new UpdatePagec($db);
        if (!empty($_FILES['backupfile']['tmp_name'])) {
            $dest = $conf->admin->dir_output.'/backup/' . basename($_FILES['backupfile']['name']);
            if (move_uploaded_file($_FILES['backupfile']['tmp_name'], $dest)) {
                setEventMessages("Fichier uploadé : $dest", null, 'mesgs');
            } else {
                setEventMessages("Erreur upload fichier", null, 'errors');
            }
        } else {
            setEventMessages("Aucun fichier sélectionné", null, 'errors');
        }
    } else {
        setEventMessages("Erreur de sécurité : token CSRF invalide", null, 'errors');
    }
}

// Traitement de la restauration
if ($action == 'restore') {
    if (GETPOST('token') == newToken()) {
        $updatepagec = new UpdatePagec($db);
        $restore_result = $updatepagec->restoreBackup();
        
        if ($restore_result['success']) {
            setEventMessages("Restauration effectuée avec succès", null, 'mesgs');
        } else {
            setEventMessages("Échec de la restauration", $restore_result['errors'], 'errors');
        }
    } else {
        setEventMessages("Erreur de sécurité : token CSRF invalide", null, 'errors');
    }
}

// Récupération des logs
$updatepagec = new UpdatePagec($db);
$logs = $updatepagec->getLogs(50); // 50 dernières lignes

// Vérification de l'existence des backups
$backup_files = $updatepagec->listBackups();

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
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<div class="info">' . $langs->trans("UpdatePagecDescription") . '</div>';
print '<br>';
print '<input type="submit" class="button button-primary" value="' . $langs->trans("LaunchUpdate") . '" onclick="return confirm(\'' . $langs->trans("ConfirmUpdate") . '\')">';
print '</form>';
print '</td>';
print '</tr>';

// Section des backups
if (!empty($backup_files)) {
    print '<tr class="liste_titre">';
    print '<td>Fichiers de sauvegarde disponibles</td>';
    print '</tr>';
    print '<tr class="oddeven">';
    print '<td>';
    print '<ul>';
    foreach ($backup_files as $file) {
        $type = (strpos($file, 'backup_') !== false ? 'sql' : 'documents');
        print '<li>' . basename($file) . ' (' . date('Y-m-d H:i:s', filemtime($file)) . ')';
        print ' <form method="post" action="" style="display:inline">';
        print '<input type="hidden" name="action" value="restorefile">';
        print '<input type="hidden" name="file" value="' . htmlspecialchars($file) . '">';
        print '<input type="hidden" name="filetype" value="' . $type . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="submit" class="button" value="Restaurer" onclick="return confirm(\'Restaurer ce fichier ?\')">';
        print '</form>';
        print '</li>';
    }
    print '</ul>';
    print '</td>';
    print '</tr>';
}
// Formulaire d'upload
print '<tr class="liste_titre">';
print '<td>Uploader un fichier d\'archive externe</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>';
print '<form method="post" enctype="multipart/form-data" action="">';
print '<input type="hidden" name="action" value="uploadbackup">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="file" name="backupfile">';
print '<input type="submit" class="button" value="Uploader">';
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
print '<input type="hidden" name="token" value="' . newToken() . '">';
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
    print '<form method="post" action="">';
    print '<input type="hidden" name="action" value="clearlogs">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="submit" class="button" value="Vider les logs" onclick="return confirm(\'Êtes-vous sûr de vouloir vider les logs ?\')">';
    print '</form>';
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
