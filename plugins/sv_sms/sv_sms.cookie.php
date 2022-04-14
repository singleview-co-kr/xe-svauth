<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  svauthSmsCookie
 * @author singleview(root@singleview.co.kr)
 * @brief  svauthSmsCookie
**/ 
class svauthSmsCookie
{
	var $_g_sSvauthSmsCookieName = 'xe_sv_sms';
/**
 * @brief Initialization
 */
	public function svauthSmsCookie( $nPluginSrl )
	{
		$this->_g_sSvauthSmsCookieName = 'svauth_restriction_'.$nPluginSrl;
	}
/**
 * @brief 
 */
	public function setRestricted( $nSec )
	{
		setcookie($this->_g_sSvauthSmsCookieName, 'restricted', time()+$nSec, '/');
	}
/**
 * @brief 
 */
	public function isRestricted()
	{
		if( $_COOKIE[$this->_g_sSvauthSmsCookieName] == 'restricted' )
			return true;
		else
			return false;
	}
}
/* End of file sv_sms.cookie.php */
/* Location: ./modules/svauth/plugins/sv_sms/sv_sms.cookie.php */