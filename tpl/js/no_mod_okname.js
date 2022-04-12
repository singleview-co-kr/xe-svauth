//fixed value
jQuery(function($) {	
	if(fixed){
		for (var val in fixed) {
			if(!fixed[val]['value']) continue;
			if(val == 'birthday2'){
				jQuery("#birthday").val(fixed[val]['value']);
				jQuery("#birthday").attr("readonly", "readonly");
			} else {
				jQuery("input[name="+fixed[val]['name']+"]").val(fixed[val]['value']);
				jQuery("input[name="+fixed[val]['name']+"]").attr("readonly", "readonly");
			}
		}
	}
	
	for (var val in no_mod_okname) {
		if(val['ty'] == 'all') continue;
		switch(val){
			case 'birthday':
				jQuery("input[name="+no_mod_okname[val]['id']+"] ~ .inputDate").attr("readonly", "readonly").removeClass('inputDate');
				jQuery("input[name="+no_mod_okname[val]['id']+"]").nextAll("span").remove();
			break;
			default:
                jQuery("input[name="+no_mod_okname[val]['id']+"]").attr("readonly", "readonly");
                break;
			break;
		}
		if(no_mod_okname[val]['ty'] == 'hide') {
			jQuery("input[name="+no_mod_okname[val]['id']+"]").parents('tr').hide();
			jQuery("input[name="+no_mod_okname[val]['id']+"]").parents('li').hide();
		}
	}
});