<?php
/**
 * brand_BrandService
 * @package modules.brand
 */
class brand_BrandService extends f_persistentdocument_DocumentService
{
	/**
	 * Singleton
	 * @var brand_BrandService
	 */
	private static $instance = null;
	
	/**
	 * @return brand_BrandService
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
	 * @return brand_persistentdocument_brand
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_brand/brand');
	}
	
	/**
	 * Create a query based on 'modules_brand/brand' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_brand/brand');
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 * @param website_persistentdocument_website $website
	 * @return boolean
	 */
	public function isPublishedInWebsite($brand, $website)
	{
		$query = $this->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('id', $brand->getId()))
			->setProjection(Projections::count('id', 'count'));
			
		$query->createPropertyCriteria('brandId', 'modules_catalog/compiledproduct')
				->add(Restrictions::published())
				->add(Restrictions::eq('websiteId', $website->getId()));
		
		$result = $query->find();
		return (count($result) && $result[0]['count'] > 0);
	}
	
	/**
	 * @param string $codeReference
	 * @return brand_persistentdocument_brand
	 */
	public function getByCodeReference($codeReference)
	{
		return $this->createQuery()->add(Restrictions::eq('codeReference', $codeReference))->findUnique();
	}
	
	/**
	 * @param string[] $codeReferences
	 * @return brand_persistentdocument_brand[]
	 */
	public function getByCodeReferences($codeReferences)
	{
		return $this->createQuery()->add(Restrictions::in('codeReference', $codeReferences))->find();
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return integer
	 */
	public function getPublishedCountByWebsite($website)
	{
		$query = $this->createQuery()
			->add(Restrictions::published())
			->setProjection(Projections::distinctCount('id', 'count'));
			
		$query->createPropertyCriteria('brandId', 'modules_catalog/compiledproduct')
				->add(Restrictions::published())
				->add(Restrictions::eq('websiteId', $website->getId()));

		$result = $query->find();
		return (count($result)) ? intval($result[0]['count']): 0;
	}
		
	/**
	 * @param website_persistentdocument_website $website
	 * @param string $firstLetter
	 * @return brand_persistentdocument_brand[]
	 */
	public function getPublishedByWebsite($website, $firstLetter = null, $topShelf = null)
	{
		$query = $this->createQuery()
				->addOrder(Order::asc('document_label'))
				->add(Restrictions::published());
				
		if ($firstLetter !== null)
		{
			$query->add(Restrictions::eq('firstLetter', $firstLetter));
		}
						
		$query->createPropertyCriteria('brandId', 'modules_catalog/compiledproduct')
				->add(Restrictions::published())
				->add(Restrictions::eq('websiteId', $website->getId()));
					
		return $query->find(); 
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return string[]
	 */
	public function getFirstLettersByWebsite($website, $topShelf = null)
	{
		$query = $this->createQuery()->addOrder(Order::asc('document_label'))
			->add(Restrictions::published())
			->setProjection(Projections::groupProperty('firstLetter', 'firstLetter'));
			
		$query->createPropertyCriteria('brandId', 'modules_catalog/compiledproduct')
				->add(Restrictions::published())
				->add(Restrictions::eq('websiteId', $website->getId()));
				
		return $query->findColumn('firstLetter'); 
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 * @param catalog_persistentdocument_shop $shop
	 * @return integer[]
	 */
	public function getProductIdsByBrandAndShop($brand, $shop)
	{
		$query = catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::eq('brand', $brand))
			->addOrder(Order::asc('label'));					
		$query->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('brandId', $brand->getId()))
			->add(Restrictions::eq('shopId', $shop->getId()));
		return $query->setProjection(Projections::property('id'))->findColumn('id');
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @param Integer $parentNodeId
	 */
	protected function preSave($document, $parentNodeId)
	{
		$firstLetter = f_util_StringUtils::strtoupper(f_util_StringUtils::substr($document->getLabel(), 0, 1));
		$document->setFirstLetter($firstLetter);
		if ($document->getPublicationstatus() === f_persistentdocument_PersistentDocument::STATUS_PUBLISHED 
			&& $document->isPropertyModified('label'))
		{
			catalog_ProductService::getInstance()->setNeedCompileForBrand($document);
		}
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @param String $oldPublicationStatus
	 * @param array $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->getPublicationstatus() === f_persistentdocument_PersistentDocument::STATUS_PUBLISHED
			|| $oldPublicationStatus === f_persistentdocument_PersistentDocument::STATUS_PUBLISHED)
		{
			catalog_ProductService::getInstance()->setNeedCompileForBrand($document);
		}
	}
		
	/**
	 * @param brand_persistentdocument_brand $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 */
	protected function postSave($document, $parentNodeId)
	{
		// Synchronize space label.
		foreach (brand_SpaceService::getInstance()->getByBrand($document) as $space)
		{
			$space->setLabel($document->getLabel());
			$space->save();
		}
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		unset($allowedSections['urlrewriting']);
		$data = parent::getResume($document, $forModuleName, $allowedSections);
			
		$rc = RequestContext::getInstance();
		$contextlang = $rc->getLang();
		$lang = $document->isLangAvailable($contextlang) ? $contextlang : $document->getLang();
		try 
		{
			$rc->beginI18nWork($lang);
			
			$urlData = array();
			
			$query = $this->createQuery()
						->add(Restrictions::eq('id', $document->getId()));
			
			$query->createPropertyCriteria('brandId', 'modules_catalog/compiledproduct')
					->add(Restrictions::eq('lang', $lang))
					->setProjection(Projections::groupProperty('websiteId', 'websiteId'));
					
			foreach ($query->find() as $row)
			{
				$website = DocumentHelper::getDocumentInstance($row['websiteId'], 'modules_website/website');
				$urlData[] = array(
					'label' => f_Locale::translateUI('&modules.brand.bo.doceditor.Url-for-website;', array('website' => $website->getLabel())), 
					'href' => str_replace('&amp;', '&', $this->generateUrlForWebsite($document, $website, $lang, array(), false)),
					'class' => ($website->isPublished()) ? 'link' : ''
				);
			}
			$data['urlrewriting'] = $urlData;
									
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
		
		return $data;
	}
	
	/**
	 * @var website_persistentdocument_website
	 */
	private $currentWebsiteForResume = null;
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @return integer
	 */
	public function getWebsiteId($document)
	{
		$website = ($this->currentWebsiteForResume !== null) ? $this->currentWebsiteForResume : website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		if ($website !== null)
		{
			return $website->getId();
		}
		return null;
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 * @param website_persistentdocument_website $shop
	 * @param string $lang
	 * @param array $parameters
	 * @param Boolean $useCache
	 * @return string
	 */
	public function generateUrlForWebsite($brand, $website, $lang = null, $parameters = array(), $useCache = true)
	{
		$this->currentWebsiteForResume = $website;
		$url = LinkHelper::getDocumentUrl($brand, $lang, $parameters, $useCache);
		$this->currentWebsiteForResume = null;
		return $url;
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @return website_persistentdocument_page
	 */
	public function getDisplayPage($document)
	{
		$space = brand_SpaceService::getInstance()->getByBrandAndWebsiteId($document, $this->getWebsiteId($document));
		if ($space !== null && $space->getTopic()->isPublished())
		{
			return $space->getTopic()->getIndexPage();
		}
		return parent::getDisplayPage($document);
	}

	/**
	 * @param brand_persistentdocument_brand $brand
	 * @return array
	 */
	public function getPublicationInWebsitesInfos($brand)
	{
		$query = $this->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('id', $brand->getId()))
			->setProjection(Projections::groupProperty('id', 'brandId'));
			
		$query->createPropertyCriteria('brandId', 'modules_catalog/compiledproduct')
				->add(Restrictions::published())
				->setProjection(Projections::groupProperty('websiteId', 'websiteId'),
					Projections::groupProperty('lang', 'lang')
				);
		$compiledByWebsiteId = array();
		foreach ($query->find() as $row)
		{
			Framework::info(var_export($row, true));
			$brand = DocumentHelper::getDocumentInstance($row['brandId'], 'modules_brand/brand');
			$compiledByWebsiteId[$row['websiteId']][] = array($brand, $row['lang']);
		}
		
		$result = array();		
		foreach ($compiledByWebsiteId as $websiteId => $brandDatas)
		{
			$websiteInfos = array();
			$website = DocumentHelper::getDocumentInstance($websiteId, 'modules_website/website');
			$websiteInfos['websiteLabel'] = $website->getLabel();				
			$websiteInfos['brands'] = array();
			foreach ($brandDatas as $data)
			{
				list($brand, $lang) = $data;
				$publication = f_Locale::translateUI(DocumentHelper::getPublicationstatusLocaleKey($brand));			
				$websiteInfos['brands'][] = array(
					'lang' => $lang,
					'plublication' => $publication
				);
			}
			$result[] = $websiteInfos;
		}
		return $result;
	}

	// Deprecated.
	
	/**
	 * @deprecated use getProductIdsByBrandAndShop
	 */
	public function getProductsByBrandAndShop($brand, $shop, $offset = 0, $maxresults = null)
	{
		$query = catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::eq('brand', $brand));
			
		$query->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('brandId', $brand->getId()))
			->add(Restrictions::eq('shopId', $shop->getId()));
			
		if ($maxresults !== null)
		{
			$query->addOrder(Order::asc('document_label'));
			$query->setFirstResult($offset);
			$query->setMaxResults($maxresults);
		}
		return $query->find();
	}
}