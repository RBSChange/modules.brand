<?php
/**
 * brand_LoadSpacesAction
 * @package modules.brand.actions
 */
class brand_LoadSpacesAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$brand = $this->getDocumentInstanceFromRequest($request);
		$result = brand_SpaceService::getInstance()->getInfosByBrand($brand);

		return $this->sendJSON($result);
	}
}