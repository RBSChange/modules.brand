<?php
/**
 * Class where to put your custom methods for document brand_persistentdocument_compiledbrand
 * @package modules.brand.persistentdocument
 */
class brand_persistentdocument_compiledbrand extends brand_persistentdocument_compiledbrandbase implements indexer_IndexableDocument
{
	/**
	 * Get the indexable document
	 *
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocument()
	{
		$indexedDoc = new indexer_IndexedDocument();
		// TODO : set the different properties you want in you indexedDocument :
		// - please verify that id, documentModel, label and lang are correct according your requirements
		// - please set text value.
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel('modules_brand/compiledbrand');
		$indexedDoc->setLabel($this->getLabel());
		$indexedDoc->setLang($this->getLang());
		$indexedDoc->setText(null); // TODO : please fill text property
		return $indexedDoc;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
//	public function addFormProperties($propertiesNames, &$formProperties)
//	{	
//	}
}