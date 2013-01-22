<?php
/**
 * brand_SpaceScriptDocumentElement
 * @package modules.brand.persistentdocument.import
 */
class brand_SpaceScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
     * @return brand_persistentdocument_space
     */
	protected function initPersistentDocument()
	{
		return brand_SpaceService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_brand/space');
	}
	
	/**
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		$mountPoint = $this->getComputedAttribute('mountPoint');
		if ($mountPoint)
		{
			$this->getPersistentDocument()->setMountParentId($mountPoint->getId());
		}
		return parent::getDocumentProperties();
	}

}