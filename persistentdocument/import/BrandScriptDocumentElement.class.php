<?php
class brand_BrandScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return brand_persistentdocument_brand
     */
    protected function initPersistentDocument()
    {
    	return brand_BrandService::getInstance()->getNewDocumentInstance();    
    }
}