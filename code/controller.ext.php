<?php
/**
 *
 * xBilling Module for ZPanel 10.1.0, Sentora 1.0.0
 * Version : 1.2.0
 * Author :  Aderemi Adewale (modpluz @ Sentora Forums)
 * Email : goremmy@gmail.com
 */
require_once('serverware.php');

class module_controller {

    static $complete;
    static $error;
    static $file_error;
    static $view;
    static $ok;
    //static $customerror;
    static $module_db = 'sentora_xbilling';
    static $server_app = 'sentora';
    static $server_vars = array();

   
    //public function __construct(){
        
    //}

   



/*START - Check for updates added by TGates*/
// Module update check functions
    static function getModuleVersion() {
        global $controller;

        $module_path="./modules/" . $controller->GetControllerRequest('URL', 'module');
        
        // Get Update URL and Version From module.xml
        $mod_xml = "./modules/" . $controller->GetControllerRequest('URL', 'module') . "/module.xml";
        $mod_config = new xml_reader(fs_filehandler::ReadFileContents($mod_xml));
        $mod_config->Parse();
        $module_version = $mod_config->document->version[0]->tagData;
        return "v".$module_version."";
    }
    
    static function getCheckUpdate() {
        global $controller;
        $module_path="./modules/" . $controller->GetControllerRequest('URL', 'module');
        
        // Get Update URL and Version From module.xml
        $mod_xml = "./modules/" . $controller->GetControllerRequest('URL', 'module') . "/module.xml";
        $mod_config = new xml_reader(fs_filehandler::ReadFileContents($mod_xml));
        $mod_config->Parse();
        $module_updateurl = $mod_config->document->updateurl[0]->tagData;
        $module_version = $mod_config->document->version[0]->tagData;

        // Download XML in Update URL and get Download URL and Version
        $myfile = self::getCheckRemoteXml($module_updateurl, $module_path."/" . $controller->GetControllerRequest('URL', 'module') . ".xml");
        $update_config = new xml_reader(fs_filehandler::ReadFileContents($module_path."/" . $controller->GetControllerRequest('URL', 'module') . ".xml"));
        $update_config->Parse();
        $update_url = $update_config->document->downloadurl[0]->tagData;
        $update_version = $update_config->document->latestversion[0]->tagData;

        if($update_version > $module_version)
            return true;
        return false;
    }

/*END - Check for updates added by TGates*/

/*START - Check for updates added by TGates*/
// Function to retrieve remote XML for update check
    static function getCheckRemoteXml($xmlurl,$destfile){
        $feed = simplexml_load_file($xmlurl);
        if ($feed)
        {
            // $feed is valid, save it
            $feed->asXML($destfile);
        } elseif (file_exists($destfile)) {
            // $feed is not valid, grab the last backup
            $feed = simplexml_load_file($destfile);
        } else {
            //die('Unable to retrieve XML file');
            echo('<div class="alert alert-danger">Unable to check for updates, your version may be outdated!.</div>');
        }
    }
/*END - Check for updates added by TGates*/

   /* Load CSS and JS files */
    static function getInit() {
        global $controller;
        
        self::$server_vars = module_serverware::getWare();
        
        if(count(self::$server_vars)){
           self::$server_app = self::$server_vars['app'];
           self::$module_db = self::$server_vars['app'].'_xbilling';
        }
        
        $line = '<link rel="stylesheet" type="text/css" href="modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/xbilling.css">';
        $line .= '<script type="text/javascript">var js_app = eval("'.self::$server_vars['js_app'].'");</script>';
        $line .= '<script type="text/javascript" src="modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/jquery.validate-1.11.1.min.js"></script>';
        $line .= '<script type="text/javascript" src="modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/xbilling.js"></script>';
        $line .= '<script type="text/javascript" src="modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/ckeditor/ckeditor.js"></script>';
        $line .= '<script type="text/javascript" src="modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/ckeditor/adapters/jquery.js"></script>';
        return $line;
    }
    
    static function getisModuleInstalled(){
        global $zdbh;
        
        $numrows = $zdbh->prepare("SELECT SCHEMA_NAME AS database_name FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".self::$module_db."'");
        $numrows->execute(); 
        $db_info = $numrows->fetch();
        
        if (isset($db_info['database_name'])){
           //self::$ok = true;
           self::$error = false;           
           return true;
        }
        
        return false;    
    }

    static function getCSFR_Tag() {
        return runtime_csfr::Token();
    }

    static function getModuleName() {
        $module_name = ui_module::GetModuleName();
        return $module_name;

    }

    static function getModuleIcon() {
        global $controller;
        $module_icon = "/modules/" . $controller->GetControllerRequest('URL', 'module') . "/assets/icon.png";
        return $module_icon;
    }


    static function getModuleDesc() {
        $message = ui_language::translate(ui_module::GetModuleDescription());
        return $message;
    }

    static function getModuleDir(){
        global $controller;
        $name = $controller->GetControllerRequest('URL', 'module');
        return "/modules/".$name;
    }

    static function getModuleNav() {
        //global $controller;
        $nav_html = '';
        
        $currentuser = ctrl_users::GetUserDetail();        
        //settings
        $nav_html = '<li title="Website Settings"';
        if(self::$view == 'website_settings'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '><a href="#website_settings" onclick="_view(\'website_settings\');"';
        if(self::$view == 'website_settings'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '>Website Settings</a></li>';

        //email settings
        $nav_html .= '<li title="Email Settings"';
        if(self::$view == 'email_settings'){
            $nav_html .=' class="active"';
        }
        $nav_html .= ' onclick="_view(\'email_settings\');"><a href="#email_settings"';
        if(self::$view == 'email_settings'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '>Email Settings</a></li>';

        //service periods
        $nav_html .= '<li title="Service Plans"';
        if(self::$view == 'service_periods'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '><a href="#service_periods" onclick="_view(\'service_periods\');"';
        if(self::$view == 'service_periods'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '>Plans</a></li>';

        //packages
        $nav_html .= '<li title="Hosting Packages"';
        if(self::$view == 'packages'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '><a href="#packages" onclick="_view(\'packages\');"';
        if(self::$view == 'packages'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '>Packages</a></li>';

        //payment options
        if($currentuser['userid'] == 1){
		$nav_html .= '<li title="Payment Options"';
		if(self::$view == 'payment_options'){
		    $nav_html .=' class="active"';
		}
		$nav_html .= '><a href="#payment_options" onclick="_view(\'payment_options\');"';
		if(self::$view == 'payment_options'){
		    $nav_html .=' class="active"';
		}
		$nav_html .= '>Payment Options</a></li>';
        }

        //payment methods
        $nav_html .= '<li title="Payment Methods"';
        if(self::$view == 'payment_methods'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '><a href="#payment_methods" onclick="_view(\'payment_methods\');"';
        if(self::$view == 'payment_methods'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '>Payment Methods</a></li>';

        //orders / invoices
        $nav_html .= '<li title="Orders / Invoices"';
        if(self::$view == 'invoices_orders'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '><a href="#invoices_orders" onclick="_view(\'invoices_orders\');"';
        if(self::$view == 'invoices_orders'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '>Orders</a></li>';

        //vouchers
        $nav_html .= '<li title="Discount Vouchers"';
        if(self::$view == 'vouchers'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '><a href="#vouchers" onclick="_view(\'vouchers\');"';
        if(self::$view == 'vouchers'){
            $nav_html .=' class="active"';
        }
        $nav_html .= '>Vouchers</a></li>';
        
        
        /*if(self::$view != 'settings'){
            $nav_html .='<a href="javascript:_view(\'settings\');" title="Settings">Settings</a>';
        } else {
            $nav_html .= '<strong>Website Settings</strong>';
        }
       $nav_html .='&nbsp;&nbsp;<strong>|</strong>&nbsp;&nbsp;';
        if(self::$view != 'service_periods'){
            $nav_html .='<a href="javascript:_view(\'service_periods\');" title="Service Periods">Service Periods</a>';
        } else {
            $nav_html .= '<strong>Service Periods</strong>';
        }
        $nav_html .='&nbsp;&nbsp;<strong>|</strong>&nbsp;&nbsp;';
        if(self::$view != 'packages'){
            $nav_html .='<a href="javascript:_view(\'packages\');" title="Hosting Packages">Hosting Packages</a>';
        } else {
            $nav_html .= '<strong>Packages</strong>';
        }
         if($currentuser['userid'] == 1){
            $nav_html .='&nbsp;&nbsp;<strong>|</strong>&nbsp;&nbsp;';
            if(self::$view != 'payment_options'){
                $nav_html .='<a href="javascript:_view(\'payment_options\');" title="Payment Options">Payment Options</a>';
            } else {
                $nav_html .= '<strong>Payment Options</strong>';
            }
        }
        $nav_html .='&nbsp;&nbsp;<strong>|</strong>&nbsp;&nbsp;';
        if(self::$view != 'payment_methods'){
            $nav_html .='<a href="javascript:_view(\'payment_methods\');" title="Payment Methods">Payment Methods</a>';
        } else {
            $nav_html .= '<strong>Payment Methods</strong>';
        }
        $nav_html .='&nbsp;&nbsp;<strong>|</strong>&nbsp;&nbsp;';
        if(self::$view != 'invoices_orders'){
            $nav_html .='<a href="javascript:_view(\'invoices_orders\');" title="Orders / Invoices">Orders / Invoices</a>';
        } else {
            $nav_html .= '<strong>Orders / Invoices</strong>';
        } */
        return $nav_html;
    }


    static function getView() {
        global $controller;
        if ($controller->GetControllerRequest('FORM', 'view')) {
            //runtime_csfr::Protect();
            self::$view = $controller->GetControllerRequest('FORM', 'view');
        } else {
            self::$view = 'invoices_orders';
        }
        
        if(!is_array(self::$error)){
        	self::$error = false;
       	}
        
        return self::$view;
    }
    

    /*static function getView() {
        global $controller;
        if ($controller->GetControllerRequest('FORM', 'view')) {
            //runtime_csfr::Protect();
            self::$view = $controller->GetControllerRequest('FORM', 'view');
        } else {
            self::$view = 'settings';
        }
        return self::$view;
    }*/
    
    /* Heading */
    /*static function getHeading(){
        switch(self::$view){
            case 'settings':
                return 'Settings';
            break;
            case 'service_periods':
                return 'Service Periods';
            break;
            case 'packages':
                return 'Packages';
            break;
            case 'payment_methods':
                return 'Payment Methods';
            break;
            case 'orders':
                return 'Orders';
            break;
            case 'invoices':
                return 'Invoices';
            break;
        }
    }*/
    /* Heading */
    
    /* View States */
    static function getViewSettings(){
        if(self::$view == 'website_settings'){
            return true;
        }
        return false;
    }

    static function getViewEmailSettings(){
        if(self::$view == 'email_settings'){
            return true;
        }
        return false;
    }

    static function getViewPeriods(){
        if(self::$view == 'service_periods'){
            return true;
        }
        return false;
    }

    static function getViewPackages(){
        if(self::$view == 'packages'){
            return true;
        }
        return false;
    }

    static function getViewPaymentMethods(){
        if(self::$view == 'payment_methods'){
            return true;
        }
        return false;
    }

    static function getViewPaymentOptions(){
        if(self::$view == 'payment_options'){
            return true;
        }
        return false;
    }

    static function getViewOrdersInvoices(){
        if(self::$view == 'invoices_orders'){
            return true;
        }
        return false;
    }

    static function getViewHelp(){
        if(self::$view == 'help'){
            return true;
        }
        return false;
    }

    static function getViewNewAccount(){
        if(self::$view == 'new_account'){
            return true;
        }
        return false;
    }

    static function getViewVouchers(){
        if(self::$view == 'vouchers'){
            return true;
        }
        return false;
    }
    /* View States */
    
    /* Miscs */
    static function getNumberCountLoop($start = 1, $max = 24){
	global $controller;
	
	$formvars = $controller->GetAllControllerRequests('FORM');
	
	$arr_num = array();
	$selected_id = 0;
	if(self::getisEditPeriod()){
	  $selected_id = self::getEditPeriodDuration();
	}
    	for($i = $start; $i <= $max; $i++){
    		$arr_num[$i]['num'] = $i;
    		if($i == $selected_id){
    		   $arr_num[$i]['selected'] = ' selected="selected"';
    		}
    	}
    	
    	return $arr_num;
    }
    /* Miscs */
    
    static function getUserResellerID($user_id=0){
        global $zdbh;
        
        if(!$user_id){
            $currentuser = ctrl_users::GetUserDetail();
            $user_id = $currentuser['userid'];
        }
        
        if($user_id == 1){
            return $user_id;
        }
        
   		$sql = "SELECT ac_reseller_fk FROM x_accounts WHERE ac_id_pk=:uid";
        $bindArray = array('uid'=>$user_id);
        $zdbh->bindQuery($sql, $bindArray);
        $reseller_info = $zdbh->returnRow();
        
        if(isset($reseller_info['ac_reseller_fk'])){
            return $reseller_info['ac_reseller_fk'];
        }   
    }    
    

    /* Settings */
    static function getSettingCurrency(){
        global $zdbh;
        
        $currentuser = ctrl_users::GetUserDetail();
        
   		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
                    WHERE setting_name='currency' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $setting_info = $zdbh->returnRow();
        
        if(isset($setting_info['setting_value'])){
            return $setting_info['setting_value'];
        }
    
    }
    
    static function getDefaultSetting($setting_name){
        global $zdbh;
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
 		            WHERE setting_name=:setting_name AND reseller_ac_id_fk='-1'";
        $bindArray = array(':setting_name'=>$setting_name);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 

        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return '';
        }  
        
    }
    
    static function getEditCompanyName(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='company_name' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 

        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('company_name');
        }  
    }

    static function getEditEmailAddress(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='email_address' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('email_address');
        }  
    }

    static function getEditBillingURL(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
 		            WHERE setting_name='website_billing_url' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('website_billing_url');        
        }
    }

    static function getEditURLProtocol(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        $res = array();
        
        $sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
                    WHERE setting_name='url_protocol' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 

        if(!isset($edit_info['setting_value'])){
            $edit_info['setting_value'] = 'http://';
        }        
        array_push($res, array('label'=>'HTTP','protocol'=>'http://'));
        array_push($res, array('label'=>'HTTPS','protocol'=>'https://'));
        
        foreach($res as $itm_idx=>$proto){
            if($proto['protocol'] == $edit_info['setting_value']){
                $res[$itm_idx]['selected_yn'] = ' selected="selected"';
                break;
            }
        }
        return $res;
    }


    static function getEditLogsEnabled(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        $res = array();
        
        $sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
                    WHERE setting_name='logs_enabled_yn' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 

        if(!isset($edit_info['setting_value'])){
            $edit_info['setting_value'] = 1;
        }        
        array_push($res, array('label'=>'Yes','id'=>1));
        array_push($res, array('label'=>'No','id'=>0));
        
        foreach($res as $itm_idx=>$setting){
            if($setting['id'] == $edit_info['setting_value']){
                $res[$itm_idx]['selected_yn'] = ' selected="selected"';
                break;
            }
        }
        return $res;
    }

    static function getEditLogoPath(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='company_logo_path' 

 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('company_logo_path');        
        }
    }

    static function getEditCurrency(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();

        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='currency' 

 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('currency');        
        }  
    }

    static function getEditCountryCode(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();

        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
 		            WHERE setting_name='country_code' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(isset($edit_info['setting_value'])){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('country_code');
        }  
    }

    static function getEditBillingEnabled(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();

        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
 		            WHERE setting_name='billing_enabled_yn' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 

        if(isset($edit_info['setting_value']) && $edit_info['setting_value'] == 0){
            return ' checked="checked"';
        } else {
            return '';
        }  
    }

    static function getEditReCaptchaEnabled(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();

        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
 		            WHERE setting_name='recaptcha_disabled_yn' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 

        if(isset($edit_info['setting_value']) && $edit_info['setting_value'] == 1){
            return ' checked="checked"';
        } else {
            return '';
        }  
    }

    static function getEditReCaptchaPublicKey(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
 		            WHERE setting_name='recaptcha_public_key' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        }  
    }

    static function getEditReCaptchaPrivateKey(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings 
 		            WHERE setting_name='recaptcha_private_key' AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        }  
    }

    static function getEditInvoiceReminderDays(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='invoice_reminder_days' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('invoice_reminder_days');
        }  
    }

    static function getEditInvoiceReminderMessage(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='invoice_reminder_message' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('invoice_reminder_message');
        }  
    }

    static function getEditPendingInvoiceDeleteDays(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='pending_invoice_delete_days' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('pending_invoice_delete_days');
        }  
    }

    static function getEditRenewalDays(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='renewal_reminder_days' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('renewal_reminder_days');
        }  
    }

