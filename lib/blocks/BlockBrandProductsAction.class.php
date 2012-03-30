<?php
/**
 * brand_BlockBrandProductsAction
 * @package modules.brand.lib.blocks
 */
class brand_BlockBrandProductsAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @return string
	 */
	protected function getBlockTitle()
	{
		$brand = $this->getBrand();
		if (!$brand)
		{
			return null;
		}
		return LocaleService::getInstance()->transFO('m.brand.frontoffice.Brand-products-title', array('ucf', 'html'), array('brand' => $brand->getLabelAsHtml()));
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[]
	 */
	protected function getProductIdArray($request)
	{
		$brand = $this->getBrand();
		if (!$brand)
		{
			return null;
		}
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		return brand_BrandService::getInstance()->getProductIdsByBrandAndShop($brand, $shop);
	}
	
	/**
	 * @var brand_persistentdocument_brand
	 */
	private $brand = false;
	
	/**
	 * @return brand_persistentdocument_brand
	 */
	protected function getBrand()
	{
		if ($this->brand === false)
		{
			$config = $this->getConfiguration();
			$brand = $config->hasNonEmptyConfigurationParameter('brand') ? $config->getBrand() : $this->getDocumentParameter();
			$this->brand = ($brand instanceof brand_persistentdocument_brand && $brand->isPublished()) ? $brand : null;
		}
		return $this->brand;
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getProductIdArray()
	 */
	protected function getProductArray($request)
	{
		$config = $this->getConfiguration();
		$brand = $config->hasNonEmptyConfigurationParameter('brand') ? $config->getBrand() : $this->getDocumentParameter();
		if (! ($brand instanceof brand_persistentdocument_brand) || ! $brand->isPublished())
		{
			return null;
		}
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$products = brand_BrandService::getInstance()->getProductsByBrandAndShop($brand, $shop);
		
		// Prepare display configuration.
		$request->setAttribute('blockTitle', 
				f_Locale::translate('&modules.brand.frontoffice.Brand-products-title;', 
						array('brand' => $brand->getLabelAsHtml())));
		$request->setAttribute('blockView', $this->getDisplayMode($request));
		return $products;
	}
}