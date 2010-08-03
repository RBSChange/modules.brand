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
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId = null)
//	{
//
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId = null)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//	}

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
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param brand_persistentdocument_compiledbrand $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//	}

	/**
	 * this method is call before saving the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param brand_persistentdocument_compiledbrand $newDocument
	 * @param brand_persistentdocument_compiledbrand $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * this method is call after saving the duplicate document.
	 * $newDocument has an id affected.
	 * Traitment of the children of $originalDocument.
	 *
	 * @param brand_persistentdocument_compiledbrand $newDocument
	 * @param brand_persistentdocument_compiledbrand $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//	}

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

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//	}

	/**
	 * @param brand_persistentdocument_compiledbrand $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
//	public function getResume($document, $forModuleName, $allowedSections = null)
//	{
//		$resume = parent::getResume($document, $forModuleName, $allowedSections);
//		return $resume;
//	}
}