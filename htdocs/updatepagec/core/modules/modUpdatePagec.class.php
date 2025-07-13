<?php
// htdocs/updatepagec/core/modules/modUpdatePagec.class.php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modUpdatePagec extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 500001;
        $this->family = "base";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Module de mise à jour Pagecran";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'generic';
        $this->module_parts = array();
        $this->dependencies = array();
        $this->conflictwith = array();
        $this->requiredby = array();
        $this->phpmin = array(7,0);
        $this->langfiles = array("updatepagec@updatepagec");

        // Déclaration du menu dans Outils d'administration
        $this->menu = array();
        $r = 0;
        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools',
            'type'=>'left',
            'titre'=>'UpdatePagec',
            'mainmenu'=>'home',
            'leftmenu'=>'updatepagec',
            'url'=>'/updatepagec/admin/update.php',
            'langs'=>'updatepagec@updatepagec',
            'position'=>1000,
            'enabled'=>'$conf->updatepagec->enabled',
            'perms'=>'$user->admin',
            'target'=>'',
            'user'=>2
        );
    }

    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
    }

    /**
     * Ajout du menu UpdatePagec dans Outils d'administration
     */
    public function menu()
    {
        global $conf, $langs, $user;
        $menu = array();
        $r = 0;
        // Menu dans Outils d'administration
        $menu[$r][0] = 'top';
        $menu[$r][1] = $langs->trans('UpdatePagec');
        $menu[$r][2] = '/updatepagec/admin/update.php';
        $menu[$r][3] = 1;
        $menu[$r][4] = 'updatepagec';
        $menu[$r][5] = 'updatepagec';
        $menu[$r][6] = 'systemadmin';
        $menu[$r][7] = 1000;
        $menu[$r][8] = '';
        $menu[$r][9] = '';
        $menu[$r][10] = 1;
        return $menu;
    }

    public function activate($options = '')
    {
        global $conf, $langs;
        $this->_load_tables('/install/mysql/', 'updatepagec');
        return $this->_activate($options);
    }

    public function disable($options = '')
    {
        $res = 0;
        return $this->_disable($options);
    }
}
?>
