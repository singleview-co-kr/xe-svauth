<?php
/**
* @class  svauth Admin View
* @author singleview(root@singleview.co.kr)
* @brief  svauth admin View class
**/ 
class svauthAdminView extends svauth 
{
/**
 * @brief 초기화
 **/
	public function init()
	{
		//템플릿 경로설정
		$this->setTemplatePath($this->module_path.'tpl');
		//모듈설정은 항상 미리세팅
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('svauth');
		Context::set('config',$config);
	}
/**
 * @brief 기본설정
 **/
	public function dispSvauthAdminDefaultSetting()
	{
		$oSvauthAdminModel = &getAdminModel('svauth');
		// plugins
		$oAuthPlugin = $oSvauthAdminModel->getPluginList();
		Context::set('plugins', $oAuthPlugin);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);
		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// get skin path
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);
		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);
		$this->setTemplateFile('setting');
	}
/**
 * @brief connect board
 **/
	public function dispSvauthAdminBoardConnect()
	{
		$output = executeQueryArray('board.getBoardList', $args);
		ModuleModel::syncModuleToSite($output->data);
		$nIdx = 0;
		$aBoard = array();
		foreach($output->data as $key=>$val)
		{
            $aBoard[$nIdx] = new stdClass();
			$aBoard[$nIdx]->module_srl =  $val->module_srl;
			$aBoard[$nIdx++]->mid =  $val->mid;
		}
		Context::set('board_list', $aBoard);
		$oSvauthAdminModel = &getAdminModel('svauth');
		$aPluginList = $oSvauthAdminModel->getPluginList();
		Context::set('plugins', $aPluginList);
		$this->setTemplateFile('board_connect_list');
	}
/**
 * @brief create plugin
 **/
	public function dispSvauthAdminInsertPlugin()
	{
		// plugins
		$oSvauthModel = &getAdminModel('svauth');
		$oPlugIn = $oSvauthModel->getPluginsXmlInfo();
		Context::set('plugins', $oPlugIn);
		$this->setTemplateFile('insertplugin');
	}
/**
 * @brief plugin update form.
 */
	function dispSvauthAdminUpdatePlugin()
	{
		$oSvauthAdminModel = &getAdminModel('svauth');
		$nPluginSrl = Context::get('plugin_srl');
		// plugin info
		$oPluginInfo = $oSvauthAdminModel->getPluginInfo($nPluginSrl);
		$sPluginSkin = 'updateplugin';
		if($oPluginInfo->title == 'KCB okcert3 플러그인')
		{
			if(!function_exists('okcert3_u') || !function_exists('okcert3'))
			{
				Context::set('plugin_title', 'kcb_okcert3');
				$sPluginSkin = 'kcb_plugin_not_installed';
			}
		}
		else if($oPluginInfo->title == 'KCB OkName 플러그인')
		{
			if(!function_exists('okname'))
			{
				Context::set('plugin_title', 'kcb_okname');
				$sPluginSkin = 'kcb_plugin_not_installed';
			}
		}
		Context::set('plugin_info', $oPluginInfo);
		$this->setTemplateFile( $sPluginSkin );
	}
/**
 * @brief list plugins.
 */
	function dispSvauthAdminPluginList()
	{
		//$args->page = Context::get('page');
		//$output = executeQueryArray('svauth.getPluginList', $args);
		//if (!$output->toBool()) 
		//	return $output;
		$oSvauthAdminModel = &getAdminModel('svauth');
		$aPluginList = $oSvauthAdminModel->getPluginList();
		Context::set('plugins', $aPluginList);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('pluginlist');
	}
