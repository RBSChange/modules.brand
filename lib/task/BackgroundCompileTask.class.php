<?php
class brand_BackgroundCompileTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$ids = brand_BrandService::getInstance()->getBrandIdsToCompile();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' brand to compile: ' . count($ids));		
		}
		$batchPath = 'modules/brand/lib/bin/batchCompile.php';
		foreach (array_chunk($ids, 10) as $chunk)
		{
			f_util_System::execHTTPScript($batchPath, $chunk);
		}	
	}
}