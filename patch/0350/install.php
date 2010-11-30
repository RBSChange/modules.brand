<?php
/**
 * brand_patch_0350
 * @package modules.brand
 */
class brand_patch_0350 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$tasks = task_PlannedtaskService::getInstance()->getBySystemtaskclassname('brand_BackgroundCompileTask');
		foreach ($tasks as $task) 
		{
			$task->delete();
		}
		$this->execChangeCommand('update-autoload', array('modules/brand'));
		$this->execChangeCommand('compile-listeners');
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'brand';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0350';
	}
}