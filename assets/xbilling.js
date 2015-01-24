$(function() {
	$('#frm_create_payment_option').submit(function (e){
		$('#new_payment_option_msg').hide();
		//js_app.loader.hideLoader();
		if(!$('#txt_payment_option_name').val()){
			$('#new_payment_option_msg').html(form_msgs['xbilling_empty_option_title']);
			$('#new_payment_option_msg').show();
			$('#txt_payment_option_name').focus();
			
			return false;		
		}

		if(!$('#txt_payment_option_form_html').val()){
			$('#new_payment_option_msg').html(form_msgs['xbilling_empty_option_html']);
			$('#new_payment_option_msg').show();
			$('#txt_payment_option_form_html').focus();
			return false;		
		}
		
		if($('#frm_create_payment_option').valid()){
			js_app.loader.showLoader();
		} else {
			return false;
		}
	});

	$('#frm_update_payment_option').submit(function (e){
		js_app.loader.hideLoader();
		$('#edit_payment_option_msg').hide();
		
		
		if(!$('#txt_payment_option_name').val()){
			$('#edit_payment_option_msg').html(form_msgs['xbilling_empty_option_title']);
			$('#edit_payment_option_msg').show();
			$('#txt_payment_option_name').focus();
			
			return false;		
		}

		if(!$('#txt_payment_option_form_html').val()){
			$('#edit_payment_option_msg').html(form_msgs['xbilling_empty_option_html']);
			$('#edit_payment_option_msg').show();
			$('#txt_payment_option_form_html').focus();
			return false;		
		}
		
		if($('#frm_update_payment_option').valid()){
			js_app.loader.showLoader();
		} else {
			return false;
		}				
	});	

	$('#frm_create_payment_method').submit(function (e){
		$('#new_payment_method_msg').hide();
		if($('#frm_create_payment_method').valid()){
			js_app.loader.showLoader();
		} else {
			return false;
		}
	});

	$('#frm_update_payment_method').submit(function (e){
		js_app.loader.hideLoader();
		$('#edit_payment_method_msg').hide();
		if($('#frm_update_payment_method').valid()){
			js_app.loader.showLoader();
		} else {
			return false;
		}
	});
		
	$('.delete-plan').on('click', function (){
		var _id = $(this).attr('data-id');
		//Confirm Plan Delete
		js_app.dialog.confirm({
			title: form_msgs['xbilling_plan_delete_dialog_title'],
			message: form_msgs['xbilling_item_delete_confirm_msg'],
			width: 300,
			cancelButton: {
			    text: form_msgs['xbilling_cancel_btn_label'],
			    show: true,
			    class: 'btn-default'
			},
			okButton: {
			    text: form_msgs['xbilling_ok_btn_label'],
			    show: true,
			    class: 'btn-primary'
			},
			cancelCallback: function() { return false; },
			okCallback: function() { 
//				$('#svn_item_id').val(_id);
				$('#frmServicePeriod').append('<input type="hidden" name="x_item_id" value="'+_id+'">');
				js_app.loader.showLoader();
				_change_action('frmServicePeriod','DeletePeriod');
					//$('#frm_forwarded_domains').submit(); 
			}
		});
	});
	
		
	$('.delete-payment-option').on('click', function (){
		var _id = $(this).attr('data-id');
		//Confirm Plan Delete
		js_app.dialog.confirm({
			title: form_msgs['xbilling_payment_option_delete_dialog_title'],
			message: form_msgs['xbilling_item_delete_confirm_msg'],
			width: 300,
			cancelButton: {
			    text: form_msgs['xbilling_cancel_btn_label'],
			    show: true,
			    class: 'btn-default'
			},
			okButton: {
			    text: form_msgs['xbilling_ok_btn_label'],
			    show: true,
			    class: 'btn-primary'
			},
			cancelCallback: function() { return false; },
			okCallback: function() { 
				$('#frmPaymentOption').append('<input type="hidden" name="x_item_id" value="'+_id+'">');
				js_app.loader.showLoader();
				_change_action('frmPaymentOption','DeletePaymentOption');
			}
		});
	});
		
		
	$('.delete-voucher').on('click', function (){
		var _id = $(this).attr('data-id');
		//Confirm Plan Delete
		js_app.dialog.confirm({
			title: form_msgs['xbilling_voucher_delete_dialog_title'],
			message: form_msgs['xbilling_item_delete_confirm_msg'],
			width: 300,
			cancelButton: {
			    text: form_msgs['xbilling_cancel_btn_label'],
			    show: true,
			    class: 'btn-default'
			},
			okButton: {
			    text: form_msgs['xbilling_ok_btn_label'],
			    show: true,
			    class: 'btn-primary'
			},
			cancelCallback: function() { return false; },
			okCallback: function() { 
				$('#frmVouchers').append('<input type="hidden" name="x_item_id" value="'+_id+'">');
				js_app.loader.showLoader();
				_change_action('frmVouchers','DeleteVoucher');
			}
		});
	});
		
	$('.resend-invoice').on('click', function (){
		var _id = $(this).attr('data-id');
		//Confirm Invoice Resend
		js_app.dialog.confirm({
			title: form_msgs['xbilling_invoice_resend_dialog_title'],
			message: form_msgs['xbilling_invoice_resend_confirm_msg'],
			width: 300,
			cancelButton: {
			    text: form_msgs['xbilling_cancel_btn_label'],
			    show: true,
			    class: 'btn-default'
			},
			okButton: {
			    text: form_msgs['xbilling_ok_btn_label'],
			    show: true,
			    class: 'btn-primary'
			},
			cancelCallback: function() { return false; },
			okCallback: function() { 
				$('#frmOrders').append('<input type="hidden" name="x_item_id" value="'+_id+'">');
				js_app.loader.showLoader();
				_change_action('frmOrders','ResendInvoice');
			}
		});
	});
	
	$('.cancel-action').on('click', function () {
		$('#xbilling_view').val($(this).attr('data-rel'));
		$('#xbilling_navForm').submit();
		//console.log($(this).attr('data-rel'));
	});


	$('#frm_create_new_account').submit(function (e){
		$('#new_account_msg').hide();
		if($('#frm_create_payment_method').valid()){
			js_app.loader.showLoader();
		} else {
			return false;
		}
		return false;
	});
	
	$('#slt_email_format').on('change', function(){
		wysiwyg_format();
	});
 });
 
 function wysiwyg_format(){
	if($('#slt_email_format').val() == 1){
		$("textarea").each(function(){
		    if(CKEDITOR.instances[$(this).attr('id')]){
			    CKEDITOR.instances[$(this).attr('id')].destroy();
		    }
		});		
	} else if($('#slt_email_format').val() == 2){
		$("textarea").each(function(){
		    //$(this).addClass('ckeditor');
		    $(this).ckeditor();  
		});
	} 	
 }

 function _toggle_recaptcha(){
    if($('#recaptcha_disabled_yn').is(':checked')){
        $('#td_recaptcha_public_key').hide();
        $('#td_recaptcha_private_key').hide();
    } else {
        $('#td_recaptcha_public_key').show();
        $('#td_recaptcha_private_key').show();    
    }
 }
 
 