    static function getEditPaymentSuccessURL(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='payment_success_url' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('payment_success_url');
        }  
    }

    static function getEditPaymentFailureURL(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='payment_failure_url' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('payment_failure_url');
        }  
    }

    static function getEditPaymentCancelURL(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='payment_cancel_url' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('payment_cancel_url');
        }  
    }



    static function doUpdateSettings(){
        global $zdbh,$controller;
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
        //$allowed_ext = array('.gif','.png','.jpg','.jpeg');
        
        $setting_keys = array('company_name','email_address','company_logo_path','currency',
                                'invoice_reminder_days', 'country_code',
                               'pending_invoice_delete_days', 'renewal_reminder_days',
                               'payment_success_url','payment_failure_url','payment_cancel_url',
                               'recaptcha_public_key','recaptcha_private_key',
                               'billing_enabled_yn','website_billing_url',
                               'url_protocol','recaptcha_disabled_yn', 
                               'logs_enabled_yn');
        if(is_array($formvars)){
            /*
             * This will be implemented in a new version due to concerns of where to 
             * save company logo - removed file upload field from module.zpm
            if(is_file($_FILES['company_logo']['tmp_name'])){
                if ($_FILES['company_logo']['error'] > 0) {
                    self::$file_error = $_FILES['modulefile']['error'];    
                    return;
                }
                
                $file_ext = fs_director::GetFileExtension($_FILES['company_logo']['name']);
                if(!in_array($file_ext, $allowed_ext)){
                    self::$file_error = 'Company Logo is not a valid image file.';    
                    return;                    
                }
                
                
            }*/
            if(!isset($formvars['billing_enabled_yn'])){
                $formvars['billing_enabled_yn'] = 0;
            }

            if(!isset($formvars['recaptcha_disabled_yn'])){
                $formvars['recaptcha_disabled_yn'] = 0;
            }

            if(!isset($formvars['logs_enabled_yn'])){
                $formvars['logs_enabled_yn'] = 0;
            }
            

            foreach($formvars as $key=>$value){
            	$billing_enabled = 0;
            	if($formvars['billing_enabled_yn'] == 0){
            		$billing_enabled = 1;
            	}
            	if($key == 'billing_enabled_yn'){
            		$value = $billing_enabled;
            	}
            	
                if(in_array($key,$setting_keys)){
                 		$sql = "SELECT setting_id FROM ".self::$module_db.".x_settings 
					                WHERE setting_name=:setting_name AND reseller_ac_id_fk=:user_id";
                        $bindArray = array(':setting_name' => $key, ':user_id'=>$currentuser['userid']);
                        $zdbh->bindQuery($sql, $bindArray);
                        $setting_info = $zdbh->returnRow(); 
                        
                        if(!is_array($setting_info)){
                           $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_settings
                                                   (setting_name,setting_value,reseller_ac_id_fk
                                                   ) VALUES (:setting_name, :setting_value, :user_id)");
                           $sql->bindParam(':setting_name', $key);
                           $sql->bindParam(':setting_value', $value);
                           $sql->bindParam(':user_id', $currentuser['userid']);
                           $sql->execute();                                                      
                        } else {
                           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_settings
                                                   SET setting_value=:setting_value 
                                                   WHERE reseller_ac_id_fk=:user_id AND setting_id=:id");
                           $sql->bindParam(':id', $setting_info['setting_id']);
                           $sql->bindParam(':setting_value', $value);
                           $sql->bindParam(':user_id', $currentuser['userid']);
                           $sql->execute();                      
                        }
                }                
            }

            self::$ok = true;
            return true;        
        }
    
    }
    /* Settings */



    /* Email Settings */
    static function getEditEmailFormatList(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='email_format' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow();
        
        $arr_options = array(
        		     	array('format' => 'HTML', 'value' => 2),
        		     	array('format' => 'Text', 'value' => 1),
        		     );
        
        if(is_array($edit_info)){
            $value =  $edit_info['setting_value'];
        } else {
            $value = 1;
        }
        
        foreach($arr_options as $idx=>$option){
        	if($value == $option['value']){
        		$arr_options[$idx]['selected'] = ' selected="selected"';
        	}
        }
        return $arr_options;
    }

    /*static function getEditEmailFormat(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='email_format' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow();
        
        return $edit_info['setting_value'];
    }*/


    static function getEditEmailFormatHTML(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='email_format' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow();
        
        if($edit_info['setting_value'] == 2){
        	return true;
        }
        
        return false;
    }

    static function getEditEmailFormatText(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='email_format' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow();
        
        if($edit_info['setting_value'] == 1){
        	return true;
        }
        
        return false;
    }

     static function getEditRenewalReminderMessage(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='renewal_reminder_message' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('renewal_reminder_message');
        }  
    }

    static function getEditOrderMessage(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='order_message' 

 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('order_message');
        }  
    }

    static function getEditWelcomeMessage(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='welcome_message' 

 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('welcome_message');
        }  
    }

    static function getEditRenewalMessage(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $edit_info = array();
        
 		$sql = "SELECT setting_value FROM ".self::$module_db.".x_settings WHERE setting_name='renewal_message' 
 		            AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $edit_info = $zdbh->returnRow(); 
        
        if(is_array($edit_info)){
            return $edit_info['setting_value'];
        } else {
            return self::getDefaultSetting('renewal_message');
        }  
    }

    static function doUpdateEmailSettings(){
        global $zdbh,$controller;
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
        //$allowed_ext = array('.gif','.png','.jpg','.jpeg');

        runtime_csfr::Protect();
        
        $setting_keys = array('invoice_reminder_message','renewal_reminder_message', 
                                'welcome_message','renewal_message','order_message',
                               'email_format');

        if(is_array($formvars)){
            /*
             * This will be implemented in a future version due to concerns of where to 
             * save company logo - removed file upload field from module.zpm
            if(is_file($_FILES['company_logo']['tmp_name'])){
                if ($_FILES['company_logo']['error'] > 0) {
                    self::$file_error = $_FILES['modulefile']['error'];    
                    return;
                }
                
                $file_ext = fs_director::GetFileExtension($_FILES['company_logo']['name']);
                if(!in_array($file_ext, $allowed_ext)){
                    self::$file_error = 'Company Logo is not a valid image file.';    
                    return;                    
                }
                
                
            }*/
            

            foreach($formvars as $key=>$value){
                if(in_array($key,$setting_keys)){
                 		$sql = "SELECT setting_id FROM ".self::$module_db.".x_settings 
					                WHERE setting_name=:setting_name AND reseller_ac_id_fk=:user_id";
                        $bindArray = array(':setting_name' => $key, ':user_id'=>$currentuser['userid']);
                        $zdbh->bindQuery($sql, $bindArray);
                        $setting_info = $zdbh->returnRow(); 
                        
                        if(!is_array($setting_info)){
                           $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_settings
                                                   (setting_name,setting_value,reseller_ac_id_fk
                                                   ) VALUES (:setting_name, :setting_value, :user_id)");
                           $sql->bindParam(':setting_name', $key);
                           $sql->bindParam(':setting_value', $value);
                           $sql->bindParam(':user_id', $currentuser['userid']);
                           $sql->execute();                                                      
                        } else {
                           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_settings
                                                   SET setting_value=:setting_value 
                                                   WHERE reseller_ac_id_fk=:user_id AND setting_id=:id");
                           $sql->bindParam(':id', $setting_info['setting_id']);
                           $sql->bindParam(':setting_value', $value);
                           $sql->bindParam(':user_id', $currentuser['userid']);
                           $sql->execute();                      
                        }
                }                
            }

            self::$ok = true;
            return true;        
        }
    
    }
    /* Email Settings */


    /* Service Periods */
    static function getServicePeriods(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $periods = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_periods 
                                        WHERE reseller_ac_id_fk=:user_id 
                                        AND period_deleted_ts IS NULL 
                                        ORDER BY period_duration ASC;");
        $periods->bindParam(':user_id', $currentuser['userid']);
        $periods->execute();
        $res = array();
        if (!fs_director::CheckForEmptyValue($periods)) {
            while ($row = $periods->fetch()) {
               array_push($res, array('duration' => $row['period_duration'],
                                      'amount' => $row['default_amount'],
                                      'id' => $row['period_id']));  
            }
            return $res;
        } else {
            return false;
        }            
    }
    
    static function getisAddPeriod(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');
        if(!isset($urlvars['action']) || !isset($formvars['period_id']) || (isset($urlvars['action']) && ($urlvars['action'] == 'UpdatePeriod' || $urlvars['action'] == 'DeletePeriod') && self::$complete)){
            return true;
        }
        return false;
    }
    
    static function getisEditPeriod(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'EditPeriod') && isset($formvars['period_id']) || (isset($urlvars['action']) && $urlvars['action'] == 'UpdatePeriod' && !self::$complete)){
            return true;
        }
        return false;
    }
    
    /*static function getisDeletePeriod(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'ConfirmDeletePeriod')){
            return true;
        }
        return false;
    }*/
    
    static function doConfirmDeletePeriod(){
        return true;
    }
    

    static function getEditPeriodDuration(){
        global $controller,$zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $period_info = array();
        if(!isset($formvars['period_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT period_duration FROM ".self::$module_db.".x_periods 
					WHERE period_id=:period_id AND reseller_ac_id_fk=:user_id 
					AND period_deleted_ts IS NULL";
        $bindArray = array(':period_id' => (int)$formvars['period_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $period_info = $zdbh->returnRow(); 
        
        if(count($period_info)){
            return $period_info['period_duration'];
        }
       
    }

    static function getEditPeriodAmount(){
        global $controller,$zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $period_info = array();
        if(!isset($formvars['period_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT default_amount FROM ".self::$module_db.".x_periods 
					WHERE period_id=:period_id AND reseller_ac_id_fk=:user_id 
					AND period_deleted_ts IS NULL";
        $bindArray = array(':period_id' => (int)$formvars['period_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $period_info = $zdbh->returnRow(); 
        
        if(count($period_info)){
            return $period_info['default_amount'];
        }
       
    }
    
    static function getCurrentPeriodID(){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['period_id'])){
            return (int)$formvars['period_id'];         
        }
    }
    
    static function getCurrentServicePeriod(){
        global $controller,$zdbh;
        
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $period_info = array();
        
        if(!isset($formvars['period_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT period_duration FROM ".self::$module_db.".x_periods 
					WHERE period_id=:period_id AND reseller_ac_id_fk=:user_id 
					AND period_deleted_ts IS NULL";
        $bindArray = array(':period_id' => (int)$formvars['period_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $period_info = $zdbh->returnRow(); 
        
        if(!isset($period_info['period_duration'])){
            self::$error = true;
            return false;        
        }
        
        $period_duration = $period_info['period_duration'].' month';
        if($period_info['period_duration'] > 1){
            $period_duration .= 's';
        }
        return $period_duration;        
    }

    static function doCreatePeriod() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if((int) $formvars['period_duration'] && (float) $formvars['period_amount']){
            if (self::ExecuteAddPeriod((int) $formvars['period_duration'], (float) $formvars['period_amount'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['period_empty'] = true;
            return false;
        }        
        return;
    }
    
    static function doEditPeriod() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        //$currentuser = ctrl_users::GetUserDetail();
        //$period_info = array();
        
        if(! (int)$formvars['period_id']){
            self::$error = true;
            return false;
        }
    }
    
    static function doUpdatePeriod(){
        global $zdbh, $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $period_info = array();
        
        if((int) $formvars['period_duration'] && (float) $formvars['period_amount'] && (int) $formvars['period_id']){
		    $sql = "SELECT * FROM ".self::$module_db.".x_periods 
					    WHERE period_id=:period_id AND reseller_ac_id_fk=:user_id 
					    AND period_deleted_ts IS NULL";
            $bindArray = array(':period_id' => (int)$formvars['period_id'], ':user_id'=>$currentuser['userid']);
            $zdbh->bindQuery($sql, $bindArray);
            $period_info = $zdbh->returnRow(); 

            if(!count($period_info)){
                self::$error = true;
                return false;        
            }

            
            if (self::ExecuteUpdatePeriod((int) $formvars['period_duration'], (float) $formvars['period_amount'], (float) $formvars['period_id'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['period_empty'] = true;
            return false;
        }        
        return;
    }
        

    static function doDeletePeriod() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if((int) $formvars['x_item_id']){
            if (self::ExecuteDeletePeriod((int) $formvars['x_item_id'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error = true;
            return false;
        }        
        return;
    }
    
    static function updatePeriodsPackages($user_id=0){
        global $zdbh;
        if(!$user_id){
            $currentuser = ctrl_users::GetUserDetail();
        } else {
            $currentuser['userid'] = $user_id;
        }
        
        //fetch periods
        $periods = $zdbh->prepare("SELECT period_id,default_amount FROM ".self::$module_db.".x_periods 
                                        WHERE reseller_ac_id_fk=:user_id 
                                        AND period_deleted_ts IS NULL;");
        $periods->bindParam(':user_id', $currentuser['userid']);
        $periods->execute();
        if (!fs_director::CheckForEmptyValue($periods)){
            while ($row = $periods->fetch()) {
                //fetch packages
                $packages = $zdbh->prepare("SELECT zpx_package_id FROM ".self::$module_db.".x_packages 
                                                WHERE reseller_ac_id_fk=:user_id;");
                $packages->bindParam(':user_id', $currentuser['userid']);
                $packages->execute();
                if (!fs_director::CheckForEmptyValue($packages)) {
                    while ($row_p = $packages->fetch()) {
                       //insert non-existing packages
                 		$sql = "SELECT package_period_id FROM ".self::$module_db.".x_packages_periods 
					                WHERE period_id=:period_id AND zpx_package_id=:pkg_id";
                        $bindArray = array(':period_id' => $row['period_id'], ':pkg_id'=>$row_p['zpx_package_id']);
                        $zdbh->bindQuery($sql, $bindArray);
                        $package_period_info = $zdbh->returnRow(); 

                        if(!is_array($package_period_info)){
                           $sql = $zdbh->prepare("INSERT IGNORE INTO ".self::$module_db.".x_packages_periods
                                                   (zpx_package_id,period_id,package_amount
                                                   ) VALUES (
                                                   :pkg_id, :pid, :pkg_amt)");
                           $sql->bindParam(':pkg_id', $row_p['zpx_package_id']);
                           $sql->bindParam(':pid', $row['period_id']);
                           $sql->bindParam(':pkg_amt', $row['default_amount']);
                           $sql->execute();                                                      
                        }
                    }
                 }        
            }
         }
    }
    /* Service Periods */


    /* Hosting Packages */
    static function getServicePackages($user_id=0){
        global $zdbh;
        
        if(!$user_id){
            $currentuser = ctrl_users::GetUserDetail();
        } else {
            $currentuser['userid'] = $user_id;
        }

        $table_1 = self::$module_db.'.x_packages';
        $table_2 = self::$server_app.'_core.x_packages';
        
        /*
            Here, we are going to fetch existing billing packages, then we fetch existing zpx packages, 
            Finally, we create zpx packages that we don't presently have(doesn't exists).
        */
        
        $packages = $zdbh->prepare("SELECT pk_id_pk FROM $table_2 WHERE pk_reseller_fk=:user_id 
                                        AND pk_deleted_ts IS NULL;");
        $packages->bindParam(':user_id', $currentuser['userid']);
        $packages->execute();
        if (!fs_director::CheckForEmptyValue($packages)) {
            while ($row = $packages->fetch()) {
               $sql = $zdbh->prepare("INSERT IGNORE INTO $table_1 (zpx_package_id,reseller_ac_id_fk,enabled_yn
                                       ) VALUES (:pkg_id, :user_id, '1')");
               $sql->bindParam(':pkg_id', $row['pk_id_pk']);
               $sql->bindParam(':user_id', $currentuser['userid']);
               $sql->execute();
            }
            
            //update service periods
            self::updatePeriodsPackages($user_id);
         }        

        $packages = $zdbh->prepare("SELECT *,pk_name_vc FROM $table_1 INNER JOIN $table_2 
                                    ON $table_2.pk_id_pk=$table_1.zpx_package_id 
                                    WHERE $table_1.reseller_ac_id_fk=:user_id AND $table_2.pk_reseller_fk=:user_id
                                    AND $table_2.pk_deleted_ts IS NULL
                                     ORDER BY $table_2.pk_id_pk ASC;");
        $packages->bindParam(':user_id', $currentuser['userid']);
        $packages->execute();
        $res = array();
        if (!fs_director::CheckForEmptyValue($packages)) {
            while ($row = $packages->fetch()) {
                $enabled_yn = 'checked="checked"';
                $free_yn = '';
                if(!$row['enabled_yn']){
                    $enabled_yn = '';
                }
                if($row['free_package_yn']){
                    $free_yn = 'checked="checked"';
                }
                
               array_push($res, array('name' => $row['pk_name_vc'],
                                      'desc' => $row['package_desc'],
                                      'id' => $row['zpx_package_id'],
                                      'enabled_yn' => $enabled_yn,
                                      'free_yn' => $free_yn));  
            }
            return $res;
        } else {
            return false;
        }            
    }

    static function doUpdatePackages(){
        global $zdbh,$controller;
        
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['pkg_desc'])){
            if (self::ExecuteUpdatePackages($formvars)) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error = true;
            return false;
        }        
        return;
    }
    
    static function doLoadPackagePeriods(){
       global $zdbh,$controller;
       
       $table_1 = self::$module_db.'.x_packages_periods';
       $table_2 = self::$module_db.'.x_periods';

       $ret_html = '';
       $urlvars = $controller->GetAllControllerRequests('URL');
       if(!isset($urlvars['pid'])){
        $ret_html = 'An error occured while fetching package service periods.';
       } else {
         //service periods
         $sql = $zdbh->prepare("SELECT period_duration,default_amount,package_amount,
                                        $table_2.period_id,package_period_id FROM $table_1 RIGHT JOIN 
                                        $table_2 ON $table_2.period_id=$table_1.period_id 
                                        WHERE $table_1.zpx_package_id=:package_id 
                                        AND $table_2.period_deleted_ts IS NULL;");
         $sql->bindParam(':package_id', $urlvars['pid']);
         $sql->execute();
         $package_periods = $sql->fetchAll();

         if (is_array($package_periods) && count($package_periods) > 0){
            //fetch package name
       		$sql_i = "SELECT pk_name_vc FROM ".self::$server_app."_core.x_packages WHERE pk_id_pk=:pkg_id 
                   		AND pk_deleted_ts IS NULL";
            $bindArray = array(':pkg_id' => $urlvars['pid']);
            $zdbh->bindQuery($sql_i, $bindArray);
            $package_info = $zdbh->returnRow();
            
            
            $ret_html = '<div align="left" style="margin-bottom: 5px;"><strong>'.ui_language::translate("Package").':</strong> '.$package_info['pk_name_vc'].'</div>

                            <table class="table table-stripped">';
            $ret_html .= '<tr><td class="col-sm-3"><strong>Service Period</strong></td>';
            $ret_html .= '<td><strong>Amount</strong></td></tr>';

            foreach($package_periods as $row_p) {
                $period_name = $row_p['period_duration'].' month';
                $period_amt = $row_p['package_amount'];
                if(!$period_amt){
                    $period_amt = $row_p['default_amount'];
                }
                if($row_p['period_duration'] > 1){
                    $period_name .= 's';
                }
                $ret_html .= '<tr><td align="left">'.$period_name.'</td>';
                $ret_html .= '<td align="left" class="vertical-baseline"><input name="period_amount['.$row_p['package_period_id'].']" type="text" id="period_amount_'.$row_p['period_id'].'" value="'.$period_amt.'"" size="5" /> '.self::getSettingCurrency().'</td></tr>';
            }

            $ret_html .= '<!-- <tr><td height="40" valign="bottom" colspan="2" align="center" style="text-align: center;">

                            <button type="submit" value="1" name="btn_submit" style="width:50px;"><img src="modules/xbilling/assets/icon_ok.png" /></button>&nbsp;&nbsp;<button type="button" value="1" name="btn_cancel" style="width:50px;" onclick="_cancel_edit();"><img src="modules/xbilling/assets/icon_cross.png" /></button>

                          </td></tr> -->';
            $ret_html .= '</table>

                            <input type="hidden" id="item_id" name="package_id" value="'.$urlvars['pid'].'">';
         } else {
            $ret_html = '<br />
                            There are no service plans to display, please add service plan first.';
         }        
       
       }
       echo($ret_html);
       exit;
    }

    static function doUpdatePackagePeriods(){
        global $zdbh,$controller;
        //$currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(!isset($formvars['package_id'])){
            echo('<div style="color: #f00;">'.ui_language::translate("An authentication error occured while processing your request, please try again.").'</div>');
            exit;
        }       
        
        if(is_array($formvars['period_amount'])){
            foreach($formvars['period_amount'] as $pid=>$amt){
               $amt = (float) $amt;
               $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_packages_periods 
                                       SET package_amount=:pkg_amt WHERE zpx_package_id=:pkg_id 
                                       AND package_period_id=:pkg_period_id");
               $sql->bindParam(':pkg_id', $formvars['package_id']);
               $sql->bindParam(':pkg_period_id', $pid);
               $sql->bindParam(':pkg_amt', $amt);
               $sql->execute();                
            }
            echo('<div style="color: #4e9a06;">'.ui_language::translate("Package service plan updated successfully.").'</div>');
            exit;        
        }
    }  
    /* Hosting Packages */

    /* Payment Options */
    static function getPaymentOptions(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        
        $reseller_id = self::getUserResellerID();
        
        $sql = "SELECT * FROM ".self::$module_db.".x_payment_options WHERE reseller_ac_id_fk=:user_id 
                    AND option_deleted_ts IS NULL";
        //$sql = "SELECT * FROM ".self::$module_db.".x_payment_options";
        if(self::getisAddMethod()){
            //$option_id = self::getCurrentPaymentOptionID();
            $pyt_options = $zdbh->prepare("SELECT payment_option_id FROM 
                                        ".self::$module_db.".x_payment_methods 
                                        WHERE reseller_ac_id_fk=:uid
                                        ;");
            $pyt_options->bindParam(':uid', $currentuser['userid']);
            $pyt_options->execute();
            if (!fs_director::CheckForEmptyValue($pyt_options)) {
                $payment_options = array();
                while ($row = $pyt_options->fetch()) {
                   array_push($payment_options, $row['payment_option_id']);
                }
                
                if(count($payment_options) > 0){
                    $payment_options_s = implode(',', $payment_options);
                    $payment_options = $payment_options_s;
                }
                if(!is_array($payment_options)){
                    $sql .= " AND payment_option_id NOT IN(".$payment_options.")";
                }
            }
        }
        $sql .= " ORDER BY payment_option_id ASC;";
        //echo($sql);
        //exit;
        
        $methods = $zdbh->prepare($sql);
        $methods->bindParam(':user_id', $reseller_id);
        /*if(self::getisAddMethod() && isset($payment_options) && !is_array($payment_options)){
            $methods->bindParam(':option_ids', $payment_options);        
        }*/
        $methods->execute();
        $res = array();
        $payment_option_id = 0;
        if (!fs_director::CheckForEmptyValue($methods)) {
            $selected_method_id = self::getCurrentPaymentMethodID();
            if($selected_method_id){
               $option = $zdbh->prepare("SELECT payment_option_id FROM 
                                        ".self::$module_db.".x_payment_methods 
                                         WHERE method_id=:id AND reseller_ac_id_fk=:user_id 
                                         AND method_deleted_ts IS NULL;");
               $option->bindParam(':id', $selected_method_id);
               $option->bindParam(':user_id', $currentuser['userid']);
               $option->execute();
               
               $row = $option->fetch();
               
               if(is_array($row)){
                $payment_option_id = $row['payment_option_id'];
               }
                
            }

            while ($row = $methods->fetch()) {
                $field_names_s = '';
                $enabled_yn = ($row['enabled_yn'] > 0) ? 'YES':'NO';
               //get selected Payment Option
                $selected = ($payment_option_id == $row['payment_option_id']) ? ' selected="selected"':'';
                $field_names = self::getPaymentOptionFields($row['payment_option_id']);
                if(is_array($field_names)){
                    foreach($field_names as $field){
                        $field_names_s .= $field['name'].',';
                    }
                    $field_names_s = substr($field_names_s, 0, -1);
                }
                $field_names = $field_names_s;
                
               array_push($res, array('name' => $row['payment_option_name'],
                                      'enabled_yn' => $enabled_yn,
                                      'field_names' => $field_names,
                                      'selected_yn' => $selected,
                                      'id' => $row['payment_option_id']));  
            }

            if(self::getisAddMethod() && !count($res)){
                array_push($res, array('name' => ui_language::translate("There are no additional payment option to add."),
                                      'enabled_yn' => 0, 'field_names' => '',
                                      'selected_yn' => '', 'id' => 0));
            }

            return $res;
        } else {
            return false;
        }            
    }
    
    static function getPaymentOptionFields($payment_option_id){
        global $zdbh;
        if($payment_option_id){
            $fields = $zdbh->prepare("SELECT field_name,field_label FROM 
                                        ".self::$module_db.".x_payment_option_fields 
                                        WHERE payment_option_id=:id ORDER BY field_name ASC;");
            $fields->bindParam(':id', $payment_option_id);
            $fields->execute();
            $res = array();
            if (!fs_director::CheckForEmptyValue($fields)) {
                while ($row = $fields->fetch()) {
                   array_push($res, array('label' => $row['field_label'],'name' => $row['field_name']));
                }
                return $res;
            } else {
                return false;
            }            
        
        }
    }
    
    /*static function getJSApp(){
        
        return self::$server_vars['js_app'];        
    }*/
    
    static function getPaymentOptionFieldCount(){
        global $zdbh, $controller;
        
        $total_fields = 1;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['option_id'])){
            $fields = $zdbh->prepare("SELECT COUNT(*) AS total_fields FROM 
                                        ".self::$module_db.".x_payment_option_fields 
                                        WHERE payment_option_id=:id ORDER BY field_name ASC;");
            $fields->bindParam(':id', $formvars['option_id']);
            $fields->execute();
            $row = $fields->fetch();
            
            $total_fields = $row['total_fields'];
        }
        
        return $total_fields;        
    }
    
    static function getisAddPaymentOption(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');
        if(!isset($urlvars['action']) || !isset($formvars['option_id']) || (isset($urlvars['action']) && ($urlvars['action'] == 'UpdatePaymentOption' || $urlvars['action'] == 'DeletePaymentOption') && self::$complete)){
            return true;
        }
        return false;
    }
    
    static function getisEditPaymentOption(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'EditPaymentOption') && isset($formvars['option_id']) || (isset($urlvars['action']) && $urlvars['action'] == 'UpdatePaymentOption' && !self::$complete)){
            return true;
        }
        return false;
    }
    
    /*static function getisDeletePaymentOption(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'ConfirmDeletePaymentOption')){
            return true;
        }
        return false;
    }
    
    static function doConfirmDeletePaymentOption(){
        return true;
    }*/
    

    static function getEditPaymentOptionHTML(){
        global $controller,$zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        $urlvars = $controller->GetAllControllerRequests('URL');

        $currentuser = ctrl_users::GetUserDetail();
        if(isset($urlvars['action']) && $urlvars['action'] == 'UpdatePaymentOption' && self::$complete){
            if(isset($formvars['payment_option_form_html'])){
               unset($formvars['payment_option_form_html']);
            }
        }
        $method_info = array();
        if(!isset($formvars['option_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT payment_option_form_html FROM ".self::$module_db.".x_payment_options 
					WHERE payment_option_id=:option_id AND reseller_ac_id_fk=:user_id 
					AND option_deleted_ts IS NULL";
        $bindArray = array(':option_id' => (int)$formvars['option_id'], 
                                ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $method_info = $zdbh->returnRow(); 
        
        if(count($method_info)){
            return $method_info['payment_option_form_html'];
        }
       
    }

    static function getEditPaymentOptionEnabled(){
        global $controller,$zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $method_info = array();
        if(!isset($formvars['option_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT enabled_yn FROM ".self::$module_db.".x_payment_options 
					WHERE payment_option_id=:option_id AND reseller_ac_id_fk=:user_id 
					AND option_deleted_ts IS NULL";
        $bindArray = array(':option_id' => (int)$formvars['option_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $option_info = $zdbh->returnRow(); 
        
        if(count($option_info)){
            $enabled_yn = ($option_info['enabled_yn'] > 0) ? ' checked="checked"':'';
            return $enabled_yn;
        }  
    }
    
    static function getEditPaymentOptionFields(){
        $fields = self::getPaymentOptionFields(self::getCurrentPaymentOptionID());
        $html = '';
        $x = 1;
        if(is_array($fields)){
            foreach($fields as $field){
                $icon_remove_field = '';
                if($x > 1){
                    $icon_remove_field = '<img id="remove_field_'.$x.'" src="'.self::getModuleDir().'/assets/icon_remove_field.png" hspace="5" onclick="remove_payment_field(\'payment_field_'.$x.'\');" style="cursor: pointer;" alt="remove field"  title="remove field"  />';
                }
                $html .= '<tr id="payment_field_'.$x.'">
                                <th nowrap="nowrap">'.ui_language::translate("Label").':</th>
                                <td><input name="field_labels['.$x.']" type="text" value="'.$field['label'].'" /></td>
                                <th nowrap="nowrap">'.ui_language::translate("Field Name").':</th>
                                <td><input name="field_names['.$x.']" type="text" value="'.$field['name'].'" /></td>
                                <td><img id="add_field_'.$x.'" src="'.self::getModuleDir().'/assets/icon_add_field.png" hspace="5" onclick="add_payment_field(\'payment_field_'.$x.'\');" style="cursor: pointer;" alt="add new field"  title="add new field" />'.$icon_remove_field.'</td>
                            </tr>';
                $x++;
            }
        }
        
        return $html;
    }

    static function getCurrentPaymentOptionID(){
        global $controller, $zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        //let's handle this differently if it's a request from Edit Payment Method screen
        if(isset($formvars['method_id'])){
            $currentuser = ctrl_users::GetUserDetail();
            $options = $zdbh->prepare("SELECT payment_option_id FROM 
                                        ".self::$module_db.".x_payment_methods 
                                        WHERE reseller_ac_id_fk=:user_id 
                                        AND method_id=:method_id 
                                        AND method_deleted_ts IS NULL LIMIT 1;");
            $options->bindParam(':user_id', $currentuser['userid']);
            $options->bindParam(':method_id', $formvars['method_id']);
            $options->execute();
            $payment_option = $options->fetch();
            
            return $payment_option['payment_option_id'];
        }
        
        if(isset($formvars['option_id'])){
            return (int)$formvars['option_id'];         
        }
    }
    
    static function getCurrentPaymentOption($option_id=0){
        global $controller,$zdbh;
        
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $method_info = array();
        
        if($option_id){
            $formvars['option_id'] = $option_id;
        }
        if(!isset($formvars['option_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT payment_option_name FROM ".self::$module_db.".x_payment_options  					WHERE payment_option_id=:option_id AND reseller_ac_id_fk=:user_id 
             		AND option_deleted_ts IS NULL";
        $bindArray = array(':option_id' => (int)$formvars['option_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $method_info = $zdbh->returnRow(); 
        
        if(!isset($method_info['payment_option_name'])){
            self::$error = true;
            return false;        
        }
        
        return $method_info['payment_option_name'];        
    }

    static function doCreatePaymentOption() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
                
        if($formvars['option_name'] && isset($formvars['field_labels']) && isset($formvars['field_names'])){
            if(!isset($formvars['enabled_yn'])){
                $formvars['enabled_yn'] = 0;
            }

            if(!isset($formvars['payment_option_form_html'])){
                $formvars['payment_option_form_html'] = '';
            }
            
            //validate field labels and names
            foreach($formvars['field_names'] as $fld_idx=>$field){
                $label = trim(filter_var($formvars['field_labels'][$fld_idx], FILTER_SANITIZE_STRING));
                $field = trim(str_replace(' ','_',filter_var($field, FILTER_SANITIZE_STRING)));
                if(!$field || !$label){
                    self::$error['option_field_empty'] = true;
                    return false;                    
                }
            }
            
            if (self::ExecuteAddPaymentOption($formvars['option_name'], $formvars['field_labels'], $formvars['field_names'], $formvars['payment_option_form_html'], $formvars['enabled_yn'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['option_empty'] = true;
            return false;
        }        
        return;
    }
    
    static function doEditPaymentOption() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        //$currentuser = ctrl_users::GetUserDetail();
        //$period_info = array();
        
        if(! (int)$formvars['option_id']){
            self::$error = true;
            return false;
        }
    }
    
    static function doUpdatePaymentOption(){
        global $zdbh, $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $method_info = array();
        
        if($formvars['option_name'] && isset($formvars['field_labels']) && isset($formvars['field_names'])){
            if(!isset($formvars['enabled_yn'])){
                $formvars['enabled_yn'] = 0;
            }
            
            if(!isset($formvars['payment_option_form_html'])){
                $formvars['payment_option_form_html'] = '';
            }

            //validate field labels and names
            foreach($formvars['field_names'] as $fld_idx=>$field){
                $field = trim(str_replace(' ','_',filter_var($field, FILTER_SANITIZE_STRING)));
                $label = trim(filter_var($formvars['field_labels'][$fld_idx], FILTER_SANITIZE_STRING));
                if(!$field || !$label){
                    self::$error['option_field_empty'] = true;
                    return false;                    
                }
            }

		    $sql = "SELECT * FROM ".self::$module_db.".x_payment_options 
					    WHERE payment_option_id=:option_id AND reseller_ac_id_fk=:user_id 
					    AND option_deleted_ts IS NULL";
            $bindArray = array(':option_id' => (int)$formvars['option_id'], ':user_id'=>$currentuser['userid']);
            $zdbh->bindQuery($sql, $bindArray);
            $option_info = $zdbh->returnRow(); 

            if(!count($option_info)){
                self::$error = true;
                return false;        
            }

            

            if (self::ExecuteUpdatePaymentOption($formvars['option_id'], $formvars['option_name'], $formvars['field_labels'], $formvars['field_names'], $formvars['payment_option_form_html'], $formvars['enabled_yn'])){
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['option_empty'] = true;
            return false;
        }        
        return;
    }
        

    static function doDeletePaymentOption() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if((int) $formvars['x_item_id']){
            if (self::ExecuteDeletePaymentOption((int) $formvars['x_item_id'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error = true;
            return false;
        }        
        return;
    }
    
    static function doLoadPaymentOptionFields($payment_option_id=0){
        global $zdbh,$controller;
        
        $currentuser = ctrl_users::GetUserDetail();
        //$user_id = $currentuser['userid'];        
        
        $urlvars = $controller->GetAllControllerRequests('URL');

        if(!$payment_option_id){
            $payment_option_id = $urlvars['id'];
        }
        
        $html = '';
        if($payment_option_id){
            $fields = $zdbh->prepare("SELECT field_name,field_label FROM 
                                        ".self::$module_db.".x_payment_option_fields 
                                        WHERE payment_option_id=:id ORDER BY field_name ASC;");
            $fields->bindParam(':id', $payment_option_id);
            $fields->execute();
            $res = array();
            if (!fs_director::CheckForEmptyValue($fields)) {
                while ($row = $fields->fetch()) {
             		$sql = "SELECT field_value FROM ".self::$module_db.".x_payment_option_values
                				WHERE payment_option_id=:id AND reseller_ac_id_fk=:user_id 
                				AND field_name=:name";
                    $bindArray = array(':id' => $payment_option_id, 
                                        ':user_id'=>$currentuser['userid'], 
                                        ':name'=>$row['field_name']);
                    $zdbh->bindQuery($sql, $bindArray);
                    $field_info = $zdbh->returnRow(); 
                    
                   //array_push($res, array('label' => $row['field_label'],'name' => $row['field_name']));  
                   $html .= '<tr><th nowrap="nowrap" class="col-sm-3">'.$row['field_label'].'</th>
                                            <td><input name="'.$row['field_name'].'" type="text" id="'.$row['field_name'].'" value="'.$field_info['field_value'].'" class="required" /></td>
                                        </tr>';
                }
            }            
        
        }
        echo $html;
        exit;
    }
    
    static function getPaymentOptionFieldValue($user_id, $option_id, $field_name){
        global $zdbh;
        
        if($user_id && $option_id && $field_name){
      		$sql = "SELECT field_value FROM ".self::$module_db.".x_payment_option_values
         				WHERE payment_option_id=:id AND reseller_ac_id_fk=:user_id 
           				AND field_name=:name";
            $bindArray = array(':id' => $option_id, ':user_id'=>$user_id, ':name'=>$field_name);
            $zdbh->bindQuery($sql, $bindArray);
            $field_info = $zdbh->returnRow();
            if(isset($field_info['field_value'])){
                return $field_info['field_value'];
            }
        }
        
    }
        
    /* Payment Options */


    /* Payment Methods */
    static function getPaymentMethods(){
        global $zdbh;

        $reseller_id = self::getUserResellerID();
        
        $currentuser = ctrl_users::GetUserDetail();
        //fetch payment options
        $options = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_payment_options 
                                    WHERE reseller_ac_id_fk=:user_id 
                                    AND option_deleted_ts IS NULL AND enabled_yn = '1' 
                                    ORDER BY payment_option_id ASC;");
        $options->bindParam(':user_id', $reseller_id);
        $options->execute();

        /*$methods = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_payment_methods 
                                    WHERE reseller_ac_id_fk=:user_id 
                                    AND method_deleted_ts IS NULL 
                                    ORDER BY method_id ASC;");
        $methods->bindParam(':user_id', $currentuser['userid']);
        $methods->execute();*/
        
        $res = array();
        if (!fs_director::CheckForEmptyValue($options)) {
            while ($row = $options->fetch()) {            
                 /*$option = $zdbh->prepare("SELECT payment_option_name 
                                            FROM ".self::$module_db.".x_payment_options 
                                            WHERE payment_option_id=:option_id AND 
                                            reseller_ac_id_fk=:user_id 
                                            AND option_deleted_ts IS NULL");
                 $option->bindParam(':user_id', $currentuser['userid']);
                 $option->bindParam(':option_id', $row['payment_option_id']);
                 $option->execute();
                 $row_option = $option->fetch();
                
                 $payment_option_name = $row_option['payment_option_name'];*/
                $sql = "SELECT method_id,enabled_yn FROM ".self::$module_db.".x_payment_methods 
                                            WHERE reseller_ac_id_fk=:user_id 
                                            AND payment_option_id=:opt_id 
                                            AND method_deleted_ts IS NULL 
                                            ORDER BY method_id ASC LIMIT 1;";
                $bindArray = array(':user_id'=>$currentuser['userid'],':opt_id'=>$row['payment_option_id']);
                $zdbh->bindQuery($sql, $bindArray);
                $method_info = $zdbh->returnRow();
                
                if(is_array($method_info)){
                    $enabled_yn = ($method_info['enabled_yn'] > 0) ? 'YES':'NO';
                    
                    array_push($res, array('method_name' => $row['payment_option_name'],
                                          'enabled_yn' => $enabled_yn,
                                          'id' => $method_info['method_id']));                    
                }                                
            }
            return $res;
        } else {
            return false;
        }            
    }

    /*static function getPaymentMethodOptions(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $methods = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_payment_options 
                                    WHERE reseller_ac_id_fk=:user_id ORDER BY payment_option_id ASC;");
        $methods->bindParam(':user_id', $currentuser['userid']);
        $methods->execute();
        $res = array();
        if (!fs_director::CheckForEmptyValue($methods)) {
            while ($row = $methods->fetch()) {
                $field_names_s = '';
                $enabled_yn = ($row['enabled_yn'] > 0) ? 'YES':'NO';
                $field_names = self::getPaymentOptionFields($row['payment_option_id']);
                if(is_array($field_names)){
                    foreach($field_names as $field){
                        $field_names_s .= $field['name'].',';
                    }
                    $field_names_s = substr($field_names_s, 0, -1);
                }
                $field_names = $field_names_s;
               array_push($res, array('name' => $row['payment_option_name'],
                                      'enabled_yn' => $enabled_yn,
                                      'field_names' => $field_names,
                                      'id' => $row['payment_option_id']));  
            }
            return $res;
        } else {
            return false;
        }            
    }*/
    
    static function getisAddMethod(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');
        if((self::getViewPaymentMethods()) && (!isset($urlvars['action']) || !isset($formvars['method_id']) || (isset($urlvars['action']) && ($urlvars['action'] == 'UpdatePaymentMethod' || $urlvars['action'] == 'DeletePaymentMethod') && self::$complete))){
            return true;
        }
        return false;
    }
    
    static function getisEditMethod(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'EditPaymentMethod') && isset($formvars['method_id']) || (isset($urlvars['action']) && $urlvars['action'] == 'UpdatePaymentMethod' && !self::$complete)){
            return true;
        }
        return false;
    }
    
    static function getisDeleteMethod(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'ConfirmDeletePaymentMethod')){
            return true;
        }
        return false;
    }
    
    static function doConfirmDeletePaymentMethod(){
        return true;
    }
    

    /*static function getEditPaymentMethodHTML(){
        global $controller,$zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $method_info = array();
        if(!isset($formvars['method_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT method_form_html FROM ".self::$module_db.".x_payment_methods 
					WHERE method_id=:method_id AND reseller_ac_id_fk=:user_id";
        $bindArray = array(':method_id' => (int)$formvars['method_id'], 'user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $method_info = $zdbh->returnRow(); 
        
        if(count($method_info)){
            return $method_info['method_form_html'];
        }
       
    }*/

    static function getEditPaymentMethodEnabled(){
        global $controller,$zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $method_info = array();
        if(!isset($formvars['method_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT enabled_yn FROM ".self::$module_db.".x_payment_methods 

					WHERE method_id=:method_id AND reseller_ac_id_fk=:user_id 
					AND method_deleted_ts IS NULL";
        $bindArray = array(':method_id' => (int)$formvars['method_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $method_info = $zdbh->returnRow(); 
        
        if(count($method_info)){
            $enabled_yn = ($method_info['enabled_yn'] > 0) ? ' checked="checked"':'';
            return $enabled_yn;
        }
       
    }

    static function getCurrentPaymentMethodID(){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['method_id'])){
            return (int)$formvars['method_id'];         
        }
    }
    
    static function getCurrentPaymentMethod(){
        global $controller,$zdbh;
        
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $method_info = array();
        
        if(!isset($formvars['method_id'])){
            self::$error = true;
            return false;
        }
        
        $reseller_id = self::getUserResellerID();
        
 		$sql = "SELECT payment_option_name FROM ".self::$module_db.".x_payment_options 
 		            INNER JOIN ".self::$module_db.".x_payment_methods ON 
 		            ".self::$module_db.".x_payment_methods.payment_option_id=".self::$module_db.".x_payment_options.payment_option_id
					WHERE method_id=:method_id AND 
					".self::$module_db.".x_payment_options.reseller_ac_id_fk=:user_id 
					AND option_deleted_ts IS NULL AND method_deleted_ts IS NULL";
        $bindArray = array(':method_id' => (int)$formvars['method_id'], ':user_id'=>$reseller_id);
        $zdbh->bindQuery($sql, $bindArray);
        $method_info = $zdbh->returnRow(); 
        
        if(!isset($method_info['payment_option_name'])){
            self::$error = true;
            return false;        
        }
        
        return $method_info['payment_option_name'];
    }

    static function doCreatePaymentMethod() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if($formvars['payment_option_id']){
            if(!isset($formvars['enabled_yn'])){
                $formvars['enabled_yn'] = 0;
            }
            
            //making sure we have more than 6 form items submitted
            if(count($formvars) < 6){
                self::$error['method_empty'] = true;
                return false;                
            }
            if (self::ExecuteAddPaymentMethod($formvars)){
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['method_empty'] = true;
            return false;
        }        
        return;
    }
    
    static function doEditPaymentMethod() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        //$currentuser = ctrl_users::GetUserDetail();
        //$period_info = array();
        
        if(!(int)$formvars['method_id']){
            self::$error = true;
            return false;
        }
    }
    
    static function doUpdatePaymentMethod(){
        global $zdbh, $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        $currentuser = ctrl_users::GetUserDetail();
        $method_info = array();
        
        if($formvars['payment_option_id'] && $formvars['method_id']){
		    $sql = "SELECT * FROM ".self::$module_db.".x_payment_methods
		              WHERE method_id=:method_id AND reseller_ac_id_fk=:user_id 
		              AND method_deleted_ts IS NULL";
            $bindArray = array(':method_id' => (int)$formvars['method_id'], ':user_id'=>$currentuser['userid']);
            $zdbh->bindQuery($sql, $bindArray);
            $method_info = $zdbh->returnRow(); 

            if(!count($method_info)){
                self::$error = true;
                return false;        
            }

            
            if(!isset($formvars['enabled_yn'])){
                $formvars['enabled_yn'] = 0;
            }
            
            if(count($formvars) < 6){
                self::$error['method_empty'] = true;
                return false;                
            }
            
            if (self::ExecuteUpdatePaymentMethod($formvars)){
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['method_empty'] = true;
            return false;
        }        
        return;
    }
        

    static function doDeletePaymentMethod() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if((int) $formvars['method_id']){
            if (self::ExecuteDeletePaymentMethod((int) $formvars['method_id'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error = true;
            return false;
        }        
        return;
    }
    /* Payment Methods */

    /* Orders / Invoices */
    static function getOrdersInvoices(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        
        $orders = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_invoices_orders 
                                    INNER JOIN ".self::$module_db.".x_orders ON ".self::$module_db.".x_orders.order_id=".self::$module_db.".x_invoices_orders.order_id 
                                    INNER JOIN ".self::$module_db.".x_invoices ON 
                                    ".self::$module_db.".x_invoices.invoice_id=".self::$module_db.".x_invoices_orders.invoice_id 
                                    WHERE ".self::$module_db.".x_invoices.reseller_ac_id_fk=:user_id 
                                    AND ".self::$module_db.".x_orders.reseller_ac_id_fk=:user_id 
                                    AND invoice_deleted_ts IS NULL AND order_deleted_ts IS NULL 
                                    GROUP BY ".self::$module_db.".x_invoices.invoice_id 
                                    ORDER BY ".self::$module_db.".x_invoices.invoice_id DESC;");
        $orders->bindParam(':user_id', $currentuser['userid']);
        $orders->execute();
        $res = array();

        if (!fs_director::CheckForEmptyValue($orders)) {
            while ($row = $orders->fetch()) {
               //select order user
              $order_user = 'N/A';
              $users = $zdbh->prepare("SELECT ac_user_vc FROM ".self::$server_app."_core.x_accounts 
                                       WHERE ac_id_pk=:order_uid AND ac_deleted_ts IS NULL;");
              $users->bindParam(':order_uid', $row['ac_id_fk']);
              $users->execute();
              $user_row = $users->fetch();
              if(isset($user_row['ac_user_vc'])){
                  $order_user = $user_row['ac_user_vc'];
              }
              
              $button_html = '';               
              $order_status = ($row['invoice_status'] == 1) ? 'Paid':'Pending';

                //view
                $button_html = '<button class="btn btn-info btn-mini" type="submit" id="button" name="inView_'.$row['invoice_id'].'" id="inView_'.$row['invoice_id'].'" value="inView_'.$row['invoice_id'].'" onclick="_change_action(\'frmOrders\',\'ViewOrder\', \''.$row['invoice_id'].'\');">'.ui_language::translate("View").'</button>&nbsp;';

              if(!$row['invoice_status']){
                //re-send
                $button_html .= '<button class="btn btn-default btn-mini resend-invoice" type="button" id="button" name="inEdit_'.$row['invoice_id'].'" id="inEdit_'.$row['invoice_id'].'" value="inEdit_'.$row['invoice_id'].'" data-id="'.$row['invoice_id'].'">'.ui_language::translate("Resend").'</button>&nbsp;';
                //edit
                $button_html .= '<button class="btn btn-warning btn-mini" type="submit" id="button" name="inEdit_'.$row['invoice_id'].'" id="inEdit_'.$row['invoice_id'].'" value="inEdit_'.$row['invoice_id'].'" onclick="_change_action(\'frmOrders\',\'EditOrder\', \''.$row['invoice_id'].'\');">'.ui_language::translate("Edit").'</button>';
              }
              
              $invoice_amount = self::getOrderInfoAmountDue($row['invoice_id']);
              $img_voucher = '';
              
              if($row['invoice_voucher_id'] > 0){
              	$img_voucher = '<img src="'.self::getModuleDir().'/assets/icon_voucher.png'.'" />';
              }
              
              //var_dump($invoice_amount);
              

              array_push($res, array('order_no' => $row['invoice_reference'],
                                      'icon' => $img_voucher,
                                      'order_user' => $order_user,
                                      'order_date' => date("Y-m-d H:i", strtotime($row['invoice_dated'])),
                                      'order_amount' => number_format($invoice_amount,2),
                                      'order_desc' => $row['order_desc'],
                                      'order_status' => $order_status,
                                      'button' => $button_html));  
            }

            return $res;
        } else {
            return false;
        }            
    }
    
    static function getisViewOrder(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'ViewOrder') && isset($formvars['order_id'])){
            return true;
        }
        return false;
    }

    static function getisEditOrder(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'EditOrder') && isset($formvars['order_id']) || (isset($urlvars['action']) && $urlvars['action'] == 'UpdateOrder' && !self::$complete)){
            return true;
        }
        return false;
    }
    
    static function getCurrentOrderID(){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id'])){
            return (int)$formvars['order_id'];         
        }
    }
    
    
    static function getOrderInfoReference(){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id'])){
            return self::getOrderInfo($formvars['order_id'], 'invoice_reference',self::$module_db.'.x_invoices');
        }
    }

    static function getOrderInfoDate(){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id'])){
            return date("Y-m-d H:i",strtotime(self::getOrderInfo($formvars['order_id'], 'invoice_dated',self::$module_db.'.x_invoices')));
        }
    }
    
    static function getOrderInfoUser(){
        global $zdbh,$controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id'])){
            $order_uid = self::getOrderInfo($formvars['order_id'], 'ac_id_fk',self::$module_db.'.x_invoices');
            if($order_uid){
              $users = $zdbh->prepare("SELECT ac_user_vc FROM ".self::$server_app."_core.x_accounts 
                                       WHERE ac_id_pk=:order_uid AND ac_deleted_ts IS NULL;");
              $users->bindParam(':order_uid', $order_uid);
              $users->execute();
              $user_row = $users->fetch();
              if(isset($user_row['ac_user_vc'])){
                  return $user_row['ac_user_vc'];
              }
            
            }
        }
    }

    
    static function getOrderVoucherDiscount($invoice_id = 0){
    	return self::getOrderInfoVoucher('discount', $invoice_id);
    }
    
    static function getOrderVoucherCode($invoice_id = 0){
    	return self::getOrderInfoVoucher('voucher_code', $invoice_id);
    }
    
    static function getOrderInfoAmountDue($invoice_id = 0){
    	if(self::getOrderInfoVoucher('discount', $invoice_id)){
    		return (float) self::getOrderInfoAmount($invoice_id) - ((self::getOrderInfoAmount($invoice_id) / 100) * self::getOrderVoucherDiscount($invoice_id));
    	} else {
    		return self::getOrderInfoAmount($invoice_id);
    	}
    	
    	
    }
    
    static function getOrderVoucherDiscountType(){
    	$discount_type_id = self::getOrderInfoVoucher('discount_type');
    	
        $discount_type = 'N/A';
        if($discount_type_id == 1){
           $discount_type = 'Once-Off';
        } elseif($discount_type_id == 2){
           $discount_type = 'Recurring';
        }
        
    	return $discount_type;
    }
    
    static function getOrderInfoVoucher($field = null, $invoice_id = 0){
        global $zdbh,$controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id']) || $invoice_id > 0){
            //var_dump($invoice_id);
            if(!$invoice_id){
            	$invoice_id = $formvars['order_id'];
            }
            $order_vid = self::getOrderInfo($invoice_id, 'invoice_voucher_id',self::$module_db.'.x_invoices');
            //die(var_dump($order_vid));
            if($order_vid){
              if(!$field){
              	return true;
              } else {
		      $sql = "SELECT ".$field." FROM ".self::$module_db.".x_vouchers 
		                               WHERE voucher_id=:vid AND voucher_deleted_ts IS NULL;";
		      $bindArray = array(':vid' => (int) $order_vid);
		      $zdbh->bindQuery($sql, $bindArray);
		      $fld_value = $zdbh->returnRow();
		      return $fld_value[$field];
              }
            } else {
            	return false;
            }
        }
    }

    static function getOrderInfoDescription(){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id'])){
            return self::getOrderInfo($formvars['order_id'], 'order_desc',self::$module_db.'.x_orders');
        }
    }


    static function getOrderInfoAmount($invoice_id = 0){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id']) || $invoice_id > 0){
            if(!$invoice_id){
            	$invoice_id = (int) $formvars['order_id'];
            }
            $order_amount = self::getOrderInfo($invoice_id, 'invoice_total_amount',self::$module_db.'.x_invoices');

            if(!$order_amount){
                $order_amount = 0;
            }
            return number_format($order_amount,2);
        }
    }


    static function getOrderInfoStatus(){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id'])){
            $invoice_payment_option_id = self::getOrderInfo($formvars['order_id'], 'payment_option_id',self::$module_db.'.x_invoices');

            $invoice_status_id = self::getOrderInfo($formvars['order_id'], 'invoice_status',self::$module_db.'.x_invoices');
            
            $invoice_status = ($invoice_status_id == 1) ? 'Paid':'Pending';

            if($invoice_payment_option_id == '-1'){
                $invoice_status .= ' manually ';
            }
            
            if($invoice_status_id == 1){
                $order_date_completed = date("Y-m-d", strtotime(self::getOrderInfo($formvars['order_id'], 'order_complete_dated',self::$module_db.'.x_orders')));
                $invoice_status .= ' on '.$order_date_completed;
            }
            
            if($invoice_payment_option_id && $invoice_payment_option_id !='-1'){
                $payment_option_name = self::getCurrentPaymentOption($invoice_payment_option_id);

                if($payment_option_name){
                    $invoice_status .=  ' using '.$payment_option_name;
                }
            }
            
            return $invoice_status;
        }
    }
    
    
    static function getOrderInfo($order_id, $field, $table){
        global $zdbh;
        if($order_id && $field && $table){
            $currentuser = ctrl_users::GetUserDetail();        
            
            $orders = $zdbh->prepare("SELECT $table.$field FROM ".self::$module_db.".x_invoices_orders 
                                        INNER JOIN ".self::$module_db.".x_orders ON ".self::$module_db.".x_orders.order_id=".self::$module_db.".x_invoices_orders.order_id 
                                        INNER JOIN ".self::$module_db.".x_invoices ON 
                                        ".self::$module_db.".x_invoices.invoice_id=".self::$module_db.".x_invoices_orders.invoice_id 
                                        WHERE ".self::$module_db.".x_invoices.reseller_ac_id_fk=:user_id 
                                        AND ".self::$module_db.".x_orders.reseller_ac_id_fk=:user_id
                                        AND ".self::$module_db.".x_invoices.invoice_id=:inv_id 
                                        AND invoice_deleted_ts IS NULL AND order_deleted_ts IS NULL 
                                        GROUP BY ".self::$module_db.".x_invoices.invoice_id 
                                        ORDER BY ".self::$module_db.".x_invoices.invoice_id ASC;");
            $orders->bindParam(':user_id', $currentuser['userid']);
            $orders->bindParam(':inv_id', $order_id);
            $orders->execute();
        
            $order = $orders->fetch();
            if(isset($order[$field])){
               return $order[$field];
            }
        
        }
    }
    
    
    static function doViewOrder(){
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(!(int)$formvars['order_id']){
            self::$error['invalid_order'] = true;
            return false;
        } else {
            //self::$ok = true;
            return true;        
        }
    }

    
    static function doResendInvoice(){
        global $controller, $zdbh;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(!(int)$formvars['x_item_id']){
            self::$error['invalid_order'] = true;
            return false;
        } else {
            $currentuser = ctrl_users::GetUserDetail();        
            
            $sql = "SELECT * FROM ".self::$module_db.".x_invoices_orders 
                                        INNER JOIN ".self::$module_db.".x_orders ON ".self::$module_db.".x_orders.order_id=".self::$module_db.".x_invoices_orders.order_id 
                                        INNER JOIN ".self::$module_db.".x_invoices ON 
                                        ".self::$module_db.".x_invoices.invoice_id=".self::$module_db.".x_invoices_orders.invoice_id 
                                        INNER JOIN ".self::$server_app."_core.x_accounts ON ".self::$server_app."_core.x_accounts.ac_id_pk=x_invoices.ac_id_fk                                         
                                        INNER JOIN ".self::$server_app."_core.x_profiles 
                                        ON ".self::$server_app."_core.x_profiles.ud_user_fk=".self::$server_app."_core.x_accounts.ac_id_pk                                         
                                        WHERE ".self::$module_db.".x_invoices.invoice_id=:inv_id 
                                        AND invoice_deleted_ts IS NULL AND order_deleted_ts IS NULL 
                                        GROUP BY ".self::$module_db.".x_invoices.invoice_id 
                                        ORDER BY ".self::$module_db.".x_invoices.invoice_id ASC;";
            
            $orders = $zdbh->prepare($sql);
            //$orders->bindParam(':user_id', $currentuser['userid']);
            $orders->bindParam(':inv_id', $formvars['x_item_id']);
            $orders->execute();
        
            $order_info = $orders->fetch();
            if(is_array($order_info)){	            
            	$invoice_link = '';
            	$panel_url = 'http://'.self::getPanelURL();
            	$today_date = date("Y-m-d");
            	$inv_del_days = self::appSetting($currentuser['userid'],'pending_invoice_delete_days');
            	$billing_url = self::appSetting($currentuser['userid'],'website_billing_url');
            	$emailbody = self::appSetting($currentuser['userid'],'invoice_reminder_message');
            	//$emailbody = $invoice_reminder_message;             	
            	
		//fetch company name
                $company_name = self::appSetting($currentuser['userid'],'company_name');
                 
                $due_date = date("Y-m-d", strtotime($order_info['invoice_dated']." +".$inv_del_days."days"));

                if($billing_url){
                    $invoice_link = $billing_url.'/view_invoice.php?invoice='.$order_info['invoice_reference'];
                }   
                
                if($invoice_link != ''){
		         $emailbody = str_replace("{{fullname}}", $order_info['ud_fullname_vc'], $emailbody);
		         $emailbody = str_replace("{{company_name}}", $company_name, $emailbody);
		         $emailbody = str_replace("{{invoice_link}}", $invoice_link, $emailbody);
		         $emailbody = str_replace("{{invoice_reference}}", $order_info['invoice_reference'], $emailbody);
		         $emailbody = str_replace("{{invoice_due_date}}", $due_date, $emailbody);
		         $emailbody = str_replace("{{panel_url}}", $panel_url, $emailbody);
		         		         
		         $subject = "Your Invoice #".$order_info['invoice_reference'].' at '.$company_name;		         
		         self::sendMail(array('to' => $order_info['ac_email_vc'], 'subject' => $subject, 'message' => $emailbody));
                
                }                                                                  	            	
            }
        
            self::$ok = true;
                        
            return true;        
        }
    }
    
    static function sendMail($parms = array()){
        //global $controller;

        if(isset($parms['to']) && isset($parms['subject']) && isset($parms['message'])){
            if(!isset($parms['reseller_id'])){
                $parms['reseller_id'] = '';
            }
         //if(isset($parms['reseller_id'])){
            $currentuser = ctrl_users::GetUserDetail($parms['reseller_id']);
         //} else {
            //$currentuser = ctrl_users::GetUserDetail();            
         //}
    	 
    	 $email_format = self::appSetting($currentuser['userid'], 'email_format');
    	 
    	 
         $phpmailer = new sys_email();
         $phpmailer->Subject = $parms['subject'];
	 if($email_format == 2){
	 	//$phpmailer->Body = $parms['message'];
	 	$phpmailer->AltBody = strip_tags($parms['message']);
	 	$phpmailer->MsgHTML($parms['message']);
	 } else {
	 	$phpmailer->Body = $parms['message'];
	 }         
         $phpmailer->AddAddress($parms['to']);
         $phpmailer->SendEmail();
         
         return true;   	
    	}
    }
    

    static function doEditOrder(){
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(!(int)$formvars['order_id']){
            self::$error['invalid_order'] = true;
            return false;
        } else {
            //self::$ok = true;
            return true;        
        }
    }

    static function doUpdateOrder(){
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['order_id']) && isset($formvars['completed_yn'])){
            if (self::ExecuteUpdateOrder($formvars['order_id'], $formvars['completed_yn'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['order_info_empty'] = true;
            return false;
        }        
        return;
    }
    /* Orders / Invoices */
    
    /* Vouchers */
    static function getVouchers(){
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail();
        $vouchers = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_vouchers 
                                        WHERE reseller_ac_id_fk=:user_id 
                                        AND voucher_deleted_ts IS NULL;");
        $vouchers->bindParam(':user_id', $currentuser['userid']);
        $vouchers->execute();
        $res = array();
        if (!fs_director::CheckForEmptyValue($vouchers)) {
            while ($row = $vouchers->fetch()) {
               $active_text = '<span class="label label-danger">No</span>';
               if($row['active_yn'] == 1){
               	$active_text = '<span class="label label-success">Yes</span>';
               }

               $usage_type = 'N/A';
               if($row['usage_type'] == 1){
               	$usage_type = 'Once';
               } elseif($row['usage_type'] == 2){
               	$usage_type = 'Multiple';
               }

               $discount_type = 'N/A';
               if($row['discount_type'] == 1){
               	$discount_type = 'Once-Off';
               } elseif($row['discount_type'] == 2){
               	$discount_type = 'Recurring';
               }
               
/*               //usages
               $total_usages = 0;
		    $sql = "SELECT COUNT(*) AS total_usages FROM ".self::$module_db.".x_invoices 
		                WHERE invoice_voucher_id=:id AND invoice_deleted_ts IS NULL";
		    $numrows = $zdbh->prepare($sql);
		    $numrows->bindParam(':id', $row['voucher_id']);
		    if ($numrows->execute()) {
		    	$voucher_usages = $numrows->fetch();
		        $total_usages = $voucher_usages['total_usages'];
		    }
*/               
               
               array_push($res, array('code' => $row['voucher_code'],
                                      'discount' => $row['discount'],
                                      'type' => $row['voucher_code'],
                                      'active_text' => $active_text,
                                      'usage_type_text' => $usage_type,
                                      'discount_type_text' => $discount_type,
                                      'total_usages' => self::getVoucherUsages($row['voucher_id']),
                                      'id' => $row['voucher_id']));  
            }
            return $res;
        } else {
            return false;
        }            
    }


    static function getVoucherUsages($voucher_id){
        global $zdbh;
        
               //usages
           $total_usages = 0;
           $sql = "SELECT COUNT(*) AS total_usages FROM ".self::$module_db.".x_invoices 
                        WHERE invoice_voucher_id=:id AND invoice_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':id', $voucher_id);
            if ($numrows->execute()) {
                $voucher_usages = $numrows->fetch();
                $total_usages = $voucher_usages['total_usages'];
            }


            return $total_usages;
    }


    
    static function getisAddVoucher(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');
        if(!isset($urlvars['action']) || !isset($formvars['voucher_id']) || (isset($urlvars['action']) && ($urlvars['action'] == 'UpdateVoucher' || $urlvars['action'] == 'DeleteVoucher') && self::$complete)){
            return true;
        }
        return false;
    }
    
    static function getisEditVoucher(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'EditVoucher') && isset($formvars['voucher_id']) || (isset($urlvars['action']) && $urlvars['action'] == 'UpdateVoucher' && !self::$complete)){
            return true;
        }
        return false;
    }
    
    /*static function getisDeletePeriod(){
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        $formvars = $controller->GetAllControllerRequests('FORM');

        if((isset($urlvars['action']) && $urlvars['action'] == 'ConfirmDeletePeriod')){
            return true;
        }
        return false;
    }
    
    static function doConfirmDeleteVoucher(){
        return true;
    } */
    

    /*static function getEditPeriodDuration(){
        global $controller,$zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $period_info = array();
        if(!isset($formvars['period_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT period_duration FROM ".self::$module_db.".x_periods 
					WHERE period_id=:period_id AND reseller_ac_id_fk=:user_id 
					AND period_deleted_ts IS NULL";
        $bindArray = array(':period_id' => (int)$formvars['period_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $period_info = $zdbh->returnRow(); 
        
        if(count($period_info)){
            return $period_info['period_duration'];
        }
       
    }

    static function getEditPeriodAmount(){
        global $controller,$zdbh;
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $period_info = array();
        if(!isset($formvars['period_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT default_amount FROM ".self::$module_db.".x_periods 
					WHERE period_id=:period_id AND reseller_ac_id_fk=:user_id 
					AND period_deleted_ts IS NULL";
        $bindArray = array(':period_id' => (int)$formvars['period_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $period_info = $zdbh->returnRow(); 
        
        if(count($period_info)){
            return $period_info['default_amount'];
        }
       
    }*/
    
    static function getCurrentVoucherID(){
        global $controller;
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if(isset($formvars['voucher_id'])){
            return (int)$formvars['voucher_id'];         
        }
    }
    
    static function getCurrentVoucher(){
        global $controller,$zdbh;
        
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $voucher_info = array();
        
        if(!isset($formvars['voucher_id'])){
            self::$error = true;
            return false;
        }
        
 		$sql = "SELECT voucher_code,usage_type,discount_type,active_yn,discount FROM ".self::$module_db.".x_vouchers 
					WHERE voucher_id=:voucher_id AND reseller_ac_id_fk=:user_id 
					AND voucher_deleted_ts IS NULL";
        $bindArray = array(':voucher_id' => (int)$formvars['voucher_id'], ':user_id'=>$currentuser['userid']);
        $zdbh->bindQuery($sql, $bindArray);
        $voucher_info = $zdbh->returnRow(); 
        
        if(!isset($voucher_info['voucher_code'])){
            self::$error = true;
            return false;        
        }
        
        return $voucher_info;        
    }
    
    static function getCurrentVoucherCode(){
    	$voucher_info = self::getCurrentVoucher();
    	return $voucher_info['voucher_code'];
    }
    
    static function getCurrentVoucherDiscount(){
    	$voucher_info = self::getCurrentVoucher();
    	return $voucher_info['discount'];
    }
    
    static function getEditDiscountType(){
    	$voucher_info = self::getCurrentVoucher();
    	
        $res = array(array('label'=>'Once Off', 'id' => 1), array('label'=>'Recurring', 'id' => 2));
        
        foreach($res as $itm_idx=>$type){
            if($type['id'] == $voucher_info['discount_type']){
                $res[$itm_idx]['selected_yn'] = ' selected="selected"';
                break;
            }
        }
    	
    	return $res;
    }
    
    static function getEditDiscountUsageType(){
    	$voucher_info = self::getCurrentVoucher();
    	
        $res = array(array('label'=>'Once', 'id' => 1), array('label'=>'Multiple', 'id' => 2));
        
        foreach($res as $itm_idx=>$type){
            if($type['id'] == $voucher_info['usage_type']){
                $res[$itm_idx]['selected_yn'] = ' selected="selected"';
                break;
            }
        }
    	
    	return $res;
    }
    
    static function getEditDiscountStatus(){
    	$voucher_info = self::getCurrentVoucher();
    	
        $res = array(array('label'=>'Yes', 'id' => 1), array('label'=>'No', 'id' => 0));
        
        foreach($res as $itm_idx=>$type){
            if($type['id'] == $voucher_info['active_yn']){
                $res[$itm_idx]['selected_yn'] = ' selected="selected"';
                break;
            }
        }
    	
    	return $res;
    }

    static function doCreateVoucher() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');        
        
        if(trim($formvars['code']) != '' && (float) $formvars['discount']){
            if (self::ExecuteAddVoucher(trim($formvars['code']), (float) $formvars['discount'], (int) $formvars['active_yn'], $formvars['type'], $formvars['usage'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['voucher_empty'] = true;
            return false;
        }        
        return;
    }
    
    static function doEditVoucher() {
        global $controller;
        runtime_csfr::Protect();
        
        $formvars = $controller->GetAllControllerRequests('FORM');
        //$currentuser = ctrl_users::GetUserDetail();
        //$period_info = array();
        
        if(! (int)$formvars['voucher_id']){
            self::$error = true;
            return false;
        }
    }
    
    static function doUpdateVoucher(){
        global $zdbh, $controller;
        runtime_csfr::Protect();
        
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        $period_info = array();
        
        if((float) $formvars['discount'] && (int) $formvars['voucher_id']){
		    $sql = "SELECT * FROM ".self::$module_db.".x_vouchers 

					    WHERE voucher_id=:voucher_id AND reseller_ac_id_fk=:user_id 
					    AND voucher_deleted_ts IS NULL";
            $bindArray = array(':voucher_id' => (int)$formvars['voucher_id'], ':user_id'=>$currentuser['userid']);
            $zdbh->bindQuery($sql, $bindArray);
            $voucher_info = $zdbh->returnRow(); 

            if(!count($voucher_info)){
                self::$error = true;
                return false;        
            }

            
            if (self::ExecuteUpdateVoucher((int) $formvars['voucher_id'], (float) $formvars['discount'], (int) $formvars['active_yn'], $formvars['type'], $formvars['usage'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error['voucher_empty'] = true;
            return false;
        }        
        return;
    }
        

    static function doDeleteVoucher() {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        
        if((int) $formvars['x_item_id']){
            if (self::ExecuteDeleteVoucher((int) $formvars['x_item_id'])) {
                self::$ok = true;
                return true;
            }        
        } else {
            self::$error = true;
            return false;
        }        
        return;
    }
    /* Vouchers */

    
    static function doFetchConfig(){
        global $zdbh;
        
        $currentuser = ctrl_users::GetUserDetail();
        
        $settings = $zdbh->prepare("SELECT so_value_tx FROM x_settings WHERE so_name_vc='apikey';");
        $settings->execute();
        $setting = $settings->fetch();
        $api_key = $setting['so_value_tx'];
        
        $settings = $zdbh->prepare("SELECT so_value_tx FROM x_settings 
                                     WHERE so_name_vc='zpanel_domain';");
        $settings->execute();
        $setting = $settings->fetch();
        $panel_url = $setting['so_value_tx'];
        
        $user_id = $currentuser['userid'];
        
        $url_protocol = self::appSetting($currentuser['userid'], 'url_protocol');
        if(!$url_protocol){
            $url_protocol = 'http://';
        }
        
        $config = '
<?php
  /**
   * API connection settings for xBilling
   * Version : 1.2.0
   * @author Aderemi Adewale (modpluz @ Sentora Forums)
   * Email : goremmy@gmail.com
   * @desc This allows front-end billing package interact with the backend module
  */
                    
  // Config;
     $cfg = array();
     $cfg[\'api_key\'] = \''.$api_key.'\';
     $cfg[\'panel_url\'] = \''.$panel_url.'\';
     $cfg[\'zpx_uid\'] = '.$user_id.';
                    
     if(strpos($cfg[\'panel_url\'], \'http\') === false){
         $cfg[\'panel_url\'] = \''.$url_protocol.'\'.$cfg[\'panel_url\'];
     }                    
?>';

	    header("Content-Type: text/php\n");
	    header("Content-Disposition: attachment; filename=config.php");

	    echo $config;
        exit;
    }
    
    /* Auto Create Account - this section handles creation of new account directly from this module */
    static function doUserPackagePeriods(){
       global $zdbh,$controller;
       
       $table_1 = self::$module_db.'.x_packages_periods';
       $table_2 = self::$module_db.'.x_periods';

       $ret_html = '';
       $urlvars = $controller->GetAllControllerRequests('URL');
       if(!isset($urlvars['pid'])){
        $ret_html = 'An error occured while fetching package service periods.';
       } else {
         //service periods
         $sql = $zdbh->prepare("SELECT period_duration,default_amount,package_amount,
                                        $table_2.period_id,package_period_id FROM $table_1 RIGHT JOIN 
                                        $table_2 ON $table_2.period_id=$table_1.period_id 
                                        WHERE $table_1.zpx_package_id=:package_id 
                                        AND $table_2.period_deleted_ts IS NULL;");
         $sql->bindParam(':package_id', $urlvars['pid']);
         $sql->execute();
         $package_periods = $sql->fetchAll();

         if (is_array($package_periods) && count($package_periods) > 0){
            //fetch package name
       		$sql_i = "SELECT pk_name_vc FROM ".self::$server_app."_core.x_packages WHERE pk_id_pk=:pkg_id 
                   		AND pk_deleted_ts IS NULL";
            $bindArray = array(':pkg_id' => $urlvars['pid']);
            $zdbh->bindQuery($sql_i, $bindArray);
            $package_info = $zdbh->returnRow();
                        
            foreach($package_periods as $row_p) {
                $period_name = $row_p['period_duration'].' month';
                $period_amt = $row_p['package_amount'];
                if(!$period_amt){
                    $period_amt = $row_p['default_amount'];
                }
                if($row_p['period_duration'] > 1){
                    $period_name .= 's';
                }
                $ret_html .= '<option value="'.$row_p['period_id'].'" align="left">'.$period_name.' @ '.$period_amt.' '.self::getSettingCurrency().'</option>';
            }
         } else {
            // return
         }        
       
       }
       echo($ret_html);
       exit;
    }
    
    static function ListDomainDirs($uid) {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail($uid);
        $res = array();
        $handle = @opendir(ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html");
        $chkdir = ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html/";
        if (!$handle) {
            # Log an error as the folder cannot be opened...
        } else {
            while ($file = @readdir($handle)) {
                if ($file != "." && $file != ".." && $file != "_errorpages") {
                    if (is_dir($chkdir . $file)) {
                        array_push($res, array('domains' => $file));
                    }
                }
            }
            closedir($handle);
        }
        return $res;
    }


    static function getDomainDirsList() {
        global $zdbh;
        global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        $domaindirectories = self::ListDomainDirs($currentuser['userid']);
        if (!fs_director::CheckForEmptyValue($domaindirectories)) {
            return $domaindirectories;
        } else {
            return false;
        }
    }
    
    
    
    static function getMinPassLength() {
        $minpasswordlength = ctrl_options::GetSystemOption('password_minlength');
        $trylength = 9;
        if ($trylength < $minpasswordlength) {
            $uselength = $minpasswordlength;
        } else {
            $uselength = $trylength;
        }
        return $uselength;
    }
    
    static function doCreateAccount(){
        global $zdbh, $controller;
        //runtime_csfr::Protect();        
        
        $error = 0;
        
        $formvars = $controller->GetAllControllerRequests('FORM');
        $currentuser = ctrl_users::GetUserDetail();
        
        $formvars['zpx_uid'] = $currentuser['userid'];
        $formvars['activate_yn'] = 0;
        
        $new_domain = self::CreateNewUser($formvars);
        //die(var_dump($new_domain));
        
        if(is_array($new_domain)){
            //fetch new user id
            $sql = $zdbh->prepare("SELECT vh_acc_fk,ac_user_vc FROM x_vhosts 
                                      INNER JOIN x_accounts ON x_accounts.ac_id_pk=x_vhosts.vh_acc_fk 
                                      WHERE vh_id_pk=:domain_id;");
            $sql->bindParam(':domain_id', $new_domain['domain_id']);
            $sql->execute();
            $new_user = $sql->fetch();
            $new_user_id = $new_user['vh_acc_fk'];
            
            if($new_user_id > 0){
                $formvars['domain_id'] = $new_domain['domain_id'];
                $new_invoice = self::SaveInvoiceOrder($new_user_id, $formvars);
                if(!is_array($new_invoice)){
                    $error = 1;
                }
            } else {
                $error = 1;                            
            }
            
            if($error){
                if(!$new_user){
                    $new_user = "An error occured while creating a new user.";
                }
                self::$error['customerror'] = $new_user;
                return false;            
            }
        } else {
            //self::$error = true;
            self::$error['customerror'] = $new_domain;
            return false;        
        }
        
        if($formvars['email_yn'] == 1){
            $emailbody = self::getEditOrderMessage();
        }
            
        if($formvars['order_status'] == 1){
            if($formvars['email_yn'] == 1){
                $emailbody = self::getEditWelcomeMessage();
            }
                
            //complete order
             $res = $zdbh->prepare("SELECT invoice_id FROM ".self::$module_db.".x_invoices 
                                      WHERE ac_id_fk=:id;");
             $res->bindParam(':id', $new_user_id);
             $res->execute();
             if (!fs_director::CheckForEmptyValue($res)){
                $order_info = $res->fetch();
                if(is_array($order_info)){
                   //update order/invoice
                    self::ExecuteUpdateOrder($order_info['invoice_id'], 1);
                }
             }                                
        }
        
        //send email
        if($formvars['email_yn'] == 1){
            //fetch invoice reference
           $sql = $zdbh->prepare("SELECT invoice_reference FROM ".self::$module_db.".x_invoices 
                                   WHERE ac_id_fk=:id;");
           $sql->bindParam(':id', $new_user_id);
           $sql->execute();
           $invoice_info = $sql->fetch();                               

          //fetch company name
           $company_name = self::appSetting($currentuser['userid'],'company_name');
          //fetch invoice unpaid days
           $inv_del_days = self::appSetting($currentuser['userid'],'pending_invoice_delete_days');
          //panel url
           $panel_url = 'http://'.self::getPanelURL();
               
           $billing_url = self::appSetting($currentuser['userid'],'website_billing_url');
           if($billing_url){
              $invoice_link = $billing_url.'/view_invoice.php?invoice='.$invoice_info['invoice_reference'];
           }
                              
           if($emailbody && $formvars['email_address']){
               $emailbody = str_replace("{{fullname}}", $formvars['fullname'], $emailbody);
               $emailbody = str_replace("{{company_name}}", $company_name, $emailbody);
               $emailbody = str_replace("{{invoice_link}}", $invoice_link, $emailbody);
               $emailbody = str_replace("{{invoice_reference}}", $invoice_info['invoice_reference'], $emailbody);
               $emailbody = str_replace("{{invoice_unpaid_days}}", $inv_del_days, $emailbody);
               $emailbody = str_replace("{{username}}", $new_user['ac_user_vc'], $emailbody);
               $emailbody = str_replace("{{password}}", $formvars['password'], $emailbody);
               $emailbody = str_replace("{{panel_url}}", $panel_url, $emailbody);

               //$phpmailer = new sys_email();
               if($formvars['order_status'] == 1){
                  $subject = "Welcome to $company_name!";
               } else {
                  $subject = "Your Order at ".$company_name;                    
               }
               /*$phpmailer->Body = $emailbody;
               $phpmailer->AddAddress($formvars['email_address']);
               $phpmailer->SendEmail(); */
               
               self::sendMail(array('to' => $formvars['email_address'], 'subject' => $subject, 'message' => $emailbody));
           }
        } 
        
        
        self::$ok = true;
        return true;
    }
    /* Auto Create Account  - this section handles creation of new account directly from this module */

    
    
    /* Web Service */
    static function getPackagePeriods($pkg_id){
       global $zdbh;
       
       $pkg_periods = '';
       if($pkg_id){
           $table_1 = self::$module_db.'.x_packages_periods';
           $table_2 = self::$module_db.'.x_periods';

           //service periods
           $periods = $zdbh->prepare("SELECT * FROM $table_1 LEFT JOIN $table_2 ON 
                                       $table_2.period_id=$table_1.period_id 
                                       WHERE $table_1.zpx_package_id=:package_id 
                                       AND package_amount>0 AND period_deleted_ts IS NULL
                                       ORDER BY $table_2.period_duration ASC;");
           $periods->bindParam(':package_id', $pkg_id);
           $periods->execute();
           if (!fs_director::CheckForEmptyValue($periods)){
              while ($row = $periods->fetch()){
                //is this a free package?
                $pkg = $zdbh->prepare("SELECT free_package_yn FROM ".self::$module_db.".x_packages 
                                        WHERE zpx_package_id=:pkg_id;");
                $pkg->bindParam(':pkg_id', $row['zpx_package_id']);
                $pkg->execute();
                $package = $pkg->fetch();
                $free_package_yn = $package['free_package_yn'];
              
                if($free_package_yn){
                  $pkg_periods['-1'] = array('id'=>'-1','duration'=>'-1','amount'=>0);                
                } else {
                  $pkg_periods[$row['period_duration']] = array('id'=>$row['period_id'],'duration'=>$row['period_duration'],'amount'=>$row['package_amount']);
                }
              }
           } else {
              $pkg_periods = 'An error occurred while fetching package service periods.';
           }        
       } else {
          $pkg_periods = 'An error occurred while fetching package service periods.';
       }
       return $pkg_periods;
    }
    
    static function getSettings($user_id){
       global $zdbh;

       $app_settings = '';
       if($user_id){
             //settings
             $valid_settings = array("'company_name'","'email_address'","'currency'",
                                         "'company_logo_path'", "'website_billing_url'",
                                         "'billing_enabled_yn'", "'recaptcha_public_key'",
                                         "'recaptcha_private_key'","'url_protocol'",
                                         "'country_code'","'pending_invoice_delete_days'",
                                         "'recaptcha_disabled_yn'",  "'logs_enabled_yn'");
             $settings = $zdbh->prepare("SELECT setting_name,setting_value FROM 
                                        ".self::$module_db.".x_settings WHERE
                                         reseller_ac_id_fk=:zpx_uid AND setting_name IN
                                         (".implode(',',$valid_settings).");");
             $settings->bindParam(':zpx_uid', $user_id);
             $settings->execute();
             if (!fs_director::CheckForEmptyValue($settings)){
                while ($row = $settings->fetch()) {                
                    $app_settings[$row['setting_name']] = $row['setting_value'];
                }
             } else {
                $app_settings = 'An error occured while fetching settings.';
             }        
       } else {
          $app_settings = 'An error occured while fetching settings.';       
       }

       //just incase this wasn't set, let's avoid an undefined index in the frontend
       if(!isset($app_settings['logs_enabled_yn'])){
            $app_settings['logs_enabled_yn'] = 0;
       }

       return $app_settings;
    }
    
    static function appSetting($user_id, $setting_name){
       global $zdbh;

       $app_settings = '';
       if($user_id && $setting_name){
             //settings
             $settings = $zdbh->prepare("SELECT setting_value FROM 
                                        ".self::$module_db.".x_settings WHERE
                                         reseller_ac_id_fk=:zpx_uid AND setting_name=:setting_name;");
             $settings->bindParam(':zpx_uid', $user_id);
             $settings->bindParam(':setting_name', $setting_name);
             $settings->execute();
             if (!fs_director::CheckForEmptyValue($settings)){
                while ($row = $settings->fetch()) {                
                    return $row['setting_value'];
                }
             }
       }
    }
    
    static function CheckUserExists($username){
        global $zdbh;
         $user_exists = '-1';
         $res = $zdbh->prepare("SELECT ac_id_pk FROM ".self::$server_app."_core.x_accounts WHERE ac_user_vc=:zpx_user;");
         $res->bindParam(':zpx_user', $username);
         $res->execute();
         if ($res->fetchColumn() > 0){
            $user_exists = 1;
         } else {
            $user_exists = 0;
         }        
        
        return $user_exists;
    }
    
    static function GetPackageName($pkg_id){
        global $zdbh;
         $pkg_name = '';
         if($pkg_id){
             $res = $zdbh->prepare("SELECT pk_name_vc FROM ".self::$server_app."_core.x_packages 
                                        WHERE pk_id_pk=:pkg_id AND pk_deleted_ts IS NULL;");
             $res->bindParam(':pkg_id', $pkg_id);
             $res->execute();
             if (!fs_director::CheckForEmptyValue($res)){
                $row = $res->fetch();
                return $row['pk_name_vc'];
             }
         }
    }
    
    static function GetPeriodInfo($pkg_id,$pid){
        global $zdbh;        
        if($pkg_id && $pid){
           $table_1 = self::$module_db.'.x_packages_periods';
           $table_2 = self::$module_db.'.x_periods';

           //service periods
           if($pid != '-1'){
               $periods = $zdbh->prepare("SELECT period_duration,package_amount FROM 
                                           $table_1 LEFT JOIN $table_2 ON 
                                           $table_2.period_id=$table_1.period_id 
                                           WHERE $table_1.zpx_package_id=:package_id 
                                           AND period_deleted_ts IS NULL 
                                           AND $table_1.period_id=:period_id LIMIT 1;");
               $periods->bindParam(':package_id', $pkg_id);
               $periods->bindParam(':period_id', $pid);
               $periods->execute();
               if (!fs_director::CheckForEmptyValue($periods)){
                  while ($row = $periods->fetch()) {  
                      return $row;
                  }
               }
           } elseif($pid =='-1'){
                $row['period_duration'] = '-1';
                $row['package_amount'] = '-1';
                return $row;
           }
            
        }
    }
    
    static function CreateNewUser($data){
        global $zdbh;
    
        if(is_array($data)){
            //check for required fields
            if(!$data['zpx_uid'] || !$data['package_id'] || !$data['period_id'] || !$data['domain'] || !$data['fullname'] || !$data['email_address'] || !$data['username']){
                return 'An authentication error has occured, a required field is missing.';
            }
            
            //check username
            if(self::CheckUserExists($data['username'])){
                return 'Username is not available.';
            }
            
            return self::ExecuteCreateClient($data);
        } else {
            return 'An authentication error has occured.';
        }
    }
    
    static function SaveInvoiceOrder($user_id, $data){
        if($user_id && is_array($data)){
            return self::ExecuteCreateOrderInvoice($user_id,$data);
        }
    }
    
    static function getInvoice($invoice_reference, $user_id){
        global $zdbh,$controller;

        if(!$invoice_reference || !$user_id){
            return 'An authentication error occured, your request is invalid';
        }
        
        //fetch invoice
         $orders = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_invoices_orders 
                                    INNER JOIN ".self::$module_db.".x_orders ON ".self::$module_db.".x_orders.order_id=".self::$module_db.".x_invoices_orders.order_id 
                                    INNER JOIN ".self::$module_db.".x_invoices ON 
                                    ".self::$module_db.".x_invoices.invoice_id=".self::$module_db.".x_invoices_orders.invoice_id 
                                    WHERE ".self::$module_db.".x_invoices.reseller_ac_id_fk=:user_id 
                                    AND ".self::$module_db.".x_orders.reseller_ac_id_fk=:user_id
                                    AND ".self::$module_db.".x_invoices.invoice_reference=:inv_ref 
                                    AND invoice_deleted_ts IS NULL AND order_deleted_ts IS NULL;");
          $orders->bindParam(':user_id', $user_id);
          $orders->bindParam(':inv_ref', $invoice_reference);
          $orders->execute();
        
          $invoice = $orders->fetch();
          if(is_array($invoice)){
            //foreach($invoice as $order){
          		$sql = "SELECT payment_option_name FROM ".self::$module_db.".x_payment_options 
                            WHERE payment_option_id=:option_id AND reseller_ac_id_fk=:user_id 
          		            AND option_deleted_ts IS NULL";
                $bindArray = array(':option_id' => $invoice['payment_option_id'], ':user_id'=>$user_id);
                $zdbh->bindQuery($sql, $bindArray);
                $option_info = $zdbh->returnRow(); 
            
                $payment_option_name = $option_info['payment_option_name'];
                if(!$payment_option_name){
                    $payment_option_name = 'N/A';
                }
                
                //fetch domain name if we have it
                $domain_name = '';                   
                if(isset($invoice['order_vh_fk'])){
              	   $sql = "SELECT vh_name_vc FROM ".self::$server_app."_core.x_vhosts 
              		            WHERE vh_id_pk=:domain_id AND vh_acc_fk=:user_id 
              		            AND vh_deleted_ts IS NULL";
                    $bindArray = array(':domain_id' => $invoice['order_vh_fk'], ':user_id'=>$invoice['ac_id_fk']);
                    $zdbh->bindQuery($sql, $bindArray);
                    $domain_info = $zdbh->returnRow(); 

                    if(isset($domain_info['vh_name_vc'])){
                        $domain_name = $domain_info['vh_name_vc'];
                    }                
                }
                
                //fetch voucher info....if any
                if($invoice['invoice_voucher_id']){
              	   $sql = "SELECT discount,discount_type,voucher_code FROM ".self::$module_db.".x_vouchers 
              		            WHERE voucher_id=:vid AND active_yn=1 
              		            AND voucher_deleted_ts IS NULL";
                    $bindArray = array(':vid' => $invoice['invoice_voucher_id']);
                    $zdbh->bindQuery($sql, $bindArray);
                    $voucher_info = $zdbh->returnRow();
                    
                    if($voucher_info){
		    	$discount_type = 'Once-Off';
			if($voucher_info['discount_type'] == 2){
				$discount_type = 'Recurring';
			}
                    
                    	$invoice_info['discount'] = $voucher_info['discount'];
                    	$invoice_info['discount_type'] = $discount_type;
                    	$invoice_info['voucher_code'] = $voucher_info['voucher_code'];
                    	//$invoice_info['discount'] = $voucher_info['discount'];
                    }
                }
                
                $invoice_info['id'] = $invoice['invoice_id'];
                $invoice_info['reference'] = $invoice['invoice_reference'];
                $invoice_info['desc'] = $invoice['order_desc'];
                $invoice_info['date'] = date("Y-m-d", strtotime($invoice['invoice_dated']));
                $invoice_info['completed_date'] = $invoice['order_complete_dated'];
                $invoice_info['status'] = $invoice['invoice_status'];
                $invoice_info['payment_method'] = $payment_option_name;
                $invoice_info['total_amount'] = $invoice['invoice_total_amount'];
                $invoice_info['user_id'] = $invoice['ac_id_fk'];
                $invoice_info['transaction_id'] = $invoice['transaction_id'];
                $invoice_info['order_type_id'] = $invoice['order_type_id'];
                $invoice_info['domain'] = $domain_name;
            //}
            return $invoice_info;
          } else {
            return 'An error occured while retrieving invoice, please try again';            
          }
        
    }

    static function ListPaymentOptions($user_id){
        global $zdbh;
        
        $methods = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_payment_options 
                                    WHERE reseller_ac_id_fk=:user_id AND enabled_yn='1' 
                                    AND option_deleted_ts IS NULL ORDER BY payment_option_id ASC;");
                                    
        $methods->bindParam(':user_id', $user_id);
        $methods->execute();
        $res = array();
        $payment_option_id = 0;
        if (!fs_director::CheckForEmptyValue($methods)) {
            $x = 1;
            while ($row = $methods->fetch()) {
                $selected = 0;
                //$safe_name = strtolower(trim(str_replace(' ', '',$row['payment_option_name'])));
                $res['option_'.$x] = json_encode(array('name' => $row['payment_option_name'],
                                      'selected_yn' => $selected,'id' => $row['payment_option_id'],
                                      'html' => urlencode($row['payment_option_form_html'])));
                $x++;
            }
            return $res;
        } else {
            return 'An error occured while retrieving payment options!';
        }            
    }
    
    static function PayInvoice($user_id,$invoice_reference,$transaction_id,$payment_method_id,$payment_date){
        global $zdbh;
        
        if($user_id && $invoice_reference && $transaction_id && $payment_method_id && $payment_date){
            //fetch invoice info
             $res = $zdbh->prepare("SELECT invoice_id,ac_id_fk FROM ".self::$module_db.".x_invoices 
                                        WHERE invoice_reference=:ref;");
             $res->bindParam(':ref', $invoice_reference);
             $res->execute();
             if (!fs_director::CheckForEmptyValue($res)){
                $invoice_info = $res->fetch();
                if(is_array($invoice_info)){
                    //update invoice
                   $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_invoices 
                                            SET payment_option_id=:payment_method_id,
                                            transaction_id=:transaction_id,invoice_status='1' WHERE 
                                            reseller_ac_id_fk=:user_id AND invoice_id=:id");
                   $sql->bindParam(':payment_method_id', $payment_method_id);
                   $sql->bindParam(':transaction_id', $transaction_id);
                   $sql->bindParam(':user_id', $user_id);
                   $sql->bindParam(':id', $invoice_info['invoice_id']);
                   $sql->execute();
                   
                   //fetch orders and update
                    $orders = $zdbh->prepare("SELECT order_id
                                                FROM ".self::$module_db.".x_invoices_orders WHERE 
                                                ".self::$module_db.".x_invoices_orders.invoice_id=:id 
                                                ORDER BY ".self::$module_db.".x_invoices_orders.order_id 
                                                ASC;");
                    $orders->bindParam(':id', $invoice_info['invoice_id']);
                    $orders->execute();
                    $res = array();
                    
                    if (!fs_director::CheckForEmptyValue($orders)) {
                        while ($row = $orders->fetch()) {
                            $order_info = $zdbh->prepare("SELECT order_vh_fk,package_period_id_fk 
                                                        FROM ".self::$module_db.".x_orders WHERE 
                                                        ".self::$module_db.".x_orders.order_id=:id 
                                                        AND order_deleted_ts IS NULL;");
                            $order_info->bindParam(':id', $row['order_id']);
                            $order_info->execute();
                            $order_row = $order_info->fetch();
                            if(is_array($order_row)){
                                //update user
                                if(isset($invoice_info['ac_id_fk'])){
                                    $sql = $zdbh->prepare("UPDATE ".self::$server_app."_core.x_accounts 
                                                            SET ac_enabled_in='1' WHERE ac_id_pk=:uid");
                                    $sql->bindParam(':uid', $invoice_info['ac_id_fk']);
                                    $sql->execute();
                                }

                                
                               $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_orders 
                                                        SET order_status='1',order_complete_dated=:date 
                                                        WHERE reseller_ac_id_fk=:user_id AND order_id=:id");
                               $sql->bindParam(':date', $payment_date);
                               $sql->bindParam(':id', $row['order_id']);
                               $sql->bindParam(':user_id', $user_id);
                               $sql->execute();
                               
                               //update domain expiration
                               if(isset($order_row['order_vh_fk']) && isset($order_row['package_period_id_fk'])){
                                    $periods = $zdbh->prepare("SELECT period_duration 
                                                                FROM ".self::$module_db.".x_periods INNER 
                                                                JOIN ".self::$module_db.".x_packages_periods 
                                                                 ON 
                                        ".self::$module_db.".x_packages_periods.period_id=".self::$module_db.".x_periods.period_id 
                                          WHERE ".self::$module_db.".x_packages_periods.package_period_id=:id 
                                          AND period_deleted_ts IS NULL;");
                                    $periods->bindParam(':id', $order_row['package_period_id_fk']);
                                    $periods->execute();
                                    $period = $periods->fetch();

                                    if(isset($period['period_duration'])){
                                        /*
                                            Lets check if this domain is expired or not
                                            if it is, then we extend the renewal from payment date
                                            else, we extend from the domain's expiration date
                                        */
                                        $renewal_date = $payment_date;
                                        $sql = $zdbh->prepare("SELECT vh_expiry_ts FROM x_vhosts 
                                                                    WHERE vh_id_pk=:id");
                                        $sql->bindParam(':id', $order_row['order_vh_fk']);
                                        $sql->execute();
                                        $domain_info = $sql->fetch();
                                        $domain_expiry = date("Y-m-d", $domain_info['vh_expiry_ts']);
                                        if($domain_expiry > date("Y-m-d")){
                                            $renewal_date = $domain_expiry;
                                        }
                                        
                                        $new_expiry_date = strtotime($renewal_date."+".$period['period_duration']." months");
                                        $sql = $zdbh->prepare("UPDATE ".self::$server_app."_core.x_vhosts 
                                                                SET vh_expiry_ts=:date,vh_enabled_in='1',
                                                                vh_invoice_created_yn='0' 
                                                                WHERE vh_id_pk=:domain_id");
                                        $sql->bindParam(':date', $new_expiry_date);
                                        $sql->bindParam(':domain_id', $order_row['order_vh_fk']);
                                        $sql->execute();
                                    
                                    }
                                    
                                
                               }
                            
                            
                            }
                        }
                    }            
                   
                    
                }
             }
             
             //generate and save new user password
               $user_password = fs_director::GenerateRandomPassword(ctrl_options::GetSystemOption('password_minlength'), 4);
               
                $crypto = new runtime_hash;
                $crypto->SetPassword($user_password);
                $randomsalt = $crypto->RandomSalt();
                $crypto->SetSalt($randomsalt);
                $secure_password = $crypto->CryptParts($crypto->Crypt())->Hash;
                
                //update user information
               $sql = $zdbh->prepare("UPDATE ".self::$server_app."_core.x_accounts SET ac_enabled_in='1',

                                        ac_pass_vc=:password,ac_passsalt_vc=:pass_salt 

                                        WHERE ac_id_pk=:user_id");
               $sql->bindParam(':user_id', $invoice_info['ac_id_fk']);
               $sql->bindParam(':password', $secure_password);
               $sql->bindParam(':pass_salt', $randomsalt);
               $sql->execute();
             
             
             //fetch welcome message and login info
             $user_info = ctrl_users::GetUserDetail($invoice_info['ac_id_fk']);
             $emailbody = self::appSetting($user_id,'welcome_message');                
             $username = $user_info['username'];
             //$user_password = $secure_password;
             $panel_url = 'http://'.self::getPanelURL();
             
             //fetch company name
             $company_name = self::appSetting($user_id,'company_name');


             if($emailbody && $user_info['email']){
                 $emailbody = str_replace("{{fullname}}", $user_info['fullname'], $emailbody);
                 $emailbody = str_replace("{{company_name}}", $company_name, $emailbody);
                 //$emailbody = str_replace("{{invoice_link}}", $invoice_link, $emailbody);
                 //$emailbody = str_replace("{{invoice_reference}}", $invoice_info['reference'], $emailbody);
                 //$emailbody = str_replace("{{invoice_unpaid_days}}", $inv_del_days, $emailbody);
                 $emailbody = str_replace("{{username}}", $username, $emailbody);
                 $emailbody = str_replace("{{password}}", $user_password, $emailbody);
                 $emailbody = str_replace("{{panel_url}}", $panel_url, $emailbody);

                 //$phpmailer = new sys_email();
                 $subject = "Welcome to $company_name!";
                 self::sendMail(array('to' => $user_info['email'], 'subject' => $subject, 'message' => $emailbody, 'reseller_id' => $user_id));
                 /*$phpmailer->Body = $emailbody;
                 $phpmailer->AddAddress($user_info['email']);
                 $phpmailer->SendEmail();*/
             }                
             


             
            return true;            
        } else {
            return false;        
        }
    }
    
    static function RemindInvoices($user_id){
        global $zdbh;
        
        $invoice_reminder_days = self::appSetting($user_id,'invoice_reminder_days');
        $inv_del_days = self::appSetting($user_id,'pending_invoice_delete_days');

        if(is_numeric($invoice_reminder_days) && $invoice_reminder_days > 0){
            $invoice_reminder_message = self::appSetting($user_id,'invoice_reminder_message');
            $billing_url = self::appSetting($user_id,'website_billing_url');


            //fetch invoices
             $res = $zdbh->prepare("SELECT invoice_id,ac_id_fk,invoice_reference,invoice_dated 
                                        FROM ".self::$module_db.".x_invoices 
                                          WHERE invoice_status='0' AND reminder_sent_yn='0' 
                                          AND reseller_ac_id_fk=:uid AND invoice_deleted_ts IS NULL;");
             $res->bindParam(':uid', $user_id);
             $res->execute();
             $today_date = date("Y-m-d");
             
             while ($row = $res->fetch()){
                $remind_date = date("Y-m-d", strtotime($row['invoice_dated']." -".$invoice_reminder_days."days"));

                $due_date = date("Y-m-d", strtotime($row['invoice_dated']." +".$inv_del_days."days"));

                if($billing_url){
                    $invoice_link = $billing_url.'/view_invoice.php?invoice='.$row['invoice_reference'];
                }                

                if($today_date >= $remind_date){
                    $user_info = ctrl_users::GetUserDetail($row['ac_id_fk']);
                    $panel_url = 'http://'.self::getPanelURL();
                    
                    //send reminder
                    if($invoice_reminder_message && $user_info['email']){
                        $emailbody = $invoice_reminder_message;

                        //fetch company name
                        $company_name = self::appSetting($user_id,'company_name');

                        $emailbody = str_replace("{{fullname}}", $user_info['fullname'], $emailbody);
                        $emailbody = str_replace("{{company_name}}", $company_name, $emailbody);
                        $emailbody = str_replace("{{invoice_link}}", $invoice_link, $emailbody);
                        $emailbody = str_replace("{{invoice_reference}}", $row['invoice_reference'], $emailbody);
                        $emailbody = str_replace("{{invoice_due_date}}", $due_date, $emailbody);
                        $emailbody = str_replace("{{panel_url}}", $panel_url, $emailbody);

                        //$phpmailer = new sys_email();
                        $subject = "Your Invoice #".$row['invoice_reference'].' at '.$company_name;
                        self::sendMail(array('to' => $user_info['email'], 'subject' => $subject, 'message' => $emailbody));
                            
                        /*$phpmailer->Body = $emailbody;
                        $phpmailer->AddAddress($user_info['email']);
                        $phpmailer->SendEmail(); */
                            
                        //update reminder sent
                        $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_invoices
                                                 SET reminder_sent_yn='1' WHERE invoice_id=:id");
                        $sql->bindParam(':id', $row['invoice_id']);
                        $sql->execute();
                    }
                }
             }
        }
    }

    static function DomainRemindExpiration($user_id){
        global $zdbh;
        
        $renewal_reminder_days = self::appSetting($user_id,'renewal_reminder_days');
        $delete_expired_days = self::appSetting($user_id,'pending_invoice_delete_days');

        if(is_numeric($renewal_reminder_days) && $renewal_reminder_days > 0){
            $renewal_reminder_message = self::appSetting($user_id,'renewal_reminder_message');
            $billing_url = self::appSetting($user_id,'website_billing_url');

            //fetch domains
             $res = $zdbh->prepare("SELECT vh_id_pk,vh_acc_fk,vh_name_vc,vh_expiry_ts,vh_invoice_created_yn 
                                        FROM x_vhosts INNER JOIN x_accounts
                                        ON x_accounts.ac_id_pk=x_vhosts.vh_acc_fk 
                                          WHERE expiration_reminder_sent_yn='0' 
                                          AND x_accounts.ac_reseller_fk=:uid 
                                          AND ac_deleted_ts IS NULL AND vh_deleted_ts IS NULL;");
             $res->bindParam(':uid', $user_id);
             $res->execute();
             $today_date = date("Y-m-d");

             while ($row = $res->fetch()){
                if($row['vh_expiry_ts']){
                    $expiry_date = date("Y-m-d", ($row['vh_expiry_ts']));
                    $remind_date = date("Y-m-d", strtotime($expiry_date." -".$renewal_reminder_days."days"));

                    if($today_date >= $remind_date){
                        //is there an existing invoice for this domain? This way, 
                        //we don't create a duplicate invoice
                        if($row['vh_invoice_created_yn'] == 1){
                            //select invoice reference
                            $invoice = $zdbh->prepare("SELECT invoice_reference FROM ".self::$module_db.".x_invoices 
                                                           INNER JOIN ".self::$module_db.".x_invoices_orders ON 
                                                           ".self::$module_db.".x_invoices_orders.invoice_id=".self::$module_db.".x_invoices.invoice_id
                                                           INNER JOIN ".self::$module_db.".x_orders ON 
                                                           ".self::$module_db.".x_orders.order_id=".self::$module_db.".x_invoices_orders.order_id
                                                           WHERE ".self::$module_db.".x_orders.order_vh_fk=:id AND invoice_status='0' 
                                                           AND order_deleted_ts IS NULL 
                                                           ORDER BY ".self::$module_db.".x_invoices.invoice_id DESC;");
                            $invoice->bindParam(':id', $row['vh_id_pk']);
                            $invoice->execute();
                            $invoice_info = $invoice->fetch();
                            $invoice_reference = $invoice_info['invoice_reference'];
                        } else {
                            $invoice_reference = self::CreateRenewalInvoice($row['vh_id_pk']);                        
                        }
                        
                       

                        $user_info = ctrl_users::GetUserDetail($row['vh_acc_fk']);
                        
                        //send reminder
                        if($renewal_reminder_message && $user_info['email']){
                            $emailbody = $renewal_reminder_message;
                            $invoice_link = '';

                            //fetch company name
                            $company_name = self::appSetting($user_id,'company_name');

                            if($billing_url){
                                $invoice_link = $billing_url.'/view_invoice.php?invoice='.$invoice_reference;
                            }


                            $emailbody = str_replace("{{fullname}}", $user_info['fullname'], $emailbody);
                            $emailbody = str_replace("{{company_name}}", $company_name, $emailbody);
                            $emailbody = str_replace("{{domain_name}}", $row['vh_name_vc'], $emailbody);
                            $emailbody = str_replace("{{expiry_date}}", $expiry_date, $emailbody);
                            $emailbody = str_replace("{{delete_expired_days}}", $delete_expired_days, $emailbody);

                            $emailbody = str_replace("{{invoice_link}}", $invoice_link, $emailbody);
                            
                            //$phpmailer = new sys_email();
                            $subject = "Your Invoice #".$invoice_reference.' at '.$company_name;
                            self::sendMail(array('to' => $user_info['email'], 'subject' => $subject, 'message' => $emailbody));
                                
                            /*$phpmailer->Body = $emailbody;
                            $phpmailer->AddAddress($user_info['email']);
                            $phpmailer->SendEmail(); */
                                
                            //update reminder sent
                            $sql = $zdbh->prepare("UPDATE x_vhosts SET expiration_reminder_sent_yn='1' 
                                                    WHERE vh_id_pk=:id");
                            $sql->bindParam(':id', $row['vh_id_pk']);
                            $sql->execute();
                        }
                    }
                
                }
             }
        }    
    }
    
    static function CreateRenewalInvoice($domain_id){
        global $zdbh;
        
        $invoice_id = 0;
        
        //fetch domain info
          $sql = $zdbh->prepare("SELECT * FROM x_vhosts WHERE vh_id_pk='".$domain_id."' 
                                    AND vh_deleted_ts IS NULL;");
          $sql->bindParam(':id', $row['order_id']);
          $sql->execute();
          $domain_info = $sql->fetch();
          
          //make sure we have a domain name
          if(!is_array($domain_info)){
            return $invoice_id;
          }

          //fetch previous order information
          $numrows = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_orders INNER JOIN ".self::$module_db.".x_invoices_orders 
                                      ON ".self::$module_db.".x_invoices_orders.order_id=".self::$module_db.".x_orders.order_id 
                                      INNER JOIN ".self::$module_db.".x_invoices 
                                      ON ".self::$module_db.".x_invoices.invoice_id=".self::$module_db.".x_invoices_orders.invoice_id
                                      WHERE ".self::$module_db.".x_orders.order_vh_fk=:domain_id 
                                      AND ".self::$module_db.".x_orders.order_deleted_ts IS NULL 
                                      ORDER BY ".self::$module_db.".x_orders.order_id DESC LIMIT 1;");
          $numrows->bindParam(':domain_id', $domain_info['vh_id_pk']);
          $numrows->execute();
          $order_info = $numrows->fetch();
          //if there are no previous orders, return
          if(!is_array($order_info)){
              return $invoice_id;
          }
            
          //fetch previous invoice information
          /*$numrows = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_orders 
                                      WHERE order_vh_fk=:domain_id 
                                      AND order_deleted_ts IS NULL ORDER BY order_id DESC;");
          $numrows->bindParam(':domain_id', $domain_info['vh_id_pk']);
          $numrows->execute();
          $order_info = $numrows->fetch();
          //if there are no previous orders, return
          if(!is_array($order_info)){
              return $invoice_id;
          }*/

          if(isset($order_info['invoice_voucher_id']) && $order_info['invoice_voucher_id'] > 0){
              //fetch voucher info
              $numrows = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_vouchers 
                                            WHERE voucher_id=:vid AND voucher_deleted_ts IS NULL;");
              $numrows->bindParam(':vid', $order_info['invoice_voucher_id']);
              $numrows->execute();
              $voucher_info = $numrows->fetch();

          }
            
          $order_complete_dated = '0000-00-00 00:00';
          $enable_billing_yn = self::appSetting($order_info['reseller_ac_id_fk'],'billing_enabled_yn');
          $order_invoice_status = 0;
          if(!$enable_billing_yn){
             $order_invoice_status = 1;
             $order_complete_dated = date("Y-m-d H:i");
          }
            
          $datetime = date("Y-m-d H:i");
          //fetch order amount
          $numrows = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_packages_periods 
                                        WHERE package_period_id=:pkg_pid;");
          $numrows->bindParam(':pkg_pid', $order_info['package_period_id_fk']);
          $numrows->execute();
          $package_period = $numrows->fetch();
            
          //fetch package name
          $numrows = $zdbh->prepare("SELECT pk_name_vc FROM ".self::$server_app.".x_packages 
                                        INNER JOIN ".self::$module_db.".x_packages ON 
                                        ".self::$module_db.".x_packages.zpx_package_id=".self::$server_app.".x_packages.pk_id_pk
                                        WHERE ".self::$module_db.".x_packages.reseller_ac_id_fk=:uid 
                                        AND ".self::$module_db.".x_packages.zpx_package_id=:pkg_id 
                                        AND pk_deleted_ts IS NULL;");
          $numrows->bindParam(':uid', $order_info['reseller_ac_id_fk']);
          $numrows->bindParam(':pkg_id', $package_period['zpx_package_id']);
          $numrows->execute();
          $package = $numrows->fetch();
          
          $order_desc = $package['pk_name_vc'];
            
          //fetch period duration
          $numrows = $zdbh->prepare("SELECT period_duration FROM ".self::$module_db.".x_periods 
                                        WHERE reseller_ac_id_fk=:uid AND period_id=:pid 
                                        AND period_deleted_ts IS NULL");
          $numrows->bindParam(':uid', $order_info['reseller_ac_id_fk']);
          $numrows->bindParam(':pid', $package_period['period_id']);
          $numrows->execute();
          $period = $numrows->fetch();
          
          if(isset($period['period_duration'])){
              $order_desc .= ' Hosting Package for '.$period['period_duration'].' Month';
              if($period['period_duration'] > 1){
                 $order_desc .= 's';
              }
          }
            
            $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_orders (ac_id_fk,
                                    reseller_ac_id_fk,order_vh_fk,order_dated,order_amount, order_desc,
									package_period_id_fk,order_status,order_type_id,order_complete_dated
									) VALUES (:userID,:zpx_uid,:domain_id,:date,:amount,:desc,:pkg_pid,
									'".$order_invoice_status."', '2', '".$order_complete_dated."')");
            $sql->bindParam(':userID', $order_info['ac_id_fk']);
            $sql->bindParam(':zpx_uid', $order_info['reseller_ac_id_fk']);
            $sql->bindParam(':date', $datetime);
            $sql->bindParam(':amount', $package_period['package_amount']);
            $sql->bindParam(':desc', $order_desc);
            $sql->bindParam(':pkg_pid', $package_period['package_period_id']);
            $sql->bindParam(':domain_id', $domain_id);
            $sql->execute();
            
            //fetch newly created order id
            $numrows = $zdbh->prepare("SELECT order_id FROM ".self::$module_db.".x_orders 

                                        WHERE reseller_ac_id_fk=:zpx_uid AND ac_id_fk=:uid 
                                        AND order_dated=:datetime");
            $numrows->bindParam(':uid', $order_info['ac_id_fk']);
            $numrows->bindParam(':zpx_uid', $order_info['reseller_ac_id_fk']);
            $numrows->bindParam(':datetime', $datetime);
            $numrows->execute();
            $order = $numrows->fetch();
            
            if(isset($order['order_id'])){
                $order_id = $order['order_id'];
                $invoice_reference = self::randomString(7);                

                $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_invoices (
                                        reseller_ac_id_fk,invoice_dated,
                                        invoice_total_amount, invoice_reference,
                                        invoice_status,ac_id_fk
									    ) VALUES (:zpx_uid,:date,:amount,
									    :ref, '".$order_invoice_status."',:uid)");
                $sql->bindParam(':uid', $order_info['ac_id_fk']);
                $sql->bindParam(':zpx_uid', $order_info['reseller_ac_id_fk']);
                $sql->bindParam(':date', $datetime);
                $sql->bindParam(':amount', $package_period['package_amount']);
                $sql->bindParam(':ref', $invoice_reference);
                $sql->execute();

                //fetch newly created invoice id
                $numrows = $zdbh->prepare("SELECT invoice_id FROM ".self::$module_db.".x_invoices 
                                            WHERE reseller_ac_id_fk=:zpx_uid AND ac_id_fk=:uid 
                                            AND invoice_reference=:ref");
                $numrows->bindParam(':uid', $order_info['ac_id_fk']);
                $numrows->bindParam(':zpx_uid', $order_info['reseller_ac_id_fk']);
                $numrows->bindParam(':ref', $invoice_reference);
                $numrows->execute();
                $invoice = $numrows->fetch();
                
                if(isset($invoice['invoice_id'])){
                    //create invoice and order relationship                    
                    $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_invoices_orders (
                                            invoice_id,order_id

                                            ) VALUES ('".$invoice['invoice_id']."',
                                            '".$order['order_id']."')");
                    $sql->execute();
                }
                
                //update invoice created
                $sql = $zdbh->prepare("UPDATE x_vhosts SET vh_invoice_created_yn='1' WHERE vh_id_pk=:id");
                $sql->bindParam(':id', $domain_id);
                $sql->execute();
                
                
                return $invoice_reference;
            }
    }
    
    static function DisableExpiredDomainProcess($user_id){
        global $zdbh;
        
        //$delete_expired_domain_days = self::appSetting($user_id,'pending_invoice_delete_days');
        //$delete_expired_days = self::appSetting($user_id,'pending_invoice_delete_days');

        //if(is_numeric($delete_expired_domain_days) && $delete_expired_domain_days > 0){
            //fetch domains
             $res = $zdbh->prepare("SELECT vh_id_pk,vh_acc_fk,vh_name_vc,vh_expiry_ts 
                                        FROM x_vhosts INNER JOIN x_accounts
                                        ON x_accounts.ac_id_pk=x_vhosts.vh_acc_fk 
                                          WHERE x_accounts.ac_reseller_fk=:uid AND vh_enabled_in='1';");
             $res->bindParam(':uid', $user_id);
             $res->execute();
             $today_date = date("Y-m-d");
             while ($row = $res->fetch()){
                if($row['vh_expiry_ts']){
                    $expiry_date = date("Y-m-d", ($row['vh_expiry_ts']));
                    $today_date = date("Y-m-d", strtotime(date("Y-m-d")));
                    //$expiry_delete_date = date("Y-m-d", strtotime($expiry_date." -".$delete_expired_domain_days."days"));


                    if(($expiry_date <= $today_date)){
                        $user_info = ctrl_users::GetUserDetail($row['vh_acc_fk']);
                        
                        //mark domain as disabled
                         $sql = $zdbh->prepare("UPDATE x_vhosts SET vh_enabled_in='0' 
                                                  WHERE vh_id_pk=:id");
                         $sql->bindParam(':id', $row['vh_id_pk']);
                         $sql->execute();
                        
                    }

                
                }
             }
        //}
    }
    
    static function DeleteExpiredDomainProcess($user_id){
        global $zdbh;
        
        $delete_expired_domain_days = self::appSetting($user_id,'pending_invoice_delete_days');
        //$delete_expired_days = self::appSetting($user_id,'pending_invoice_delete_days');

        if(is_numeric($delete_expired_domain_days) && $delete_expired_domain_days > 0){
            //fetch domains
             $res = $zdbh->prepare("SELECT vh_id_pk,vh_acc_fk,vh_name_vc,vh_expiry_ts 
                                        FROM x_vhosts INNER JOIN x_accounts
                                        ON x_accounts.ac_id_pk=x_vhosts.vh_acc_fk 
                                          WHERE x_vhosts.vh_acc_fk=x_accounts.ac_id_pk 
                                          AND x_accounts.ac_reseller_fk=:uid 
                                          AND vh_deleted_ts IS NULL AND vh_expiry_ts > 0;");
             $res->bindParam(':uid', $user_id);
             $res->execute();
             
             $today_date = date("Y-m-d");
             while ($row = $res->fetch()){
                if($row['vh_expiry_ts'] > 0){
                    $expiry_date = date("Y-m-d", ($row['vh_expiry_ts']));
                    $today_date = date("Y-m-d", strtotime(date("Y-m-d")));
                    $expiry_delete_date = date("Y-m-d", strtotime($expiry_date." -".$delete_expired_domain_days."days"));

                    if(($expiry_date <= $today_date) && ($expiry_delete_date <= $today_date)){
                        $user_info = ctrl_users::GetUserDetail($row['vh_acc_fk']);
                        
                        //mark domain as deleted
                         $sql = $zdbh->prepare("UPDATE x_vhosts SET vh_deleted_ts='".time()."' 
                                                  WHERE vh_id_pk=:id");
                         $sql->bindParam(':id', $row['vh_id_pk']);
                         $sql->execute();
                        
                    }
                   
                   self::deleteDomainAccount($user_id);
                }
             }
        }
        
    }
    
    static function deleteDomainAccount($user_id){
        global $zdbh;
        
             $res = $zdbh->prepare("SELECT vh_acc_fk FROM x_vhosts INNER JOIN x_accounts
                                        ON x_accounts.ac_id_pk=x_vhosts.vh_acc_fk 
                                          WHERE x_vhosts.vh_acc_fk=x_accounts.ac_id_pk 
                                          AND x_accounts.ac_reseller_fk=:uid AND vh_deleted_ts IS NULL;");
             $res->bindParam(':uid', $user_id);
             $res->execute();
             
             while ($row = $res->fetch()){
                //ignore and move on if this is zadmin user.
                if($row['vh_acc_fk'] <> 1){
                     //now let's see if this user have all their domains deleted, if so, deleted the user as well
                     $res_acc = $zdbh->prepare("SELECT COUNT(vh_id_pk) FROM x_vhosts WHERE 
                                                vh_acc_fk=:uid AND vh_deleted_ts IS NOT NULL;");
                     $res_acc->bindParam(':uid', $row['vh_acc_fk']);
                     $res_acc->execute();
                     if($res_acc->fetchColumn() == 0){
                        //delete user account
                        $sql = $zdbh->prepare("UPDATE x_accounts SET ac_deleted_ts='".time()."' 
                                                  WHERE ac_id_pk=:id AND ac_deleted_ts IS NULL");
                         $sql->bindParam(':id', $row['vh_acc_fk']);
                         $sql->execute();
                     }
                }
             }
    }

    static function getVoucher($user_id, $code){
        global $zdbh;
        
        //$currentuser = ctrl_users::GetUserDetail();
        $vouchers = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_vouchers 
                                        WHERE reseller_ac_id_fk=:user_id AND voucher_code=:code 
                                        AND voucher_deleted_ts IS NULL;");
        $vouchers->bindParam(':user_id', $user_id);
        $vouchers->bindParam(':code', $code);
        $vouchers->execute();
        
        //return $vouchers->fetch();
        
        $res = array();
        if ($voucher = $vouchers->fetch()) {
            //$voucher = $vouchers->fetch();
            //while ($row = $vouchers->fetch()) {
               if(!$voucher['active_yn']){
               	return false;
               }


               //usages
               $total_usages = self::getVoucherUsages($voucher['voucher_id']);	      
               if($voucher['usage_type'] == 1 && $total_usages > 0){
               	    return false;
               }
            //}
            return $voucher;
        } else {
            return false;
        }            
    }

    /* Web Service */

    /* Executions */
    static function ExecuteCreateClient($data) {
        global $zdbh;

        //check for and validate domain
        if($data['domain']){
            $domain_res = self::CheckCreateDomainForErrors($data['domain'], $data['package_id'], $data['zpx_uid']);
            if($domain_res !== true){
                return $domain_res;
            }
        }
        
        
        // Check for spaces and remove if found...
        $username = strtolower(str_replace(' ', '', $data['username']));
        //set user group to Users
        $groupid = 3;
        //generate password
        //$password = self::rand_string(ctrl_options::GetSystemOption('password_minlength'));
        if(!isset($data['password'])){
            $password = fs_director::GenerateRandomPassword(ctrl_options::GetSystemOption('password_minlength'), 4);
        } else {
            $password = $data['password'];
        }
        $reseller = ctrl_users::GetUserDetail($data['zpx_uid']);
        // Check for errors before we continue...
        if (!fs_director::CheckForEmptyValue(self::CheckCreateForErrors($username, $data['package_id'], $groupid, $data['email_address'], $password))) {
            return false;
        }
        runtime_hook::Execute('OnBeforeCreateClient');

        $crypto = new runtime_hash;
        $crypto->SetPassword($password);
        $randomsalt = $crypto->RandomSalt();
        $crypto->SetSalt($randomsalt);
        $secure_password = $crypto->CryptParts($crypto->Crypt())->Hash;

        // No errors found, so we can add the user to the database...
        $sql = $zdbh->prepare("INSERT INTO x_accounts (ac_user_vc, ac_pass_vc, ac_passsalt_vc,
                                 ac_email_vc, ac_package_fk, ac_group_fk, ac_usertheme_vc, 
                                 ac_usercss_vc, ac_reseller_fk, ac_created_ts, ac_enabled_in
                                 ) VALUES (:username, :password, :passsalt, :email, :packageid, :groupid, 
                                 :resellertheme, :resellercss, :uid, :time, '0')");
        $sql->bindParam(':uid', $data['zpx_uid']);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->bindParam(':username', $username);
        $sql->bindParam(':password', $secure_password);
        $sql->bindParam(':passsalt', $randomsalt);
        $sql->bindParam(':email', $data['email_address']);
        $sql->bindParam(':packageid', $data['package_id']);
        $sql->bindParam(':groupid', $groupid);
        $sql->bindParam(':resellertheme', $reseller['usertheme']);
        $sql->bindParam(':resellercss', $reseller['usercss']);
        $sql->execute();

        // Now lets pull back the client ID so that we can add their personal address details etc...
        $numrows = $zdbh->prepare("SELECT * FROM x_accounts WHERE ac_reseller_fk=:uid ORDER BY ac_id_pk DESC");
        $numrows->bindParam(':uid', $data['zpx_uid']);
        $numrows->execute();
        $client = $numrows->fetch();
        
        
        $sql = $zdbh->prepare("INSERT INTO x_profiles (ud_user_fk, ud_fullname_vc, ud_group_fk, ud_package_fk, ud_address_tx, ud_postcode_vc, ud_phone_vc, ud_created_ts) VALUES (:userid, :fullname, :packageid, :groupid, :address, :postcode, :phone, :time)");
        $sql->bindParam(':userid', $client['ac_id_pk']);
        $sql->bindParam(':fullname', $data['fullname']);
        $sql->bindParam(':packageid', $data['package_id']);
        $sql->bindParam(':groupid', $groupid);
        $sql->bindParam(':address', $data['address']);
        $sql->bindParam(':postcode', $data['postal_code']);
        $sql->bindParam(':phone', $data['phone']);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        // Now we add an entry into the bandwidth table, for the user for the upcoming month.
        $sql = $zdbh->prepare("INSERT INTO x_bandwidth (bd_acc_fk, bd_month_in, bd_transamount_bi, bd_diskamount_bi) VALUES (:ac_id_pk, :date, 0, 0)");
        $date = date("Ym", time());
        $sql->bindParam(':date', $date);
        $sql->bindParam(':ac_id_pk', $client['ac_id_pk']);
        $sql->execute();
        // Lets create the client diectories
        fs_director::CreateDirectory(ctrl_options::GetSystemOption('hosted_dir') . $username);
        fs_director::SetFileSystemPermissions(ctrl_options::GetSystemOption('hosted_dir') . $username, 0777);
        fs_director::CreateDirectory(ctrl_options::GetSystemOption('hosted_dir') . $username . "/public_html");
        fs_director::SetFileSystemPermissions(ctrl_options::GetSystemOption('hosted_dir') . $username . "/public_html", 0777);
        fs_director::CreateDirectory(ctrl_options::GetSystemOption('hosted_dir') . $username . "/backups");
        fs_director::SetFileSystemPermissions(ctrl_options::GetSystemOption('hosted_dir') . $username . "/backups", 0777);
        
        
        if($data['domain']){
            if(!isset($data['autohome'])){
                $data['autohome'] = 1;
            }

            if(!isset($data['destination'])){
                $data['destination'] = "";
            }
            $domain_id = self::CreateUserDomain($client['ac_id_pk'], $data['domain'],$data['zpx_uid'],$data['period_id'],$data['package_id'],$data['autohome'],$data['destination']);
            if(!$domain_id){
                return $domain_id;
            }
            $client['domain_id'] = $domain_id;
        }
        
        runtime_hook::Execute('OnAfterCreateClient');
        //self::$resetform = true;
        //self::$ok = true;
        return $client;
    }
    
    /* Create User Domain */
    static function CreateUserDomain($uid, $domain, $zpx_uid, $period_id, $package_id,$autohome=1,$destination=""){
        global $zdbh,$controller;
        
        if($uid && $domain){
            $retval = false;
            runtime_hook::Execute('OnBeforeAddDomain');
            $currentuser = ctrl_users::GetUserDetail($uid);
            $domain = strtolower(str_replace(' ', '', $domain));
            if (!fs_director::CheckForEmptyValue(self::CheckCreateDomainForErrors($domain, $package_id, $zpx_uid))){
               /*$destination = "/" . str_replace(".", "_", $domain);
               $vhost_path = ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html/" . $destination . "/";
               fs_director::CreateDirectory($vhost_path);
               fs_director::SetFileSystemPermissions($vhost_path, 0777);*/
                //** New Home Directory **//
                if ($autohome == 1) {
                    $destination = "/" . str_replace(".", "_", $domain);
                    $vhost_path = ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html/" . $destination . "/";
                    fs_director::CreateDirectory($vhost_path);
                    fs_director::SetFileSystemPermissions($vhost_path, 0777);
                    //** Existing Home Directory **//
                } else {
                    $destination = "/" . $destination;
                    $vhost_path = ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html/" . $destination . "/";
                }
               

                // Copy error pages over
                fs_director::CreateDirectory($vhost_path . "/_errorpages/");
                $errorpages = ctrl_options::GetSystemOption('static_dir') . "/errorpages/";
                if (is_dir($errorpages)) {
                    if ($handle = @opendir($errorpages)) {
                        while (($file = @readdir($handle)) !== false) {
                            if ($file != "." && $file != "..") {
                                $page = explode(".", $file);
                                if (!fs_director::CheckForEmptyValue(self::CheckErrorDocument($page[0]))) {
                                    fs_filehandler::CopyFile($errorpages . $file, $vhost_path . '/_errorpages/' . $file);
                                }
                            }
                        }
                        closedir($handle);
                    }
                }
                
                // Lets copy the default welcome page across...
                if ((!file_exists($vhost_path . "/index.html")) && (!file_exists($vhost_path . "/index.php")) && (!file_exists($vhost_path . "/index.htm"))) {
                    fs_filehandler::CopyFileSafe(ctrl_options::GetSystemOption('static_dir') . "pages/welcome.html", $vhost_path . "/index.html");
                }
                // If all has gone well we need to now create the domain in the database...
                $enabled_yn = 0;
                $billing_enabled_yn = self::appSetting($zpx_uid,'billing_enabled_yn');
                if(!$billing_enabled_yn || $period_id == '-1'){
                    $enabled_yn = 1;
                }

                $sql = $zdbh->prepare("INSERT INTO x_vhosts (vh_acc_fk,
														     vh_name_vc,
														     vh_directory_vc,
														     vh_type_in,
														     vh_enabled_in,
														     vh_created_ts,vh_invoice_created_yn,
														     vh_expiry_ts) VALUES (
														     :userid,
														     :domain,
														     :destination,
														     1,
														     :enabled_yn,
														     :time,'1',:expiry_time)"); //CLEANER FUNCTION ON $domain and $homedirectory_to_use (Think I got it?)
                $time = time();
                $expiry_time = time();
                if($enabled_yn){
                    $expiry_time = 0;
                }
                $sql->bindParam(':time', $time);
                $sql->bindParam(':expiry_time', $expiry_time);
                $sql->bindParam(':userid', $currentuser['userid'] );
                $sql->bindParam(':domain', $domain);
                $sql->bindParam(':destination', $destination);
                $sql->bindParam(':enabled_yn', $enabled_yn);
                $sql->execute();

                # Only run if the Server platform is Windows.
                if (sys_versions::ShowOSPlatformVersion() == 'Windows') {
                    if (ctrl_options::GetSystemOption('disable_hostsen') == 'false') {
                        # Lets add the hostname to the HOSTS file so that the server can view the domain immediately...
                        @exec("C:/zpanel/bin/zpss/setroute.exe " . $domain . "");
                        @exec("C:/zpanel/bin/zpss/setroute.exe www." . $domain . "");
                    }
                }
        	    self::CreateDefaultRecords($domain, $currentuser['userid']);           
                self::SetWriteApacheConfigTrue();
                //$retval = TRUE;
                runtime_hook::Execute('OnAfterAddDomain');
                
                // Now lets pull back the domain ID so that we can return it
                $numrows = $zdbh->prepare("SELECT vh_id_pk FROM x_vhosts WHERE vh_name_vc=:domain;");
                $numrows->bindParam(':domain', $domain);
                $numrows->execute();
                $domain = $numrows->fetch();
                
                if(isset($domain['vh_id_pk'])){
                  return $domain['vh_id_pk'];
                }
            }
            
        }
    }
    
    static function CreateDefaultRecords($domain, $user_id) {
        global $zdbh;
        global $controller;

        $numrows = $zdbh->prepare("SELECT * FROM x_vhosts WHERE vh_name_vc=:domain AND vh_type_in !=2 AND vh_deleted_ts IS NULL");
        $numrows->bindParam(':domain', $domain);
        $numrows->execute();
        $domain_info = $numrows->fetch();

        if (!fs_director::CheckForEmptyValue(ctrl_options::GetSystemOption('server_ip'))) {
            $target = ctrl_options::GetSystemOption('server_ip');
        } else {
            $target = $_SERVER["SERVER_ADDR"]; //This needs checking on windows 7 we may need to use LOCAL_ADDR :- Sam Mottley
        }
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk,
															dn_name_vc,
															dn_vhost_fk,
															dn_type_vc,
															dn_host_vc,
															dn_ttl_in,
															dn_target_vc,
															dn_priority_in,
															dn_weight_in,
															dn_port_in,
															dn_created_ts) VALUES (
															:userID,
															:vh_name_vc,
															:domainID,
															'A',
															'@',
															3600,
															:target,
															NULL,
															NULL,
															NULL,
															:time)");
        $sql->bindParam(':userID', $user_id);
        $sql->bindParam(':vh_name_vc', $domain);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $sql->bindParam(':target', $target);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk, dn_name_vc, dn_vhost_fk,
                                dn_type_vc,	dn_host_vc,	dn_ttl_in, dn_target_vc,
                                dn_priority_in,	dn_weight_in, dn_port_in, dn_created_ts
                                ) VALUES (:userID,:vh_name_vc,:domainID,'CNAME',
                                'www',3600,'@', NULL,NULL,NULL,:time)");
        $sql->bindParam(':userID', $user_id);
        $sql->bindParam(':vh_name_vc', $domain);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk,dn_name_vc,	dn_vhost_fk,
                                dn_type_vc,	dn_host_vc,	dn_ttl_in, dn_target_vc,
                                dn_priority_in,	dn_weight_in, dn_port_in, dn_created_ts
                                ) VALUES (:userID,:vh_name_vc,:domainID,'CNAME','ftp',
                                3600,'@',NULL,NULL,	NULL,:time)");
        $sql->bindParam(':userID', $user_id);
        $sql->bindParam(':vh_name_vc', $domain);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk,
															dn_name_vc,
															dn_vhost_fk,
															dn_type_vc,
															dn_host_vc,
															dn_ttl_in,
															dn_target_vc,
															dn_priority_in,
															dn_weight_in,
															dn_port_in,
															dn_created_ts) VALUES (
															:userID,
															:vh_name_vc,
															:domainID,
															'A',
															'mail',
															86400,
															:target,
															NULL,
															NULL,
															NULL,
															:time)");
        $sql->bindParam(':userID', $user_id);
        $sql->bindParam(':vh_name_vc', $domain);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $sql->bindParam(':target', $target);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk,
															dn_name_vc,
															dn_vhost_fk,
															dn_type_vc,
															dn_host_vc,
															dn_ttl_in,
															dn_target_vc,
															dn_priority_in,
															dn_weight_in,
															dn_port_in,
															dn_created_ts) VALUES (
															:userID,
															:vh_name_vc,
															:domainID,
															'MX',
															'@',
															86400,
															:vh_name_vc,
															10,
															NULL,
															NULL,
															:time)");
        $sql->bindParam(':userID', $user_id);
        $vh_name_vc = 'mail.' . $domain;
        $sql->bindParam(':vh_name_vc', $vh_name_vc);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $sql->bindParam(':target', $target);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk,
															dn_name_vc,
															dn_vhost_fk,
															dn_type_vc,
															dn_host_vc,
															dn_ttl_in,
															dn_target_vc,
															dn_priority_in,
															dn_weight_in,
															dn_port_in,
															dn_created_ts) VALUES (
															:userID,
															:vh_name_vc,
															:domainID,
															'A',
															'ns1',
															172800,
															:target,
															NULL,
															NULL,
															NULL,
															:time)");
        $sql->bindParam(':userID', $user_id);
        $sql->bindParam(':vh_name_vc', $domain);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $sql->bindParam(':target', $target);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk,
															dn_name_vc,
															dn_vhost_fk,
															dn_type_vc,
															dn_host_vc,
															dn_ttl_in,
															dn_target_vc,
															dn_priority_in,
															dn_weight_in,
															dn_port_in,
															dn_created_ts) VALUES (
															:userID,
															:vh_name_vc,
															:domainID,
															'A',
															'ns2',
															172800,
															:target,
															NULL,
															NULL,
															NULL,
															:time)");
        $sql->bindParam(':userID', $user_id);
        $sql->bindParam(':vh_name_vc', $domain);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $sql->bindParam(':target', $target);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk,
															dn_name_vc,
															dn_vhost_fk,
															dn_type_vc,
															dn_host_vc,
															dn_ttl_in,
															dn_target_vc,
															dn_priority_in,
															dn_weight_in,
															dn_port_in,
															dn_created_ts) VALUES (
															:userID,
															:vh_name_vc,
															:domainID,
															'NS',
															'@',
															172800,
															:target2,
															NULL,
															NULL,
															NULL,
															:time)");
        $sql->bindParam(':userID', $user_id);
        $sql->bindParam(':vh_name_vc', $domain);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $target2 = 'ns1.' . $domain;
        $sql->bindParam(':target2', $target2);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
        $sql = $zdbh->prepare("INSERT INTO x_dns (dn_acc_fk,
															dn_name_vc,
															dn_vhost_fk,
															dn_type_vc,
															dn_host_vc,
															dn_ttl_in,
															dn_target_vc,
															dn_priority_in,
															dn_weight_in,
															dn_port_in,
															dn_created_ts) VALUES (
															:userID,
															:vh_name_vc,
															:domainID,
															'NS',
															'@',
															172800,
															:target2,
															NULL,
															NULL,
															NULL,
															:time)");
        $sql->bindParam(':userID', $user_id);
        $sql->bindParam(':vh_name_vc', $domain);
        $sql->bindParam(':domainID', $domain_info['vh_id_pk']);
        $target2 = 'ns2.' . $domain;
        $sql->bindParam(':target2', $target2);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();


		//TriggerDNSUpdate
        $GetRecords = ctrl_options::GetSystemOption('dns_hasupdates');
		$records = explode(",", $GetRecords);

		foreach ($records as $record){
			$RecordArray[] = $record;
		}

		if (!in_array($domain_info['vh_id_pk'], $RecordArray)){	
        	$newlist = $GetRecords . "," . $domain_info['vh_id_pk'];
	        $newlist = str_replace(",,", ",", $newlist);
            $sql = "UPDATE x_settings SET so_value_tx=:newlist WHERE so_name_vc='dns_hasupdates'";
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':newlist', $newlist);
            $sql->execute();
	        //return true;
		} 
    }

    static function SetWriteApacheConfigTrue() {
        global $zdbh;
        $sql = $zdbh->prepare("UPDATE x_settings
								SET so_value_tx='true'
								WHERE so_name_vc='apache_changed'");
        $sql->execute();
    }
    /* Create User Domain */ 
    
    /* Create Order Invoice */
    static function ExecuteCreateOrderInvoice($user_id,$data) {
        global $zdbh;        
        
        if($user_id && $data){
            $order_invoice_status = 0;
            $user_activated = 0;
            $new_pwd = '';
            $order_complete_dated = '0000-00-00 00:00';
            $enable_billing_yn = self::appSetting($data['zpx_uid'],'billing_enabled_yn');
            
            $activate_yn = 0;
            
            if(!$enable_billing_yn || $data['period_id'] == '-1'){
                $activate_yn = 1;
            }

            if(isset($data['activate_yn']) && $data['activate_yn'] == 0){
                $enable_billing_yn = 1;
                $activate_yn = 0;
            }
            
            //var_dump($activate_yn);
            //var_dump($enable_billing_yn);
            //var_dump($enable_billing_yn);
            //die(var_dump($data));
            
            if($activate_yn){
               $order_invoice_status = 1;
               $order_complete_dated = date("Y-m-d H:i");
               
               //activate user account
               $user_pwd = self::ActivateUser($user_id);
               $user_activated = 1;
            }
            
            //print_r($data);
            //exit;
            $datetime = date("Y-m-d H:i");
            //fetch order amount
            if($data['period_id'] > 0){
                $numrows = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_packages_periods 
                                            WHERE zpx_package_id=:pkg_id AND period_id=:pid;");
                $numrows->bindParam(':pkg_id', $data['package_id']);
                $numrows->bindParam(':pid', $data['period_id']);
                $numrows->execute();
                $package_period = $numrows->fetch();
            } elseif($data['period_id'] == '-1') {
                $package_period['package_period_id'] = '-1';
                $package_period['package_amount'] = 0;
            }
            
            //fetch package name
            $numrows = $zdbh->prepare("SELECT pk_name_vc FROM ".self::$server_app."_core.x_packages 
                                        INNER JOIN ".self::$module_db.".x_packages ON 
                                        ".self::$module_db.".x_packages.zpx_package_id=".self::$server_app."_core.x_packages.pk_id_pk
                                        WHERE ".self::$module_db.".x_packages.reseller_ac_id_fk=:uid 
                                        AND ".self::$module_db.".x_packages.zpx_package_id=:pkg_id;");
            $numrows->bindParam(':uid', $data['zpx_uid']);
            $numrows->bindParam(':pkg_id', $data['package_id']);
            $numrows->execute();
            $package = $numrows->fetch();
            
            $order_desc = $package['pk_name_vc'];
            
            //fetch period duration
            if($data['period_id'] > 0){
                $numrows = $zdbh->prepare("SELECT period_duration FROM ".self::$module_db.".x_periods 
                                            WHERE reseller_ac_id_fk=:uid AND period_id=:pid");
                $numrows->bindParam(':uid', $data['zpx_uid']);
                //$numrows->bindParam(':pkg_id', $data['package_id']);
                $numrows->bindParam(':pid', $data['period_id']);
                $numrows->execute();
                $period = $numrows->fetch();
            } elseif($data['period_id'] == '-1') {
                $period['period_duration'] = 0;
            }
            
            if(isset($period['period_duration'])){
                if($period['period_duration'] > 0){
                    $order_desc .= ' Hosting Package for '.$period['period_duration'].' Month';
                    if($period['period_duration'] > 1){
                        $order_desc .= 's';
                    }
                } elseif($period['period_duration'] == 0){
                    $order_desc .= ' - Free Hosting';

                    //$order_invoice_status = 1;
                    //$order_complete_dated = date("Y-m-d H:i");
                    //$user_pwd = self::ActivateUser($user_id);
                    //$user_activated = 1;
                }
            }
                        
            $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_orders (ac_id_fk,
                                    reseller_ac_id_fk,order_vh_fk,order_dated,order_amount, order_desc,
									package_period_id_fk,order_status,order_complete_dated
									) VALUES (:userID,:zpx_uid,:domain_id,:date,:amount,:desc,:pkg_pid,
									'".$order_invoice_status."', '".$order_complete_dated."')");
            $sql->bindParam(':userID', $user_id);
            $sql->bindParam(':zpx_uid', $data['zpx_uid']);
            $sql->bindParam(':date', $datetime);
            $sql->bindParam(':amount', $package_period['package_amount']);
            $sql->bindParam(':desc', $order_desc);
            $sql->bindParam(':pkg_pid', $package_period['package_period_id']);
            $sql->bindParam(':domain_id', $data['domain_id']);
            $sql->execute();
            
            //fetch newly created order id
            $numrows = $zdbh->prepare("SELECT order_id FROM ".self::$module_db.".x_orders 
                                        WHERE reseller_ac_id_fk=:zpx_uid AND ac_id_fk=:uid 
                                        AND order_dated=:datetime");
            $numrows->bindParam(':uid', $user_id);
            $numrows->bindParam(':zpx_uid', $data['zpx_uid']);
            $numrows->bindParam(':datetime', $datetime);
            $numrows->execute();
            $order = $numrows->fetch();
            if(isset($order['order_id'])){
                $order_id = $order['order_id'];
                $voucher_id = (int) isset($data['voucher_id']) ? $data['voucher_id'] : 0;
                $invoice_reference = self::randomString(7);                

                $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_invoices (
                                        reseller_ac_id_fk,invoice_dated,
                                        invoice_total_amount, invoice_reference,
                                        invoice_status,ac_id_fk, invoice_voucher_id
									    ) VALUES (:zpx_uid,:date,:amount,
									    :ref, '".$order_invoice_status."',:uid,:vid)");
                $sql->bindParam(':uid', $user_id);
                $sql->bindParam(':zpx_uid', $data['zpx_uid']);
                $sql->bindParam(':date', $datetime);
                $sql->bindParam(':amount', $package_period['package_amount']);
                $sql->bindParam(':ref', $invoice_reference);
                $sql->bindParam(':vid', $voucher_id);
                $sql->execute();

                //fetch newly created invoice id
                $numrows = $zdbh->prepare("SELECT invoice_id FROM ".self::$module_db.".x_invoices 
                                            WHERE reseller_ac_id_fk=:zpx_uid AND ac_id_fk=:uid 
                                            AND invoice_reference=:ref");
                $numrows->bindParam(':uid', $user_id);
                $numrows->bindParam(':zpx_uid', $data['zpx_uid']);
                $numrows->bindParam(':ref', $invoice_reference);
                $numrows->execute();
                $invoice = $numrows->fetch();
                
                if(isset($invoice['invoice_id'])){
                    //create invoice and order relationship                    
                    $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_invoices_orders (
                                            invoice_id,order_id
                                            ) VALUES ('".$invoice['invoice_id']."',
                                            '".$order['order_id']."')");
                    $sql->execute();
                }
                
                if($user_activated){
                    //fetch username
                    $numrows = $zdbh->prepare("SELECT ac_user_vc FROM x_accounts WHERE ac_id_pk=:uid");
                    $numrows->bindParam(':uid', $user_id);
                    $numrows->execute();
                    $user = $numrows->fetch();
                                    
                    $invoice_info['username'] = $user['ac_user_vc'];
                    $invoice_info['user_password'] = $user_pwd;
                    $invoice_info['activated_yn'] = $user_activated;
                }
                

            }       
        }
        
        if(isset($invoice_reference)){
           $invoice_info['reference'] = $invoice_reference;
           return $invoice_info;
        }
    }
    /* Create Order Invoice */
    
    /* Activate User Account */
    static function ActivateUser($user_id){
        global $zdbh;
        
        $new_pwd = fs_director::GenerateRandomPassword(ctrl_options::GetSystemOption('password_minlength'), 4);
               
        $crypto = new runtime_hash;
        $crypto->SetPassword($new_pwd);
        $randomsalt = $crypto->RandomSalt();
        $crypto->SetSalt($randomsalt);
        $secure_password = $crypto->CryptParts($crypto->Crypt())->Hash;
                
        //update user information
        $sql = $zdbh->prepare("UPDATE ".self::$server_app."_core.x_accounts SET ac_enabled_in='1',
                                 ac_pass_vc=:password,ac_passsalt_vc=:pass_salt 
                                 WHERE ac_id_pk=:user_id");
        $sql->bindParam(':user_id', $user_id);
        $sql->bindParam(':password', $secure_password);

        $sql->bindParam(':pass_salt', $randomsalt);
        $sql->execute();
        
        return $new_pwd;
    }
    /* Activate User Account */
    
    static function getPanelURL(){
        global $zdbh;
        $numrows = $zdbh->prepare("SELECT so_value_tx FROM x_settings WHERE so_name_vc='zpanel_domain' LIMIT 1");
        $numrows->execute();
        $panel = $numrows->fetch();
        return $panel['so_value_tx'];
    }
    
    static function randomString($length = 10){      
        $chars = 'BCDFGHJKLMNPQRSTVWXYZAEIU23456789';
        $str = '';
        for ($i=0; $i<$length; $i++){
            $str .= ($i%2) ? $chars[mt_rand(19, 25)] : $chars[mt_rand(0, 18)];
        }

        return $str;
    }    
    
    static function CheckCreateDomainForErrors($domain, $package_id, $zpx_uid) {
        global $zdbh;
        // Check for spaces and remove if found...
        $domain = strtolower(str_replace(' ', '', $domain));
        // Check to make sure the domain is not blank before we go any further...
        if ($domain == '') {
            //self::$blank = TRUE;
            //return FALSE;
           return 'Domain name cannot be empty.';
        }
        // Check for invalid characters in the domain...
        if (!fs_director::IsValidDomainName($domain)) {
            //self::$badname = TRUE;
            //return FALSE;
            return 'Invalid domain name specified.';
        }
        // Check to make sure the domain is in the correct format before we go any further...
        $wwwclean = stristr($domain, 'www.');
        if ($wwwclean == true) {
            //self::$error = TRUE;
            //return FALSE;
            return 'Domain is not valid.';
        }
        // Check to see if the domain already exists in ZPanel somewhere and redirect if it does....
        $numrows = $zdbh->prepare("SELECT COUNT(*) FROM x_vhosts WHERE vh_name_vc=:domain AND vh_deleted_ts IS NULL");
        $numrows->bindParam(':domain', $domain);
        
        if ($numrows->execute()) {
            if ($numrows->fetchColumn() > 0) {
                return 'Domain is not available.';
            }
        }
        
        //check to make sure the selected package max domain hasn't been reached
        // COMMENTED OUT because zpanel does not tie domains to packages
        /*$sql = "SELECT COUNT(vh_id_pk) AS total_domains FROM x_vhosts INNER JOIN x_accounts ON 
                    x_accounts.ac_id_pk=x_vhosts.vh_acc_fk WHERE x_accounts.ac_reseller_fk=:zpx_uid 
                    AND vh_deleted_ts IS NULL";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':zpx_uid', $zpx_uid);
        $numrows->execute();
        $domains = $numrows->fetch();
        if(isset($domains['total_domains'])){
            $total_domains = $domains['total_domains'];
        } else {
            $total_domains = 0;  
        }*/

        //check to make sure the selected package max clients limit hasn't been reached
        //skip this check if this is zadmin
        if($zpx_uid != 1){
            $sql = "SELECT COUNT(ac_id_pk) AS total_clients FROM x_accounts WHERE ac_reseller_fk=:zpx_uid 
                        AND ac_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':zpx_uid', $zpx_uid);
            $numrows->execute();
            $clients = $numrows->fetch();
            if(isset($clients['total_clients'])){
                $total_clients = $clients['total_clients'];
            } else {
                $total_clients = 0;
            }

            $numrows = $zdbh->prepare("SELECT qt_domains_in FROM x_quotas WHERE qt_package_fk=:pkg_id");
            $numrows->bindParam(':pkg_id', $package_id);
            $numrows->execute();
            $package = $numrows->fetch();
            if(isset($package['qt_domains_in'])){
                $max_domains = $package['qt_domains_in'];
            } else {
                $max_domains = 0;  
            }
            
            /*print_r($package);
            echo('<br>');
            print_r($domains);*/
            
            $package_error = 0;
            if(!$max_domains){
                $package_error = 1;
            } elseif($max_domains > 0){
                $domain_left = ($max_domains - $total_clients);
                if($domain_left < 1){
                    $package_error = 1;
                }
            }
        } elseif($zpx_uid == 1){
            $package_error = 0;
        }
        
        if($package_error){
           return 'Maximum number of clients limit reached.';
        }
        
        
        
        
        // Check to make sure user not adding a subdomain and blocks stealing of subdomains....
        // Get shared domain list
        /*$SharedDomains = array();
        $a = ctrl_options::GetSystemOption('shared_domains');
        $a = explode(',', $a);
        foreach ($a as $b) {
            $SharedDomains[] = $b;
        }
        if (substr_count($domain, ".") > 1) {
            $part = explode('.', $domain);
            foreach ($part as $check) {
                if (!in_array($check, $SharedDomains)) {
                    if (strlen($check) > 3) {
                        $sql = $zdbh->prepare("SELECT * FROM x_vhosts WHERE vh_name_vc LIKE :check AND vh_type_in !=2 AND vh_deleted_ts IS NULL");
                        $checkSql = '%'.$check.'%';
                        $sql->bindParam(':check', $checkSql);
                        $sql->execute();
                        while ($rowcheckdomains = $sql->fetch()) {
                            $subpart = explode('.', $rowcheckdomains['vh_name_vc']);
                            foreach ($subpart as $subcheck) {
                                if (strlen($subcheck) > 3) {
                                    if ($subcheck == $check) {
                                        if (substr($domain, -7) == substr($rowcheckdomains['vh_name_vc'], -7)) {
                                            self::$nosub = TRUE;
                                            return FALSE;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }*/
        return TRUE;
    }

    static function CheckErrorDocument($error) {
        $errordocs = array(100, 101, 102, 200, 201, 202, 203, 204, 205, 206, 207,
            300, 301, 302, 303, 304, 305, 306, 307, 400, 401, 402,
            403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413,
            414, 415, 416, 417, 418, 419, 420, 421, 422, 423, 424,
            425, 426, 500, 501, 502, 503, 504, 505, 506, 507, 508,
            509, 510);
        if (in_array($error, $errordocs)) {
            return true;
        } else {
            return false;
        }
    }
    
    
    static function ExecuteAddPeriod($duration, $amount){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

        if($duration && $amount){
            //make sure we don't have an existing period with the same duration
            $sql = "SELECT COUNT(*) FROM ".self::$module_db.".x_periods 
                        WHERE period_duration=:duration AND reseller_ac_id_fk=:user_id 
                        AND period_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':duration', $duration);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() > 0) {
                    self::$error['period_exists'] = true;
                    return false;
                }
            }
           
           runtime_hook::Execute('OnBeforeCreateServicePeriod');
           //add new service period
           $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_periods (period_duration,default_amount,reseller_ac_id_fk
                                   ) VALUES (:duration, :amount, :user_id)");
           $sql->bindParam(':duration', $duration);
           $sql->bindParam(':amount', $amount);
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->execute();
           
           //update package periods
           self::updatePeriodsPackages();
           runtime_hook::Execute('OnAfterCreateServicePeriod');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    


    static function ExecuteUpdatePeriod($duration, $amount, $period_id){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

        if($duration && $amount && $period_id){
            //make sure we don't have an existing period with the same duration
            $sql = "SELECT COUNT(*) FROM ".self::$module_db.".x_periods 
                        WHERE period_id<>:period_id AND period_duration=:duration 
                        AND reseller_ac_id_fk=:user_id";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':period_id', $period_id);
            $numrows->bindParam(':duration', $duration);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() > 0) {
                    self::$error['period_exists'] = true;
                    return false;
                }
            }
            
           //update service period
           runtime_hook::Execute('OnBeforeUpdateServicePeriod');
           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_periods SET period_duration=:duration,
                                    default_amount=:amount WHERE reseller_ac_id_fk=:user_id AND period_id=:id");
           $sql->bindParam(':duration', $duration);
           $sql->bindParam(':amount', $amount);
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $period_id);
           $sql->execute();
           
           //update package periods
           self::updatePeriodsPackages();
           
           runtime_hook::Execute('OnAfterUpdateServicePeriod');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    



    static function ExecuteDeletePeriod($period_id){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

           //delete service period
           runtime_hook::Execute('OnBeforeDeleteServicePeriod');
           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_periods 
                                    SET period_deleted_ts='".time()."' 
                                    WHERE period_id=:id AND reseller_ac_id_fk=:user_id");
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $period_id);
           $sql->execute();

           //delete packages periods as well
           $sql = $zdbh->prepare("DELETE FROM ".self::$module_db.".x_packages_periods WHERE period_id=:id");
           $sql->bindParam(':id', $period_id);
           $sql->execute();

           runtime_hook::Execute('OnAfterDeleteServicePeriod');
           
           self::$complete = true;
           return true;            

    }


    static function ExecuteUpdatePackages($data){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

        if(is_array($data)){
           runtime_hook::Execute('OnBeforeUpdateServicePackages');
           $pkg_desc = $data['pkg_desc'];
           $pkg_enabled_yn = (isset($data['enabled_yn'])) ? $data['enabled_yn']:array();
           $pkg_free_yn = $data['free_yn'];
           if(is_array($pkg_desc)){
            foreach($pkg_desc as $pkg_id=>$desc){
                $enabled_yn = 0;
                $free_yn = 0;
                if(isset($pkg_enabled_yn[$pkg_id])){
                    $enabled_yn = 1;
                }

                if(isset($pkg_free_yn[$pkg_id])){
                    $free_yn = 1;
                }
                  $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_packages SET package_desc=:desc,
                                            enabled_yn=:enabled_yn, free_package_yn=:free_yn 
                                            WHERE reseller_ac_id_fk=:user_id AND zpx_package_id=:id");
                   $sql->bindParam(':desc', $desc);
                   $sql->bindParam(':enabled_yn', $enabled_yn);
                   $sql->bindParam(':free_yn', $free_yn);
                   $sql->bindParam(':user_id', $currentuser['userid']);
                   $sql->bindParam(':id', $pkg_id);
                   $sql->execute();                
            }
           }
           runtime_hook::Execute('OnAfterUpdateServicePeriod');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    


    static function ExecuteAddPaymentMethod($data){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();
        
        //print_r($data);
        //exit;

        if($data['payment_option_id']){
            //make sure we don't have an existing payment method with the same name
            $sql = "SELECT COUNT(*) FROM ".self::$module_db.".x_payment_methods 
                        WHERE payment_option_id=:id AND reseller_ac_id_fk=:user_id 
                        AND method_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':id', $data['payment_option_id']);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() > 0) {
                    self::$error['method_exists'] = true;
                    return false;
                }
            }
           
           runtime_hook::Execute('OnBeforeCreatePaymentMethod');
           //fetch payment option fields
           $payment_option_fields = self::getPaymentOptionFields($data['payment_option_id']);
           /*print_r($data);
           echo('<br><br>');
           print_r($payment_option_fields);
           exit;*/
                      
           //add new payment method
           $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_payment_methods
                                     (payment_option_id,enabled_yn,reseller_ac_id_fk
                                     ) VALUES (:id, :enabled_yn, :user_id)");
           $sql->bindParam(':id', $data['payment_option_id']);
           $sql->bindParam(':enabled_yn', $data['enabled_yn']);
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->execute();
           
           // fetch newly inserted method id
           $res = $zdbh->prepare("SELECT method_id FROM ".self::$module_db.".x_payment_methods 
                                     WHERE payment_option_id=:id AND reseller_ac_id_fk=:user_id;");
           $res->bindParam(':id', $data['payment_option_id']);
           $res->bindParam(':user_id', $currentuser['userid']);
           $res->execute();
           if (!fs_director::CheckForEmptyValue($res)){
               $row = $res->fetch();
               $method_id = $row['method_id'];
               
               if(is_array($payment_option_fields)){
                foreach($payment_option_fields as $field){
                    if($field['name']){
                       //add payment option field values
                       $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_payment_option_values
                                                 (payment_option_id,reseller_ac_id_fk,
                                                 field_name,field_value) VALUES(
                                                 :id, :user_id, :field_name, :field_value)");
                       $sql->bindParam(':id', $data['payment_option_id']);
                       $sql->bindParam(':user_id', $currentuser['userid']);
                       $sql->bindParam(':field_name', $field['name']);
                       $sql->bindParam(':field_value', $data[$field['name']]);
                       $sql->execute();    
                    }
                }
               }
               
           }


           runtime_hook::Execute('OnAfterCreatePaymentMethod');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    


    static function ExecuteUpdatePaymentMethod($data){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();
        
        if($data['method_id'] && $data['payment_option_id']){
            //make sure we don't have an existing payment method with the same name
            $sql = "SELECT COUNT(*) FROM ".self::$module_db.".x_payment_methods 
                        WHERE payment_option_id=:option_id AND reseller_ac_id_fk=:user_id AND method_id<>:id";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':option_id', $data['payment_option_id']);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            $numrows->bindParam(':id', $data['method_id']);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() > 0) {
                    self::$error['method_exists'] = true;
                    return false;
                }
            }
            
            $payment_option_fields = self::getPaymentOptionFields($data['payment_option_id']);            
            
           //update payment method
           runtime_hook::Execute('OnBeforeUpdatePaymentMethod');
           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_payment_methods SET 
                                    enabled_yn=:enabled_yn WHERE reseller_ac_id_fk=:user_id 
                                    AND method_id=:id");
           //$sql->bindParam(':name', $method_name);
           //$sql->bindParam(':html', $method_html);
           $sql->bindParam(':enabled_yn', $data['enabled_yn']);
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $data['method_id']);
           $sql->execute();
           
           
           //add payment option field values
           if(is_array($payment_option_fields)){
              foreach($payment_option_fields as $field){
                if($field['name']){
                   //delete existing payment option field values
                   unset($sql);
                   $sql = $zdbh->prepare("DELETE FROM ".self::$module_db.".x_payment_option_values 
                                            WHERE payment_option_id=:option_id AND 
                                            reseller_ac_id_fk=:user_id AND field_name=:field_name");
                   $sql->bindParam(':user_id', $currentuser['userid']);
                   $sql->bindParam(':option_id', $data['payment_option_id']);
                   $sql->bindParam(':field_name', $field['name']);
                   $sql->execute();
                   
                   unset($sql);
                
                  //add payment option field values
                  $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_payment_option_values
                                           (payment_option_id,reseller_ac_id_fk,
                                            field_name,field_value) VALUES(
                                            :option_id, :user_id, :field_name, :field_value);");
                  $sql->bindParam(':option_id', $data['payment_option_id']);
                  $sql->bindParam(':user_id', $currentuser['userid']);
                  $sql->bindParam(':field_name', $field['name']);
                  $sql->bindParam(':field_value', $data[$field['name']]);
                  $sql->execute();    
                }
              }
           }
           
           
           runtime_hook::Execute('OnAfterUpdatePaymentMethod');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    

    static function ExecuteDeletePaymentMethod($method_id){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

           //delete payment method
           runtime_hook::Execute('OnBeforeDeletePaymentMethod');
           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_payment_methods 
                                    SET method_deleted_ts='".time()."' 
                                    WHERE method_id=:id AND reseller_ac_id_fk=:user_id");
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $method_id);
           $sql->execute();
           runtime_hook::Execute('OnAfterDeletePaymentMethod');
           
           self::$complete = true;
           return true;            

    }

    static function ExecuteAddPaymentOption($option_name, $field_labels, $field_names, $option_html, $enabled_yn){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

        if($option_name && $field_labels && $field_names){
            //make sure we don't have an existing payment option with the same name
            $sql = "SELECT COUNT(*) FROM ".self::$module_db.".x_payment_options 
                        WHERE payment_option_name=:name AND reseller_ac_id_fk=:user_id 
                        AND option_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':name', $option_name);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() > 0) {
                    self::$error['option_exists'] = true;
                    return false;
                }
            }
           
           runtime_hook::Execute('OnBeforeCreatePaymentOption');
           //add new payment option
           $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_payment_options
                                 (payment_option_name,payment_option_form_html,
                                    enabled_yn,reseller_ac_id_fk
                                   ) VALUES (:name, :html, :enabled_yn, :user_id)");
           $sql->bindParam(':name', $option_name);
           $sql->bindParam(':html', $option_html);
           $sql->bindParam(':enabled_yn', $enabled_yn);
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->execute();
           
           //fetch newly created payment option id
            $numrows = $zdbh->prepare("SELECT payment_option_id 
                                        FROM ".self::$module_db.".x_payment_options 
                                        WHERE reseller_ac_id_fk=:user_id AND payment_option_name=:name");
            $numrows->bindParam(':user_id', $currentuser['userid']);
            $numrows->bindParam(':name', $option_name);
            $numrows->execute();
            $option_info = $numrows->fetch();
            $option_id = $option_info['payment_option_id'];
            
            //now insert new payment option fields
            foreach($field_names as $fld_idx=>$fld){
                $lbl = trim(filter_var($field_labels[$fld_idx], FILTER_SANITIZE_STRING));
                $fld = strtolower(trim(str_replace(' ','_',filter_var($fld, FILTER_SANITIZE_STRING))));
                
                $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_payment_option_fields
                                         (payment_option_id,field_label,field_name
                                         ) VALUES (:id, :label, :name)");
                $sql->bindParam(':label', $lbl);
                $sql->bindParam(':name', $fld);
                $sql->bindParam(':id', $option_id);
                $sql->execute();                
            }
           
           runtime_hook::Execute('OnAfterCreatePaymentOption');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    


    static function ExecuteUpdatePaymentOption($option_id, $option_name, $field_labels, $field_names, $option_html, $enabled_yn){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

        if($option_id && $option_name && $field_labels && $field_names){
            //make sure we don't have an existing payment option with the same name
            $sql = "SELECT COUNT(*) FROM ".self::$module_db.".x_payment_options 
                        WHERE payment_option_name=:name AND 
                        reseller_ac_id_fk=:user_id AND payment_option_id<>:id 
                        AND option_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':name', $option_name);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            $numrows->bindParam(':id', $option_id);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() > 0) {
                    self::$error['option_exists'] = true;
                    return false;
                }
            }
            
           //update payment option
           runtime_hook::Execute('OnBeforeUpdatePaymentOption');
            //delete existing payment option fields
            $numrows = $zdbh->prepare("DELETE FROM ".self::$module_db.".x_payment_option_fields 
                                        WHERE payment_option_id=:id");
            $numrows->bindParam(':id', $option_id);
            $numrows->execute();
            
            //now insert new payment option fields
            foreach($field_names as $fld_idx=>$fld){
                $lbl = trim(filter_var($field_labels[$fld_idx], FILTER_SANITIZE_STRING));
                $fld = strtolower(trim(str_replace(' ','_',filter_var($fld, FILTER_SANITIZE_STRING))));
                
                $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_payment_option_fields
                                         (payment_option_id,field_label,field_name
                                         ) VALUES (:id, :label, :name)");
                $sql->bindParam(':label', $lbl);
                $sql->bindParam(':name', $fld);
                $sql->bindParam(':id', $option_id);
                $sql->execute();                
            }
           
           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_payment_options
                                    SET payment_option_name=:name, enabled_yn=:enabled_yn, 
                                    payment_option_form_html=:html
                                    WHERE reseller_ac_id_fk=:user_id AND payment_option_id=:id");
           $sql->bindParam(':name', $option_name);
           $sql->bindParam(':html', $option_html);
           $sql->bindParam(':enabled_yn', $enabled_yn);
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $option_id);
           $sql->execute();
           
           runtime_hook::Execute('OnAfterUpdatePaymentOption');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    

    static function ExecuteDeletePaymentOption($option_id){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

           //delete payment option
           runtime_hook::Execute('OnBeforeDeletePaymentOption');
           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_payment_options 
                                    SET option_deleted_ts='".time()."' 
           WHERE payment_option_id=:id AND reseller_ac_id_fk=:user_id");
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $option_id);
           $sql->execute();
           
           //delete existing payment option fields
           /*$numrows = $zdbh->prepare("DELETE FROM ".self::$module_db.".x_payment_option_fields 
                                       WHERE payment_option_id=:id");
           $numrows->bindParam(':id', $option_id);
           $numrows->execute();
           */
           
           runtime_hook::Execute('OnAfterDeletePaymentOption');
           
           self::$complete = true;
           return true;            

    }

    static function ExecuteUpdateOrder($order_id, $completed_yn){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

        if($order_id && $completed_yn){
            //make sure this order exists
            $sql = "SELECT ac_id_fk FROM ".self::$module_db.".x_invoices 
                        WHERE invoice_id=:id AND reseller_ac_id_fk=:user_id 
                        AND invoice_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            $numrows->bindParam(':id', $order_id);
            if ($numrows->execute()) {
                $invoice_user = $numrows->fetch();
                if (!isset($invoice_user['ac_id_fk'])) {
                    self::$error['invalid_order'] = true;
                    return false;
                }
            }
            
            //activate user and fetch user password
             if(isset($invoice_user['ac_id_fk'])){
                $new_pwd = self::ActivateUser($invoice_user['ac_id_fk']);
                /*$sql = $zdbh->prepare("UPDATE zpanel_core.x_accounts 
                                          SET ac_enabled_in='1' WHERE ac_id_pk=:uid");
                $sql->bindParam(':uid', $invoice_user['ac_id_fk']);
                $sql->execute();*/
             }
            
            
           //update payment option
           runtime_hook::Execute('OnBeforeUpdateOrder');

           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_invoices
                                    SET invoice_status='1', payment_option_id='-1'
                                    WHERE reseller_ac_id_fk=:user_id AND invoice_id=:id");
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $order_id);
           $sql->execute();
           
           //find and update orders relating to this invoice
            /*$sql = $zdbh->prepare("SELECT order_id FROM ".self::$module_db.".x_invoices_orders 
                                        WHERE ".self::$module_db.".x_invoices_orders.invoice_id=:id
                                        ORDER BY ".self::$module_db.".x_invoices_orders.order_id ASC;");*/
                                        
            $sql = $zdbh->prepare("SELECT * FROM ".self::$module_db.".x_invoices_orders 
                                        INNER JOIN ".self::$module_db.".x_orders ON ".self::$module_db.".x_orders.order_id=".self::$module_db.".x_invoices_orders.order_id 
                                        INNER JOIN ".self::$module_db.".x_invoices ON 
                                        ".self::$module_db.".x_invoices.invoice_id=".self::$module_db.".x_invoices_orders.invoice_id 
                                        INNER JOIN ".self::$server_app."_core.x_accounts ON ".self::$server_app."_core.x_accounts.ac_id_pk=x_invoices.ac_id_fk                                         
                                        INNER JOIN ".self::$server_app."_core.x_profiles ON ".self::$server_app."_core.x_profiles.ud_user_fk=".self::$server_app."_core.x_accounts.ac_id_pk                                         
                                        WHERE ".self::$module_db.".x_invoices.invoice_id=:inv_id 
                                        AND invoice_deleted_ts IS NULL AND order_deleted_ts IS NULL 
                                        GROUP BY ".self::$module_db.".x_invoices.invoice_id 
                                        ORDER BY ".self::$module_db.".x_invoices.invoice_id ASC;");
                                        
            $sql->bindParam(':inv_id', $order_id);
            $sql->execute();
            $order = $sql->fetch();

            //order info
            if(is_array($order)){
                /*$orders['order_id'] = $order['order_id'];
                $sql = $zdbh->prepare("SELECT order_vh_fk,package_period_id_fk FROM 
                                            ".self::$module_db.".x_orders 
                                            WHERE order_id=:id;");
                $sql->bindParam(':id', $order['order_id']);
                $sql->execute();
                $order_info = $sql->fetch();*/
                //if(is_array($order_info)){
                    $orders['order_vh_fk'] = $order['order_vh_fk'];
                    $orders['package_period_id_fk'] = $order['package_period_id_fk'];
                //}
            } else {
                $orders = '';
            }
            $res = array();

            if (!fs_director::CheckForEmptyValue($orders) && is_array($orders)){
            	$invoice_link = '';
            	$panel_url = 'http://'.self::getPanelURL();
            	//$today_date = date("Y-m-d");
            	$inv_del_days = self::appSetting($currentuser['userid'],'pending_invoice_delete_days');
            	$billing_url = self::appSetting($currentuser['userid'],'website_billing_url');
            	
		//fetch company name
                $company_name = self::appSetting($currentuser['userid'],'company_name');
                $emailbody = module_controller::appSetting($currentuser['userid'],'welcome_message');
                 
                //$due_date = date("Y-m-d", strtotime($order_info['invoice_dated']." +".$inv_del_days."days"));

                /*if($billing_url){
                    $invoice_link = $billing_url.'/view_invoice.php?invoice='.$order_info['invoice_reference'];
                }*/   
            
                //while ($row = $orders->fetch()) {
                    //update domain expiration
                      if(isset($orders['order_vh_fk']) && isset($orders['package_period_id_fk'])){
                            $periods = $zdbh->prepare("SELECT period_duration 
                                                         FROM ".self::$module_db.".x_periods INNER 
                                                         JOIN ".self::$module_db.".x_packages_periods ON 
                                                        ".self::$module_db.".x_packages_periods.period_id=".self::$module_db.".x_periods.period_id 
                                                      WHERE ".self::$module_db.".x_packages_periods.package_period_id=:id 
                                                      AND period_deleted_ts IS NULL;");
                             $periods->bindParam(':id', $orders['package_period_id_fk']);
                             $periods->execute();
                             $period = $periods->fetch();

                             if(isset($period['period_duration'])){
                                /*
                                   Lets check if this domain has expired or not
                                   if it is, then we extend the renewal from payment date(today's date)
                                   else, we extend from the domain's expiration date
                                */
                                 $renewal_date = date("Y-m-d");
                                 $sql = $zdbh->prepare("SELECT vh_expiry_ts FROM x_vhosts 
                                                          WHERE vh_id_pk=:id");
                                 $sql->bindParam(':id', $orders['order_vh_fk']);
                                 $sql->execute();
                                 $domain_info = $sql->fetch();
                                 $domain_expiry = date("Y-m-d", $domain_info['vh_expiry_ts']);
                                 if($domain_expiry > date("Y-m-d")){
                                     $renewal_date = $domain_expiry;
                                 }
                             
                                $new_expiry_date = strtotime($renewal_date."+".$period['period_duration']." months");
                                
                                $sql = $zdbh->prepare("UPDATE ".self::$server_app."_core.x_vhosts 
                                                         SET vh_expiry_ts=:date,vh_enabled_in='1',
                                                         vh_invoice_created_yn='0' 
                                                         WHERE vh_id_pk=:domain_id");
                                $sql->bindParam(':date', $new_expiry_date);
                                $sql->bindParam(':domain_id', $orders['order_vh_fk']);
                                $sql->execute();                                
                             }                                
                      }
                
                      $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_orders SET order_status='1', 
                                                order_complete_dated='".date("Y-m-d H:i")."'
                                                WHERE order_id=:id");
                      $sql->bindParam(':id', $orders['order_id']);
                      $sql->execute();
                      
		    //send welcome email
		       $emailbody = str_replace("{{fullname}}", $order['ud_fullname_vc'], $emailbody);
		       $emailbody = str_replace("{{company_name}}", $company_name, $emailbody);
		       //$emailbody = str_replace("{{invoice_reference}}", $order_info['invoice_reference'], $emailbody);
		       //$emailbody = str_replace("{{invoice_unpaid_days}}", $inv_del_days, $emailbody);
		       $emailbody = str_replace("{{username}}", $order['ac_user_vc'], $emailbody);
		       $emailbody = str_replace("{{password}}", $new_pwd, $emailbody);
		       $emailbody = str_replace("{{panel_url}}", $panel_url, $emailbody);
		       
		       $subject = "Welcome to $company_name!";
		       self::sendMail(array('to' => $order['ac_email_vc'], 'subject' => $subject, 'message' => $emailbody));
                                  
                //}
            }  
            
            //die(var_dump($order_info));         	    	                
           
           runtime_hook::Execute('OnAfterUpdateOrder');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    
    
    
    static function ExecuteAddVoucher($code, $discount, $active_yn = 1, $discount_type = 1, $usage_type = 1){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

        if($code && $discount){
            //make sure we don't have an existing voucher with the same code
            $sql = "SELECT COUNT(*) FROM ".self::$module_db.".x_vouchers 
                        WHERE voucher_code=:code AND reseller_ac_id_fk=:user_id 
                        AND voucher_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':code', $code);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() > 0) {
                    self::$error['voucher_exists'] = true;
                    return false;
                }
            }
           
           runtime_hook::Execute('OnBeforeCreateVoucher');
           //add new voucher
           $sql = $zdbh->prepare("INSERT INTO ".self::$module_db.".x_vouchers (voucher_code,discount,reseller_ac_id_fk,active_yn,
           				discount_type,usage_type,voucher_created_ts
                                   ) VALUES (:code, :discount, :user_id, :active_yn, :discount_type, :usage_type, :date)");
           $sql->bindParam(':code', strtoupper($code));
           $sql->bindParam(':discount', $discount);
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':active_yn', $active_yn);
           $sql->bindParam(':discount_type', $discount_type);
           $sql->bindParam(':usage_type', $usage_type);
           $sql->bindParam(':date', time());
           $sql->execute();
           
           //update package periods
           self::updatePeriodsPackages();
           runtime_hook::Execute('OnAfterCreateVoucher');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    


    static function ExecuteUpdateVoucher($voucher_id, $discount, $active_yn = 1, $discount_type = 1, $usage_type = 1){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

        if($discount && $voucher_id){
            //make sure we don't have an existing voucher with the same code SRATCH THE BELOW!! We are not changing the voucher code!
            /*$sql = "SELECT COUNT(*) FROM ".self::$module_db.".x_periods 

                        WHERE period_id<>:period_id AND period_duration=:duration 

                        AND reseller_ac_id_fk=:user_id";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':period_id', $period_id);
            $numrows->bindParam(':duration', $duration);
            $numrows->bindParam(':user_id', $currentuser['userid']);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() > 0) {
                    self::$error['period_exists'] = true;
                    return false;
                }
            } */
            
           //update service period
           runtime_hook::Execute('OnBeforeUpdateVoucher');
           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_vouchers SET discount=:discount,
                                    active_yn=:active_yn, discount_type=:discount_type, usage_type=:usage_type
                                     WHERE reseller_ac_id_fk=:user_id AND voucher_id=:id");
           $sql->bindParam(':discount', $discount);
           $sql->bindParam(':active_yn', $active_yn);
           $sql->bindParam(':discount_type', $discount_type);
           $sql->bindParam(':usage_type', $usage_type);
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $voucher_id);
           $sql->execute();
           
           runtime_hook::Execute('OnAfterUpdateVoucher');
           
           self::$complete = true;
           return true;            
        } else {
            return false;
        }
    }    



    static function ExecuteDeleteVoucher($voucher_id){
        global $zdbh,$controller;
    
        $currentuser = ctrl_users::GetUserDetail();

           //delete discount voucher
           runtime_hook::Execute('OnBeforeDeleteVoucher');
           $sql = $zdbh->prepare("UPDATE ".self::$module_db.".x_vouchers 

                                    SET voucher_deleted_ts='".time()."' 
                                    WHERE voucher_id=:id AND reseller_ac_id_fk=:user_id");
           $sql->bindParam(':user_id', $currentuser['userid']);
           $sql->bindParam(':id', $voucher_id);
           $sql->execute();

           runtime_hook::Execute('OnAfterDeleteVoucher');
           
           self::$complete = true;
           return true;            

    }

    

    /* Executions */

    static function CheckCreateForErrors($username, $packageid, $groupid, $email, $password = "") {
        global $zdbh;
        $username = strtolower(str_replace(' ', '', $username));
        // Check to make sure the username is not blank or exists before we go any further...
        if (!fs_director::CheckForEmptyValue($username)) {
            $sql = "SELECT COUNT(*) FROM x_accounts WHERE UPPER(ac_user_vc)=:user AND ac_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $user = strtoupper($username);
            $numrows->bindParam(':user', $user);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() <> 0) {
                    //self::$alreadyexists = true;
                    //return false;
                    die('That Username is not available.');
                }
            }
            if (!fs_director::IsValidUserName($username)) {
                //self::$badname = true;
                //return false;
                 die('Please provide a valid username.');                
            }
        } else {
            //self::$userblank = true;
            //return false;
            die('Username cannot be empty.');
        }
        // Check to make sure the packagename is not blank and exists before we go any further...
        if (!fs_director::CheckForEmptyValue($packageid)) {
            $sql = "SELECT COUNT(*) FROM x_packages WHERE pk_id_pk=:packageid AND pk_deleted_ts IS NULL";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':packageid', $packageid);
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() == 0) {
                   die('Invalid hosting package specified.');                
                }
            }
        } else {
            die('Hosting Package cannot be blank.');                
        }
        // Check to make sure the groupname is not blank and exists before we go any further...
        if (!fs_director::CheckForEmptyValue($groupid)) {
            $sql = "SELECT COUNT(*) FROM x_groups WHERE ug_id_pk=:groupid";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':groupid', $groupid);
            
            if ($numrows->execute()) {
                if ($numrows->fetchColumn() == 0) {
                    die('Invalid User Group specified.');                
                }
            }
        } else {
            die('User group cannot be blank.');                
        }
        // Check for invalid characters in the email and that it exists...
        if (!fs_director::CheckForEmptyValue($email)) {
            if (!fs_director::IsValidEmail($email)) {
                die('Invalid email address specified.');                
            }
        } else {
            die('Email Address cannot be blank.');                
        }
        // Check for password length...
        if (!fs_director::CheckForEmptyValue($password)) {
            if (strlen($password) < ctrl_options::GetSystemOption('password_minlength')) {
                die('Password too short.');                
            }
        } else {
            die('Password cannot be blank.');                
        }

        //return true;
    }
    
    static function getResult() {
        if (!fs_director::CheckForEmptyValue(self::$ok)) {
            return ui_sysmessage::shout(ui_language::translate("Operation completed successfully."), "zannounceok");
        }

        if (isset(self::$error['period_empty']) && !fs_director::CheckForEmptyValue(self::$error['period_empty'])) {
            return ui_sysmessage::shout(ui_language::translate("Period Duration and Amount must be a number greater than zero."), "zannounceerror");
        }

        if (isset(self::$error['voucher_empty']) && !fs_director::CheckForEmptyValue(self::$error['voucher_empty'])) {
            return ui_sysmessage::shout(ui_language::translate("Voucher Discount must be a number greater than zero."), "zannounceerror");
        }

        if (isset(self::$error['period_exists']) && !fs_director::CheckForEmptyValue(self::$error['period_exists'])) {
            return ui_sysmessage::shout(ui_language::translate("Cannot complete process, a duplicate service period exists."), "zannounceerror");
        }

        if (isset(self::$error['voucher_exists']) && !fs_director::CheckForEmptyValue(self::$error['voucher_exists'])) {
            return ui_sysmessage::shout(ui_language::translate("Cannot complete process, a duplicate discount voucher exists."), "zannounceerror");
        }


        if (isset(self::$error['method_empty']) && !fs_director::CheckForEmptyValue(self::$error['method_empty'])) {
            return ui_sysmessage::shout(ui_language::translate("Please fill in all fields."), "zannounceerror");
        }

        if (isset(self::$error['method_exists']) && !fs_director::CheckForEmptyValue(self::$error['method_exists'])) {
            return ui_sysmessage::shout(ui_language::translate("Cannot complete process, a duplicate payment method exists."), "zannounceerror");
        }

        if (!fs_director::CheckForEmptyValue(self::$file_error['method_exists'])) {
            return ui_sysmessage::shout(ui_language::translate("An error occurred - ".self::$file_error), "zannounceerror");
        }

        if (isset(self::$error['option_empty']) && !fs_director::CheckForEmptyValue(self::$error['option_empty'])) {
            return ui_sysmessage::shout(ui_language::translate("Payment Option Name, Field Labels and Field Names cannot be empty."), "zannounceerror");
        }

        if (isset(self::$error['option_exists']) && !fs_director::CheckForEmptyValue(self::$error['option_exists'])) {
            return ui_sysmessage::shout(ui_language::translate("Cannot complete process, a duplicate payment option exists."), "zannounceerror");
        }

        if (isset(self::$error['option_field_empty']) && !fs_director::CheckForEmptyValue(self::$error['option_field_empty'])) {
            return ui_sysmessage::shout(ui_language::translate("Please ensure all Field Names and Labels are filled in correctly."), "zannounceerror");
        }

        if(isset(self::$error['invalid_order']) && !fs_director::CheckForEmptyValue(self::$error['invalid_order'])) {
            return ui_sysmessage::shout(ui_language::translate("The Order you selected does not exist on the system."), "zannounceerror");
        }

        if(isset(self::$error['order_info_empty']) && !fs_director::CheckForEmptyValue(self::$error['order_info_empty'])) {
            return ui_sysmessage::shout(ui_language::translate("Please fill in all fields."), "zannounceerror");
        }

        if(isset(self::$error['customerror']) && !fs_director::CheckForEmptyValue(self::$error['customerror'])) {
            return ui_sysmessage::shout(ui_language::translate(self::$error['customerror']), "zannounceerror");
        }

        if (!fs_director::CheckForEmptyValue(self::$error)) {
            return ui_sysmessage::shout(ui_language::translate("An error has occurred while processing your request, please try again."), "zannounceerror");
        }
        return;
    }


}

?>
