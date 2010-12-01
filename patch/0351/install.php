<?php
/**
 * brand_patch_0351
 * @package modules.brand
 */
class brand_patch_0351 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/brand/persistentdocument/brand.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'brand', 'brand');
		$newProp = $newModel->getPropertyByName('codeReference');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('brand', 'brand', $newProp);
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
		return '0351';
	}
}