<?php
/* Get app variables depending on server software (Sentora or Zpanel) */
    static function getAppWare() {
       // global $zdbh, $controller;
        
        $app = '';
        $sys_path = '/etc/';
        $host_path = '/var/';

        if(fs_director::CheckFolderExists(ConvertSlashes($sys_path.'zpanel')) && fs_director::CheckFolderExists(ConvertSlashes($host_path.'zpanel'))){
            $app = 'zpanel';
        } elseif(fs_director::CheckFolderExists(ConvertSlashes($sys_path.'sentora')) && fs_director::CheckFolderExists(ConvertSlashes($host_path.'sentora'))){
            $app = 'sentora';
        }

        if($app != ''){
            self::$server_app = $app;
            self::$module_db = $app.'_xbilling';
            $server_vars = array('sysdir' => ConvertSlashes($sys_path.$app.'/'), 
                                        'hostdir' => ConvertSlashes($host_path.$app.'/'),
                                        'app' => $app,
                                        );
           self::$server_vars = $server_vars;

            return $server_vars;
        }


    }
/* Get app variables depending on server software (Sentora or Zpanel) */
