<?php
/**
* @class  svauthModel
* @author singleview(root@singleview.co.kr)
* @brief  svauth module Model class
**/ 
class svauthModel extends svauth 
{
/**
 * @brief initialization
 **/
	function init() 
	{
	}
/**
 * @brief svcrm.controller.php::triggerInsertMemberAfter()에서 호출
 * 개인 정보 누출 위험 높은 메소드->어떻게 보완해야 할까?
 */
	function getMemberAuthInfo($nMemberSrl)
	{
		if($nMemberSrl == 0)
			return false;
		$args->member_srl = $nMemberSrl;
		$args->is_deleted = 'N';
		$output = executeQuery('svauth.getMemberAuthInfo', $args);
		return $output->data;
	}
/**
 * @brief
 */
	function getPlugin($nPluginSrl)
	{
		if($nPluginSrl == 0)
			return new BaseObject(-1, 'invalid_plugin_srl');
		$plugin_info = $this->_getPluginInfo($nPluginSrl);
		if(!$plugin_info)
			return new BaseObject(-1, 'no_detected_plugin');
		require_once(sprintf("%ssvauth.plugin.php",$this->module_path));
		require_once(sprintf("%splugins/%s/%s.plugin.php",$this->module_path, $plugin_info->plugin, $plugin_info->plugin));
		$sExcutable = sprintf('$pluginObj = new %s();', $plugin_info->plugin);
        eval($sExcutable);
		$pluginObj->init($plugin_info);
		return $pluginObj;
	}
/**
 * @brief ./addon/svauth/class/svauthaddon.class.php::_checkAuthLogExist()에서 호출
 */
	public function getAuthLog($sAuthClue)
	{
		$oModuleModel = &getModel('module');
		$oModuleConfig = $oModuleModel->getModuleConfig('svauth');
		$nPluginSrl = $oModuleConfig->plugin_srl;
		if(!$nPluginSrl) 
			return new BaseObject(-1, 'no plugin_srl');
		$oSvauthModel = &getModel('svauth');
		$oPlugin = $oSvauthModel->getPlugin($nPluginSrl);
		$sAuthPluginType = trim($oPlugin->_g_oPluginInfo->plugin);
		switch($sAuthPluginType)
		{
			case 'sv_sms':
				$output = $oPlugin->checkValidAuthLog($sAuthClue);
                if(strlen($output) > 0)
					return $output;
				else
					return null;
				break;
			case 'kcb_okcert3':
			case 'kcb_okname':
				$args->di = $sAuthClue;
				$output = executeQuery('svauth.getLog', $args);
				if(!$output->data) 
					return null;

				if(strlen($output->data->di) > 0)
					return unserialize($output->data->auth_info);
				else
					return null;
				break;
			default:
				echo 'invalid auth plugin';
				return;
		}
	}
/**
 * @brief
 */
	private function _getPluginInfo($nPluginSrl)
	{
		$args = new stdClass();
		$args->plugin_srl = $nPluginSrl;
		$output = executeQuery('svauth.getPluginInfo', $args);
		if(!$output->data) 
			return;
		$oPluginInfo = $this->__getPluginInfo($output->data);
		return $oPluginInfo;
	}
/**
 * @brief
 */
	private function __getPluginInfo($info)
	{
		$plugin_title = $info->title;
		$plugin = $info->plugin;
		$plugin_srl = $info->plugin_srl;
		$vars = unserialize($info->extra_vars);
		$output = $this->getPluginsXmlInfo($plugin, $vars);
		$output->plugin_title = $plugin_title;
		$output->plugin = $plugin;
		$output->plugin_srl = $plugin_srl;
		return $output;
	}
/**
 * @brief parse xml, retrieve plugin info.
 **/
	public function getPluginsXmlInfo($plugin, $vars=[])
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
			// unset($author_obj);
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
                $buff .= sprintf('$plugin_info->extra_var = new stdClass();');
				for($i=0;$i<$extra_var_count;$i++)
				{
					unset($var);
					unset($options);
					$var = $extra_vars[$i];
					$name = $var->attrs->name;
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
/**
 * @brief 
 **/
	public function getNextAuthLogSrl()
	{
		$output = executeQuery('svauth.getMaxAuthLogSrl' );
		if( !$output->toBool() )
			return new BaseObject(-1, 'msg_error_svauth_log_db_query');
		else
			return ++$output->data->auth_log_srl;
	}
}
?>