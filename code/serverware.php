<?php
class module_serverware {
    /* Get app variables depending on the server software (Sentora or Zpanel) */
    static function getWare() {
        global $zdbh, $controller;
        
        $app = '';
        $sys_path = '/etc/';
        $host_path = '/var/';
	$js_app = 'zPanel';

        //This needs to be verified as the Sentora BETA installer created a zPanel directory symlink as well
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
            // Or a sentora installation?
            $numrows = $zdbh->prepare("SELECT SCHEMA_NAME AS database_name FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'sentora_core'");
            $numrows->execute(); 
            $db_info = $numrows->fetch();        
            if (isset($db_info['database_name'])){
               $app = 'sentora';
	       $js_app = 'Sentora';
            }
        }

        if($app != ''){
            //self::$server_app = $app;
            //self::$module_db = $app.'_xbilling';
            $server_vars = array('sysdir' => fs_director::ConvertSlashes($sys_path.$app.'/'), 
                                        'hostdir' => fs_director::ConvertSlashes($host_path.$app.'/'),
                                        'app' => $app, 'js_app' => $js_app,
                                        );
           //self::$server_vars = $server_vars;


            return $server_vars;
        }

        return FALSE;
    }
}
/* Get app variables depending on the server software (Sentora or Zpanel) */
