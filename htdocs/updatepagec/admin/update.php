<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../../main.inc.php';
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

if (
    GETPOST('action') === 'set_git_repo_path'
    && GETPOST('token') == newToken()
) {
    $path = trim(GETPOST('git_repo_path', 'alphanohtml'));
    dolibarr_set_const($db, 'UPDATEPAGEC_GIT_REPO_PATH', $path, 'chaine', 0, '', $conf->entity);
    setEventMessages("Chemin du repository Git enregistré", null, 'mesgs');
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

// Champ de configuration du chemin du repo git
print '<form method="post" action="" style="margin-bottom:16px;">';
print '<label for="git_repo_path"><b>Chemin du repository Git à utiliser pour la mise à jour :</b></label> ';
print '<input type="text" id="git_repo_path" name="git_repo_path" value="'.dol_escape_htmltag(isset($conf->global->UPDATEPAGEC_GIT_REPO_PATH) ? $conf->global->UPDATEPAGEC_GIT_REPO_PATH : '').'" size="60"> ';
print '<input type="hidden" name="action" value="set_git_repo_path">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="submit" class="button" value="Enregistrer">';
print '</form>';

// Bouton de mise à jour (SANS cadre)
print '<form method="post" action="" style="margin-bottom:24px; text-align:center;">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="submit" class="button button-primary" value="LANCER LA MISE À JOUR (GIT PULL)" onclick="return confirm(\'Confirmer la mise à jour ?\')">';
print '</form>';

// Logs
print '<h3>Logs de mise à jour</h3>';
if ($logs) {
    print '<pre style="max-height: 300px; max-width: 100%; overflow-y: auto; overflow-x: auto; background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin:0; white-space: pre-wrap; word-wrap: break-word;">';
    print $updatepagec->formatOutput($logs);
    print '</pre>';
    // Bouton vider les logs à droite
    print '<div style="text-align:right;">';
    print '<form method="post" action="" style="display:inline;">';
    print '<input type="hidden" name="action" value="clearlogs">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="submit" class="button" style="font-size:0.8em; padding:2px 8px;" value="VIDER LES LOGS" onclick="return confirm(\'Êtes-vous sûr de vouloir vider les logs ?\')">';
    print '</form>';
    print '</div>';
}

// Tableau sauvegardes
print '<h3>Sauvegardes des données utilisateurs</h3>';

// Avertissement (SANS cadre) - seulement si il y a des backups
if (!empty($backup_files)) {
    print '<div class="warning" style="margin:16px 0;">';
    print '<strong>Attention :</strong> La restauration peut écraser des données récentes.';
    print '</div>';
}

if (!empty($backup_files)) {
    print '<table class="noborder">';
    print '<tr class="liste_titre">';
    print '<th>Nom</th><th>Taille</th><th>Date</th><th>Actions</th>';
    print '</tr>';
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
    print '</table>';
} else {
    print '<p class="opacitymedium">Aucun fichier de sauvegarde trouvé.</p>';
}

// Upload (SANS cadre)
print '<form method="post" enctype="multipart/form-data" action="" style="margin-top:16px;">';
print '<label>Uploader un fichier d\'archive externe</label><br>';
print '<input type="hidden" name="action" value="uploadbackup">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="file" name="backupfile"> ';
print '<input type="submit" class="button" value="UPLOADER">';
print '</form>';

// Infos système
print '<h3>Informations système</h3>';
print '<table class="noborder">';
$sysinfo = $updatepagec->getSystemInfo();
print '<tr class="oddeven"><td>Fichier de logs:</td><td>' . $sysinfo['log_file'] . ' (' . ($sysinfo['log_writable'] ? 'Écriture autorisée' : 'Écriture interdite') . ')</td></tr>';
print '<tr class="oddeven"><td>Utilisateur:</td><td>' . $sysinfo['user'] . ' (' . ($sysinfo['user_admin'] ? 'Admin' : 'Non admin') . ')</td></tr>';
print '<tr class="oddeven"><td>Version Dolibarr:</td><td>' . $sysinfo['dolibarr_version'] . '</td></tr>';
print '<tr class="oddeven"><td>Version PHP:</td><td>' . $sysinfo['php_version'] . '</td></tr>';
print '</table>';

llxFooter();
$db->close();
?>