/**
 * @brief auth list. 인증취소는 1개씩만 허용
 */
	function dispSvauthAdminManageAuthMemberList()
	{
        $args = new stdClass;
		$args->page = Context::get('page');
		$output = executeQueryArray('svauth.getAuthList', $args);
		if(!$output->toBool()) 
			return $output;
		Context::set('auth_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('auth_member_list');
	}
/**
 * @brief auth log list. 인증취소는 1개씩만 허용
 */
	function dispSvauthAdminManageAuthList()
	{
		$sSearchKeyword = Context::get('search_keyword');
		if($sSearchKeyword)
			$args->search_keyword = $sSearchKeyword;
        $args = new stdClass;
		$args->page = Context::get('page');
		$output = executeQueryArray('svauth.getAuthLogList', $args);
		if(!$output->toBool()) 
			return $output;
		foreach($output->data as $key=>$val)
		{
			$aAuthInfo = unserialize($val->auth_info);
			$output->data[$key]->user_name = $aAuthInfo[user_name];
			$output->data[$key]->mobile = $aAuthInfo[mobile];		
			$output->data[$key]->auth_date = $aAuthInfo[auth_date];
		}
		Context::set('auth_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('auth_log_list');
	}
/**
 * @brief SMS를 이용한 핸드폰 소유 인증 내역 관리
 */
	function dispSvauthAdminManageSmsAuthList()
	{
		$sSearchKeyword = Context::get('search_keyword');
		$args = new stdClass;
        if($sSearchKeyword)
			$args->search_keyword = $sSearchKeyword;
		$args->page = Context::get('page');
		$output = executeQueryArray('svauth.getSmsAuthLogList', $args);
		if(!$output->toBool()) 
			return $output;
		foreach($output->data as $key=>$val)
		{
			if($output->data[$key]->authcode == 'err1')
				$output->data[$key]->authcode = '인증없이 인증코드 요청 반복';
			else if($output->data[$key]->authcode == 'err2')
				$output->data[$key]->authcode = '인증 완료 후 재시도';
			else if($output->data[$key]->authcode == 'err3' || $output->data[$key]->authcode == 'err5')
				$output->data[$key]->authcode = '인증번호 반복 요청하여 1일 차단';
			else if($output->data[$key]->authcode == 'err4' ||  $output->data[$key]->authcode == 'err6')
				$output->data[$key]->authcode = 'X초 동안 인증번효 요청 거부';
		}
		Context::set('auth_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		$this->setTemplateFile('sms_auth_log_list');
	}

/**
 * @brief 인증로그로 인증내역 삭제. 인증취소는 1개씩만 허용
 * birthday,gender,nationality,ISP
 */
	function dispSvauthAdminDeleteAuthLog()
	{
		$args->di = Context::get('di');
		$output = executeQueryArray('svauth.getAuthInfoByAuthLog', $args);
		if(!$output->toBool()) 
			return $output;
		if(count($output->data) != 1)
			return new BaseObject(-1, 'msg_weird_auth_record');
		$aRst = unserialize($output->data[0]->auth_info);
		Context::set('ci', $aRst[CI]);
		Context::set('valid_di', $aRst[DI]);
		Context::set('result_msg', $aRst[resultMsg]);
		Context::set('auth_date', $aRst[auth_date]);
		Context::set('user_name', $aRst[user_name]);
		Context::set('mobile', $aRst[mobile]);
		$this->setTemplateFile('auth_log_delete');
	}
/**
 * @brief 회원번호로 인증내역 삭제. 인증취소는 1개씩만 허용
 * birthday,gender,nationality,ISP
 */
	function dispSvauthAdminDeleteAuthByMemberSrl()
	{
		$args->member_srl = Context::get('member_srl');
		$args->is_deleted = 'N';
		$output = executeQueryArray('svauth.getMemberAuthInfo', $args);
		if(!$output->toBool())
			return $output;
		if(count($output->data ) != 1)
			return new BaseObject(-1, 'msg_weird_auth_record');
		Context::set('result_msg', $output->data[0]->result_msg);
		Context::set('auth_date', $output->data[0]->auth_date);
		Context::set('user_name', $output->data[0]->user_name);
		Context::set('mobile', $output->data[0]->mobile);
		$this->setTemplateFile('auth_delete');
	}
}
?>