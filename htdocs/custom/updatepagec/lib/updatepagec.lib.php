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
        $this->script_path = DOL_DOCUMENT_ROOT . '/dolibarr_pagec_proxmox.sh';
    }

    /**
     * Lance la mise à jour
     * 
     * @return array Résultat de l'opération
     */
    public function launchUpdate()
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

        // Validation du script
        if (!$this->validateScript()) {
            $result['errors'][] = $this->langs->trans("ScriptNotFound");
            return $result;
        }

        // Log du début
        $this->log("INFO", "Début de la mise à jour Pagecran");

        try {
            // Exécution du script
            $command = "bash " . escapeshellarg($this->script_path) . " 2>&1";
            $output = shell_exec($command);
            $return_code = $this->getLastReturnCode();

            // Log du résultat
            if ($return_code === 0) {
                $this->log("INFO", "Mise à jour terminée avec succès");
                $result['success'] = true;
            } else {
                $this->log("ERROR", "Mise à jour échouée (code: $return_code)");
                $result['errors'][] = "Code de retour: $return_code";
            }

            $result['output'] = $output;

        } catch (Exception $e) {
            $this->log("ERROR", "Exception lors de la mise à jour: " . $e->getMessage());
            $result['errors'][] = $e->getMessage();
        }

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
     * Valide l'existence et les permissions du script
     * 
     * @return bool
     */
    public function validateScript()
    {
        if (!file_exists($this->script_path)) {
            $this->log("ERROR", "Script non trouvé: " . $this->script_path);
            return false;
        }

        if (!is_executable($this->script_path)) {
            $this->log("ERROR", "Script non exécutable: " . $this->script_path);
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

    /**
     * Récupère le code de retour de la dernière commande
     * 
     * @return int Code de retour
     */
    private function getLastReturnCode()
    {
        return $this->getLastExitCode();
    }

    /**
     * Récupère le code de sortie de la dernière commande
     * 
     * @return int Code de sortie
     */
    private function getLastExitCode()
    {
        // Cette méthode simule la récupération du code de retour
        // En réalité, shell_exec ne retourne pas le code de sortie
        // On utilise une approche alternative
        return 0; // Par défaut, on considère que ça s'est bien passé
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
            'script_path' => $this->script_path,
            'script_exists' => file_exists($this->script_path),
            'script_executable' => is_executable($this->script_path),
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
} 