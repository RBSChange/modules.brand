<?php
/**
 * brand_CompiledbrandService
 * @package modules.brand
 */
class brand_CompiledbrandService extends f_persistentdocument_DocumentService
{
	/**
	 * @var brand_CompiledbrandService
	 */
	private static $instance;

	/**
	 * @return brand_CompiledbrandService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return brand_persistentdocument_compiledbrand
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_brand/compiledbrand');
	}

	/**
	 * Create a query based on 'modules_brand/compiledbrand' model.
	 * Return document that are instance of modules_brand/compiledbrand,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_brand/compiledbrand');
	}
	
	/**
	 * Create a query based on 'modules_brand/compiledbrand' model.
	 * Only documents that are strictly instance of modules_brand/compiledbrand
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_brand/compiledbrand', false);
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 * @param integer $websiteId
	 * @return brand_persistentdocument_compiledbrand
	 */
	public function getByBrandAndWebsiteId($brand, $websiteId)
	{
		return $this->createQuery()->add(Restrictions::eq('brand', $brand))->add(Restrictions::eq('websiteId', $websiteId))->findUnique();
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 */
	public function deleteForBrand($brand)
	{
		if ($brand instanceof brand_persistentdocument_brand) 
		{
			$this->createQuery()->add(Restrictions::eq('brand', $brand))->delete();
		}
	}

	/**
	 * @param website_persistentdocument_website $website
	 */
	public function deleteForWebsite($website)
	{	
		if ($website instanceof website_persistentdocument_website) 
		{
			$this->createQuery()->add(Restrictions::eq('websiteId', $website->getId()))->delete();
		}
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 */
	public function generateForBrand($brand)
	{
		if (!($brand instanceof brand_persistentdocument_brand)) 
		{
			return;
		}
		
		try 
		{
			$this->tm->beginTransaction();

			$websiteIds = catalog_CompiledproductService::getInstance()->createQuery()->add(Restrictions::eq('brandId', $brand->getId()))
				->setProjection(Projections::property('websiteId'))->findColumn('websiteId'); 

			$CBIds = array();
			$rqc = RequestContext::getInstance();
			foreach ($websiteIds as $websiteId)
			{
				try 
				{
					$website = DocumentHelper::getDocumentInstance($websiteId, 'modules_website/website');
				}
				catch (Exception $e)
				{
					if (Framework::isDebugEnabled())
					{
						Framework::debug(__METHOD__ . ' ' . $e->getMessage());
					}
					continue;
				}
				
				foreach ($rqc->getSupportedLanguages() as $lang)
				{
					if ($brand->isLangAvailable($lang) && $website->isLangAvailable($lang))
					{
						try 
						{
							$rqc->beginI18nWork($lang);	
							$cb = $this->generate($brand, $websiteId);
							$CBIds[] = $cb->getId();
							$rqc->endI18nWork();
						}
						catch (Exception $rce)
						{
							$rqc->endI18nWork($rce);	
						}
					}
				}
			}
			
			$query = $this->createQuery()->add(Restrictions::eq('brand', $brand));			
			if (count($CBIds))
			{
				$query->add(Restrictions::notin('id', $CBIds));
			}
			$query->delete();
			brand_BrandService::getInstance()->setCompiled($brand->getId());
			
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
	
	/**
	 * @param brand_persistentdocument_brand $brand
	 * @param integer $websiteId
	 * @return brand_persistentdocument_compiledbrand
	 */
	private function generate($brand, $websiteId)
	{
		$lang = RequestContext::getInstance()->getLang();
		$compiledBrand = $this->createQuery()->add(Restrictions::eq('brand', $brand))
			->add(Restrictions::eq('websiteId', $websiteId))
			->add(Restrictions::eq('lang', $lang))->findUnique();
		if ($compiledBrand === null)
		{
			$compiledBrand = $this->getNewDocumentInstance();
			$compiledBrand->setLang($lang);
			$compiledBrand->setBrand($brand);
			$compiledBrand->setWebsiteId($websiteId);
		}
		$this->compile($compiledBrand);
		
		return $compiledBrand;
	}
	
	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 */	
	protected function compile($document)
	{
		$document->setLabel($document->getBrand()->getLabel());
		$this->refreshPublishedProductCount($document);
		if ($document->isNew() || $document->isModified())
		{
			$this->save($document);
		}
		else
		{
			$this->publishDocumentIfPossible($document, array('cause' => 'compilation'));
		}
	}
	
	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 */
	private function refreshPublishedProductCount($document)
	{
		$row = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('indexed', true))->add(Restrictions::published())->add(Restrictions::eq('lang', $document->getLang()))
			->add(Restrictions::eq('brandId', $document->getBrand()->getId()))->add(Restrictions::eq('websiteId', $document->getWebsiteId()))
			->setProjection(Projections::rowCount('count'))->findUnique();
		$document->setPublishedProductCount($row['count']);
	}
	
	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
	public function isPublishable($document)
	{
		if ($document->getPublishedProductCount() < 1)
		{
			$this->setActivePublicationStatusInfo($document, '&modules.brand.document.compiledbrand.publication.no-product-published-in-website;');
			return false;
		}
		if (!$document->getBrand()->isPublished())
		{
			$this->setActivePublicationStatusInfo($document, '&modules.brand.document.compiledbrand.publication.brand-not-published;');
			return false;
		}
		return parent::isPublishable($document);
	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->isPublished() || $oldPublicationStatus == 'PUBLICATED')
		{	
			foreach (brand_SpaceService::getInstance()->getByBrand($document->getBrand()) as $space)
			{
				$space->getDocumentService()->publishIfPossible($space->getId());
			}
		}
	}

	/**
	 * Returns the URL of the document if has no URL Rewriting rule.
	 *
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
	public function generateUrl($document, $lang, $parameters)
	{
		return LinkHelper::getDocumentUrl($document->getBrand(), $lang, $parameters);
	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @return integer | null
	 */
	public function getWebsiteId($document)
	{
		return $document->getWebsiteId();
	}
}