<?php
/**
 * brand_BlockBrandAction
 * @package modules.brand.lib.blocks
 */
class brand_BlockBrandAction extends website_BlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
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
		
		$context = $this->getPage();
		$isOnDetailPage = TagService::getInstance()->hasTag($context->getPersistentPage(), 'functional_brand_brand-detail');
		$brand = $this->getDocumentParameter();
		if ($brand === null || !($brand instanceof brand_persistentdocument_brand) || !$brand->isPublished())
		{
			if ($isOnDetailPage)
			{
				HttpController::getInstance()->redirect("website", "Error404");
			}
			return website_BlockView::NONE;
		}
		
		$request->setAttribute('brand', $brand);
		$request->setAttribute('isOnDetailPage', $isOnDetailPage);
		
		return website_BlockView::SUCCESS;
	}
}