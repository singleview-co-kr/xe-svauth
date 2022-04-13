<?php
/**
 * @class  svauthAdminModel
 * @author singleview(root@singleview.co.kr)
 * @brief  svauth admin model 
 **/
class svauthAdminModel extends svauth
{
	var $_g_aDefaultPrivacyInfoList = array('auth_date'=>1,'user_name'=>0,'birthday'=>0,'gender'=>0,'nationality'=>1,'mobile'=>0,'ISP'=>0); 
	public function init()
	{
	}
/**
 * @brief svcrm.admin.view.php::dispSvcrmAdminCustomerList()에서 호출
 **/
	public function getMemberAuthCheck($nMemberSrl)
	{
		$args->member_srl = $nMemberSrl;
		$args->is_deleted = 'N';
		$output = executeQuery('svauth.getAdminMemberAuthCheck', $args);
		if(count($output->data) == 1)
			return 'validated';
		else if(count($output->data) == 0)
			return 'not_validated';
		
		return 'wiered auth';
	}
/**
 * @brief svcrm.admin.view.php::dispSvcrmAdminConsumerInterest()에서 호출
 **/
	public function getMemberAuthInfo($nMemberSrl,$aPrivacyAccess)
	{
		$oLoggedInfo = Context::get('logged_info');
		foreach($aPrivacyAccess[$oLoggedInfo->member_srl]->allow_list as $allowval)
		{
			if(!$this->_g_aDefaultPrivacyInfoList[$allowval])
				$this->_g_aDefaultPrivacyInfoList[$allowval] = 1;
		}
		$args->member_srl = $nMemberSrl;
		$args->is_deleted = 'N';
		$output = executeQuery('svauth.getAdminMemberAuthInfo', $args);
		return $this->_translatePrivacy($output->data);
	}
/**
 * @brief 
 */
	private function _translatePrivacy($oData)
	{
		$oParsedData = new stdClass();
		foreach($oData as $key=>$val)
		{
			$sKeyTitle = Context::getLang($key);
			if($this->_g_aDefaultPrivacyInfoList[$key])
			{
				switch($key)
				{
					case 'nationality':
						$val = $val == 'd' ? '내국인':'외국인';
						break;
					case 'gender':
						$val = $val == 'm' ? '남자':'여자';
						break;
				}
				$oParsedData->{$sKeyTitle} = $val;
			}
			else
				$oParsedData->{$sKeyTitle} = Context::getLang('sealed');
		}
		return $oParsedData;
	}
/**
 * @brief 
 */
	public function getPluginInfo($nPluginSrl)
	{
		// 일단 DB에서 정보를 가져옴
        $oArgs = new stdClass();
		$oArgs->plugin_srl = $nPluginSrl;
		$oRst = executeQuery('svauth.getPluginInfo', $oArgs);
        unset($oArgs);
		if(!$oRst->data) 
			return;
		// plugin, extra_vars를 정리한 후 xml 파일 정보를 정리해서 return
		$oPluginInfo = $this->_getPluginInfo($oRst->data);
		return $oPluginInfo;
	}
/**
 * @brief 
 */
	public function getPluginList()
	{
        $oArgs = new stdClass();
		$oArgs->page = Context::get('page');
		$oRst = executeQueryArray('svauth.getPluginList');
        unset($oArgs);
		return $oRst->data;
	}
/**
 * @brief
 */
	private function _getPluginInfo($info)
	{
		$plugin_title = $info->title;
		$plugin = $info->plugin;
		$plugin_srl = $info->plugin_srl;
		$vars = unserialize($info->extra_vars);
		$output = $this->_getPluginsXmlInfo($plugin, $vars);
		$output->plugin_title = $plugin_title;
		$output->plugin = $plugin;
		$output->plugin_srl = $plugin_srl;
		return $output;
	}
/**
 * @brief read pg plugin xml files.
 **/
	public function getPluginsXmlInfo()
	{
		// read Auth plugins
		$aSearched = FileHandler::readDir(_XE_PATH_.'modules/svauth/plugins');
		$nSearchedCount = count($aSearched);
		if(!$nSearchedCount) 
			return;
		sort($aSearched);

		$list = array();
		for($i=0;$i<$nSearchedCount;$i++)
		{
			$sPluginName = $aSearched[$i];
			$info = $this->_getPluginsXmlInfo($sPluginName);
			$info->plugin = $sPluginName;
			$list[] = $info;
		}
		return $list;
	}
/**
 * @brief parse xml, retrieve plugin info.
 * (this function will be removed in the future)
 **/
	private function _getPluginsXmlInfo($plugin, $vars=array())
	{
		$plugin_path = _XE_PATH_."modules/svauth/plugins/".$plugin;
		$xml_file = sprintf(_XE_PATH_."modules/svauth/plugins/%s/info.xml", $plugin);
		if(!file_exists($xml_file)) 
			return;

		$oXmlParser = new XeXmlParser();
		$tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
		$xml_obj = $tmp_xml_obj->plugin;
		if(!$xml_obj) 
			return;
        $plugin_info = new stdClass();
		$plugin_info->title = $xml_obj->title->body;
		$plugin_info->description = $xml_obj->description->body;
		$plugin_info->version = $xml_obj->version->body;

        $date_obj = new stdClass();
		sscanf($xml_obj->date->body, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
		$plugin_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
		$plugin_info->license = $xml_obj->license->body;
		$plugin_info->license_link = $xml_obj->license->attrs->link;

		if(!is_array($xml_obj->author)) 
			$author_list[] = $xml_obj->author;
		else 
			$author_list = $xml_obj->author;

		foreach($author_list as $author)
		{
			$author_obj = new stdClass();
			$author_obj->name = $author->name->body;
			$author_obj->email_address = $author->attrs->email_address;
			$author_obj->homepage = $author->attrs->link;
			$plugin_info->author[] = $author_obj;
		}

		$buff = '';
		$buff .= sprintf('$plugin_info->site_srl = "%s";', $site_srl);

		// 추가 변수 (템플릿에서 사용할 제작자 정의 변수)
		$extra_var_groups = $xml_obj->extra_vars->group;
		if(!$extra_var_groups) 
			$extra_var_groups = $xml_obj->extra_vars;
		if(!is_array($extra_var_groups)) 
			$extra_var_groups = array($extra_var_groups);
		foreach($extra_var_groups as $group)
		{
			$extra_vars = $group->var;
			if($extra_vars)
			{
				if(!is_array($extra_vars)) $extra_vars = array($extra_vars);

				$extra_var_count = count($extra_vars);

				$buff .= sprintf('$plugin_info->extra_var_count = "%s";', $extra_var_count);
				for($i=0;$i<$extra_var_count;$i++)
				{
					unset($var);
					unset($options);
					$var = $extra_vars[$i];
					$name = $var->attrs->name;
					$buff .= sprintf('$plugin_info->extra_var = new stdClass();');
                    $buff .= sprintf('$plugin_info->extra_var->%s = new stdClass();', $name);
                    $buff .= sprintf('$plugin_info->extra_var->%s->group = "%s";', $name, $group->title->body);
					$buff .= sprintf('$plugin_info->extra_var->%s->title = "%s";', $name, $var->title->body);
					$buff .= sprintf('$plugin_info->extra_var->%s->type = "%s";', $name, $var->attrs->type);
					$buff .= sprintf('$plugin_info->extra_var->%s->default = "%s";', $name, $var->attrs->default);
					if ($var->attrs->type=='image'&&$var->attrs->location) 
						$buff .= sprintf('$plugin_info->extra_var->%s->location = "%s";', $name, $var->attrs->location);
					$buff .= sprintf('$plugin_info->extra_var->%s->value = $vars->%s;', $name, $name);
					$buff .= sprintf('$plugin_info->extra_var->%s->description = "%s";', $name, str_replace('"','\"',$var->description->body));

					$options = $var->options;
					if(!$options) 
						continue;

					if(!is_array($options)) 
						$options = array($options);
					$options_count = count($options);
					$thumbnail_exist = false;
					for($j=0; $j < $options_count; $j++)
					{
						$thumbnail = $options[$j]->attrs->src;
						if($thumbnail)
						{
							$thumbnail = $plugin_path.$thumbnail;
							if(file_exists($thumbnail))
							{
								$buff .= sprintf('$plugin_info->extra_var->%s->options["%s"]->thumbnail = "%s";', $var->attrs->name, $options[$j]->attrs->value, $thumbnail);
								if(!$thumbnail_exist)
								{
									$buff .= sprintf('$plugin_info->extra_var->%s->thumbnail_exist = true;', $var->attrs->name);
									$thumbnail_exist = true;
								}
							}
						}
						$buff .= sprintf('$plugin_info->extra_var->%s->options["%s"]->val = "%s";', $var->attrs->name, $options[$j]->attrs->value, $options[$j]->title->body);
					}
				}
			}
		}
		if ($buff) 
			eval($buff);

		return $plugin_info;
	}
}
/* End of file svauth.admin.model.php */
/* Location: ./modules/svauth/svauth.admin.model.php */