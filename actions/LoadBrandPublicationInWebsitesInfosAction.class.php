<?php
/**
 * brand_LoadBrandPublicationInWebsitesInfosAction
 * @package modules.brand.actions
 */
class brand_LoadBrandPublicationInWebsitesInfosAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$brand = $this->getDocumentInstanceFromRequest($request);
		$result['infos'] = $brand->getDocumentService()->getPublicationInWebsitesInfos($brand);

		return $this->sendJSON($result);
	}
}