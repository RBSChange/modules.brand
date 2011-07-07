<?php
/**
 * brand_BlockBrandListAction
 * @package modules.brand.lib.blocks
 */
class brand_BlockBrandListAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		
		$bs = brand_BrandService::getInstance();
		$brandsByFirstLetter = array();
		$topShelf = $this->getConfiguration()->getTopshelf();
		if ($this->getConfiguration()->getPaginated())
		{
			$letter = $request->getParameter('letter', 'A');
			$brands = $bs->getPublishedByWebsite($website, $letter, $topShelf);
			$maxresults = $this->getConfiguration()->getItemsPerPage();
			$page = $request->getParameter(paginator_Paginator::PAGEINDEX_PARAMETER_NAME, 1);
			if (!is_numeric($page) || $page < 1 || $page > ceil(count($brands) / $maxresults))
			{
				$page = 1;
			}
			$paginator = new paginator_Paginator('brand', $page, $brands, $maxresults);
			$brandsByFirstLetter[$letter] = $paginator;
			$request->setAttribute('firstLetters', $bs->getFirstLettersByWebsite($website, $topShelf));
			$this->getContext()->addCanonicalParam('letter', $letter, $this->getModuleName());
			$this->getContext()->addCanonicalParam('page', $page > 1 ? $page : null, $this->getModuleName());
		}
		else
		{
			$brands = $bs->getPublishedByWebsite($website, null, $topShelf);
			foreach ($brands as $brand)
			{
				$firstLetter = $brand->getFirstLetter();
				$brandsByFirstLetter[$firstLetter][] = $brand;
			}
			$request->setAttribute('firstLetters', array_keys($brandsByFirstLetter));
		}
		$request->setAttribute('brandsByFirstLetter', $brandsByFirstLetter);
				
		return website_BlockView::SUCCESS;
	}
}