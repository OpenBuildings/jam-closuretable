<?php defined('SYSPATH') OR die('No direct access allowed.'); 

class Model_Test_Closurelist extends Jam_Model {

	static public function initialize(Jam_Meta $meta)
	{
		$meta->db(Kohana::TESTING);

		$meta
			->behaviors(array(
				'closuretable' => Jam::behavior('closuretable', array('children_dependent' => Jam_Association::DELETE)),
			))

			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
			));

	}
}