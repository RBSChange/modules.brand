<?php
/**
 * brand_CompileBrandAction
 * @package modules.brand.actions
 */
class brand_CompileBrandAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$brand = $this->getBrand($request);
		brand_CompiledbrandService::getInstance()->generateForBrand($brand);
	
		$result['id'] = $brand->getId();
		$result['compiled'] = $brand->getCompiled();
		
		$this->logAction($brand);
		return $this->sendJSON($result);
	}
	
	/**
	 * @param Request $request
	 * @return brand_persistentdocument_brand
	 */	
	private function getBrand($request)
	{
		$brand = $this->getDocumentInstanceFromRequest($request);
		if ($brand instanceof brand_persistentdocument_brand) 
		{
			return $brand;
		}
		throw new BaseException('Invalid brand', 'modules.brand.errors.compilebrandAction.Invalid-brand');
	}
}