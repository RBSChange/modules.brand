<?php
/**
 * brand_BrandCompilationListener
 * @package modules.brand
 */
class brand_BrandCompilationListener
{
	/**
	 * @param f_persistentdocument_PersistentDocument $sender
	 * @param array $params
	 * @return void
	 */
	public function onPersistentDocumentDeleted($sender, $params)
	{
		$document = $params['document'];
		if ($document instanceof website_persistentdocument_website)
		{
			brand_CompiledbrandService::getInstance()->deleteForWebsite($document);
		}
	}
}