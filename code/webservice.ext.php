<?php

/**
 *
 * xBilling Module for Sentora 1.0.0
 * Version : 1.2.1
 * Author :  Remi Adewale (modpluz @ Sentora Forums)
 * Email : goremmy@gmail.com
 */

class webservice extends ws_xmws {


    /**
     * Get the full list of all active hosting packages belonging to the specified user on the server.
     * @global type
     * @return type 
     */
    public function GetHostingPackages() {
        $user_id = 0;

        $request_data = ws_generic::XMLToArray($this->wsdata);
        if(isset($request_data['xmws']['content']['zpx_uid']) && ($request_data['xmws']['content']['zpx_uid']) > 0){
            $user_id = $request_data['xmws']['content']['zpx_uid'];
        }

        $response_xml = "\n";
        $pkgs = module_controller::getServicePackages($user_id);

        if(is_array($pkgs)){
            /*foreach($pkgs as $pkg_idx=>$pkg){
                $pkgs[$pkg_idx]['name'] = htmlentities($pkg['name']);
                $pkgs[$pkg_idx]['desc'] = htmlentities($pkg['desc']);
            }*/

            $packages = '';
            foreach ($pkgs as $pkg) {
                //is this package enabled?
                if($pkg['enabled_yn']){
                    //fetch package plans
                    if(!$pkg['desc']){
                        $pkg['desc'] = 'N/A';
                    }
                    $pkg_periods = module_controller::getPackagePeriods($pkg['id']);
                    //only return this package if it has valid service periods
                    if(is_array($pkg_periods)){
                        if($pkg['id']){
                            $response_xml = $response_xml . ws_xmws::NewXMLContentSection('packages', array(
                                        'id' => $pkg['id'],
                                        'name' => ($pkg['name']),
                                        'desc' => utf8_decode($pkg['desc']),
                                        'service_periods' => json_encode($pkg_periods),
                                    ));
                        }
                    }
                }
            } 
        }

        //die(var_dump($response_xml));
        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);
        return $dataobject->getDataObject();

