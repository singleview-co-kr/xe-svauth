<?php
/**
 * @class  svauthMobile
 * @author singleview(root@singleview.co.kr)
 * @brief  svauthMobile class
 */
class svauthMobile extends svauthView
{
	function init()
	{
		$oModuleModel = &getModel('module');
		$oModuleConfig = $oModuleModel->getModuleConfig('svauth');
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $oModuleConfig->mskin);
		if(!is_dir($template_path)||!$this->module_info->mskin) 
		{
			$oModuleConfig->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $oModuleConfig->mskin);
		}
		$this->setTemplatePath($template_path);
	}
}
/* End of file svauth.mobile.php */
/* Location: ./modules/svauth/svauth.mobile.php */