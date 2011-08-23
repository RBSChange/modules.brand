<?php
/**
 * brand_SpaceService
 * @package modules.brand
 */
class brand_SpaceService extends f_persistentdocument_DocumentService
{
	/**
	 * @var brand_SpaceService
	 */
	private static $instance;

	/**
	 * @return brand_SpaceService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return brand_persistentdocument_space
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_brand/space');
	}

	/**
	 * Create a query based on 'modules_brand/space' model.
	 * Return document that are instance of modules_brand/space,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_brand/space');
	}
	
	/**
	 * Create a query based on 'modules_brand/space' model.
	 * Only documents that are strictly instance of modules_brand/space
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_brand/space', false);
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 * @param integer $websiteId
	 * @return brand_persistentdocument_space
	 */
	public function getByBrandAndWebsiteId($brand, $websiteId)
	{
		return $this->createQuery()->add(Restrictions::eq('brand', $brand))->add(Restrictions::eq('websiteId', $websiteId))->findUnique();
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 * @return brand_persistentdocument_space[]
	 */
	public function getByBrand($brand)
	{
		return $this->createQuery()->add(Restrictions::eq('brand', $brand))->find();
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 */
	public function getInfosByBrand($brand)
	{
		$infos = array('spaces' => array());
		foreach ($this->getByBrand($brand) as $space)
		{
			$infos['spaces'][] = array(
				'id' => $space->getId(), 
				'path' => $space->getTopic()->getPathOf(),
				'status' => $space->getPublicationstatus(), 
				'editorModel' => $space->getPersistentModel()->getBackofficeName(),
				'topicId' => $space->getTopic()->getId(),
				'brandId' => $brand->getId(),
				'brandEditorModel' => $brand->getPersistentModel()->getBackofficeName()
			);
		}
		$infos['spacesJSON'][] = JsonService::getInstance()->encode($infos['spaces']);
		return $infos;
	}

	/**
	 * @param brand_persistentdocument_space $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		parent::preInsert($document, $parentNodeId);
		
		// Set brand and label.
		$brand = DocumentHelper::getDocumentInstance($parentNodeId, 'modules_brand/brand');
		$document->setBrand($brand);
		$document->setLabel($brand->getLabel());
		$document->setInsertInTree(false);
		
		$this->handleMountParent($document);
	}

	/**
	 * @param brand_persistentdocument_space $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{
		parent::preUpdate($document, $parentNodeId);
		
		$this->handleMountParent($document);
	}
	
	/**
	 * @param brand_persistentdocument_space $document
	 */
	protected function handleMountParent($document)
	{
		// Check parent type.
		$parent = $document->getMountParent();
		if ($parent === null)
		{
			throw new BaseException('Space must have a mount parent!', 'modules.brand.document.space.exception.Mount-parent-required');
		}
		else if (!($parent instanceof website_persistentdocument_topic) && !($parent instanceof website_persistentdocument_website))
		{
			throw new BaseException('Space parent must be a topic or a website!', 'modules.brand.document.space.exception.Mount-parent-bad-type');
		}
		
		// Generate or move the topic.
		$topic = $document->getTopic();
		if ($topic === null)
		{
			$brand = $document->getBrand();
			$topic = website_SystemtopicService::getInstance()->getNewDocumentInstance();
			$topic->setReferenceId($document->getId());
			$topic->setLabel($brand->getLabel());
			$topic->setDescription($brand->getDescription());
			$topic->setPublicationstatus('DRAFT');
			$topic->save($parent->getId());
			$document->setTopic($topic);
		}
		else if ($parent !== $this->getParentOf($topic))
		{
			$topic->getDocumentService()->moveTo($topic, $parent->getId());
		}
		
		// Update the website.
		$website = website_WebsiteService::getInstance()->createQuery()
			->add(Restrictions::ancestorOf($topic->getId()))
			->findUnique();
		$document->setWebsiteId($website->getId());
	}

	/**
	 * @param brand_persistentdocument_space $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		// Synchronize topic properties.
		$topic = $document->getTopic();
		$topic->setLabel($document->getLabel());
		$topic->save();
	}

	/**
	 * @param brand_persistentdocument_space $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{
		parent::postSave($document, $parentNodeId);
		
		// Ensure that there may be only space by website for a given brand.
		$query = $this->createQuery()
			->add(Restrictions::ne('id', $document->getId()))
			->add(Restrictions::eq('brand', $document->getBrand()))
			->add(Restrictions::eq('websiteId', $document->getWebsiteId()));
		if ($query->findUnique() !== null)
		{
			throw new BaseException('There may be only one space by website for a given brand.', 'modules.brand.document.space.exception.Only-one-space-by-website');
		}
		
		// Fix referenceId if set to -1 (when the topic is created in the pre-save).
		$topic = $document->getTopic();
		if ($topic->getReferenceId() === -1)
		{
			$topic->setReferenceId($document->getId());
			$topic->save();
		}
		if ($topic->getPublicationstatus() == 'DRAFT')
		{
			$topic->activate();
		}
	}

	/**
	 * @param brand_persistentdocument_space $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		$result = parent::isPublishable($document);
		if ($result)
		{
			$website = DocumentHelper::getDocumentInstance($document->getWebsiteId());
			if (!brand_BrandService::getInstance()->isPublishedInWebsite($document->getBrand(), $website))
			{
				$this->setActivePublicationStatusInfo($document, '&modules.brand.document.space.publication.brand-not-published-in-website;');
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * @param brand_persistentdocument_space $space
	 * @param website_persistentdocument_systemtopic $systemtopic
	 */
	public function isSystemtopicPublishable($space, $systemtopic)
	{
		$ds = $systemtopic->getDocumentService();
		if (!$space->isPublished())
		{
			$this->setActivePublicationStatusInfo($systemtopic, '&modules.brand.document.space.systemtopic-publication.space-not-published;');
			return false;
		}
		if (!$ds->hasPublishedPages($systemtopic))
		{
			$this->setActivePublicationStatusInfo($systemtopic, '&modules.brand.document.space.systemtopic-publication.has-no-published-page;');
			return false;
		}
		return true;
	}

	/**
	 * @param brand_persistentdocument_space $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->isPublished() || $oldPublicationStatus == 'PUBLICATED')
		{	
			$topic = $document->getTopic();
			$topic->getDocumentService()->publishIfPossible($topic->getId());
		}
	}

	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param brand_persistentdocument_space $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
	{
		return $urlRewritingService->getDocumentLinkForWebsite($document->getBrand(), $website, $lang, $parameters);
	}	
}