<?php
class sv_sms extends SvauthPlugin 
{
	var $_g_oPluginInfo;
/**
 * @brief
 */
	function pluginInstall($args) 
	{
		// mkdir
		FileHandler::makeDir(_XE_PATH_.'files/svauth/sv_sms/'.$args->plugin_srl.'/log');
	}
/**
 * @brief
 */
	public function sv_sms() 
	{
		parent::svauthPlugin();
	}
/**
 * @brief
 */
	public function init(&$args)
	{
		$this->_g_oPluginInfo = new StdClass();
		foreach($args as $key=>$val)
			$this->_g_oPluginInfo->{$key} = $val;
		foreach($args->extra_var as $key=>$val)
			$this->_g_oPluginInfo->{$key} = $val->value;
		Context::set('plugin_info', $this->_g_oPluginInfo);
	}
/**
 * @brief
 */
	public function getFormData()
	{
		if(!$this->_g_oPluginInfo->plugin_srl)
			return new BaseObject(-1,'plugin_srl not defined');
		Context::set('plugin_srl', $this->_g_oPluginInfo->plugin_srl);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/svauth/plugins/sv_sms/tpl";
		$tpl_file = 'formdata.html';
		$form_data = $oTemplate->compile($tpl_path, $tpl_file);
		$output = new BaseObject();
		$output->data = $form_data;
		return $output;
	}
/**
 * @brief
 */
	private function _getErrorMessage($error_code)
	{
		switch($error_code)
		{
			case "InvalidAPIKey":
				$error_message = "문자메시지 모듈의 관리자 설정을 확인해주세요.";
				break;
			case "SignatureDoesNotMatch":
				$error_message = "문자메시지 모듈의 관리자 설정을 확인해주세요.";
				break;
			case "NotEnoughBalance":
				$error_message = "잔액이 부족합니다.";
				break;
			case "InternalError":
				$error_message = "서버오류";
				break;
			default:
				$error_message = "메시지 전송 오류";
				break;
		}
		return sprintf("%s(%s)", $error_message, $error_code);
	}
/**
 * @brief 
 */
	public function setSmsAuthCode($nRequestModuleSrl)
	{
		if(!$nRequestModuleSrl)
			return new BaseObject(-1, 'msg_invalid_module_srl');
		$oPluginInfo = $this->_g_oPluginInfo;
		if($oPluginInfo->plugin != 'sv_sms')
			return new BaseObject(-1, 'invalid_plugin');
		require_once(_XE_PATH_.'modules/svauth/plugins/sv_sms/sv_sms.cookie.php');
		$oCookie = new svauthSmsCookie($oPluginInfo->plugin_srl);
		if((int)$oPluginInfo->duplicate_restriction_sec)
		{
			if($oCookie->isRestricted())
			{
				$oCookie->setRestricted((int)$oPluginInfo->duplicate_restriction_sec);
				return new BaseObject(-1, 'msg_already_registered');
			}
		}
		$phonenum = Context::get('phone_number');
		if(preg_match('/[^0-9]/i', $phonenum))
			return new BaseObject(-1, '숫자만 입력 가능합니다.');
		if(!$phonenum)
			return new BaseObject(-1, '국가 및 휴대폰 번호를 전부 입력해주세요.');
		
        // var_dump($oPluginInfo->forbid_exchange_no);
        if($oPluginInfo->forbid_exchange_no)
        {
            $asExchangeNo = explode(',', $oPluginInfo->forbid_exchange_no);
            foreach($asExchangeNo as $key => $val)
            {
                if(strpos($phonenum,  $val) === 0) 
                    return new BaseObject(-1, '인증이 금지된 국번입니다.');
            }
        }
		//load config
		$oModuleModel = &getModel('module');
		$oModuleConfig = $oModuleModel->getModuleConfig('svauth');
		$args = new stdClass();
        // check duplicated.
		if($oModuleConfig->free_di == 'N') // forbid duplication
		{		 
			$args->clue = $phonenum;
			$args->module_srl = $nRequestModuleSrl;
			$output = executeQueryArray('svauth.getSmsAuthCountByClue', $args);
			if(!$output->toBool()) 
				return $output;
			$bDenyAuth = false;
			$nAuth=count($output->data);
			if($nAuth > 0)
			{
				foreach($output->data as $nIdx => $oAuthRec)
				{
					if($oAuthRec->passed=='N')
					{
						$dateAuth = strtotime($oAuthRec->regdate);
						$dateNow = strtotime('now');
						if(floor(($dateNow-$dateAuth)/3600/24) == 0)
						{
							$bDenyAuth = true;
							$this->_recordDeniedApproach($nRequestModuleSrl, $phonenum, 'err1');
							$sErrMsg = '오늘 인증을 시도하였지만 인증되지 않은 휴대폰 번호입니다.';
							break;
						}
					}	
					elseif($oAuthRec->passed=='Y')
					{
						$bDenyAuth = true;
						$this->_recordDeniedApproach($nRequestModuleSrl, $phonenum, 'err2', 'Y');
						$sErrMsg = '이미 인증받은 휴대폰 번호입니다.';
						break;
					}
				}
				if($bDenyAuth)
					return new BaseObject(-1, $sErrMsg);
			}
		}
		// check abusing by phone number begin
		// generate auth-code
		$keystr = $this->_getRandNumber($oPluginInfo->digit_number);
		// check day try limit
		$args->module_srl = $nRequestModuleSrl;
		$args->clue = $phonenum;
		$args->regdate = date("Ymd", mktime(0,0,0,date("m"),date("d"),date("Y")));
		$output = executeQuery('svauth.getTryCountByClue', $args);
		if(!$output->toBool()) 
			return $output;
		unset($args);
		if($output->data->count > $oPluginInfo->day_try_limit)
		{
			$this->_recordDeniedApproach($nRequestModuleSrl, $phonenum, 'err3');
			return new BaseObject(-1, '잦은 인증번호 요청으로 금지되셨습니다. 내일 다시 시도해주십시오.');
		}
		// check day try limit
        $args = new stdClass();
		$args->module_srl = $nRequestModuleSrl;
		$args->clue = $phonenum;
		$args->regdate = date('YmdHis', time()-$oPluginInfo->authcode_delay_sec);
		$output = executeQuery('svauth.getTryCountByClue', $args);
		if(!$output->toBool())
			return $output;
		unset($args);
		if($output->data->count > 0)
		{
			$this->_recordDeniedApproach($nRequestModuleSrl, $phonenum, 'err4');
			return new BaseObject(-1, $oPluginInfo->authcode_delay_sec . '초 동안 다시 받으실 수 없습니다.');
		}
		// check abusing by phone number end

		// check abusing by IP begin
		// check day try limit
        $args = new stdClass();
		$args->module_srl = $nRequestModuleSrl;
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->regdate = date("Ymd", mktime(0,0,0,date("m"),date("d"),date("Y")));
		$output = executeQuery('svauth.getTryCountByIp', $args);
		if(!$output->toBool()) 
			return $output;
		unset($args);
		if($oPluginInfo->day_try_limit)
		{
			if($output->data->count > $oPluginInfo->day_try_limit)
			{
				$this->_recordDeniedApproach($nRequestModuleSrl, $phonenum, 'err5');
				return new BaseObject(-1, '잦은 인증번호 요청으로 금지되셨습니다. 내일 다시 시도해주십시오.');
			}
		}
		// check day try limit
        $args = new stdClass();
		$args->module_srl = $nRequestModuleSrl;
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->regdate = date('YmdHis', time()-$oPluginInfo->duplicate_restriction_sec);
		$output = executeQuery('svauth.getTryCountByIp', $args);
		if(!$output->toBool())
			return $output;
		if($output->data->count > 0)
		{
			$this->_recordDeniedApproach($nRequestModuleSrl, $phonenum, 'err6');
			$nRestrictionSec = $oPluginInfo->duplicate_restriction_sec;
			if($nRestrictionSec > 3600)
				$sWarning = ceil($nRestrictionSec/3600).'시간 후에 다시 시도해 주세요.';
			else if($nRestrictionSec > 60)
				$sWarning = ceil($nRestrictionSec/60).'분 후에 다시 시도해 주세요.';
			else
				$sWarning = $nRestrictionSec.'초 후에 다시 시도해 주세요.';
			return new BaseObject(-1,$sWarning);
		}
		// check abusing by IP end
		unset($args);
		// save auth info
        $args = new stdClass();
		$args->country_code = 82;
		$args->module_srl = $nRequestModuleSrl;
		$args->clue = $phonenum;
		$args->authcode = $keystr;
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$output = executeQuery('svauth.insertSmsAuth', $args);
		if(!$output->toBool()) 
			return $output;
		$oDB = DB::getInstance();
		$nSmsAuthSrl = $oDB->db_insert_id();
		$_SESSION['svauth_sms_auth_srl'] = $nSmsAuthSrl;
		$args->recipient_no =  $phonenum;
		if(is_null($oPluginInfo->sender_no))
			return new BaseObject(-1, '발신번호가 설정되어 있지 않습니다.');
		$args->sender_no = $oPluginInfo->sender_no;
		if($oPluginInfo->message_content)
		{
			$content = str_replace(array("%authcode%"),array($keystr),$oPluginInfo->message_content);
			$args->content = $content;
		}
		else
			$args->content = $keystr;
		$args->country = $country_code;
		$oTxtmsgController = &getController('textmessage');
		$output = $oTxtmsgController->sendMessage($args);
		if(!$output->toBool()) 
		{
			if($output->get('error_code'))
			{
				$error_message = $this->_getErrorMessage($output->get('error_code'));
				return new BaseObject(-1, $error_message);
			}
		}
		else
		{
			//$output->add('sms_auth_srl', $nSmsAuthSrl);
			return $output;
		}
	}
/**
 * @brief validate sms auth code
 **/
	function processResult()
	{
		$oPluginInfo = $this->_g_oPluginInfo;
		require_once(_XE_PATH_.'modules/svauth/plugins/sv_sms/sv_sms.cookie.php');
		$oCookie = new svauthSmsCookie($oPluginInfo->plugin_srl);
		if((int)$oPluginInfo->duplicate_restriction_sec)
		{
			if($oCookie->isRestricted())
			{
				$oCookie->setRestricted((int)$oPluginInfo->duplicate_restriction_sec);
				return new BaseObject(-1, 'msg_already_registered');
			}
		}
		$nModuleSrl = (int)Context::get('module_srl');
		if(!$nModuleSrl)
			return new BaseObject(-1, 'msg_invalid_module_srl');
		$sSmsAuthCode = Context::get('authcode');
		$output = $this->_verifyAuthCode($sSmsAuthCode);
		if(!$output->toBool()) 
			return $output;
		else //인증 성공하고 중복 인증이 아니면 쿠키에 저장
		{
			if((int)$oPluginInfo->duplicate_restriction_sec)
				$oCookie->setRestricted((int)$oPluginInfo->duplicate_restriction_sec);
			return new BaseObject();
		}
	}
/**
 * @brief 
 */
	private function _verifyAuthCode()
	{
        $args = new stdClass();
		$args->sms_auth_srl = (int)$_SESSION['svauth_sms_auth_srl'];
		$output = executeQuery('svauth.getSmsAuth', $args);
		if(!$output->toBool()) 
			return $output;
		$sPhoneNumber = str_replace('-', '', strip_tags(trim(Context::get('phone_number'))));
		if(strlen($sPhoneNumber) == 0)
			return new BaseObject(-1, 'msg_no_phone_number');
		if(strlen($output->data->clue) == 0 || $sPhoneNumber != $output->data->clue)
			return new BaseObject(-1, '잘못된 전화번호입니다.');
		$authentication_1 = Context::get('authcode');
		$authentication_2 = $output->data->authcode;
		if($authentication_1 == $authentication_2) // 다른 기기에서 인증코드를 받아서 입력하는 어뷰징 방어
		{
			$args->passed = 'Y';
			$args->sms_auth_srl = $_SESSION['svauth_sms_auth_srl'];
			$output = executeQuery('svauth.updateSmsAuth', $args);
			if(!$output->toBool()) 
				return $output;
			return new BaseObject();
		}
		return new BaseObject(-1, '잘못된 인증번호입니다.');
	}
/**
 * @brief 
 */
	public function checkValidAuthLog($sAuthClue)
	{
        $args = new stdClass();
		$args->authcode = $sAuthClue;
		$args->passed = 'Y';
		$args->is_valid = 'Y';
		$output = executeQuery('svauth.getValidSmsAuthLog', $args);
		if(!$output->data) 
			return null;
		if(strlen( $output->data->clue ) > 0)
			return $output->data->clue;
		else
			return null;
	}
/**
 * @brief 인증난수 생성
 **/
	private function _getRandNumber($e)
	{
		if(is_null($e))
			$e = 5;
		for($i=0;$i<$e;$i++)
			 $rand=$rand.rand(0,9); 
		return $rand;
	}
/**
 * @brief save denied auth info
 */
	private function _recordDeniedApproach($nRequestModuleSrl, $sPhonenum, $sErrCode, $sPassedOption='N')
	{
		$args->country_code = 82;
		$args->module_srl = $nRequestModuleSrl;
		$args->clue = $sPhonenum;
		$args->authcode = $sErrCode;
		$args->passed = $sPassedOption; //'Y';
		$args->is_valid = 'N';
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$output = executeQuery('svauth.insertSmsAuth', $args);
	}
}
/* End of file sv_sms.plugin.php */
/* Location: ./modules/svauth/plugins/sv_sms/sv_sms.plugin.php */