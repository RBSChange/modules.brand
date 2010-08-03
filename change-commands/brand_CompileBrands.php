<?php
/**
 * commands_brand_CompileBrands
 * @package modules.brand
 */
class commands_brand_CompileBrands extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Compile the brands.";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile brands ==");
		$this->loadFramework();
		$batchPath = 'modules/brand/lib/bin/batchCompile.php';
			
		$ids = brand_BrandService::getInstance()->getAllBrandIdsToCompile();
		$count = count($ids);			
		$this->message('There are '.$count.' brands to be compiled.');
		$index = 0;	
		foreach (array_chunk($ids, 10) as $chunk)
		{
			f_util_System::execHTTPScript($batchPath, $chunk);
			$index = $index + count($chunk);
			echo "Compiling brands: $index \n";
		}
		
		$this->quitOk("All brands are compiled successfully.");
	}
}