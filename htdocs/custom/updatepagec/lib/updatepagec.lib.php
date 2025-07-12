<?php
// htdocs/custom/updatepagec/lib/updatepagec.lib.php

class UpdatePagec
{
    public $db;
    public $logfile;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->logfile = DOL_DATA_ROOT . '/updatepagec.log';
    }
    
    public function launchUpdate()
    {
        global $langs, $conf;
        
        $result = array('success' => false, 'errors' => array());
        
        // Vérifications préalables
        if (!$this->checkRequirements()) {
            $result['errors'][] = $langs->trans("RequirementsNotMet");
            return $result;
        }
        
        // Log du début de la mise à jour
        $this->log("=== Début de la mise à jour Pagecran ===");
        $this->log("Lancé par : " . $GLOBALS['user']->login);
        $this->log("Date : " . date('Y-m-d H:i:s'));
        
        try {
            // URL du script de mise à jour
            $script_url = "https://raw.githubusercontent.com/Pagecran/dolibarr/Pagec/dolibarr_pagec_proxmox.sh";
            
            // Commande de mise à jour
            $command = "bash -c \"$(curl -fsSL $script_url)\" 2>&1";
            
            // Exécution de la commande
            $this->log("Exécution de la commande : " . $command);
            $output = shell_exec($command);
            
            // Log du résultat
            $this->log("Résultat de la commande :");
            $this->log($output);
            
            if ($output === null) {
                throw new Exception("Erreur lors de l'exécution de la commande");
            }
            
            $this->log("=== Mise à jour terminée ===");
            $result['success'] = true;
            
        } catch (Exception $e) {
            $this->log("ERREUR : " . $e->getMessage());
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
    
    private function checkRequirements()
    {
        // Vérifier que shell_exec est disponible
        if (!function_exists('shell_exec')) {
            return false;
        }
        
        // Vérifier que curl est disponible
        $curl_check = shell_exec("which curl 2>/dev/null");
        if (empty($curl_check)) {
            return false;
        }
        
        // Vérifier les permissions d'écriture
        if (!is_writable(DOL_DATA_ROOT)) {
            return false;
        }
        
        return true;
    }
    
    private function log($message)
    {
        $log_entry = date('Y-m-d H:i:s') . " - " . $message . "\n";
        file_put_contents($this->logfile, $log_entry, FILE_APPEND);
    }
    
    public function getLogs($lines = 100)
    {
        if (!file_exists($this->logfile)) {
            return array();
        }
        
        $logs = file($this->logfile);
        return array_slice($logs, -$lines);
    }
    
    public function clearLogs()
    {
        if (file_exists($this->logfile)) {
            unlink($this->logfile);
        }
    }
}
?>
