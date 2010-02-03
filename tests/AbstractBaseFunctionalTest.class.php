<?php
/**
 * @package modules.brand.tests
 */
abstract class brand_tests_AbstractBaseFunctionalTest extends brand_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('functional-test.sql', true, false);
	}
}