function completeInsertPlugin(ret_obj) 
{
	alert(ret_obj['message']);
	location.replace( current_url.setQuery('act','dispSvauthAdminUpdatePlugin').setQuery('plugin_srl',ret_obj['plugin_srl']) );
}