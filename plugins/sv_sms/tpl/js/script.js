var _g_$oBtn;

function getAuthCode()
{
	var sPhoneNumber = jQuery('#phone_number').val();
	sPhoneNumber = sPhoneNumber.trim(); 

	if( sPhoneNumber.length == 0 )
	{
		alert('연락처를 입력해 주세요.');
		return;
	}

	disableBtn( '#get_authcode' );

	var nPluginSrl = jQuery('#plugin_srl').val();
	var params = new Array();

	params['plugin_srl'] = nPluginSrl;
	params['phone_number'] = sPhoneNumber;

	var respons = ['success'];
	exec_xml('svdocs', 'procSvauthSetAuthCode', params, function(ret_obj) {
		if( ret_obj['message'] )
			alert(ret_obj['message']);

		if( ret_obj['success'] == -1 )
			_activateBtn();
	},respons);
}

function disableBtn( sBtnId )
{
	_g_$oBtn = jQuery(sBtnId);
	_g_$oBtn.prop('disabled', true);
	_g_$oBtn.css('background-color','#323232');
	_g_$oBtn.css('color','#b0b0b0');
	_g_$oBtn.css('border','1px solid #323232');
}

function _activateBtn()
{
	_g_$oBtn.prop('disabled', false);
	_g_$oBtn.css('background-color','#ed1c24');
	_g_$oBtn.css('color','#fff');
	_g_$oBtn.css('border','1px solid #ed1c24');
}


function validateAuthCode() 
{
	var sAuthcode = jQuery('#authcode').val();
	var sPhoneNumber = jQuery('#phone_number').val();
	if( sPhoneNumber.length == 0 )
	{
		alert('핸드폰 번호를 입력해 주세요.');
		return;
	}
	
	var nPluginSrl = jQuery('#plugin_srl').val();
	var params = new Array();

	params['plugin_srl'] = nPluginSrl;
	params['authcode'] = sAuthcode;
	params['phone_number'] = sPhoneNumber;
	disableBtn( '#btn_registration' );

	var respons = ['cleee','result'];
	exec_xml('svauth', 'procSvauthValidateAuthCode', params, function(ret_obj) {
		if( ret_obj['message'] == 'success' )
		{
			alert('인증 성공했습니다.');
			window.location.href = '/index.php?act=dispMemberSignUpForm';
		}
	},respons);
}