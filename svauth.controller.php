<?php
/**
* @class  svauthController
* @author singleview(root@singleview.co.kr)
* @brief  svauth module Controller class
**/ 
class svauthController extends svauth 
{
/**
 * @brief initialization
 **/
	function init() 
	{
	}
/**
 * @brief 게시판 글 등록 후 트리거
 **/
	public function triggerInsertDocument(&$obj) 
	{
//var_dump( $obj);
//exit;
		$oModuleModel = &getModel('module');
		$oConfig = $oModuleModel->getModuleConfig('svauth');
		$nModuleSrl = $obj->module_srl;
		$nSvPluginSrl = $obj->sv_plugin_srl;
		if(isset($oConfig->board_plugin[$nModuleSrl]))
		{
			if($oConfig->board_plugin[$nModuleSrl] == $nSvPluginSrl)
			{
				if($obj->is_interested == '1')
				{
					$sApplicantPhoneNumber = $obj->applicant_phone_number;
					Context::set('plugin_srl', $nSvPluginSrl );
					Context::set('phone_number', $sApplicantPhoneNumber);
					$output = $this->procSvauthValidateAuthCode();
					if(!$output->toBool()) 
						return new BaseObject(-1, $output->message);
				}
				else
					return new BaseObject();
			}
			else
				return new BaseObject(-1, '인증 설정이 잘못되었습니다. 관리자에게 문의해 주세요.');
		}
		else  // svauth_plugin이 설정되어 있지 않으면 intercept 중지
			return new BaseObject();
	}
/**
 * @brief 회원 DB 추가전 트리거 (세션값들을 extra_vars에 입력함)
 **/
	function triggerInsertMemberBefore(&$obj) 
	{
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('svauth');
		if(!(int)$config->plugin_srl ) // svauth_plugin이 설정되어 있지 않으면 intercept 중지
			return new BaseObject();
		$oSvauthModel = getModel('svauth');
		$aAuth = $oSvauthModel->getAuthLog($_COOKIE['sv_auth_info']);
        if($aAuth['user_name'])
			$obj->user_name = $aAuth['user_name'];
		return new BaseObject();
	}
/**
 * @brief 회원 DB 추가후 인증시도가 검색되면 세션값 저장
 **/
	function triggerInsertMemberAfter(&$obj) 
	{
		$args = new stdClass();
		$args->member_srl = $obj->member_srl;
		$oSvauthModel = getModel('svauth');
		$aAuth = $oSvauthModel->getAuthLog($_COOKIE['sv_auth_info']);
		if(is_array($aAuth))
		{
			if(strlen($aAuth["DI"]) > 0)
			{
				$args->di = $aAuth["DI"];
				$args->ci = $aAuth["CI"];
				$args->result_cd = $aAuth["resultCd"]; 
				$args->result_msg = $aAuth["resultMsg"]; 
				$args->seqno = $aAuth["hsCertSvcTxSeqno"]; 
				$args->auth_date = $aAuth["auth_date"]; 
				$args->user_name = $aAuth["user_name"]; 
				$args->birthday = $aAuth["birthday"]; 
				$args->gender = $aAuth["gender"];
				$args->nationality = $aAuth["nationality"];
				$args->ISP = $aAuth["ISP"];
				$args->mobile = $aAuth["mobile"];
				$output = executeQuery('svauth.insertAuth',$args);
				if(!$output->toBool()) 
					return $output;
			}
		}
		unset($aAuth);
		unset($oSvauthModel);
		unset($oSvauargsthModel);
		setcookie('sv_auth_info', '', 0, '/');
		//unset($_SESSION['auth_info']);
		return new BaseObject();
	}
/**
 * @brief 회원 DB 삭제 전 트리거 (di정보 제거)
 * 회원이 자발적으로 탈퇴한 경우에는 모든 기록을 삭제하지 않음
 * 기명 할인 쿠폰 등의 어뷰징을 감시하기 위해서
 **/
	function triggerDeleteMemberBefore(&$obj) 
	{
		$args = new stdClass();
		$args->member_srl = $obj->member_srl;
		$args->is_deleted = 'Y';
		$output = executeQuery('svauth.deleteAuthByMemberSrl',$args);
debugPrint($args);
debugPrint($output);
		if(!$output->toBool())
			return $output;
		return new BaseObject();
	}
/**
 * @brief 인증 일련번호와 인증 정보를 저장
 **/
	function addAuthLog($aRst)
	{
		$oSvauthModel = &getModel('svauth');
		$nAuthLogSrl = $oSvauthModel->getNextAuthLogSrl();
		$args->auth_log_srl = $nAuthLogSrl;
		$args->di = $aRst["DI"];
		$args->ci = $aRst["CI"];
		$args->auth_info = serialize($aRst);
		$output = executeQuery('svauth.insertAuthLog',$args);
		if(!$output->toBool()) 
			return $output;
	}
/**
 * @brief initiate SMS auth code
 **/
	function procSvauthSetAuthCode($nRequestModuleSrl=null)
	{
		if(!$nRequestModuleSrl)
		{
			$nAjaxModuleSrl = (int)Context::get('module_srl');
			if(!$nAjaxModuleSrl)
				return new BaseObject(-1, 'msg_invalid_1module_srl');
			else
				$nRequestModuleSrl = $nAjaxModuleSrl;
		}
		$nPluginSrl = Context::get('plugin_srl');
		if(!$nPluginSrl) 
			return new BaseObject(-1, 'no plugin_srl');
		$oSvauthModel = &getModel('svauth');
		$oPlugin = $oSvauthModel->getPlugin($nPluginSrl);
		$output = $oPlugin->setSmsAuthCode($nRequestModuleSrl);
		if(!$output->toBool()) 
			return $output;
		// svauth module에서 ajax 호출할 때 반환 
		$this->setMessage('인증번호를 발송하였습니다.');
		// svdocs.controller.php::procSvdocsSetAuthCode()에서 호출할 때 반환 
		return new BaseObject(0, '인증번호를 발송하였습니다.');
	}
/**
 * @brief validate phone number
 **/
	function procSvauthValidateAuthCode()
	{
		$nPluginSrl = Context::get('plugin_srl');
		if(!$nPluginSrl) 
			return new BaseObject(-1, 'no plugin_srl');
		$oSvauthModel = &getModel('svauth');
		$oPlugin = $oSvauthModel->getPlugin($nPluginSrl);
		return $oPlugin->processResult();
	}
}
?>