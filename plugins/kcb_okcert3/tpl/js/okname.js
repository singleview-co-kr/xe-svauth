function jsSubmit(){	var okname_frm = document.okname_frm;
exec_xml("okname","procOknameSafeHsPOP",false,function(ret_obj){
	jQuery("#rqst_data").val(ret_obj['message']);
	window.open("", "auth_popup", "width=432,height=560,scrollbar=yes");
	openPop();
});
}
function certKCBIpin(){
	var popupWindow = window.open( "", "kcbPop", "left=200, top=100, status=0, width=450, height=550" );
	document.kcbInForm.target = "kcbPop";
	document.kcbInForm.action = "https://ipin.ok-name.co.kr/tis/ti/POTI01A_LoginRP.jsp";
	document.kcbInForm.submit();
	popupWindow.focus();	
	return;	
}
function openPop(){
	window.name = "";
	document.auth_frm.action = "http://safe.ok-name.co.kr/CommonSvl";
	document.auth_frm.target = "auth_popup";
	document.auth_frm.method = "post";
	document.auth_frm.submit();
}