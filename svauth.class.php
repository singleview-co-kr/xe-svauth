<?php
/**
* @class  svauth
* @author singleview(root@singleview.co.kr)
* @brief  svauth module high class
**/ 
//$oDB = &DB::getInstance();
class svauth extends ModuleObject 
{
/**
 * @brief install the module
 **/
	function moduleInstall() 
	{
		return new BaseObject();
	}
/**
 * @brief chgeck module method
 **/
	function checkUpdate() 
	{
		$oModuleModel = &getModel('module');
		if(!$oModuleModel->getTrigger('member.insertMember', 'svauth', 'controller', 'triggerInsertMemberAfter', 'after'))
			return true;
		if(!$oModuleModel->getTrigger('member.insertMember', 'svauth', 'controller', 'triggerInsertMemberBefore', 'before'))
			return true;
		if(!$oModuleModel->getTrigger('member.deleteMember', 'svauth', 'controller', 'triggerDeleteMemberBefore', 'before'))
			return true;
		if(!$oModuleModel->getTrigger('document.insertDocument', 'svauth', 'controller', 'triggerInsertDocument', 'before'))
			return true;
		return false;
	}
/**
 * @brief update module
 **/
	function moduleUpdate() 
	{
		// 회원가입 트리거
		$oModuleController = &getController('module');
		$oModuleController->insertTrigger('member.insertMember', 'svauth', 'controller', 'triggerInsertMemberBefore', 'before');
		$oModuleController->insertTrigger('member.insertMember', 'svauth', 'controller', 'triggerInsertMemberAfter', 'after');
		// 회원탈퇴 트리거
		$oModuleController->insertTrigger('member.deleteMember', 'svauth', 'controller', 'triggerDeleteMemberBefore', 'before');
		// 게시판 글 등록 트리거
		$oModuleController->insertTrigger('document.insertDocument', 'svauth', 'controller', 'triggerInsertDocument', 'before');
		return new BaseObject(0, 'success_updated');
	}
/**
 * @brief
 **/
	function moduleUninstall() {
		return new BaseObject();
	}
/**
 * @brief re-generate the cache files
 **/
	function recompileCache() 
	{
	}
}
?>