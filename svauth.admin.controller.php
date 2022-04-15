<?php
/**
* @class  svauthAdminController
* @author singleview(root@singleview.co.kr)
* @brief  svauth admin Controller class
**/ 
class svauthAdminController extends svauth 
{
 /**
 * @brief 초기화
 **/
	function init()
	{
	}
/**
* @brief arrange and save module config
**/
	private function _saveModuleConfig($oArgs)
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');
		unset($args->act,$args->error_return_url);
		$config = $oModuleModel->getModuleConfig('svauth');

		foreach($oArgs as $k => $v) 
			$config->{$k} = $v;
		$output = $oModuleController->insertModuleConfig('svauth',$config);
		return $output;
	}	
/**
* @기본 & 회원모듈 연동 설정저장
**/
	function procSvauthAdminInsertConfig()
	{
		$args = Context::getRequestVars();
		if($args->use_mobile != 'Y') 
			$args->use_mobile = '';

		$output = $this->_saveModuleConfig($args);

		//$oModuleController = &getController('module');
		//$oModuleModel = &getModel('module');
		//unset($args->act,$args->error_return_url);
		//$config = $oModuleModel->getModuleConfig('svauth');

		//foreach($args as $k => $v) 
		//	$config->{$k} = $v;
		//$output = $oModuleController->insertModuleConfig('svauth',$config);
		$this->setMessage('success_updated');
		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvauthAdminDefaultSetting');
		$this->setRedirectUrl($returnUrl);
	}
/**
 * @brief insert plugin info.
 */
	function procSvauthAdminInsertPlugin()
	{
		$plugin_srl = getNextSequence();
        $args = new stdClass;
		$args->plugin_srl = $plugin_srl;
		$args->plugin = Context::get('plugin');
		$args->title = Context::get('title');
		$output = executeQuery("svauth.insertPlugin", $args);
		if(!$output->toBool()) 
			return $output;

		require_once(_XE_PATH_.'modules/svauth/svauth.plugin.php');
		require_once(_XE_PATH_.'modules/svauth/plugins/'.$args->plugin.'/'.$args->plugin.'.plugin.php');
        $sExcutable = sprintf('$oPlugin = new %s();', $args->plugin);
        eval($sExcutable);
		if(@method_exists($oPlugin,'pluginInstall'))
			$oPlugin->pluginInstall($args);
		$this->add('plugin_srl', $plugin_srl);
	}
/**
 * @brief direct connect board to plugin
 */
	function procSvauthAdminUpdateBoardConnect()
	{
		$aBoardPluginMatch = array();
		$oArgs = Context::getRequestVars();
		foreach($oArgs as $k => $v)
		{
			if(substr($k,0,5) == 'board')
			{
				$aChunk = explode('_', $k);
				$nBoardModuleSrl = $aChunk[1];
				$nPluginSrl = $v;
				$aBoardPluginMatch[$nBoardModuleSrl] = $nPluginSrl;
			}			
		}
        $oNewArgs = new stdClass;
		$oNewArgs->board_plugin = $aBoardPluginMatch;
		$output = $this->_saveModuleConfig($oNewArgs);
		if(!$output->toBool())
			$this->setMessage( 'error_occured' );
		else
			$this->setMessage( 'success_updated' );
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispSvauthAdminBoardConnect'));
	}
