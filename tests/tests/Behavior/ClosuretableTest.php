<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jamclosuretable_Behavior_ClosuretableTest 
 *
 * @group behavior
 * @group behavior.closuretable
 * 
 * @package Jam closuretable
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Behavior_ClosuretableTest extends Testcase_Closuretable {


	public function data_ansestors()
	{
		return array(
			array(3, array(2, 1)),
			array(6, array(4, 1)),
			array(1, array()),
		);
	}
	
	/**
	 * @dataProvider data_ansestors
	 */
	public function test_ansestors($item_id, $expected_ansestors_ids)
	{
		$this->assertEquals($expected_ansestors_ids, Jam::find('test_closurelist', $item_id)->ansestors()->ids());	
	}

	public function data_descendants()
	{
		return array(
			array(2, array(3)),
			array(4, array(5, 6)),
			array(1, array(2, 3, 4, 5, 6)),
		);
	}
	
	/**
	 * @dataProvider data_descendants
	 */
	public function test_descendants($item_id, $expected_descendants_ids)
	{
		$this->assertEquals($expected_descendants_ids, Jam::find('test_closurelist', $item_id)->descendants()->ids());	
	}

	public function data_is_ansestor_of()
	{
		return array(
			array(2, 1, FALSE),
			array(2, 2, FALSE),
			array(2, 3, TRUE),
			array(1, 6, TRUE),
			array(4, 3, FALSE),
		);
	}
	
	/**
	 * @dataProvider data_is_ansestor_of
	 */
	public function test_is_ansestor_of($ansestor, $descendant, $expected_result)
	{
		$this->assertEquals($expected_result, Jam::find('test_closurelist', $ansestor)->is_ansestor_of($descendant));
	}
	
	public function data_is_descendant_of()
	{
		return array(
			array(2, 1, TRUE),
			array(3, 1, TRUE),
			array(2, 2, FALSE),
			array(4, 3, FALSE),
			array(5, 2, FALSE),
		);
	}
	
	/**
	 * @dataProvider data_is_descendant_of
	 */
	public function test_is_descendant_of($descendant, $ansestor, $expected_result)
	{
		$this->assertEquals($expected_result, Jam::find('test_closurelist', $descendant)->is_descendant_of($ansestor));
	}

	public function test_depth()
	{
		$this->assertEquals(0, Jam::find('test_closurelist', 1)->depth());
		$this->assertEquals(1, Jam::find('test_closurelist', 2)->depth());
		$this->assertEquals(2, Jam::find('test_closurelist', 6)->depth());
	}

	public function test_deep()
	{
		$eight = Jam::create('test_closurelist', array('name' => 'eight'));

		$two = Jam::find('test_closurelist', 2);

		$two->children->add($eight);
		$two->save();

		$one = Jam::find('test_closurelist', 1);
		$two = Jam::find('test_closurelist', 2);
		$four = Jam::find('test_closurelist', 4);

		$this->assertTrue($eight->is_descendant_of($two));
		$this->assertTrue($eight->is_descendant_of($one));
		$this->assertFalse($eight->is_descendant_of($four));

		$seven = Jam::find('test_closurelist', 7);

		$seven->parent = $eight;
		$seven->save();

		$this->assertTrue($seven->is_descendant_of($eight));
		$this->assertTrue($seven->is_descendant_of($two));
		$this->assertTrue($seven->is_descendant_of($one));
		$this->assertFalse($seven->is_descendant_of($four));

		$this->assertTrue($eight->children->has($seven));

		$two->delete();

		$this->assertEquals(array(4,5,6), $one->descendants()->ids());
	}


	public function test_mass_children()
	{
		$n1 = Jam::create('test_closurelist', array('name' => 'n1', 'children' => array(
			array('name' => 'n2', 'children' => array(
				array('name' => 'n3')
			))
		)));

		$this->assertEquals('n1', $n1->name());
		$this->assertCount(1, $n1->children);
		$this->assertEquals('n2', $n1->children[0]->name());
		$this->assertCount(1, $n1->children[0]->children);
		$this->assertEquals('n3', $n1->children[0]->children[0]->name());

		$n1 = Jam::find('test_closurelist', 'n1');

		$this->assertEquals('n1', $n1->name());
		$this->assertCount(1, $n1->children);
		$this->assertEquals('n2', $n1->children[0]->name());
		$this->assertCount(1, $n1->children[0]->children);
		$this->assertEquals('n3', $n1->children[0]->children[0]->name());
	}
}
