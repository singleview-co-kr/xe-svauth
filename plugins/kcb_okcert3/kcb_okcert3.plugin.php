<?php
class kcb_okcert3 extends SvauthPlugin 
{
	var $_g_oPluginInfo;
/**
 * @brief
 */
	function pluginInstall($args) 
	{
		// mkdir
		FileHandler::makeDir(_XE_PATH_.'files/svauth/kcb_okcert3/'.$args->plugin_srl.'/log');
		// copy files
		//FileHandler::copyFile(_XE_PATH_.'modules/svpg/plugins/iniescrow/.htaccess',sprintf(_XE_PATH_."files/svpg/%s/.htaccess",$args->plugin_srl));
		//FileHandler::copyFile(_XE_PATH_.'modules/svpg/plugins/iniescrow/readme.txt',sprintf(_XE_PATH_."files/svpg/%s/readme.txt",$args->plugin_srl));
		//FileHandler::copyFile(_XE_PATH_.'modules/svpg/plugins/iniescrow/key/pgcert.pem',sprintf(_XE_PATH_."files/svpg/%s/key/pgcert.pem",$args->plugin_srl));
	}
/**
 * @brief
 */
	public function kcb_okcert3() 
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
	public function getFormData($args)
	{
		if(!$this->_g_oPluginInfo->plugin_srl)
			return new BaseObject(-1,'plugin_srl not defined');

		Context::set('plugin_srl', $this->_g_oPluginInfo->plugin_srl);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/svauth/plugins/kcb_okcert3/tpl";
		$tpl_file = 'formdata.html';
		$form_data = $oTemplate->compile($tpl_path, $tpl_file);
		$output = new BaseObject();
		$output->data = $form_data;
		return $output;
	}
/**
 * @brief 파일명: hs_cnfrm_popup2.php
 * 본인확인서비스 개인 정보 입력 화면(고객 인증정보 KCB팝업창에서 입력용)
 * ※주의
 * 실제 운영시에는 response.write를 사용하여 화면에 보여지는 데이터를 
 * 삭제하여 주시기 바랍니다. 방문자에게 사이트데이터가 노출될 수 있습니다.
 */
	public function processReview()
	{
		$oArgs = Context::getRequestVars();
		// okname 본인확인서비스 파라미터
		$name = "x"; // 성명
		$birthday = "x"; // 생년월일 
		$sex = "x"; // 성별
		$nation="x"; // 내외국인구분 
		//$telComCd="x"; // 이동통신사코드 
		$telNo="x"; // 휴대폰번호 

		// * 파라미터에 대한 유효성여부를 검증한다.
		$inTpBit = $oArgs->in_tp_bit;// $_POST["in_tp_bit"];	// 입력구분코드(0:없음, 1:기본정보, 2:내외국인, 4:휴대폰정보)
		if(preg_match('~[^0-9]~', $inTpBit, $match)) 
		{
			echo ("<script>alert('입력구분코드에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
			exit;
		}
		$inTpBitVal = intval($inTpBit, 0);
		if(($inTpBitVal & 1) == 1) 
		{
			$name = $oArgs->name; //$_POST["name"]; // 성명
			if(preg_match('~[^\x{ac00}-\x{d7af}a-zA-Z ]~u', $name, $match)) 
			{	// UTF-8인 경우
				echo ("<script>alert('성명에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
		}
		if(($inTpBitVal & 2) == 2) 
		{
			$birthday = $oArgs->birthday;//$_POST["birthday"]; // 생년월일
			if(preg_match('~[^0-9]~', $birthday, $match)) 
			{
				echo ("<script>alert('생년월일에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
		}
		if(($inTpBitVal & 4) == 4) 
		{
			$sex = $oArgs->sex;// $_POST["sex"]; // 성별
			$nation = $oArgs->nation;// $_POST["nation"]; // 내외국인구분
			if(preg_match('~[^01]~', $sex, $match)) 
			{
				echo ("<script>alert('성별에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
			if(preg_match('~[^12]~', $nation, $match)) 
			{
				echo ("<script>alert('내외국인 구분에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
		}
		if(($inTpBitVal & 8) == 8) 
		{
			//$telComCd = $oArgs->tel_com_cd;// $_POST["tel_com_cd"]; // 통신사코드
			$telNo = $oArgs->tel_no;// $_POST["tel_no"]; // 휴대폰번호
			//if (preg_match('~[^0-9]~', $telComCd, $match)) 
			//{
			//	echo ("<script>alert('통신사코드에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
			//	exit;
			//}
			if(preg_match('~[^0-9]~', $telNo, $match)) 
			{
				echo ("<script>alert('휴대폰번호에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
				exit;
			}
		}
		$rqstCausCd = $oArgs->rqst_caus_cd;//$_POST["rqst_caus_cd"]; // 인증요청사유코드 2byte  (00:회원가입, 01:성인인증, 02:회원정보수정, 03:비밀번호찾기, 04:상품구매, 99:기타)
		if(preg_match('~[^0-9]~', $rqstCausCd, $match)) 
		{
			echo ("<script>alert('인증요청사유코드에 유효하지 않은 문자열이 있습니다.'); self.close();</script>");
			exit;
		}
		if(strlen($this->_g_oPluginInfo->CP_CD) == 0 || is_null($this->_g_oPluginInfo->CP_CD))
		{
			echo ("<script>alert('회원사코드(CP_CD)를 입력해주세요.'); self.close();</script>");
			exit;
		}

		if(strlen($this->_g_oPluginInfo->SITE_URL) == 0 || is_null($this->_g_oPluginInfo->SITE_URL))
		{
			echo ("<script>alert('사이트 URL을 입력해주세요.'); self.close();</script>");
			exit;
		}

		// # 리턴 URL 설정
		// opener(hs_cnfrm_popup1.php)의 도메일과 일치하도록 설정해야 함. 
		$returnUrl = getNotEncodedFullUrl('','module','svauth','act','dispSvauthResult', 'plugin_srl',$oArgs->plugin_srl);
		// # 운영전환시 변경 필요
		$popupUrl = "https://safe.ok-name.co.kr/CommonSvl";	// 운영 URL
		//' 라이센스 파일
		//$license = "C:\\okcert3_license\\".$CP_CD."_IDS_01_".$target."_AES_license.dat";
		$license = $this->_g_oPluginInfo->license;
		// # 로그 경로 지정 및 권한 부여 (절대경로)
		$logPath = _XE_PATH_.'files/svauth/kcb_okcert3/'.$oArgs->plugin_srl.'/log';
		// okcert3 request param JSON String
		$CP_CD = $this->_g_oPluginInfo->CP_CD;
		$RETURN_URL = $returnUrl;
		$SITE_NAME = $this->_g_oPluginInfo->SITE_NAME;
		$SITE_URL = $this->_g_oPluginInfo->SITE_URL;

		$params  = '{ "CP_CD":"'.$CP_CD.'",';
		$params .= '"RETURN_URL":"'.$RETURN_URL.'",';
		$params .= '"SITE_NAME":"'.$SITE_NAME.'",';
		$params .= '"SITE_URL":"'.$SITE_URL.'",';

		//$params .= '"CHNL_CD":"'.$CHNL_CD.'",';
		//$params .= '"RETURN_MSG":"'.$RETURN_MSG.'",';
		// 사전에 입력받은 정보로 팝업창 개인정보를 고정할 경우 사용 (가이드 참고)
		$params .= '"IN_TP_BIT":"'.$inTpBit.'",';
		$params .= '"NAME":"'.$name.'",';
		$params .= '"BIRTHDAY":"'.$birthday.'",';
		$params .= '"TEL_NO":"'.$telNo.'",';
		$params .= '"NTV_FRNR_CD":"'.$nation.'",';// 내국인 L 외국인 F
		$params .= '"SEX_CD":"'.$sex.'",';		// 남성 M 여성 F

		//' 거래일련번호는 기본적으로 모듈 내에서 자동 채번되고 채번된 값을 리턴해줌.
		//'	회원사가 직접 채번하길 원하는 경우에만 아래 코드를 주석 해제 후 사용.
		//' 각 거래마다 중복 없는 $을 생성하여 입력. 최대길이:20바이트
		$svcTxSeqno = $this->_generateSvcTxSeqno();	// 거래번호. 동일문자열을 두번 사용할 수 없음. (최대 30자리의 문자열. 0-9,A-Z,a-z 사용)
		$params .= '"TX_SEQ_NO":"'.$svcTxSeqno.'",'; 
		
		$RQST_CAUS_CD = $rqstCausCd;
		$params .= '"RQST_CAUS_CD":"'.$RQST_CAUS_CD.'" }';
		$svcName = "IDS_HS_POPUP_START";
		$out = NULL;

		$target = "PROD";
		// okcert3 실행
		$ret = okcert3_u($target, $CP_CD, $svcName, $params, $license, $out);	// UTF-8
		//$ret = okcert3($target, $CP_CD, $svcName, $params, $license, $out);	// EUC-KR

		// okcert3 응답 정보
		$RSLT_CD = "";						// 결과코드
		$RSLT_MSG = "";						// 결과메시지
		$MDL_TKN = "";						// 모듈토큰
		$TX_SEQ_NO = "";					// 거래일련번호
		if($ret == 0) // 함수 실행 성공일 경우 변수를 결과에서 얻음
		{
			//$out = iconv("euckr","utf-8",$out);		// 인코딩 icnov 처리. okcert3 호출(EUC-KR)일 경우에만 사용 (json_decode가 UTF-8만 가능)
			$output = json_decode($out,true);		// $output = UTF-8
			$RSLT_CD = $output['RSLT_CD'];
			//$RSLT_MSG  = iconv("utf-8","euckr", $output["RSLT_MSG"]);	// 다시 EUC-KR 로 변환
			$RSLT_MSG  = $output['RSLT_MSG'];
			if(isset($output['TX_SEQ_NO']))
				$TX_SEQ_NO = $output['TX_SEQ_NO']; // 필요 시 거래 일련 번호 에 대하여 DB저장 등의 처리
			if($RSLT_CD == 'B000')  // B000 : 정상건
				$MDL_TKN = $output['MDL_TKN']; 
		}
		else 
			echo ("<script>alert('Fuction Fail / ret: ".$ret."'); self.close();</script>");
		Context::set('popupUrl', $popupUrl);
		Context::set('RSLT_CD', $RSLT_CD);
		Context::set('RSLT_MSG', $RSLT_MSG);
		Context::set('CP_CD', $CP_CD); // 회원사코드
		Context::set('MDL_TKN', $MDL_TKN);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/svauth/plugins/kcb_okcert3/tpl";
		$tpl_file = 'review.html';
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
/**
 * @brief 서비스거래번호를 생성한다.
 */
	private function _generateSvcTxSeqno() 
	{   
		$numbers  = "0123456789";   
		$svcTxSeqno = date("YmdHis");   
		$nmr_loops = 6;   
		while($nmr_loops--){
			$svcTxSeqno .= $numbers[mt_rand(0, strlen($numbers)-1)];   
		}   
		return $svcTxSeqno;   
	}   
/**
 * @brief 파일명 : hs_cnfrm_popup3.php
 * 본인확인서비스 결과 화면(return url)
 */
	public function processResult($oModuleConfig = null)
	{
		$oArgs = Context::getRequestVars();

		// okcert3 본인확인 서비스 파라미터
		$MDL_TKN	=	$oArgs->mdl_tkn;// $_POST["mdl_tkn"];			// 모듈토큰

		// KCB로부터 부여받은 회원사코드(아이디) 설정 (12자리)
		$CP_CD = $this->_g_oPluginInfo->CP_CD;				// 회원사코드(아이디)

		// 타겟 : 운영/테스트 전환시 변경 필요
		$target = "PROD"; // 테스트="TEST", 운영="PROD"
		
		//'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
		//' 라이센스 파일
		//'''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''
		//$license = "C:\\okcert3_license\\".$CP_CD."_IDS_01_".$target."_AES_license.dat";
		$license = $this->_g_oPluginInfo->license;
		
		/**************************************************************************
		okcert3 request param JSON String
		**************************************************************************/
		$params = '{ "MDL_TKN":"'.$MDL_TKN.'" }';
		
		$svcName = "IDS_HS_POPUP_RESULT";
		$out = NULL;
		
		// okcert3 실행
		$ret = okcert3_u($target, $CP_CD, $svcName, $params, $license, $out);  // UTF-8
		//$ret = okcert3($target, $CP_CD, $svcName, $params, $license, $out);  // EUC-KR
//var_dump(  	$ret );
//echo '<BR>';
//var_dump(  	$out );
//string(584) "{"MDL_TKN":"edc6337f5a2146f0b4f2dd95bce8e5af","RSLT_NAME":"김철수","MSG_VER":"03","TEL_NO":"01032458245","CP_CD":"P21190000000","TX_SEQ_NO":"190619104008KC000168","RSLT_NTV_FRNR_CD":"L","REMOTE_IP":"182.162.104.235","MDL_VER":"okcert3-php-2.0.1","SERVER_IP":"0.0.0.0","CI":"Fvkt9MBfFHm6TFXFFe03bp7rkxN8bzIRgq8ZWGPr4WBk5EaQ+9jIKL/oIlCdt0svHNzCEOGI/YWy4vvVuFxtoA==","TEL_COM_CD":"01","RETURN_MSG":"","RSLT_BIRTHDAY":"19890515","RSLT_MSG":"본인인증 완료","RSLT_CD":"B000","RSLT_SEX_CD":"M","DI":"MC0GCCqGSIb3DQIJAyEAmKmc1NumzrbpxeXFvMH8x2XCN15T/6h1Aj0hqx9zos4=","CI_UPDATE":"1"}" 

		if($ret == 0)  // 함수 실행 성공일 경우 변수를 결과에서 얻음
		{
			//$out = iconv("euckr","utf-8",$out);		// 인코딩 icnov 처리. okcert3 호출(EUC-KR)일 경우에만 사용 (json_decode가 UTF-8만 가능)
			$output = json_decode($out,true);		// $output = UTF-8
			$RSLT_CD	= $output['RSLT_CD'];
			//$RSLT_MSG  = iconv("utf-8","euckr", $output["RSLT_MSG"]);	// 다시 EUC-KR 로 변환
			$RSLT_MSG  = $output['RSLT_MSG'];
			
			if(isset($output['TX_SEQ_NO'])) 
				$TX_SEQ_NO = $output['TX_SEQ_NO']; // 필요 시 거래 일련 번호 에 대하여 DB저장 등의 처리
			if(isset($output['RETURN_MSG']))  
				$RETURN_MSG  = $output['RETURN_MSG'];
			
			if($RSLT_CD == 'B000') // B000 : 정상건
			{ 
				//$RSLT_NAME  = iconv("utf-8","euckr",$output['RSLT_NAME']); // 다시 EUC-KR 로 변환
				$RSLT_NAME  = $output['RSLT_NAME']; // 다시 EUC-KR 로 변환
				$RSLT_BIRTHDAY	= $output['RSLT_BIRTHDAY'];
				$RSLT_SEX_CD	= $output['RSLT_SEX_CD'];
				$RSLT_NTV_FRNR_CD=$output['RSLT_NTV_FRNR_CD'];
				$DI				= $output['DI'];
				$CI 			= $output['CI'];
				$CI_UPDATE		= $output['CI_UPDATE'];
				$TEL_COM_CD		= $output['TEL_COM_CD'];
				$TEL_NO			= $output['TEL_NO'];
			}
		}
// array(19) { ["MDL_TKN"]=> string(32) "cd23d6d3ae3b4f7f94f24fd80c68c29a" ["RSLT_NAME"]=> string(9) "김철수" ["MSG_VER"]=> string(2) "03" ["TEL_NO"]=> string(11) "01032458245" ["CP_CD"]=> string(12) "P21190000000" ["TX_SEQ_NO"]=> string(20) "20190619111800135427" ["RSLT_NTV_FRNR_CD"]=> string(1) "L" ["REMOTE_IP"]=> string(15) "182.162.104.235" ["MDL_VER"]=> string(17) "okcert3-php-2.0.1" ["SERVER_IP"]=> string(7) "0.0.0.0" ["CI"]=> string(88) "Fvkt9MBfFHm6TFXFFe03bp7rkxN8bzIRgq8ZWGPr4WBk5EaQ+9jIKL/oIlCdt0svHNzCEOGI/YWy4vvVuFxtoA==" ["TEL_COM_CD"]=> string(2) "01" ["RETURN_MSG"]=> string(0) "" ["RSLT_BIRTHDAY"]=> string(8) "19890515" ["RSLT_MSG"]=> string(19) "본인인증 완료" ["RSLT_CD"]=> string(4) "B000" ["RSLT_SEX_CD"]=> string(1) "M" ["DI"]=> string(64) "MC0GCCqGSIb3DQIJAyEAmKmc1NumzrbpxeXFvMH8x2XCN15T/6h1Aj0hqx9zos4=" ["CI_UPDATE"]=> string(1) "1" } 
			
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/svauth/plugins/kcb_okcert3/tpl";

		//만약 중복가입을 방지하고 있다면, DI조회후 결과값있는경우 다른페이지 set
		if($oModuleConfig->free_di != "Y")
		{
			$oSvauthModel = getModel('svauth');
			$aRst = $oSvauthModel->getAuthLog($DI);//$result[4]);
			if(count($aRst))
				return $oTemplate->compile($tpl_path, 'result_duplicated.html');
		}
		//인증 성공하고 중복 인증이 아니면 세션에 저장
		if($RSLT_CD == 'B000')
		{
			//setcookie('sv_auth_info', $result[4], 0, '/');
			setcookie('sv_auth_info', $DI, 0, '/');
			$aResult["resultCd"] = $RSLT_CD; //처리결과코드
			$aResult["resultMsg"] = $RSLT_MSG; //처리결과메시지
			$aResult["hsCertSvcTxSeqno"] = $TX_SEQ_NO; //거래일련번호 (sequence처리)
			$aResult["auth_date"] = date('Ymdhis'); //인증일시
			$aResult["DI"] = $DI; //DI
			$aResult["CI"] = $CI; //CI
			$aResult["user_name"] = $RSLT_NAME; //성명
			$aResult["birthday"] = $RSLT_BIRTHDAY; //생년월일
			//$aResult["age"] = substr(date('Ymd')-$result[8],0,2); //만 나이
			switch($RSLT_SEX_CD) //성별 M:남, F:여
			{
				case 'M':
					$sGender = 'f';
					break;
				case 'F':
					$sGender = 'm';
					break;
				default:
					$sGender = 'n';
			}
			$aResult["gender"] = $sGender;

			switch($RSLT_NTV_FRNR_CD) //내외국인구분 L:내국인, F:외국인 
			{
				case 'L':
					$sNationality = 'd';
					break;
				case 'F':
					$sNationality = 'f';
					break;
				default:
					$sNationality = 'n';
			}
			$aResult["nationality"] = $sNationality;
			
			switch($TEL_COM_CD) //통신사코드 01:SKT, 02:KT, 03:LGU+, 04:SKT알뜰폰, 05:KT알뜰폰, 06:LGU+알뜰폰
			{
				case '01':
					$sIsp = 'SKT';
					break;
				case '02':
					$sIsp = 'KT';
					break;
				case '03':
					$sIsp = 'LGU+';
					break;
				case '04':
					$sIsp = 'SKT_ECO';
					break;
				case '05':
					$sIsp = 'KT_ECO';
					break;
				case '06':
					$sIsp = 'LGU+_ECO';
					break;
				default:
					$sIsp = 'N/A';
			}
			$aResult["ISP"] = $sIsp; 
			$aResult["mobile"] = $TEL_NO; //휴대폰번호
			$oSvauthController = getController('svauth');
			$oSvauthController->addAuthLog($aResult);
		}
		else
			setcookie('sv_auth_info', '', 0, '/');
		Context::set('ret', $ret);
		Context::set('retcode', $RSLT_CD);
		return $oTemplate->compile($tpl_path, 'result.html');
	}
}
/* End of file kcb_okcert3.plugin.php */
/* Location: ./modules/svauth/plugins/kcb_okcert3/kcb_okcert3.plugin.php */