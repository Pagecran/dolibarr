<?php
/**
 * Classe UpdatePagec - Gestion des mises à jour Pagecran
 * 
 * @package UpdatePagec
 * @author Pagecran
 * @version 1.0
 */

class UpdatePagec
{
    private $db;
    private $langs;
    private $user;
    private $conf;
    private $logfile;
    private $script_path;

    /**
     * Constructeur
     */
    public function __construct($db)
    {
        global $langs, $user, $conf;
        
        $this->db = $db;
        $this->langs = $langs;
        $this->user = $user;
        $this->conf = $conf;
        $this->logfile = DOL_DATA_ROOT . '/updatepagec.log';
        $this->script_url = 'https://raw.githubusercontent.com/Pagecran/dolibarr/Pagec/dolibarr_pagec_proxmox.sh';
    }

    public function createBackup()
    {
        global $conf;
        $result = array('success' => false, 'errors' => array(), 'files' => array());
        $backupdir = $conf->admin->dir_output.'/backup';
        if (!is_dir($backupdir)) {
            if (!dol_mkdir($backupdir)) {
                $this->log("ERROR", "Impossible de créer le dossier de backup: $backupdir");
                $result['errors'][] = "Impossible de créer le dossier de backup: $backupdir";
                return $result;
            }
        }
        $date = date('Ymd_His');
        // 1. Dump SQL
        require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
        $utils = new Utils($this->db);
        $compression = 'none';
        $what = $this->db->type == 'pgsql' ? 'postgresql' : 'mysql';
        $file = "backup_{$date}.sql";
        $filepath = $backupdir . '/' . $file;
        $lowmemorydump = getDolGlobalString('MAIN_LOW_MEMORY_DUMP');
        $this->log("INFO", "Début du dump SQL dans $filepath");
        $utils->dumpDatabase($compression, $what, 0, $file, 0, 0, $lowmemorydump);
        if (!empty($utils->error)) {
            $this->log("ERROR", "Erreur dump SQL: " . $utils->error);
            $result['errors'][] = $utils->error;
        } else {
            $this->log("INFO", "Dump SQL terminé: $filepath");
            $result['files'][] = $filepath;
        }
        // 2. Archive documents
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        $docdir = DOL_DATA_ROOT . '/documents';
        $archivefile = "documents_{$date}.tar.gz";
        $archivepath = $backupdir . '/' . $archivefile;
        $this->log("INFO", "Début de l'archive des documents dans $archivepath");
        $tarcmd = "tar -czf " . escapeshellarg($archivepath) . " -C " . escapeshellarg(DOL_DATA_ROOT) . " documents";
        $output = shell_exec($tarcmd . " 2>&1");
        if (!file_exists($archivepath)) {
            $this->log("ERROR", "Erreur archive documents: $output");
            $result['errors'][] = $output;
        } else {
            $this->log("INFO", "Archive documents terminée: $archivepath");
            $result['files'][] = $archivepath;
        }
        $result['success'] = empty($result['errors']);
        return $result;
    }

    public function launchUpdate()
    {
        $result = array(
            'output' => '',
            'logs' => '',
            'errors' => array()
        );
        $this->log("INFO", "Début de la sauvegarde automatique avant mise à jour");
        $backup = $this->createBackup();
        if (!$backup['success']) {
            $result['errors'][] = "Erreur lors de la sauvegarde: " . implode(' | ', $backup['errors']);
            $this->log("ERROR", "Abandon de la mise à jour car la sauvegarde a échoué");
            $result['logs'] = $this->getLogs(50);
            return $result;
        }
        $this->log("INFO", "Début de la mise à jour (git pull uniquement)");
        $dolibarr_root = dirname(DOL_DOCUMENT_ROOT);
        $this->log("DEBUG", "Chemin du dépôt : $dolibarr_root");
        $whoami = trim(shell_exec('whoami'));
        $this->log("DEBUG", "Utilisateur courant : $whoami");
        $command = "cd " . escapeshellarg($dolibarr_root) . " && git pull 2>&1";
        $output = shell_exec($command);
        $this->log("DEBUG", "Sortie du git pull :\n" . $output);
        $result['output'] = $output;
        $result['logs'] = $this->getLogs(50);
        return $result;
    }

