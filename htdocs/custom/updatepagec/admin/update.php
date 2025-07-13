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

// Gestion de la suppression d'un backup
if ($action == 'deletebackup') {
    if (GETPOST('token') == newToken()) {
        $file = GETPOST('file');
        if ($file && file_exists($file)) {
            if (unlink($file)) {
                setEventMessages("Fichier supprimé : ".basename($file), null, 'mesgs');
            } else {
                setEventMessages("Erreur lors de la suppression du fichier", null, 'errors');
            }
        } else {
            setEventMessages("Fichier non trouvé", null, 'errors');
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

// Affichage principal en colonne unique : logs, puis sauvegardes, puis upload/avertissement

// Bouton de mise à jour
print '<form method="post" action="" style="margin-bottom:24px">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="submit" class="button button-primary" value="Lancer la mise à jour (git pull)" onclick="return confirm(\'Confirmer la mise à jour ?\')">';
print '</form>';

// Titre logs
print '<div style="font-weight:bold; margin-bottom:4px;">Logs de mise à jour</div>';
// Logs
if ($logs) {
    print '<div style="margin-bottom:24px; position:relative;">';
    print '<pre style="max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
    print $updatepagec->formatOutput($logs);
    print '</pre>';
    // Bouton vider les logs en bas à droite, taille réduite
    print '<form method="post" action="" style="position:absolute; right:0; bottom:0; margin:8px;">';
    print '<input type="hidden" name="action" value="clearlogs">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="submit" class="button" style="font-size:0.8em; padding:2px 8px;" value="VIDER LES LOGS" onclick="return confirm(\'Êtes-vous sûr de vouloir vider les logs ?\')">';
    print '</form>';
    print '</div>';
}

// Titre du tableau
print '<div style="font-weight:bold; margin-bottom:8px;">Sauvegardes des données utilisateurs</div>';
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Nom</th><th>Taille</th><th>Date</th><th>Actions</th>';
print '</tr>';
if (!empty($backup_files)) {
    foreach ($backup_files as $file) {
        $basename = basename($file);
        $size = function_exists('dol_print_size') ? dol_print_size(filesize($file), 1, 1) : round(filesize($file)/1024).' Ko';
        $date = function_exists('dol_print_date') ? dol_print_date(filemtime($file), 'dayhour') : date('d/m/Y H:i', filemtime($file));
        $type = (strpos($basename, 'backup_') !== false ? 'sql' : 'documents');
        print '<tr class="oddeven">';
        print '<td><i class="fa '.($type=='sql'?'fa-database':'fa-archive').'"></i> '.$basename.'</td>';
        print '<td>'.$size.'</td>';
        print '<td>'.$date.'</td>';
        print '<td style="white-space:nowrap">';
        // Télécharger
        print '<a class="btn btn-default" href="'.DOL_URL_ROOT.'/document.php?modulepart=admin&file=backup/'.$basename.'" target="_blank"><i class="fa fa-download"></i></a> ';
        // Restaurer (texte + icône, un seul bouton submit par formulaire)
        print '<form method="post" action="" style="display:inline;margin:0;padding:0;">';
        print '<input type="hidden" name="action" value="restorefile">';
        print '<input type="hidden" name="file" value="' . htmlspecialchars($file) . '">';
        print '<input type="hidden" name="filetype" value="' . $type . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<button class="btn btn-warning" type="submit" onclick="return confirm(\'Restaurer ce fichier ?\')"><i class="fa fa-refresh"></i> Restaurer</button>';
        print '</form> ';
        // Supprimer
        print '<form method="post" action="" style="display:inline;margin:0;padding:0;">';
        print '<input type="hidden" name="action" value="deletebackup">';
        print '<input type="hidden" name="file" value="' . htmlspecialchars($file) . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<button class="btn btn-danger" type="submit" onclick="return confirm(\'Supprimer ce fichier ?\')"><i class="fa fa-trash"></i></button>';
        print '</form>';
        print '</td>';
        print '</tr>';
    }
} else {
    print '<tr><td colspan="4" class="opacitymedium">Aucun fichier de sauvegarde trouvé.</td></tr>';
}
print '</table>';
print '</div>';
// Section upload + avertissement SOUS le tableau
// Formulaire d'upload
print '<div class="form-upload-backup">';
print '<form method="post" enctype="multipart/form-data" action="">';
print '<input type="hidden" name="action" value="uploadbackup">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<label>Uploader un fichier d\'archive externe</label><br>';
print '<input type="file" name="backupfile"> ';
print '<input type="submit" class="button" value="UPLOADER">';
print '</form>';
print '</div>';
// Avertissement restauration
print '<div class="warning" style="margin:16px 0;">';
print '<strong>Attention :</strong> La restauration peut écraser des données récentes.';
print '</div>';
// Espace après l'avertissement
print '<div style="margin-top:24px"></div>';

// Espace avant la section informations système
print '<div style="height:32px;"></div>';
// Section des informations système
print '<tr class="liste_titre">';
print '<td style="margin-top:24px; padding-top:24px;">Informations système</td>';
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

