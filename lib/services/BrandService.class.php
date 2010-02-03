<?php
/**
 * brand_BrandService
 * @package modules.brand
 */
class brand_BrandService extends f_persistentdocument_DocumentService
{
	/**
	 * Singleton
	 * @var brand_BrandService
	 */
	private static $instance = null;

	/**
	 * @return brand_BrandService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return brand_persistentdocument_brand
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_brand/brand');
	}

	/**
	 * Create a query based on 'modules_brand/brand' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_brand/brand');
	}
}