/**
 * @brief update plugin info. (it will be deleted in the future)
 */
	function procSvauthAdminUpdatePlugin()
	{
		$oSvauthAdminModel = &getAdminModel('svauth');
		// module, act, layout_srl, layout, title을 제외하면 확장변수로 판단.. 좀 구리다..
		$extra_vars = Context::getRequestVars();
		unset($extra_vars->module);
		unset($extra_vars->act);
		unset($extra_vars->plugin_srl);
		unset($extra_vars->plugin);
		unset($extra_vars->title);

		$args = Context::gets('plugin_srl','title');
		$oPluginInfo = $oSvauthAdminModel->getPluginInfo($args->plugin_srl);

		// extra_vars의 type이 image일 경우 별도 처리를 해줌
		if($oPluginInfo->extra_var) 
		{
			foreach($oPluginInfo->extra_var as $name => $vars) 
			{
				if($vars->type=='file' || $vars->type=='image')
				{
					$image_obj = $extra_vars->{$name};
					$extra_vars->{$name} = $oPluginInfo->extra_var->{$name}->value;
					// 삭제 요청에 대한 변수를 구함
					$del_var = $extra_vars->{"del_".$name};
					unset($extra_vars->{"del_".$name});
					// 삭제 요청이 있거나, 새로운 파일이 업로드 되면, 기존 파일 삭제
					if($del_var == 'Y' || $image_obj['tmp_name']) 
					{
						FileHandler::removeFile($extra_vars->{$name});
						$extra_vars->{$name} = '';
						if($del_var == 'Y' && !$image_obj['tmp_name']) 
							continue;
					}
					// 정상적으로 업로드된 파일이 아니면 무시
					if(!$image_obj['tmp_name'] || !is_uploaded_file($image_obj['tmp_name'])) 
						continue;
					// 이미지 파일이면 확장자 검사
					if($vars->type=='image')
					{
						if(!preg_match("/\.(jpg|jpeg|gif|png|swf|enc|pem)$/i", $image_obj['name'])) 
							continue;
					}
					elseif($vars->type=='file') // 일반 파일이면 kcb okcert 라이센스 파일인 dat 확장자만 허용
					{
						if(!preg_match("/\.(dat)$/i", $image_obj['name'])) 
							continue;
					}
					// 경로를 정해서 업로드
					if($vars->type=='file')
					{
						$path = sprintf("./files/svauth/%s/",$args->plugin_srl);
						$sWebserverRoot = str_replace('/modules/svauth/svauth.admin.controller.php', '', realpath(__FILE__)); 
						$sFileAbsPath = $sWebserverRoot.'/files/svauth/'.$args->plugin_srl.'/';
					}
					elseif($vars->type=='image')
						$path = sprintf("./files/attach/images/%s/", $args->plugin_srl);

					// 디렉토리 생성
					if(!FileHandler::makeDir($path))
						continue;
					$filename = $path.$image_obj['name'];
					// 파일 이동
					if(!move_uploaded_file($image_obj['tmp_name'], $filename))
						continue;
					// 경로를 정해서 업로드
					if($vars->type=='file')
						$extra_vars->{$name} = $sFileAbsPath.$image_obj['name'];
					elseif($vars->type=='image')
						$extra_vars->{$name} = $filename;
				}
			}
		}
		// DB에 입력하기 위한 변수 설정
		$args->extra_vars = serialize($extra_vars);
		$output = executeQuery('svauth.updatePlugin', $args);
		if(!$output->toBool()) 
			return $output;
		$this->setMessage('설정이 저장되었습니다.');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSvauthAdminUpdatePlugin','plugin_srl',$args->plugin_srl));
	}
/**
 * @brief SMS 핸드폰 소유 인증 번호로 인증 삭제
 **/
	function procSvauthAdminDeleteSmsAuth()
	{
		$aSmsAuthSrl = Context::get('sms_auth_srls');
		$args = new stdClass();
		foreach($aSmsAuthSrl as $key=>$val)
		{
			$args->sms_auth_srl = $val;
			$output = executeQuery('svauth.deleteSmsAuthByAuthSrl', $args);
			if(!$output->toBool())
				return $output;
		}
		$this->setRedirectUrl(getNotEncodedUrl('', 'module','admin','act','dispSvauthAdminManageSmsAuthList'));
	}
/**
 * @brief 인증 번호로 인증 삭제
 **/
	function procSvauthAdminDeleteAuthByAuthLog()
	{
		$sDi = Context::get('di');
		$sCi = Context::get('ci');
		if(strlen($sDi) == 0 || strlen($sCi) == 0)
			return new BaseObject(-1, 'msg_invalid_requet');
		$args->di = $sDi;
		$args->ci = $sCi;
		$output = executeQuery('svauth.deleteAuthLogByCiByDi', $args);
		if(!$output->toBool())
			return $output;
		$this->setRedirectUrl(getNotEncodedUrl('', 'module','admin','act','dispSvauthAdminManageAuthList'));
	}
/**
 * @brief 회원 번호로 인증 삭제
 **/
	function procSvauthAdminDeleteAuthByMemberSrl()
	{
		$nMemberSrl = Context::get('member_srl');
		$args->is_deleted = 'N';
		$args->member_srl = $nMemberSrl;
		$output = executeQuery('svauth.getMemberAuthInfo', $args);
		$args->ci = $output->data->ci;
		$args->di = $output->data->di;
		$output = executeQuery('svauth.deleteAuthLogByCiByDi', $args);
		if(!$output->toBool())
			return $output;
		$args->is_deleted = 'Y';
		$output = executeQuery('svauth.deleteAuthByMemberSrl', $args);
		if(!$output->toBool())
			return $output;
		$oMemberController = getController('member');
		$output = $oMemberController->deleteMember($nMemberSrl);
		if(!$output->toBool()) 
			return $output;
		$this->setRedirectUrl(getNotEncodedUrl('', 'module','admin','act','dispSvauthAdminManageAuthMemberList'));
	}
}
?>