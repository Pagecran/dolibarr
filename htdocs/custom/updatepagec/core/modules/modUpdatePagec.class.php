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
    }

    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
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
