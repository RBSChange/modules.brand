<?php
/**
 * @package modules.brand.lib.services
 */
class brand_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var brand_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return brand_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	public function getStructureInitializationAttributes($container, $attributes, $script)
	{
		switch ($script)
		{
			case 'globalDefaultStructure':
				return $this->getGlobalStructureInitializationAttributes($container, $attributes, $script);
				
			case 'spaceDefaultStructure' :
				return $this->getSpaceStructureInitializationAttributes($container, $attributes, $script);
			
			default:
				throw new BaseException('Unknown structure initialization script: '.$script, 'm.website.bo.actions.unknown-structure-initialization-script', array('script' => $script));
		}
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	protected function getGlobalStructureInitializationAttributes($container, $attributes, $script)
	{
		// Check container.
		if (!$container instanceof website_persistentdocument_website && !$container instanceof website_persistentdocument_topic)
		{
			throw new BaseException('Invalid website or topic', 'm.website.bo.general.invalid-website-or-topic');
		}
		
		if ($container instanceof website_persistentdocument_website)
		{
			$websiteId = $container->getId();
		}
		else 
		{
			$websiteId = $container->getDocumentService()->getWebsiteId($container);
		}
		
		$website = DocumentHelper::getDocumentInstance($websiteId, 'modules_website/website');
		if (TagService::getInstance()->hasDocumentByContextualTag('contextual_website_website_modules_brand_brandlist', $website) || 
			TagService::getInstance()->hasDocumentByContextualTag('contextual_website_website_modules_brand_brand', $website))
		{
			throw new BaseException('Some pages are already initialized', 'm.website.bo.actions.some-pages-already-initialized');
		}
		
		// Set atrtibutes.
		$attributes['byDocumentId'] = $container->getId();
		$attributes['type'] = $container->getPersistentModel()->getName();
		return $attributes;
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param array $attributes
	 * @param string $script
	 * @return array
	 */
	protected function getSpaceStructureInitializationAttributes($container, $attributes, $script)
	{
		// Check container.
		if (!$container instanceof brand_persistentdocument_space)
		{
			throw new BaseException('Invalid brand space', 'modules.brand.bo.general.Invalid-space');
		}
		
		$node = TreeService::getInstance()->getInstanceByDocument($container->getTopic());
		if (count($node->getChildren('modules_website/page')) > 0)
		{
			throw new BaseException('This brand space already contains pages', 'modules.brand.bo.general.Space-already-contains-pages');
		}
		
		// Set atrtibutes.
		$brand = $container->getBrand();
		$attributes['byDocumentId'] = $container->getTopic()->getId();
		$attributes['type'] = $container->getTopic()->getPersistentModel()->getName();
		$attributes['brandId'] = $brand->getId();
		return $attributes;
	}
}