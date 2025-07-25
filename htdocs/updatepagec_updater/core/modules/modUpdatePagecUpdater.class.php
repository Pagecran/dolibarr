<?php
/**
 *  Module updatepagec_updater - Permet de mettre à jour le module updatePagec via git pull
 */
class modUpdatePagecUpdater extends DolibarrModules
{
    /**
     *  Constructor. Define names, constants, boxes, permissions
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;
        $this->numero = 999999; // Numéro fictif
        $this->rights_class = 'updatepagec_updater';
        $this->family = "base";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Permet de mettre à jour le module updatePagec via git pull";
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'technic';
        $this->module_parts = array();
        $this->dirs = array();
        $this->config_page_url = array('updatepagec_updater_admin.php');
        $this->langfiles = array("updatepagec_updater@updatepagec_updater");
        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->phpmin = array(7,0);
        $this->need_dolibarr_version = array(15,0);
        $this->tabs = array();
        $this->rights = array();
        $this->rights_class = 'updatepagec_updater';
        $this->menu = array();
        $r = 0;
        $this->menu[$r]=array(
            'fk_menu'=>'',
            'type'=>'top',
            'titre'=>'UpdatePagecUpdaterAdmin',
            'mainmenu'=>'updatepagec_updater',
            'leftmenu'=>'updatepagec_updater',
            'url'=>'/custom/updatepagec_updater/updatepagec_updater_admin.php',
            'langs'=>'updatepagec_updater@updatepagec_updater',
            'position'=>1000,
            'enabled'=>'$conf->updatepagec_updater->enabled',
            'perms'=>'$user->admin',
            'target'=>'',
            'user'=>2
        );
    }
} 