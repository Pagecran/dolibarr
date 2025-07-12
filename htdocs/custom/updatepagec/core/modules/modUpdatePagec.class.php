<?php
// htdocs/custom/updatepagec/core/modules/modUpdatePagec.class.php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modUpdatePagec extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;
        
        $this->db = $db;
        $this->numero = 500000;
        $this->family = "other";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Mise à jour Pagecran - Script personnalisé de mise à jour Dolibarr";
        $this->descriptionlong = "Module pour lancer les mises à jour Pagecran depuis l'interface Dolibarr. Utilise le script dolibarr_pagec_proxmox.sh.";
        $this->editor_name = 'Pagecran';
        $this->editor_url = 'https://pagecran.fr';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'generic';
        
        // Dossiers à créer
        $this->dirs = array("/updatepagec/temp");
        
        // Pages de configuration
        $this->config_page_url = array("update.php@updatepagec");
        
        // Dépendances
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->phpmin = array(7,0);
        $this->need_dolibarr_version = array(11,0);
        $this->langfiles = array("updatepagec@updatepagec");
        
        // Permissions
        $this->rights = array();
        $this->rights[0][0] = $this->numero;
        $this->rights[0][1] = 'Lancer les mises à jour Pagecran';
        $this->rights[0][3] = 0;
        $this->rights[0][4] = 'update';
    }
    
    public function init($options = '')
    {
        $sql = array();
        
        return $this->_init($sql, $options);
    }
    
    public function loadMenus()
    {
        global $langs, $conf;
        
        $r = 0;
        
        // Menu principal
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=home,fk_leftmenu=setup',
            'type'=>'left',
            'titre'=>'Mise à jour Pagecran',
            'mainmenu'=>'home',
            'leftmenu'=>'updatepagec',
            'url'=>'/updatepagec/admin/update.php',
            'langs'=>'updatepagec@updatepagec',
            'position'=>100,
            'enabled'=>'$conf->updatepagec->enabled',
            'perms'=>'$user->admin',
            'target'=>'',
            'user'=>2
        );
        
        return $r;
    }
}
?>