function _view(view){
        if(view){
            $('#xbilling_view').val(view);
            $('#xbilling_navForm').submit();
        }
}

    function _change_action(fid, _action, item_id){
        var _attr = './?module=xbilling';
        if(!_action){
            _action = '';
        }
        
        if(!item_id){
            item_id = 0;
        }

        if(item_id){
            $('#item_id').val(item_id);
        }
        if(_action){
            _attr += '&action='+_action;
        }
        
        $('#'+fid).attr('action', _attr);
        $('#'+fid).submit();
    }
    
    function _package_periods(pkg_id){
        if(pkg_id){
		js_app.loader.showLoader();
		$div = $('<div><form action="./?module=xbilling&action=UpdatePackagePeriods" method="post" id="frmPackagesPeriods"></form></div>');
		$div.attr('id', 'package_plans');
        
          /*$('#div_child_periods').html('<p>&nbsp;</p><img src="'+_module_dir+'/assets/loader.gif"><br />Please wait...');
          $('#div_package_periods').show();
          
            $("#div_child_periods").load("./?module=xbilling&action=LoadPackagePeriods&pid="+pkg_id, function(response, status, xhr) {
              if (status == "error") {
                $('#div_child_periods').html('<p><: An error occured while fetching package service periods. :>');
              }
            });*/
            
            $('#xbillingModalBody').html('Please wait...');
            
	    $div.find('form').load("./?module=xbilling&action=LoadPackagePeriods&pid="+pkg_id, function(response, status, xhr) {
		    if (status == "error") {
			 $div.html('<p><: An error occured while fetching package service plans. :>');
		    }
	    });
	    js_app.loader.hideLoader();
	    $('#xbillingModalTitle').html('Manage Service Plans');
	    $('#xbillingModalBody').html($div);
	    
            $('#xbilling_btn_save_modal').show();
            $('#xbilling_btn_close_modal').html('Cancel');
	    
	    $('#xbilling_btn_save_modal').on('click', function (){
	    	_update_package_periods();
	    });
       
            $('#xbillingModal').modal({
		backdrop: 'static',
		show: true
            });
        }
    }

    function _free_pkg(_id){
        if(_id){
            $('#free_yn_'+_id).is(':checked') ? $("#manage_periods_"+_id).hide() : $("#manage_periods_"+_id).show();
        }
        $('#div_package_periods').hide();
    }

    
    function _update_package_periods(){
        var _action = $('#frmPackagesPeriods').attr('action');
        var _data = $('#frmPackagesPeriods').serialize();
        var _req = $.post(_action,_data, function(response, status, xhr){
                        if(status == 'error'){
                           $('#package_plans').html('<p>&nbsp</p><div style="color: #f00;"><: An error occured while updating package service periods. :></div>');
                        } else if (status == "success") {
                           $('#package_plans').html('<p>&nbsp</p>'+response);
                        }
                    });
                    
                    $('#xbilling_btn_save_modal').hide();
                    $('#xbilling_btn_close_modal').html('Close');
        return false;    
    }
    
    function _cancel_edit(){
        $('#div_package_periods').hide();
    }

    function add_payment_field(field_container_id){
        total_fields++;
        
        var $new_field = $('#payment_field_1').clone();
        $new_field.attr('id', 'payment_field_'+total_fields);
        $new_field.find('input[name^=field_labels]').attr('name', 'field_labels['+total_fields+']').val('');
        $new_field.find('input[name^=field_names]').attr('name', 'field_names['+total_fields+']').val('');

        var _remove_icon = '<img id="remove_field_'+total_fields+'" src="'+_module_dir+'/assets/icon_remove_field.png" hspace="5" onclick="remove_payment_field(\'payment_field_'+total_fields+'\');" style="cursor: pointer;" alt="remove field"  title="remove field"  />'

        var _last_tr_id = '';
        $.each($('#payment_option').children(), function(idx,_tbody){
            $.each($(_tbody).children(), function(idx,_tr){
                //console.log(_tr);
                if($(_tr).attr('id')){
                    _last_tr_id = $(_tr).attr('id');
                }
            });            
        });

        $('#'+_last_tr_id).after($new_field);
        $("input",'#payment_field_'+total_fields).attr('value','');        
        $("img",'#payment_field_'+total_fields).after(_remove_icon);
        
        payment_options_validation_rules();

    }
    
    function payment_options_validation_rules(){
    	$("[name^=field_labels").each(function(){
            $(this).rules("add", {
            required: true,
             });   
        });

    	$("[name^=field_names").each(function(){
            $(this).rules("add", {
            required: true,
             });   
        });
    }

    function payment_methods_validation_rules(){
    	$("input[type=text]").each(function(){
            $(this).rules("add", {
            required: true,
             });   
        });
    }

    function new_account_validation_rules(){    	
    	/*$("input[type=text]").each(function(){
            $(this).rules("add", {
            required: true,
             });   
        });*/
/* 	$("#frm_create_new_account").validate({
	  ignore: ".ignore",
		rules: {
		    // simple rule, converted to {required:true}
		    name: "required",
		    
		  }	  
	});
	$("#frm_create_new_account").removeAttr("novalidate");  */      
    }

    function remove_payment_field(field_container_id){
        $('#'+field_container_id).remove();
    }
 
 function _reload_option_fields(){
    var option_id = $('#payment_option_id').val();
    if(option_id){
       $("#payment_option_fields").load("./?module=xbilling&action=LoadPaymentOptionFields&id="+option_id, function(response, status, xhr) {
          if (status == "error") {
            $('#payment_option_fields').html('<p><: An error occured while fetching payment option fields. :>');
          }
       });                  
    }
 }    

    $.extend({
        password: function (length, special) {
            var iteration = 0;
            var password = "";
            var randomNumber;
            if(special == undefined){
                var special = false;
            }
            while(iteration < length){
                randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
                if(!special){
                    if ((randomNumber >=33) && (randomNumber <=47)) { continue; }
                    if ((randomNumber >=58) && (randomNumber <=64)) { continue; }
                    if ((randomNumber >=91) && (randomNumber <=96)) { continue; }
                    if ((randomNumber >=123) && (randomNumber <=126)) { continue; }
                }
                iteration++;
                password += String.fromCharCode(randomNumber);
            }
            return password;
        }
    });
    

 function _toggle_domain_home(_typ){
    if(_typ == 1){
        $('#domain_home').hide();
    } else if(_typ == 2){
        $('#domain_home').show();    
    }
 }
 
 function _pkg_periods(){
    var pkg_id = $("#slt_packages").val();
    if(pkg_id){
          $('#slt_periods').html('<option value="0"><: please wait... :>.</option>');
            $("#slt_periods").load("./?module=xbilling&action=UserPackagePeriods&pid="+pkg_id, function(response, status, xhr) {
              if (status == "error") {
                $('#slt_periods').html('<option value="0"><: no service periods configured :>.</option>');
              }
            });          
    
    }
 }
    
/*function _change_action(fid, _action, item_id){
        var _attr = './?module=xbilling';
        if(!_action){
            _action = '';
        }
        
        if(!item_id){
            item_id = 0;
        }

        if(item_id){
            $('#item_id').val(item_id);
        }
        
        if(_action){
            _attr += '&action='+_action;
        }
        
        $('#'+fid).attr('action', _attr);
        $('#'+fid).submit();
}*/
