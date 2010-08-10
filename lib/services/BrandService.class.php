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
	 * @param website_persistentdocument_website $website
	 * @param string $firstLetter
	 */
	public function getPublishedByWebsite($website, $firstLetter = null)
	{
		$query = $this->createQuery()->addOrder(Order::asc('document_label'));
		if ($firstLetter !== null)
		{
			$query->add(Restrictions::eq('firstLetter', $firstLetter));
		}
		$query->createCriteria('compiledbrand', 'modules_brand/compiledbrand')->add(Restrictions::published())
			->add(Restrictions::eq('websiteId', $website->getId()));
		return $query->find(); 
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 */
	public function getFirstLettersByWebsite($website)
	{
		$query = $this->createQuery()->addOrder(Order::asc('document_label'))->setProjection(Projections::property('firstLetter'));
		$query->createCriteria('compiledbrand', 'modules_brand/compiledbrand')->add(Restrictions::published())
			->add(Restrictions::eq('websiteId', $website->getId()));
		return $query->findColumn('firstLetter'); 
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer $offset
	 * @param integer $maxresults
	 * @return catalog_persistentdocument_product
	 */
	public function getProductsByBrandAndShop($brand, $shop, $offset = 0, $maxresults = null)
	{
		$query = catalog_CompiledproductService::getInstance()->createQuery();
		$query->add(Restrictions::published())->add(Restrictions::eq('indexed', true))
			->add(Restrictions::eq('brandId', $brand->getId()))->add(Restrictions::eq('shopId', $shop->getId()))
			->setProjection(Projections::property('product', 'product'));
		if ($maxresults !== null)
		{
			$query->setFirstResult($offset);
			$query->setMaxResults($maxresults);
		}
		return $query->findColumn('product');
	}
	
	/**
	 * @return integer[]
	 */
	public final function getAllBrandIdsToCompile()
	{
		return $this->createQuery()->setProjection(Projections::property('id', 'id'))->findColumn('id');
	}
	
	/**
	 * @return integer[]
	 */
	public final function getBrandIdsToCompile()
	{
		return $this->createQuery()->add(Restrictions::eq('compiled', false))->setProjection(Projections::property('id', 'id'))->findColumn('id');
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if (!isset($params['cause']) || $params['cause'] != 'delete')
		{
			if ($document->isPublished() || $oldPublicationStatus == 'PUBLICATED')
			{	
				$this->updateCompiledProperty($document, false);
			}
		}
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @param Integer $parentNodeId
	 */
	protected function preSave($document, $parentNodeId)
	{
		parent::preSave($document, $parentNodeId);
		
		$firstLetter = f_util_StringUtils::strtoupper(f_util_StringUtils::substr($document->getLabel(), 0, 1));
		$document->setFirstLetter($firstLetter);
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 */
	protected function preDelete($document)
	{
		brand_CompiledbrandService::getInstance()->deleteForBrand($document);
	}

	/**
	 * @param brand_persistentdocument_brand $document
	 */
	protected function postDeleteLocalized($document)
	{
		$this->updateCompiledProperty($document, false);
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 */
	protected function postSave($document, $parentNodeId)
	{
		$this->updateCompiledProperty($document, false);
		
		// Synchronize space label.
		foreach (brand_SpaceService::getInstance()->getByBrand($document) as $space)
		{
			$space->setLabel($document->getLabel());
			$space->save();
		}
	}
	
	/**
	 * @param integer[] $brandIds
	 */
	public function setNeedCompile($brandIds)
	{
		if (f_util_ArrayUtils::isNotEmpty($brandIds))
		{
			try 
			{
				$this->tm->beginTransaction();
				foreach (brand_BrandService::getInstance()->createQuery()->add(Restrictions::in('id', $brandIds))->find() as $brand) 
				{
					$brand->getDocumentService()->updateCompiledProperty($brand, false);
				}
				$this->tm->commit();
			} 
			catch (Exception $e)
			{
				$this->tm->rollBack($e);
			}
		}
	}
	
	/**
	 * @param brand_persistentdocument_brand $document
	 * @param boolean $compiled
	 */
	protected function updateCompiledProperty($document, $compiled)
	{
		if ($document->getCompiled() != $compiled)
		{
			if ($document->isModified())
			{
				Framework::warn(__METHOD__ . $document->__toString() . ", $compiled : Is not possible on modified brand");
				return;		
			}
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' ' . $document->__toString() . ($compiled ? ' is compiled':' to recompile'));
			}
			$document->setCompiled($compiled);
			$this->pp->updateDocument($document);
		}
	}
	
	/**
	 * @param integer $brandId
	 */
	public function setCompiled($brandId)
	{
		$brand = $this->getDocumentInstance($brandId, 'modules_brand/brand');
		$brand->getDocumentService()->updateCompiledProperty($brand, true);
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
		
		$data['properties']['compiled'] = f_Locale::translateUI('&framework.boolean.' . ($document->getCompiled() ? 'True' : 'False') . ';');
			
		$rc = RequestContext::getInstance();
		$contextlang = $rc->getLang();
		$lang = $document->isLangAvailable($contextlang) ? $contextlang : $document->getLang();
		try 
		{
			$rc->beginI18nWork($lang);
			
			$urlData = array();
			
			$query = brand_CompiledbrandService::getInstance()->createQuery()->add(Restrictions::eq('brand.id', $document->getId()))
				->setProjection(Projections::property('websiteId'), Projections::property('publicationstatus'));
			foreach ($query->find() as $row)
			{
				$website = DocumentHelper::getDocumentInstance($row['websiteId'], 'modules_website/website');
				$urlData[] = array(
					'label' => f_Locale::translateUI('&modules.brand.bo.doceditor.Url-for-website;', array('website' => $website->getLabel())), 
					'href' => str_replace('&amp;', '&', $this->generateUrlForWebsite($document, $website, $lang, array(), false)),
					'class' => ($website->isPublished() && $row['publicationstatus'] == 'PUBLICATED') ? 'link' : ''
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
		$cbs = brand_CompiledbrandService::getInstance();
		if (!$brand->getCompiled())
		{
			$cbs->generateForBrand($brand);
		}
		
		$compiledByWebsiteId = array();
		$query = $cbs->createQuery()->add(Restrictions::eq('brand', $brand))->addOrder(Order::asc('websiteId'));
		foreach ($query->find() as $compiledBrand)
		{
			$websiteId = $compiledBrand->getWebsiteId();
			if (!isset($compiledByWebsiteId[$websiteId]))
			{
				$compiledByWebsiteId[$websiteId] = array();
			}
			$compiledByWebsiteId[$websiteId][] = $compiledBrand;
		}
		
		$result = array();		
		foreach ($compiledByWebsiteId as $websiteId => $compiledBrands)
		{
			$websiteInfos = array();
			$website = DocumentHelper::getDocumentInstance($websiteId, 'modules_website/website');
			$websiteInfos['websiteLabel'] = $website->getLabel();		
			
			$websiteInfos['brands'] = array();
			foreach ($compiledBrands as $compiledBrand)
			{
				$lang = $compiledBrand->getLang();
				$publication = f_Locale::translateUI(DocumentHelper::getPublicationstatusLocaleKey($compiledBrand));
				if ($compiledBrand->getPublicationStatus() === 'ACTIVE' && $compiledBrand->hasMeta('ActPubStatInf'.$lang))
				{
					$publication .= ' (' . f_Locale::translateUI($compiledBrand->getMeta('ActPubStatInf'.$lang)) . ')';
				}
				
				$websiteInfos['brands'][] = array(
					'lang' => $lang,
					'plublication' => $publication
				);
			}
			$result[] = $websiteInfos;
		}
		return $result;
	}
}