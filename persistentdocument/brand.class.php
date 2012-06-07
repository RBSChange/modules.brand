<?php
/**
 * brand_persistentdocument_brand
 * @package modules.brand
 */
class brand_persistentdocument_brand extends brand_persistentdocument_brandbase implements indexer_IndexableDocument
{
	/**
	 * Get the indexable document
	 *
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		$indexedDoc = new indexer_IndexedDocument();
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel('modules_brand/brand');
		$indexedDoc->setLabel($this->getLabel());
		$indexedDoc->setLang(RequestContext::getInstance()->getLang());
		$text = f_util_StringUtils::htmlToText($this->getDescription(), false, true);
		$indexedDoc->setText($text);
		return $indexedDoc;
	}
}