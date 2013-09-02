<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jamclosuretable_Associaiton_ParentTest 
 *
 * @group associaiton
 * @group associaiton.parent
 * 
 * @package Jam closuretable
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Jamclosuretable_Associaiton_ParentTest extends Testcase_Closuretable {

	public function setUp()
	{
		parent::setUp();

		$this->association = Jam::meta('test_closurelist')->association('parent');

		$this->model = Jam::build('test_closurelist')->load_fields(array('id' => 4, 'name' => 'one'));
	}

	public function test_load_fields()
	{
		$value = $this->association->load_fields($this->model, array('id' => 2, 'name' => 'Test'));

		$this->assertTrue($value->loaded());
		$this->assertEquals(2, $value->id());
		$this->assertEquals('Test', $value->name());
	}

	public function test_join()
	{
		$expected = "JOIN `test_closurelists_branches` ON (`test_closurelists_branches`.`descendant_id` = `test_closurelists`.`id`) JOIN `test_closurelists` AS `parent` ON (`parent`.`id` = `test_closurelists_branches`.`ansestor_id` AND 1 >= `test_closurelists_branches`.`depth`)";

		$this->assertEquals($expected, (string) $this->association->join('parent'));
	}

	public function test_get()
	{
		$one = Jam::find('test_closurelist', 1);
		$four = Jam::find('test_closurelist', 4);
		$two = Jam::find('test_closurelist', 2);

		// Check for Null Value changed
		$this->assertNull($this->association->get($one, NULL, TRUE));

		$this->assertEquals($one->id(), $this->association->get($four, NULL, FALSE)->id());

		$this->assertEquals($two->id(), $this->association->get($four, 2, TRUE)->id());

		$returned = $this->association->get($four, array('id' => 2, 'title' => 'new post title'), TRUE);
		$this->assertEquals($two->id(), $returned->id());
		$this->assertEquals('new post title', $returned->title);
	}

	public function test_check()
	{
		$child = Jam::build('test_closurelist')->load_fields(array('id' => 1));
		$parent = $this->getMock('Model_Test_Closurelist', array('check'), array('test_closurelist'));
		$parent->load_fields(array('id' => 2));
		$parent
			->expects($this->once())
			->method('check')
			->will($this->returnValue(TRUE));

		$parent->name = 'new name';

		$child->parent = $parent;

		$this->association->model_after_check($child, new Jam_Event_Data(array()), array('parent' => $parent));
	}

	public function test_build()
	{
		$value = $this->association->build($this->model);
		$this->assertInstanceOf('Model_Test_Closurelist', $value);
	}

	public function test_erase_query()
	{
		$expected = "DELETE FROM `test_closurelists_branches` WHERE `descendant_id` = 4 AND `depth` > 0";
		$this->assertEquals($expected, (string) $this->association->erase_query($this->model));
	}

	public function test_set_query()
	{
		$expected = "INSERT INTO `test_closurelists_branches` (`ansestor_id`, `descendant_id`, `depth`) VALUES ('2', 4, 1), ('1', 4, 2)";
		$this->assertEquals($expected, $this->association->set_query($this->model, 2)->compile(Kohana::TESTING));
	}

	public function test_ansestors_query()
	{
		$expected = "SELECT `test_closurelists_branches`.* FROM `test_closurelists_branches` WHERE `descendant_id` = 2";
		$this->assertEquals($expected, $this->association->ansestors_query(2)->compile(Kohana::TESTING));
	}
}