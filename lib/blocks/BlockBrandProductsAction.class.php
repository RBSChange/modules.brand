<?php
/**
 * brand_BlockBrandProductsAction
 * @package modules.brand.lib.blocks
 */
class brand_BlockBrandProductsAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$config = $this->getConfiguration();
		$brand = $config->hasNonEmptyConfigurationParameter('brand') ? $config->getBrand() : $this->getDocumentParameter();
		if ($brand === null || !($brand instanceof brand_persistentdocument_brand) || !$brand->isPublished())
		{
			return website_BlockView::NONE;
		}

		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$products = brand_BrandService::getInstance()->getProductsByBrandAndShop($brand, $shop);
		if (count($products) > 0)
		{
			$maxresults = $this->getMaxresults($request);
			$page = $request->getParameter(paginator_Paginator::REQUEST_PARAMETER_NAME, 1);
			$request->setAttribute('products', new paginator_Paginator('brand', $page, $products, $maxresults));
		}
		
		// Prepare display configuration.
		$request->setAttribute('displayConfig', $this->getDisplayConfig($shop));		 
		$request->setAttribute('shop', $shop);
		$request->setAttribute('blockTitle', f_Locale::translate('&modules.brand.frontoffice.Brand-products-title;', array('brand' => $brand->getLabelAsHtml())));
		$request->setAttribute('blockView', $this->getDisplayMode($request));
		
		return $this->forward('catalog', 'productlist');
	}
}