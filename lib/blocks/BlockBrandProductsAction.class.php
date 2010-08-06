<?php
/**
 * brand_BlockBrandProductsAction
 * @package modules.brand.lib.blocks
 */
class brand_BlockBrandProductsAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[]
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