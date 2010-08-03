<?php
/**
 * brand_patch_0301
 * @package modules.brand
 */
class brand_patch_0301 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Generate database.
		f_util_System::execChangeCommand('generate-database', array('brand'));
		
		// New property in brands.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/brand/persistentdocument/brand.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'brand', 'brand');
		$newProp = $newModel->getPropertyByName('compiled');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('brand', 'brand', $newProp);
		$newProp = $newModel->getPropertyByName('firstLetter');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('brand', 'brand', $newProp);
		
		// Fill firstlLetter field.
		foreach (brand_BrandService::getInstance()->createQuery()->find() as $brand)
		{
			$brand->setModificationdate(null);
			$brand->save();
		}
		
		// Planned task.
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('brand_BackgroundCompileTask');
		$task->setLabel('brand_BackgroundCompileTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'brand'));
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
		return '0301';
	}
}