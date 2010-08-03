<?php
/**
 * @package modules.brand.setup
 */
class brand_Setup extends object_InitDataSetup
{
	public function install()
	{
		// $this->executeModuleScript('init.xml');
		$this->addBackGroundCompileTask();
	}

	/**
	 * @return array<string>
	 */
	public function getRequiredPackages()
	{
		// Return an array of packages name if the data you are inserting in
		// this file depend on the data of other packages.
		// Example:
		// return array('modules_website', 'modules_users');
		return array();
	}
	
	/**
	 * @return void
	 */
	private function addBackGroundCompileTask()
	{
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('brand_BackgroundCompileTask');
		$task->setLabel('brand_BackgroundCompileTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'brand'));
	}
}