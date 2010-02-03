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
}