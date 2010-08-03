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
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param string $pageTemplate
	 * @param string $script
	 * @param DOMDocument $scriptPath
	 */
	public function updateStructureInitializationScript($container, $pageTemplate, $script, $scriptDom)
	{
		switch ($script)
		{
			case 'globalDefaultStructure':
				$this->updateGlobalStructureInitializationScript($container, $pageTemplate, $script, $scriptDom);
				break;
				
			case 'spaceDefaultStructure' :
				$this->updateSpaceStructureInitializationScript($container, $pageTemplate, $script, $scriptDom);
				break;
			
			default:
				throw new BaseException('Unknown structure initialization script: '.$script, 'modules.brand.bo.actions.Unknown-structure-initialization-script', array('script' => $script));
				break;
		}
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param string $pageTemplate
	 * @param string $script
	 * @param DOMDocument $scriptPath
	 */
	protected function updateGlobalStructureInitializationScript($container, $pageTemplate, $script, $scriptDom)
	{
		// Check container.
		if (!$container instanceof website_persistentdocument_website && !$container instanceof website_persistentdocument_topic)
		{
			throw new BaseException('Invalid website or topic', 'modules.brand.bo.general.Invalid-website-or-topic');
		}
		else
		{
			if ($container instanceof website_persistentdocument_website)
			{
				$websiteId = $container->getId();
			}
			else 
			{
				$websiteId = $container->getDocumentService()->getWebsiteId($container);
			}
			
			$query = $this->getPersistentProvider()->createQuery()->add(Restrictions::descendentOf($websiteId));
			$query->add(Restrictions::orExp(
				Restrictions::hasTag('contextual_website_website_modules_brand_brandlist'),
				Restrictions::hasTag('contextual_website_website_modules_brand_brand')
			));
			$query->setProjection(Projections::rowCount('count'));
			$row = $query->findUnique();
			if ($row['count'] > 0)
			{
				throw new BaseException('Some pages of the global structure are already initialized', 'modules.brand.bo.general.Some-pages-already-initialized');
			}
		}
		
		// Fix script content.
		$xmlRoot = $scriptDom->getElementsByTagName('documentRef')->item(0);
		$xmlRoot->setAttribute('byDocumentId', $container->getId());
		$xmlRoot->setAttribute('type', $container->getPersistentModel()->getName());
	}
	
	/**
	 * @param f_peristentdocument_PersistentDocument $container
	 * @param string $pageTemplate
	 * @param string $script
	 * @param DOMDocument $scriptPath
	 */
	protected function updateSpaceStructureInitializationScript($container, $pageTemplate, $script, $scriptDom)
	{
		// Check container.
		if (!$container instanceof brand_persistentdocument_space)
		{
			throw new BaseException('Invalid brand space', 'modules.brand.bo.general.Invalid-space');
		}
		else
		{
			$node = TreeService::getInstance()->getInstanceByDocument($container->getTopic());
			if (count($node->getChildren('modules_website/page')) > 0)
			{
				throw new BaseException('This brand space already contains pages', 'modules.brand.bo.general.Space-already-contains-pages');
			}
		}
		
		// Fix script content.
		$brand = $container->getBrand();
		$xmlTopic = $scriptDom->getElementsByTagName('documentRef')->item(0);
		$xmlTopic->setAttribute('type', $container->getTopic()->getPersistentModel()->getName());
		$xmlTopic->setAttribute('byDocumentId', $container->getTopic()->getId());
		$xmlBlocks = $scriptDom->getElementsByTagName('changeblock');
		for ($i = 0; $i < $xmlBlocks->length; $i++)
		{
			$xmlBlock = $xmlBlocks->item($i);
			if ($xmlBlock->getAttribute('type') == 'modules_brand_brand')
			{
				$xmlBlock->setAttribute('__cmpref', $brand->getId());
			}
			else if ($xmlBlock->getAttribute('type') == 'modules_brand_brandProducts')
			{
				$xmlBlock->setAttribute('__brand', $brand->getId());
			}
		}
	}
}