<?php
/**
 * @package modules.brand.tests
 */
abstract class brand_tests_AbstractBaseUnitTest extends brand_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->resetDatabase();
	}
}