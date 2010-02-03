<?php
/**
 * brand_BlockDashboardGeneralStatisticsAction
 * @package modules.brand.lib.blocks
 */
class brand_BlockDashboardGeneralStatisticsAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @param f_mvc_Request $request
	 * @param boolean $forEdition
	 */
	protected function setRequestContent($request, $forEdition)
	{
		if ($forEdition)
		{
			return;
		}
		
		$bs = brand_BrandService::getInstance();				
		$widget = array(
			array(
				f_Locale::translate('&modules.brand.bo.blocks.dashboardgeneralstatistics.Brands-count;'), 
				$this->getCount($bs, true),
				$this->getCount($bs, false), 
				$this->getTodayCount($bs, true), 
				$this->getTodayCount($bs, false)
			)
		);
		$request->setAttribute('widget', $widget);
	}
	
	/**
	 * @param DocumentService $service
	 * @param Boolean $published
	 * @return Integer
	 */
	private function getCount($service, $used)
	{
		$query = $service->createQuery();
		if ($used)
		{
			$query->createCriteria('product');
		}
		$result = $query->setProjection(Projections::rowCount('count'))->findUnique();
		return $result['count'];
	}

	/**
	 * @param DocumentService $service
	 * @param Boolean $published
	 * @return Integer
	 */
	private function getTodayCount($service, $created)
	{
		$query = $service->createQuery();
		$query->add(Restrictions::like($created ? 'creationdate' : 'modificationdate', date('Y-m-d'), MatchMode::START()));
		$result = $query->setProjection(Projections::rowCount('count'))->findUnique();
		return $result['count'];
	}
}