<?php
/**
 * @class svauthPlugin
 * @author singleview(root@singleview.co.kr)
 * @brief plugin abstract class
 **/
class svauthPlugin
{
	function svauthPlugin() { }
	function getFormData() { }
	function processPayment() { }
	function processReview() { }
	function processReport() { }
	function dispExtra1(&$svauthObj) { }
	function dispExtra2(&$svauthObj) { }
	function dispExtra3(&$svauthObj) { }
	function dispExtra4(&$svauthObj) { }
	function procExtra1() { }
	function procExtra2() { }
	function procExtra3() { }
	function procExtra4() { }
}
/* End of file svauth.plugin.php */
/* Location: ./modules/svauth/svauth.plugin.php */
