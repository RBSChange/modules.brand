<?php
/**
 * @package modules.brand.tests
 */
abstract class brand_tests_AbstractBaseIntegrationTest extends brand_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('integration-test.sql', true, false);
	}
}