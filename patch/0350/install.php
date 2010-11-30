<?php
/**
 * brand_patch_0350
 * @package modules.brand
 */
class brand_patch_0350 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$task = task_PlannedtaskService::getInstance()->getBySystemtaskclassname('brand_BackgroundCompileTask');
		if ($task !== null)
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