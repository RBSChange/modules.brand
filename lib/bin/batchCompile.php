<?php
if (!defined("WEBEDIT_HOME"))
{
	define("WEBEDIT_HOME", realpath('.'));
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	$brandIdArray = array_slice($_SERVER['argv'], 1);
}
else
{
	$brandIdArray = $_POST['argv'];
}
Controller::newInstance("controller_ChangeController");
$tm = f_persistentdocument_TransactionManager::getInstance();
try
{
	$tm->beginTransaction();
	foreach ($brandIdArray as $brandId)
	{
		Framework::info(date_Calendar::getInstance()->toString() . " Compile $brandId ...");
		$brand = DocumentHelper::getDocumentInstance($brandId, 'modules_brand/brand');
		brand_CompiledbrandService::getInstance()->generateForBrand($brand);
	}
	$tm->commit();
}
catch (Exception $e)
{
	$tm->rollBack($e);
}