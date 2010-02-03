<?php
/**
 * brand_patch_0300
 * @package modules.brand
 */
class brand_patch_0300 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		// -- Update database structure.
		
		$this->log('Update database structure...');
		
		// Rename tables.
		$this->executeSQLQuery("RENAME TABLE m_catalog_doc_brand TO m_brand_doc_brand;");
		$this->executeSQLQuery("RENAME TABLE m_catalog_doc_brand_i18n TO m_brand_doc_brand_i18n;");
		$this->executeSQLQuery("UPDATE m_brand_doc_brand SET document_model = 'modules_brand/brand' WHERE document_model = 'modules_catalog/brand';");
		
		// Update f_document and f_relation.
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_brand/brand' WHERE document_model = 'modules_catalog/brand';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_brand/brand' WHERE document_model_id1 = 'modules_catalog/brand';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_brand/brand' WHERE document_model_id2 = 'modules_catalog/brand';");
		
		// -- Move existing brands to brand tree.
		
		$ts = TreeService::getInstance();
		$oldRootId = ModuleService::getInstance()->getRootFolderId('catalog');
		$newRootId = ModuleService::getInstance()->getRootFolderId('brand');
		$oldBrandFolders = array('folder' => DocumentHelper::getDocumentInstance($oldRootId), 'children' => array());
		$newBrandFolders = array('folder' => DocumentHelper::getDocumentInstance($newRootId), 'children' => array());
		
		// Move brands.
		$brands = brand_BrandService::getInstance()->createQuery()->find();
		$this->log('Move existing brands to brand tree... (' . count($brands) . ' brands to move)');
		foreach ($brands as $brand)
		{
			// Replicate folders.
			$ancestors = brand_BrandService::getInstance()->getAncestorsOf($brand, 'modules_generic/folder');
			$newParent = $this->replicateFolders($ancestors, $oldBrandFolders, $newBrandFolders);
						
			// Move the document.
			$treeNode = $ts->getInstanceByDocument($brand);
			if ($treeNode !== null)
			{
				$ts->deleteNode($treeNode);
			}
			$ts->newLastChildForNode($ts->getInstanceByDocument($newParent['folder']), $brand->getId());
		}
		
		// Clear tree node cache.
		$ts->setTreeNodeCache(false)->setTreeNodeCache(true);
		
		// Clean old folders in catalog tree.
		$this->log('Clean old folders in catalog tree...');
		$this->deleteEmptyFolders($oldBrandFolders);
	}
	
	/**
	 * @param Array $ancestors
	 * @param Array $parentOldFolder
	 * @param Array $parentNewFolder
	 * @return Array
	 */
	private function replicateFolders($ancestors, &$parentOldFolder, &$parentNewFolder)
	{
		if (count($ancestors) > 0)
		{
			$ancestor = array_shift($ancestors);
			$folderId = $ancestor->getId();
					
			// Construct old folders array.
			if (!isset($parentOldFolder['children'][$folderId]))
			{
				$parentOldFolder['children'][$folderId] = array('folder' => $ancestor, 'children' => array());
			}
			$oldFolder = &$parentOldFolder['children'][$folderId];
			
			// Construct new folders array.
			if (!isset($parentNewFolder['children'][$folderId]))
			{
				$fs = generic_FolderService::getInstance();
				$parentId = $parentNewFolder['folder']->getId();
				$newFolder = $fs->createQuery()->add(Restrictions::childOf($parentId))->add(Restrictions::eq('label', $ancestor->getLabel()))->findUnique();
				if ($newFolder === null)
				{
					$newFolder = $fs->getNewDocumentInstance();
					$newFolder->setLabel($ancestor->getLabel());			
					$newFolder->setDescription($ancestor->getDescription());
					$newFolder->save($parentId);
				}
				$parentNewFolder['children'][$folderId] = array('folder' => $newFolder, 'children' => array());
			}
			$newFolder = &$parentNewFolder['children'][$folderId];
			return $this->replicateFolders($ancestors, $oldFolder, $newFolder);
		}
		else
		{
			return $parentNewFolder;
		}
	}

	/**
	 * @param Array $brandFolders
	 */
	private function deleteEmptyFolders($brandFolders)
	{
		foreach ($brandFolders['children'] as $folder)
		{
			$this->deleteEmptyFolders($folder);
		}
		
		$document = $brandFolders['folder'];
		if (!($document instanceof generic_persistentdocument_rootfolder) && count($document->getDocumentService()->getChildrenOf($document)) == 0)
		{
			$document->delete();
		}
	}
	
	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'brand';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}
}