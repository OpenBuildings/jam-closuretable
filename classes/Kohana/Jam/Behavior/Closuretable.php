<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 *  Nested behavior for Jam ORM library
 *  Implements Closure Table pattern for storing hierarchies in a separate table
 * 
 * @package    Openbuildings\Jam
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Closuretable extends Jam_Behavior {

	protected $_branches_table = NULL;
	protected $_ansestor_key = 'ansestor_id';
	protected $_descendant_key = 'descendant_id';
	protected $_depth_key = 'depth';
	protected $_children_dependent = NULL;

	public function initialize(Jam_Meta $meta, $name) 
	{
		parent::initialize($meta, $name);

		if ( ! $this->_branches_table)
		{
			$this->_branches_table = ($meta->table() ?: Inflector::plural($meta->model())).'_branches';
		}

		$meta->associations(array(
			'parent' => Jam::association('closuretable_parent', array(
				'foreign_model' => $this->_model,
				'branches_table' => $this->_branches_table,
				'ansestor_key' => $this->_ansestor_key,
				'descendant_key' => $this->_descendant_key,
				'depth_key' => $this->_depth_key,
				'inverse_of' => 'children',
			)),
			'children' => Jam::association('closuretable_children', array(
				'foreign_model' => $this->_model,
				'branches_table' => $this->_branches_table,
				'ansestor_key' => $this->_ansestor_key,
				'descendant_key' => $this->_descendant_key,
				'depth_key' => $this->_depth_key,
				'inverse_of' => 'parent',
				'children_dependent' => $this->_children_dependent,
			)),
		));

		$meta->events()->bind('model.after_save', array($this, 'model_before_after_save'), Jam_Event::ATTRIBUTE_PRIORITY + 1);
	}

	public function builder_call_children_of(Jam_Query_Builder_Collection $collection, Jam_Event_Data $data, $parent)
	{
		$collection
			->join_table($this->_branches_table)
				->on($this->_descendant_key, '=', ':primary_key')
				->on($this->_depth_key, '=', DB::expr(':depth', array(':depth' => 1)))
			->end()
			->where($this->_branches_table.'.'.$this->_ansestor_key, '=', $parent);
	}


	public function builder_call_descendants_of(Jam_Query_Builder_Collection $collection, Jam_Event_Data $data, $parent)
	{
		$collection
			->join_table($this->_branches_table)
				->on($this->_descendant_key, '=', ':primary_key')
			->end()
			->where($this->_branches_table.'.'.$this->_ansestor_key, '=', $parent);
	}

	public function model_call_depth(Jam_Model $model, Jam_event_data $data)
	{
		$data->return = DB::select($this->_depth_key)
			->from($this->_branches_table)
			->where($this->_descendant_key, '=', $model->id())
			->order_by($this->_depth_key, 'DESC')
			->execute($model->meta()->db())
			->get($this->_depth_key, 0);
	}

	public function model_call_ansestors(Jam_Model $model, Jam_Event_Data $data)
	{
		$data->return = Jam::all($this->_model)
			->join_table($this->_branches_table)
				->on($this->_ansestor_key, '=', ':primary_key')
				->on($this->_depth_key, '>', DB::expr(':depth', array(':depth' => 0)))
			->end()
			->where($this->_branches_table.'.'.$this->_descendant_key, '=', $model->id());
	}

	public function model_call_descendants(Jam_Model $model, Jam_Event_Data $data)
	{
		$data->return = Jam::all($this->_model)
			->join_table($this->_branches_table)
				->on($this->_descendant_key, '=', ':primary_key')
				->on($this->_depth_key, '>', DB::expr(':depth', array(':depth' => 0)))
			->end()
			->where($this->_branches_table.'.'.$this->_ansestor_key, '=', $model->id());
	}

	public function model_call_is_descendant_of(Jam_Model $model, Jam_Event_Data $data, $ansestor)
	{
		$ansestor_id = $ansestor instanceof Jam_Model ? $ansestor->id() : $ansestor;

		$data->return = $model->ansestors()->where(':primary_key', '=', $ansestor_id)->count_all() > 0;
	}

	public function model_call_is_ansestor_of(Jam_Model $model, Jam_Event_Data $data, $descendant)
	{
		$descendant_id = $descendant instanceof Jam_Model ? $descendant->id() : $descendant;

		$data->return = $model->descendants()->where_key($descendant_id)->count_all() > 0;
	}

	public function model_before_after_save(Jam_Model $model, Jam_Event_Data $data, $changed, $event_type)
	{
		if ($event_type == 'create')
		{
			DB::insert($this->_branches_table)
				->columns(array($this->_ansestor_key, $this->_descendant_key, $this->_depth_key))
				->values(array($model->id(), $model->id(), 0))
				->execute($model->meta()->db());			
		}
	}

	public function model_after_delete(Jam_Model $model)
	{
		DB::delete($this->_branches_table)
			->where($this->_ansestor_key, '=', $model->id())
			->where($this->_descendant_key, '=', $model->id())
			->execute($model->meta()->db());
	}
}
