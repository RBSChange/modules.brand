<?php
/**
 * brand_LoadSpacesAction
 * @package modules.brand.actions
 */
class brand_LoadSpacesAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$brand = $this->getDocumentInstanceFromRequest($request);
		$result = brand_SpaceService::getInstance()->getInfosByBrand($brand);

		return $this->sendJSON($result);
	}
}