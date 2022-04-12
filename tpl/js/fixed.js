jQuery(function($) {
    try{
        jQuery("input[name=user_name]").val(user_name).attr("readonly", "readonly");
    }catch(e){}
    
    try{
        jQuery("#date_birthday").val(birthday).attr("readonly", "readonly");
        jQuery("#date_birthday ~ .inputDate").val(birthday2);
    }catch(e){}
	
	
}	