        //die(var_dump($result));
    }

    /**
     * Gets a website settings for the specified user.
     * @global type
     * @return type 
     */
    public function GetSettings() {
        $user_id = 0;

        $request_data = ws_generic::XMLToArray($this->wsdata);
        if(isset($request_data['xmws']['content']['zpx_uid']) && ($request_data['xmws']['content']['zpx_uid']) > 0){
            $user_id = $request_data['xmws']['content']['zpx_uid'];
        }

        $response_xml = "\n";
        $settings = module_controller::getSettings($user_id);

        if (!fs_director::CheckForEmptyValue($settings)) {
             $response_xml = $response_xml . ws_xmws::NewXMLContentSection('settings', $settings);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();
    }
    
    public function CheckUserName(){
        $zpx_user = '';
        $request_data = ws_generic::XMLToArray($this->wsdata);
        if(isset($request_data['xmws']['content']['zpx_user'])){
            $zpx_user = $request_data['xmws']['content']['zpx_user'];
        }
        

        if($zpx_user){
            $response_xml = "\n";
            $user_exists = module_controller::CheckUserExists($zpx_user);
            
            if (is_numeric($user_exists)){
                $user_check['user_exists'] = $user_exists;
                 $response_xml = $response_xml . ws_xmws::NewXMLContentSection('result', $user_check);
            }

            $dataobject = new runtime_dataobject();
            $dataobject->addItemValue('response', '');
            $dataobject->addItemValue('content', $response_xml);

            return $dataobject->getDataObject();
        }
    }

    
    public function CheckVoucher(){
    
        $zpx_user = '';
        $request_data = ws_generic::XMLToArray($this->wsdata);
        if(isset($request_data['xmws']['content']['zpx_uid']) && isset($request_data['xmws']['content']['voucher'])){
            $zpx_user = $request_data['xmws']['content']['zpx_uid'];
            $code = $request_data['xmws']['content']['voucher'];
        }
        
        //die(var_dump($request_data));
        

        if($zpx_user && $code){
            $response_xml = "\n";
            $voucher_info = module_controller::getVoucher($zpx_user, $code);
            
            //die(var_dump($voucher_exists));
            
            if ($voucher_info){
            	    $discount_type = 'Once-Off';
		    if($voucher_info['discount_type'] == 2){
		    	$discount_type = 'Recurring';
		    }
                //$voucher_check['voucher_exists'] = $voucher_exists;
                 $voucher = array('id' => $voucher_info['voucher_id'], 'code' => $voucher_info['voucher_code'], 'discount' => $voucher_info['discount'], 'type' => $discount_type);
                 $response_xml = $response_xml . ws_xmws::NewXMLContentSection('result', $voucher);
            }

            $dataobject = new runtime_dataobject();
            $dataobject->addItemValue('response', '');
            $dataobject->addItemValue('content', $response_xml);

            return $dataobject->getDataObject();
        }
    }

    public function GetPackageName(){
        $pkg_name = '';
        $pkg_id = 0;
        $request_data = ws_generic::XMLToArray($this->wsdata);
        if(isset($request_data['xmws']['content']['package_id'])){
            //$zpx_uid = $request_data['xmws']['content']['zpx_uid'];
            $pkg_id = $request_data['xmws']['content']['package_id'];
        }
        

        if($pkg_id){
            $response_xml = "\n";
            $pkg_name = module_controller::GetPackageName($pkg_id);
            
            if (!fs_director::CheckForEmptyValue($pkg_name)) {
                $package['name'] = $pkg_name;
                 $response_xml = $response_xml . ws_xmws::NewXMLContentSection('package', $package);
            }

            $dataobject = new runtime_dataobject();
            $dataobject->addItemValue('response', '');
            $dataobject->addItemValue('content', $response_xml);

            return $dataobject->getDataObject();
        }   
    }
    
    public function GetPeriodInfo(){
        //$period_duration = 0;
        //$period_amt = 0;
        $pkg_id = 0;
        $pid = 0;
        $request_data = ws_generic::XMLToArray($this->wsdata);
        
        if(isset($request_data['xmws']['content']['package_id']) && isset($request_data['xmws']['content']['period_id'])){
            $pid = $request_data['xmws']['content']['period_id'];
            $pkg_id = $request_data['xmws']['content']['package_id'];
        }
        

        if($pkg_id && $pid){
            $response_xml = "\n";
            $period_info = module_controller::GetPeriodInfo($pkg_id, $pid);
            //var_dump($period_info);
            if (!fs_director::CheckForEmptyValue($period_info)) {
                $period['duration'] = $period_info['period_duration'];
                $period['amount'] = $period_info['package_amount'];
                
                 $response_xml = $response_xml . ws_xmws::NewXMLContentSection('period', $period);
            }

            $dataobject = new runtime_dataobject();
            $dataobject->addItemValue('response', '');
            $dataobject->addItemValue('content', $response_xml);

            return $dataobject->getDataObject();
        }    
    }
    
    public function CreateNewAccount(){
        $user_data = '';
        $request_data = ws_generic::XMLToArray($this->wsdata);

        if(isset($request_data['xmws']['content']) && is_array($request_data['xmws']['content'])){
            foreach($request_data['xmws']['content'] as $itm_idx=>$data){
                $user_data[$itm_idx] = $data;
            }
        }
        
        //print_r($user_data);
        //exit;
        
        if(is_array($user_data)){
            $response_xml = "\n";
            //create new user
            $new_user = module_controller::CreateNewUser($user_data);
            $domain_id = '';
            $new_user_id = '';
            

            if(isset($new_user['ac_id_pk'])){
                $new_user_id = $new_user['ac_id_pk'];
            }
            
            if(isset($new_user['domain_id'])){
               $domain_id = $new_user['domain_id'];
            }

            if (!fs_director::CheckForEmptyValue($new_user_id)){
                //if(!is_numeric($domain_id)){
                   // return $new_user;
                //}                
                
                if(is_numeric($new_user_id)){
                    $user_data['domain_id'] = $domain_id;
                    //create order invoice
                    $invoice_info = module_controller::SaveInvoiceOrder($new_user_id,$user_data);
                    //die(var_dump($invoice_info));
                    $invoice_link = '';
                    if($invoice_info){
                        //fetch billing URL from reseller user settings
                        $billing_url = module_controller::appSetting($user_data['zpx_uid'],'website_billing_url');
                        $billing_enabled_yn = module_controller::appSetting($user_data['zpx_uid'],'billing_enabled_yn');

                        if($billing_url){
                            $invoice_link = $billing_url.'/view_invoice.php?invoice='.$invoice_info['reference'];
                        }
                        
                        if(isset($invoice_info['activated_yn']) && $invoice_info['activated_yn'] == 1){
                            $billing_enabled_yn = 0;
                        }
                        
                        //send Welcome Email
                        $user_password = '';
                        $username = '';
                        $panel_url = '';
                        $new_invoice_info = array();
                        if(!$billing_enabled_yn){
                        //fetch welcome message
                            $emailbody = module_controller::appSetting($user_data['zpx_uid'],'welcome_message');                
                            $username = $invoice_info['username'];
                            $user_password = $invoice_info['user_password'];
                            $panel_url = 'http://'.module_controller::getPanelURL();
                        } else {
                            //fetch welcome message
                            $emailbody = module_controller::appSetting($user_data['zpx_uid'],'order_message');                
                        }

                        //fetch company name
                        $company_name = module_controller::appSetting($user_data['zpx_uid'],'company_name');

                        //fetch invoice unpaid days
                        $inv_del_days = module_controller::appSetting($user_data['zpx_uid'],'pending_invoice_delete_days');
                
                        if($emailbody && $user_data['email_address']){
                            $emailbody = str_replace("{{fullname}}", $user_data['fullname'], $emailbody);
                            $emailbody = str_replace("{{company_name}}", $company_name, $emailbody);
                            $emailbody = str_replace("{{invoice_link}}", $invoice_link, $emailbody);
                            $emailbody = str_replace("{{invoice_reference}}", $invoice_info['reference'], $emailbody);
                            $emailbody = str_replace("{{invoice_unpaid_days}}", $inv_del_days, $emailbody);
                            $emailbody = str_replace("{{username}}", $username, $emailbody);
                            $emailbody = str_replace("{{password}}", $user_password, $emailbody);
                            $emailbody = str_replace("{{panel_url}}", $panel_url, $emailbody);

                            //$phpmailer = new sys_email();
                            $subject = "Your Order at ".$company_name;                    
                            if(!$billing_enabled_yn){
                                $subject = "Welcome to $company_name!";
                            }
                            /*$phpmailer->Body = $emailbody;
                            $phpmailer->AddAddress($user_data['email_address']);
                            $phpmailer->SendEmail();*/

                            module_controller::sendMail(array('to' => $user_data['email_address'], 'subject' => $subject, 'message' => $emailbody, 'reseller_id' => $user_data['zpx_uid']));        
                        }                
                        $new_invoice_info['reference'] = $invoice_info['reference'];               
                        
                    }
                    
                
                
                } else {
                   $new_invoice_info['message'] = $new_user;
                }
            }

                    
            $response_xml = $response_xml . ws_xmws::NewXMLContentSection('invoice', $new_invoice_info);

            $dataobject = new runtime_dataobject();
            $dataobject->addItemValue('response', '');
            $dataobject->addItemValue('content', $response_xml);

            return $dataobject->getDataObject();
        } else {
            die('An authentication error has occured.');
        }
    }
    
    
    public function GetInvoiceInfo(){
        $request_data = ws_generic::XMLToArray($this->wsdata);
        $invoice_result['error'] = 0;
        if((isset($request_data['xmws']['content']['zpx_uid']) && ($request_data['xmws']['content']['zpx_uid']) > 0) && (isset($request_data['xmws']['content']['ref']))){
            $user_id = $request_data['xmws']['content']['zpx_uid'];
            $inv_ref = $request_data['xmws']['content']['ref'];

            $invoice = module_controller::getInvoice($inv_ref,$user_id);
            
            if(!is_array($invoice)){
                $invoice_result['error'] = 1;
                $invoice_result['message'] = $invoice;
            } else {
                $invoice_result = $invoice;
                //$invoice_result['error'] = 0;
            }
        } else {
            $invoice_result['error'] = 1;
            $invoice_result['message'] = 'An authentication error occured, cannot fetch invoice!';
        }

        $response_xml = "\n";

        if (!fs_director::CheckForEmptyValue($invoice_result)){
             $response_xml = $response_xml . ws_xmws::NewXMLContentSection('invoice', $invoice_result);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();          
    }
    
    public function GetUserInfo(){
        $request_data = ws_generic::XMLToArray($this->wsdata);
        $user_result['error'] = 0;
        if((isset($request_data['xmws']['content']['zpx_uid']) && ($request_data['xmws']['content']['zpx_uid']) > 0) && (isset($request_data['xmws']['content']['uid']))){
            //$zpx_uid = $request_data['xmws']['content']['zpx_uid'];
            $user_id = $request_data['xmws']['content']['uid'];

            $user = ctrl_users::GetUserDetail($user_id);
            
            if(!is_array($user)){
                $user_result['error'] = 1;
                $user_result['message'] = $user;
            } else {
                $user_result['fullname'] = utf8_decode($user['fullname']);
                $user_result['email'] = $user['email'];
                $user_result['address'] = utf8_decode($user['address']);
                $user_result['postcode'] = $user['postcode'];
                $user_result['phone'] = $user['phone'];

                //$user_result['error'] = 0;
            }
        } else {
            $user_result['error'] = 1;
            $user_result['message'] = 'An authentication error occured, cannot fetch customer info!';
        }

        $response_xml = "\n";

        if (!fs_director::CheckForEmptyValue($user_result)){
             $response_xml = $response_xml . ws_xmws::NewXMLContentSection('user', $user_result);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();    
    }
    
    
    public function GetPaymentMethods(){
        $request_data = ws_generic::XMLToArray($this->wsdata);
        $options_result['error'] = 0;
        if(isset($request_data['xmws']['content']['zpx_uid']) && $request_data['xmws']['content']['zpx_uid'] > 0){
            $zpx_uid = $request_data['xmws']['content']['zpx_uid'];

            $options = module_controller::ListPaymentOptions($zpx_uid);
            if(!is_array($options)){
                $options_result['error'] = 1;
                $options_result['message'] = $options;
            } else {
                
                $options_result['options'] = $options;
                 //$options_result['error'] = 0;
            }
        } else {
            $options_result['error'] = 1;
            $options_result['message'] = 'An authentication error occured, cannot fetch payment methods!';
        }

        $response_xml = "\n";
        if(!fs_director::CheckForEmptyValue($options_result['options'])){
           $response_xml .= $response_xml . ws_xmws::NewXMLContentSection('options', $options_result['options']);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();    
    }
    
    public function GetPaymentCustomFields(){
        $request_data = ws_generic::XMLToArray($this->wsdata);
        $options_result['error'] = 0;
        if((isset($request_data['xmws']['content']['zpx_uid']) && $request_data['xmws']['content']['zpx_uid'] > 0) && (isset($request_data['xmws']['content']['id']) && $request_data['xmws']['content']['id'] > 0)){

            $zpx_uid = $request_data['xmws']['content']['zpx_uid'];
            $payment_option_id = $request_data['xmws']['content']['id'];

            $fields = module_controller::getPaymentOptionFields($payment_option_id);
            if(!is_array($fields)){
                $fields_result['error'] = 1;
                $fields_result['message'] = $fields;
            } else {
                $x = 1;
                $res = array();
                foreach($fields as $field){
                    $field_value = module_controller::getPaymentOptionFieldValue($zpx_uid, $payment_option_id, $field['name']);
                    $res['field_'.$x] = json_encode(array('name' => $field['name'],
                                                            'value' => $field_value));
                    
                    $x++;
                }
                $fields = $res;
                
                $fields_result['fields'] = $fields;
                 //$options_result['error'] = 0;
            }
        } else {
            $fields_result['error'] = 1;
            $fields_result['message'] = 'An authentication error occured, cannot fetch payment methods!';
        }

        $response_xml = "\n";
        if(!fs_director::CheckForEmptyValue($fields_result['fields'])){
           $response_xml .= $response_xml . ws_xmws::NewXMLContentSection('fields', $fields_result['fields']);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();
    
    }
    
    
    public function CompleteInvoice(){
        $request_data = ws_generic::XMLToArray($this->wsdata);
        $payment_result['error'] = 0;
        
        if((isset($request_data['xmws']['content']['zpx_uid']) && $request_data['xmws']['content']['zpx_uid'] > 0) && (isset($request_data['xmws']['content']['ref'])) && (isset($request_data['xmws']['content']['transaction_id'])) && (isset($request_data['xmws']['content']['date']))){
            $zpx_uid = $request_data['xmws']['content']['zpx_uid'];
            $transaction_id = $request_data['xmws']['content']['transaction_id'];
            $date = $request_data['xmws']['content']['date'];
            $invoice_reference = $request_data['xmws']['content']['ref'];
            $payment_method_id = $request_data['xmws']['content']['payment_method_id'];

            $payment = module_controller::PayInvoice($zpx_uid,$invoice_reference,$transaction_id,$payment_method_id,$date);
            if(!$payment){
                $payment_result['error'] = 1;
                $payment_result['message'] = $payment;
            } else {
                
                $payment_result['status'] = $payment;
                 //$payment_result['error'] = 0;
            }
        } else {
            $payment_result['error'] = 1;
            $payment_result['message'] = 'An authentication error occured, cannot make payment!';
        }

        $response_xml = "\n";
        if(!fs_director::CheckForEmptyValue($payment_result['status'])){
           $response_xml .= $response_xml . ws_xmws::NewXMLContentSection('result', $payment_result);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();        
    }
    
    public function InvoiceReminder(){
       $request_data = ws_generic::XMLToArray($this->wsdata);
        if((isset($request_data['xmws']['content']['zpx_uid']) && $request_data['xmws']['content']['zpx_uid'] > 0)){

            $zpx_uid = $request_data['xmws']['content']['zpx_uid'];

            $result = module_controller::RemindInvoices($zpx_uid);
        }
        
        $response_xml = "\n";
        if(!fs_director::CheckForEmptyValue($result['status'])){
           $response_xml .= $response_xml . ws_xmws::NewXMLContentSection('result', $result);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();        
    }

    public function RemindDomainExpiration(){
       $request_data = ws_generic::XMLToArray($this->wsdata);
        if((isset($request_data['xmws']['content']['zpx_uid']) && $request_data['xmws']['content']['zpx_uid'] > 0)){

            $zpx_uid = $request_data['xmws']['content']['zpx_uid'];

            $result = module_controller::DomainRemindExpiration($zpx_uid);
        }
        
        $response_xml = "\n";
        if(!fs_director::CheckForEmptyValue($result['status'])){
           $response_xml .= $response_xml . ws_xmws::NewXMLContentSection('result', $result);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();        
    }
    
    public function DisableExpiredDomains(){
       $request_data = ws_generic::XMLToArray($this->wsdata);
        if((isset($request_data['xmws']['content']['zpx_uid']) && $request_data['xmws']['content']['zpx_uid'] > 0)){

            $zpx_uid = $request_data['xmws']['content']['zpx_uid'];

            $result = module_controller::DisableExpiredDomainProcess($zpx_uid);
        }
        
        $response_xml = "\n";
        if(!fs_director::CheckForEmptyValue($result['status'])){
           $response_xml .= $response_xml . ws_xmws::NewXMLContentSection('result', $result);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();    
    }

    public function DomainExpireDelete(){
       $request_data = ws_generic::XMLToArray($this->wsdata);
        if((isset($request_data['xmws']['content']['zpx_uid']) && $request_data['xmws']['content']['zpx_uid'] > 0)){

            $zpx_uid = $request_data['xmws']['content']['zpx_uid'];

            $result = module_controller::DeleteExpiredDomainProcess($zpx_uid);
        }
        
        $response_xml = "\n";
        if(!fs_director::CheckForEmptyValue($result['status'])){
           $response_xml .= $response_xml . ws_xmws::NewXMLContentSection('result', $result);
        }

        $dataobject = new runtime_dataobject();
        $dataobject->addItemValue('response', '');
        $dataobject->addItemValue('content', $response_xml);

        return $dataobject->getDataObject();    
    }

}

?>
