<?php
/**
 * brand_BlockBrandAction
 * @package modules.brand.lib.blocks
 */
class brand_BlockBrandAction extends website_BlockAction
{
	/**
	 * @return array<String, String>
	 */
	public function getMetas()
	{
		$doc = $this->getDocumentParameter();
		if ($doc instanceof brand_persistentdocument_brand)
		{
			$label = $doc->getLabel();
			$description = f_util_StringUtils::shortenString(f_util_HtmlUtils::htmlToText($doc->getDescriptionAsHtml()), 100);
			return array('brandname' => $label, 'branddescription' => $description);
		}
		return array('brandname' => null, 'branddescription' => null);
	}
	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$context = $this->getContext();
		$isOnDetailPage = TagService::getInstance()->hasTag($context->getPersistentPage(), 'functional_brand_brand-detail');
		$brand = $this->getDocumentParameter();
		if ($brand === null || !($brand instanceof brand_persistentdocument_brand) || !$brand->isPublished())
		{
			if ($isOnDetailPage)
			{
				change_Controller::getInstance()->redirect("website", "Error404");
			}
			return website_BlockView::NONE;
		}
		
		$request->setAttribute('brand', $brand);
		$request->setAttribute('isOnDetailPage', $isOnDetailPage);
		
		return website_BlockView::SUCCESS;
	}
}