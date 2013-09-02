<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @group associaiton
 * @group associaiton.children
 * 
 * @package Jam closuretable
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Associaiton_ChildrenTest extends Testcase_Closuretable {

	public $model;
	public $children;

	public function setUp()
	{
		parent::setUp();

		$this->children = Jam::meta('test_closurelist')->association('children');

		$this->model = Jam::build('test_closurelist')->load_fields(array('id' => 4, 'name' => 'one'));
	}

	public function test_collection()
	{
		$expected = "SELECT `test_closurelists`.* FROM `test_closurelists` JOIN `test_closurelists_branches` ON (`test_closurelists_branches`.`descendant_id` = `test_closurelists`.`id` AND `test_closurelists_branches`.`depth` = 1) WHERE `test_closurelists_branches`.`ansestor_id` = 4";

		$this->assertEquals($expected, (string) $this->children->collection($this->model));
	}

	public function test_erase_branches_query()
	{
		$expected = "DELETE FROM `test_closurelists_branches` WHERE `depth` > 0 AND `descendant_id` = 4";

		$this->assertEquals($expected, (string) $this->children->erase_branches_query($this->model));
	}

	public function test_erase_query()
	{
		$expected = "DELETE FROM `test_closurelists` WHERE `test_closurelists`.`id` IN ('5', '6')";

		$this->assertEquals($expected, (string) $this->children->erase_query($this->model));
	}

	public function test_remove_items_query()
	{
		$expected = "DELETE FROM `test_closurelists_branches` WHERE `ansestor_id` IN ('4', '1') AND `descendant_id` IN (5, 6)";

		$this->assertEquals($expected, $this->children->remove_items_query($this->model, array(5, 6))->compile(Kohana::TESTING));
	}

	public function test_add_items_query_one()
	{
		$expected = "INSERT INTO `test_closurelists_branches` (`ansestor_id`, `descendant_id`, `depth`) VALUES ('4', 7, 1), ('1', 7, 2)";

		$this->assertEquals($expected, $this->children->add_items_query($this->model, array(7))->compile(Kohana::TESTING));
	}

	public function test_add_items_query_deep()
	{
		$expected = "INSERT INTO `test_closurelists_branches` (`ansestor_id`, `descendant_id`, `depth`) VALUES ('4', 10, 1), ('4', '11', 2), ('1', 10, 2), ('1', '11', 3)";

		$this->assertEquals($expected, $this->children->add_items_query($this->model, array(10))->compile(Kohana::TESTING));
	}

	public function test_join()
	{
		$expected = "JOIN `test_closurelists_branches` ON (`test_closurelists_branches`.`ansestor_id` = `test_closurelists`.`id`) JOIN `test_closurelists` AS `children` ON (`children`.`id` = `test_closurelists_branches`.`descendant_id` AND 1 >= `test_closurelists_branches`.`depth`)";

		$this->assertEquals($expected, (string) $this->children->join('children'));
	}
}