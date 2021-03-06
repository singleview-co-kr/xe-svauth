<?php
/**
 * @class  svauthView
 * @author singleview(root@singleview.co.kr)
 * @brief  svauth module Controller class
**/ 
class svauthView extends svauth 
{
/**
 * @brief initialization
 **/
	public function init() 
	{
		 // Get the member configuration
		$oMemberModel = &getModel('member');
		$this->member_config = $oMemberModel->getMemberConfig();
		Context::set('member_config', $this->member_config);

		//load config
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('svauth');
		$skin = $config->skin ? $config->skin : 'default';
		$template_path = sprintf('%sskins/%s', $this->module_path, $skin);
		// Template path
		$this->setTemplatePath($template_path);

		$oLayoutModel = &getModel('layout');
		$layout_info = $oLayoutModel->getLayout($this->member_config->layout_srl);
		if($layout_info)
		{
			$this->module_info->layout_srl = $this->member_config->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}
/**
 * @brief 신용정보사의 플러그인 화면을 표시
 **/
	function dispSvauthPopup()
	{
		$nPluginSrl = Context::get('plugin_srl');
		if(!$nPluginSrl) 
			return new BaseObject(-1, 'no plugin_srl');
		$oSvauthModel = &getModel('svauth');
		$oPlugin = $oSvauthModel->getPlugin($nPluginSrl);
		$output = $oPlugin->processReview();
		Context::set('content', $output);
		// generate blank window
		$this->setLayoutPath(_XE_PATH_.'common/tpl/');
		$this->setLayoutFile('default_layout');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('popup');
	}
/**
 * @brief 로그인폼 대체, 레이아웃은 member의 레이아웃설정을따름.
 **/
	public function dispSvauthLoginForm()
	{
		if(Context::get('is_logged'))
		{
			header("location:".getNotEncodedUrl('act',''));
			return;
		}
		Context::set('target_module', $in_args->target_module);
		Context::set('join_form', $in_args->join_form);
		//load config
		$oModuleModel = &getModel('module');
		$oModuleConfig = $oModuleModel->getModuleConfig('svauth');
		if(!$oModuleConfig) 
			return new BaseObject(-1, 'msg_invalid_svauth_module');
		if(!$oModuleConfig->skin) 
			$oModuleConfig->skin = 'default';
		Context::set('svauth_module_info', $oModuleConfig);

		$sFormHtml = '';
		if($oModuleConfig->plugin_srl)
		{
			$oSvauthModel = &getModel('svauth');
			$oPlugin = $oSvauthModel->getPlugin($oModuleConfig->plugin_srl);
			$output = $oPlugin->getFormData();
			if(!$output->toBool()) 
				return $output;
			$sFormHtml = $output->data;
		}
		else
			$sFormHtml = 'No plugin selected';
		Context::set('form_data', $sFormHtml);
		$sTemplatePath = $this->module_path."skins/".$oModuleConfig->skin;
		if(!is_dir($sTemplatePath)||!$this->module_info->skin) 
		{
			$this->module_info->skin = 'default';
			$sTemplatePath = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		}
		// Set a template file
		$this->setTemplateFile('signup_form');
	}
/**
 * @brief 안심본인인증 결과처리
 **/
	public function dispSvauthResult()
	{
		$nAuthPluginSrl = (int)Context::get('plugin_srl');
		if(!$nAuthPluginSrl)
			return new BaseObject(-1,"illegal approach ");

		$oSvauthModel = &getModel('svauth');
		$oPlugin = $oSvauthModel->getPlugin($nAuthPluginSrl);
		if(!$oPlugin) 
			return new BaseObject(-1,"illegal approach ");

		$oModuleModel = &getModel('module');
		$oModuleConfig = $oModuleModel->getModuleConfig('svauth');
		$sForm = $oPlugin->processResult($oModuleConfig );
		Context::set('content', $sForm);

		// generate blank window
		$this->setLayoutPath(_XE_PATH_.'common/tpl/');
		$this->setLayoutFile('default_layout');
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('popup');
	}
}
?>