<?php
class module_serverware {
    /* Get app variables depending on server software (Sentora or Zpanel) */
    static function getWare() {
        global $zdbh, $controller;
        
        $app = '';
        $sys_path = '/etc/';
        $host_path = '/var/';

        //This needs to be verified as the BETA installer created both zpanel and sentora directories
        /*if(fs_director::CheckFolderExists(fs_director::ConvertSlashes($sys_path.'zpanel')) && fs_director::CheckFolderExists(fs_director::ConvertSlashes($host_path.'zpanel'))){
            $app = 'zpanel';
        } elseif(fs_director::CheckFolderExists(fs_director::ConvertSlashes($sys_path.'sentora')) && fs_director::CheckFolderExists(fs_director::ConvertSlashes($host_path.'sentora'))){
            $app = 'sentora';
        }*/

        // Is this a zpanel installation?
        $numrows = $zdbh->prepare("SELECT SCHEMA_NAME AS database_name FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'zpanel_core'");
        $numrows->execute(); 
        $db_info = $numrows->fetch();        
        if (isset($db_info['database_name'])){
           $app = 'zpanel';
        } else {
            // Is this a zpanel installation?
            $numrows = $zdbh->prepare("SELECT SCHEMA_NAME AS database_name FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'sentora_core'");
            $numrows->execute(); 
            $db_info = $numrows->fetch();        
            if (isset($db_info['database_name'])){
               $app = 'sentora';
            }
        }

        if($app != ''){
            //self::$server_app = $app;
            //self::$module_db = $app.'_xbilling';
            $server_vars = array('sysdir' => fs_director::ConvertSlashes($sys_path.$app.'/'), 
                                        'hostdir' => fs_director::ConvertSlashes($host_path.$app.'/'),
                                        'app' => $app,
                                        );
           //self::$server_vars = $server_vars;

            return $server_vars;
        }

        return FALSE;
    }
}
/* Get app variables depending on server software (Sentora or Zpanel) */