    /**
     * Valide les permissions de l'utilisateur
     * 
     * @return bool
     */
    public function validatePermissions()
    {
        // Vérification des droits admin
        if (!$this->user->admin) {
            $this->log("ERROR", "Tentative d'accès sans droits admin par l'utilisateur " . $this->user->login);
            return false;
        }

        // Vérification des permissions d'écriture pour les logs
        if (!is_writable(dirname($this->logfile))) {
            $this->log("ERROR", "Impossible d'écrire dans le dossier de logs");
            return false;
        }

        return true;
    }

    /**
     * Valide l'accessibilité du script GitHub
     * 
     * @return bool
     */
    public function validateScript()
    {
        // Test de l'accessibilité de l'URL GitHub
        $headers = get_headers($this->script_url);
        if (!$headers || strpos($headers[0], '200') === false) {
            $this->log("ERROR", "Script GitHub inaccessible: " . $this->script_url);
            return false;
        }

        return true;
    }

    /**
     * Écrit un message dans le log
     * 
     * @param string $level Niveau (INFO, WARNING, ERROR)
     * @param string $message Message à logger
     */
    public function log($level, $message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($this->logfile, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Récupère les logs
     * 
     * @param int $lines Nombre de lignes à récupérer (0 = tout)
     * @return string Contenu des logs
     */
    public function getLogs($lines = 0)
    {
        if (!file_exists($this->logfile)) {
            return $this->langs->trans("NoLogsAvailable");
        }

        $content = file_get_contents($this->logfile);
        
        if ($lines > 0) {
            $lines_array = explode(PHP_EOL, $content);
            $content = implode(PHP_EOL, array_slice($lines_array, -$lines));
        }

        return $content;
    }

    /**
     * Nettoie les anciens logs
     * 
     * @param int $days Nombre de jours à conserver
     */
    public function cleanLogs($days = 30)
    {
        if (file_exists($this->logfile)) {
            $filetime = filemtime($this->logfile);
            if (time() - $filetime > ($days * 24 * 3600)) {
                unlink($this->logfile);
                $this->log("INFO", "Anciens logs supprimés (plus de $days jours)");
            }
        }
    }

    public function clearLogs()
    {
        if (file_exists($this->logfile)) {
            unlink($this->logfile);
        }
    }


    /**
     * Récupère l'état Git du répertoire
     * 
     * @return string État Git
     */
    private function getGitStatus()
    {
        // Le répertoire racine de Dolibarr est le répertoire parent de htdocs
        $dolibarr_root = dirname(DOL_DOCUMENT_ROOT);
        
        // Vérification si on est dans un repo Git
        if (!is_dir($dolibarr_root . '/.git')) {
            return "Pas de repository Git dans: " . $dolibarr_root;
        }

        // Récupération de l'état Git
        $git_status = shell_exec("cd " . escapeshellarg($dolibarr_root) . " && git status --porcelain 2>&1");
        $git_branch = shell_exec("cd " . escapeshellarg($dolibarr_root) . " && git branch --show-current 2>&1");
        
        if (empty(trim($git_status))) {
            return "Repository propre sur la branche: " . trim($git_branch);
        } else {
            $modified_files = explode("\n", trim($git_status));
            $modified_count = count(array_filter($modified_files));
            return "Repository avec $modified_count fichier(s) modifié(s) sur la branche: " . trim($git_branch);
        }
    }

    /**
     * Formate la sortie pour l'affichage
     * 
     * @param string $output Sortie brute
     * @return string Sortie formatée
     */
    public function formatOutput($output)
    {
        // Remplacement des caractères spéciaux pour l'affichage HTML
        $output = htmlspecialchars($output);
        
        // Coloration syntaxique basique
        $output = preg_replace('/ERROR:/', '<span style="color: red; font-weight: bold;">ERROR:</span>', $output);
        $output = preg_replace('/WARNING:/', '<span style="color: orange; font-weight: bold;">WARNING:</span>', $output);
        $output = preg_replace('/INFO:/', '<span style="color: blue; font-weight: bold;">INFO:</span>', $output);
        
        return $output;
    }

    /**
     * Récupère les informations du système
     * 
     * @return array Informations système
     */
    public function getSystemInfo()
    {
        return array(
            'log_file' => $this->logfile,
            'log_writable' => is_writable(dirname($this->logfile)),
            'user' => $this->user->login,
            'user_admin' => $this->user->admin,
            'dolibarr_version' => DOL_VERSION,
            'php_version' => PHP_VERSION
        );
    }

    /**
     * Récupère la liste des fichiers de backup disponibles
     * 
     * @return array Liste des fichiers de backup
     */
    public function getBackupFiles()
    {
        $backup_files = array();
        
        // Vérifier les fichiers de backup temporaires
        $temp_backups = array(
            '/tmp/dolibarr_conf_backup.php',
            '/tmp/dolibarr_documents_backup.tar.gz'
        );
        
        foreach ($temp_backups as $file) {
            if (file_exists($file)) {
                $backup_files[] = $file;
            }
        }
        
        return $backup_files;
    }

    /**
     * Restaure les données depuis les fichiers de backup
     * 
     * @return array Résultat de l'opération
     */
    public function restoreBackup()
    {
        $result = array(
            'success' => false,
            'output' => '',
            'errors' => array()
        );

        // Validation des permissions
        if (!$this->validatePermissions()) {
            $result['errors'][] = $this->langs->trans("InsufficientPermissions");
            return $result;
        }

        $this->log("INFO", "Début de la restauration des données");

        try {
            $restored_files = array();

            // Restauration de la configuration
            $conf_backup = '/tmp/dolibarr_conf_backup.php';
            if (file_exists($conf_backup)) {
                $conf_target = DOL_DOCUMENT_ROOT . '/htdocs/conf/conf.php';
                
                // Sauvegarde de la configuration actuelle
                if (file_exists($conf_target)) {
                    copy($conf_target, $conf_target . '.before_restore');
                }
                
                // Restauration
                if (copy($conf_backup, $conf_target)) {
                    $restored_files[] = 'Configuration (conf.php)';
                    $this->log("INFO", "Configuration restaurée depuis le backup");
                } else {
                    $result['errors'][] = "Impossible de restaurer la configuration";
                    $this->log("ERROR", "Échec de la restauration de la configuration");
                }
            }

            // Restauration des documents
            $docs_backup = '/tmp/dolibarr_documents_backup.tar.gz';
            if (file_exists($docs_backup)) {
                $docs_target = DOL_DOCUMENT_ROOT . '/documents';
                
                // Sauvegarde des documents actuels
                if (is_dir($docs_target)) {
                    $backup_current = '/tmp/dolibarr_documents_current_' . date('Y-m-d_H-i-s') . '.tar.gz';
                    $command = "tar -czf $backup_current -C " . DOL_DOCUMENT_ROOT . " documents/";
                    shell_exec($command);
                    $this->log("INFO", "Sauvegarde des documents actuels créée: $backup_current");
                }
                
                // Restauration
                $command = "tar -xzf $docs_backup -C " . DOL_DOCUMENT_ROOT;
                $output = shell_exec($command . " 2>&1");
                
                if (empty($output) || strpos($output, 'error') === false) {
                    $restored_files[] = 'Documents (documents/)';
                    $this->log("INFO", "Documents restaurés depuis le backup");
                } else {
                    $result['errors'][] = "Impossible de restaurer les documents: $output";
                    $this->log("ERROR", "Échec de la restauration des documents: $output");
                }
            }

            if (empty($restored_files)) {
                $result['errors'][] = "Aucun fichier de backup trouvé";
                $this->log("ERROR", "Aucun fichier de backup disponible pour la restauration");
            } else {
                $result['success'] = true;
                $result['output'] = "Fichiers restaurés: " . implode(', ', $restored_files);
                $this->log("INFO", "Restauration terminée avec succès");
            }

        } catch (Exception $e) {
            $this->log("ERROR", "Exception lors de la restauration: " . $e->getMessage());
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    public function restoreBackupFile($file, $type)
    {
        $result = array('success' => false, 'output' => '', 'errors' => array());
        if (!file_exists($file)) {
            $result['errors'][] = "Fichier non trouvé : $file";
            $this->log("ERROR", "Fichier non trouvé : $file");
            return $result;
        }
        if ($type == 'sql') {
            $this->log("INFO", "Début restauration base depuis $file");
            $dbtype = $this->db->type;
            $dbuser = $this->db->user;
            $dbpass = $this->db->pass;
            $dbname = $this->db->database_name;
            $dbhost = $this->db->host;
            if ($dbtype == 'pgsql') {
                $cmd = "PGPASSWORD=".escapeshellarg($dbpass)." psql -U ".escapeshellarg($dbuser)." -h ".escapeshellarg($dbhost)." -d ".escapeshellarg($dbname)." -f ".escapeshellarg($file);
            } else {
                $cmd = "mysql -u".escapeshellarg($dbuser);
                if ($dbpass !== '') {
                    $cmd .= " -p" . escapeshellarg($dbpass);
                }
                $cmd .= " ".escapeshellarg($dbname)." < ".escapeshellarg($file);
            }
            $output = shell_exec($cmd . " 2>&1");
            $this->log("DEBUG", "Sortie restauration base :\n" . $output);
            $result['output'] = $output;
            $result['success'] = true;
        } elseif ($type == 'documents') {
            $this->log("INFO", "Début restauration documents depuis $file");
            $docdir = DOL_DATA_ROOT . '/documents';
            $tarcmd = "tar -xzf " . escapeshellarg($file) . " -C " . escapeshellarg(DOL_DATA_ROOT);
            $output = shell_exec($tarcmd . " 2>&1");
            $this->log("DEBUG", "Sortie restauration documents :\n" . $output);
            $result['output'] = $output;
            $result['success'] = true;
        } else {
            $result['errors'][] = "Type de fichier inconnu : $type";
            $this->log("ERROR", "Type de fichier inconnu : $type");
        }
        return $result;
    }

    public function listBackups()
    {
        global $conf;
        $backupdir = $conf->admin->dir_output.'/backup';
        $files = array();
        if (is_dir($backupdir)) {
            foreach (scandir($backupdir) as $file) {
                if (preg_match('/^(backup_\d{8}_\d{6}\.sql|documents_\d{8}_\d{6}\.tar\.gz)$/', $file)) {
                    $files[] = $backupdir . '/' . $file;
                }
            }
        }
        usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
        return $files;
    }

    public function restoreLastBackup()
    {
        global $conf;
        $backupdir = $conf->admin->dir_output.'/backup';
        $result = array('success' => false, 'output' => '', 'errors' => array());
        // 1. Trouver le dernier dump SQL
        $sqlfiles = glob($backupdir . '/backup_*.sql');
        $docfiles = glob($backupdir . '/documents_*.tar.gz');
        if (!$sqlfiles || !$docfiles) {
            $result['errors'][] = "Aucun fichier de sauvegarde trouvé dans $backupdir";
            $this->log("ERROR", "Aucun fichier de sauvegarde trouvé dans $backupdir");
            return $result;
        }
        usort($sqlfiles, function($a, $b) { return filemtime($b) - filemtime($a); });
        usort($docfiles, function($a, $b) { return filemtime($b) - filemtime($a); });
        $lastsql = $sqlfiles[0];
        $lastdoc = $docfiles[0];
        // 2. Restauration base
        $this->log("INFO", "Début restauration base depuis $lastsql");
        $dbtype = $this->db->type;
        $dbuser = $this->db->user;
        $dbpass = $this->db->pass;
        $dbname = $this->db->database_name;
        $dbhost = $this->db->host;
        if ($dbtype == 'pgsql') {
            $cmd = "PGPASSWORD=".escapeshellarg($dbpass)." psql -U ".escapeshellarg($dbuser)." -h ".escapeshellarg($dbhost)." -d ".escapeshellarg($dbname)." -f ".escapeshellarg($lastsql);
        } else {
            $cmd = "mysql -u".escapeshellarg($dbuser);
            if ($dbpass !== '') {
                $cmd .= " -p" . escapeshellarg($dbpass);
            }
            $cmd .= " ".escapeshellarg($dbname)." < ".escapeshellarg($lastsql);
        }
        $output = shell_exec($cmd . " 2>&1");
        $this->log("DEBUG", "Sortie restauration base :\n" . $output);
        // 3. Restauration documents
        $this->log("INFO", "Début restauration documents depuis $lastdoc");
        $docdir = DOL_DATA_ROOT . '/documents';
        $tarcmd = "tar -xzf " . escapeshellarg($lastdoc) . " -C " . escapeshellarg(DOL_DATA_ROOT);
        $output2 = shell_exec($tarcmd . " 2>&1");
        $this->log("DEBUG", "Sortie restauration documents :\n" . $output2);
        $result['success'] = true;
        $result['output'] = "Base restaurée depuis $lastsql\nDocuments restaurés depuis $lastdoc";
        return $result;
    }
} 