<?php
/**
 * brand_CompiledbrandScriptDocumentElement
 * @package modules.brand.persistentdocument.import
 */
class brand_CompiledbrandScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return brand_persistentdocument_compiledbrand
     */
    protected function initPersistentDocument()
    {
    	return brand_CompiledbrandService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_brand/compiledbrand');
	}
}