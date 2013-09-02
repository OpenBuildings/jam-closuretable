<?php

/**
 * @package    Openbuildings\Jam
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
abstract class Testcase_Closuretable extends PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
		Database::instance(Kohana::TESTING)->begin();
	}

	public function tearDown()
	{
		Database::instance(Kohana::TESTING)->rollback();	
		parent::tearDown();
	}
}