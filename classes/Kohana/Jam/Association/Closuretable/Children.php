<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jam_Association_Closuretable_Children definition
 *
 * @package    Openbuildings\Jam
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
abstract class Kohana_Jam_Association_Closuretable_Children extends Jam_Association_Collection {

	/**
	 * Set this to false to disable deleting the association table entry when the model is deleted
	 * @var boolean
	 */
	public $children_dependent = FALSE;

	/**
	 * The name of the field on the join table, corresponding to the model
	 * @var string
	 */
	public $ansestor_key = 'ansestor_id';

	/**
	 * The name of the field on the join table, corresponding to the key for the foreign model
	 * @var string
	 */
	public $descendant_key = 'descendant_id';


	public $depth_key = 'depth';

	/**
	 * Then n ame of the join table
	 * @var string
	 */
	public $branches_table = NULL;

	/**
	 * Return a Jam_Query_Builder_Join object to allow a query to join with this association
	 * @param  string $alias table name alias
	 * @param  string $type  join type (LEFT, NATURAL)
	 * @return Jam_Query_Builder_Join        
	 */
	public function join($alias, $type = NULL)
	{
		return Jam_Query_Builder_Join::factory($this->branches_table, $type)
			->context_model($this->model)
			->model($this->foreign_model)
			->on($this->branches_table.'.'.$this->ansestor_key, '=', ':primary_key')
			->join_table($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
				->on(':primary_key', '=' , $this->branches_table.'.'.$this->descendant_key)
				->on(DB::expr(':depth', array(':depth' => 1)), '>=', $this->branches_table.'.'.$this->depth_key)
				->context_model($this->model)
			->end();
	}

	public function collection(Jam_Model $model)
	{
		$collection = Jam::all($this->foreign_model);

		return $collection	
			->join_table($this->branches_table)
				->context_model($this->foreign_model)
				->on($this->descendant_key, '=', ':primary_key')
				->on($this->depth_key, '=', DB::expr(':depth', array(':depth' => 1)))
			->end()
			->where($this->branches_table.'.'.$this->ansestor_key, '=' , $model->id());
	}

	/**
	 * Generate a query to delete associated models in the database
	 * @param  Jam_Model $model 
	 * @return Database_Query           
	 */
	public function erase_branches_query(Jam_Model $model)
	{
		return DB::delete($this->branches_table)
			->where($this->depth_key, '>', 0)
			->where($this->descendant_key, '=', $model->id());
	}

	public function erase_query(Jam_Model $model)
	{
		$descendant_ids = $this
			->descendants_query($model->id())
			->where($this->depth_key, '>', 0)
			->execute($model->meta()->db())
			->as_array(NULL, $this->descendant_key);

		return Jam::delete($this->foreign_model)
			->where_key($descendant_ids);
	}

	/**
	 * Generate a query to remove models from the association (without deleting them), for specific ids
	 * @param  Jam_Model $model
	 * @param  array     $ids  
	 * @return Database_Query
	 */
	public function remove_items_query(Jam_Model $model, array $ids)
	{
		$ansestor_ids = $this
			->ansestors_query($model->id())
			->execute($model->meta()->db())
			->as_array(NULL, $this->ansestor_key);

		return DB::delete($this->branches_table)
			->where($this->ansestor_key, 'IN', $ansestor_ids)
			->where($this->descendant_key, 'IN', $ids);
	}

	/**
	 * Before the model is deleted, and the depenedent option is set, remove the dependent models
	 * @param  Jam_Model $model 
	 */
	public function model_before_delete(Jam_Model $model)
	{
		if ($model->loaded())
		{
			switch ($this->children_dependent) 
			{
				case Jam_Association::DELETE:
					foreach ($model->{$this->name} as $item) 
					{
						$item->delete();
					}
				break;

				case Jam_Association::ERASE:
					$this->erase_query($model)->execute($model->meta()->db());
				break;
			}

			$this->erase_branches_query($model)->execute($model->meta()->db());
		}
	}

	public function descendants_query($id)
	{
		return DB::select($this->branches_table.'.*')
			->from($this->branches_table)
			->where($this->ansestor_key, '=', $id);
	}

	public function ansestors_query($id)
	{
		return DB::select($this->branches_table.'.*')
			->from($this->branches_table)
			->where($this->descendant_key, '=', $id);
	}

	/**
	 * Generate a query to add models from the association (without deleting them), for specific ids
	 * @param  Jam_Model $model
	 * @param  array     $ids  
	 * @return Database_Query
	 */
	public function add_items_query(Jam_Model $model, array $ids)
	{
		$query = DB::insert($this->branches_table)
			->columns(array($this->ansestor_key, $this->descendant_key, $this->depth_key));

		foreach ($ids as $id) 
		{
			foreach ($this->ansestors_query($model->id())->execute($model->meta()->db()) as $ansestor) 
			{
				$query->values(array($ansestor[$this->ansestor_key], $id, $ansestor[$this->depth_key] + 1));
				
				foreach ($this->descendants_query($id)->where($this->depth_key, '>', 0)->execute($model->meta()->db()) as $descendant) 
				{
					$query->values(array($ansestor[$this->ansestor_key], $descendant[$this->descendant_key], $ansestor[$this->depth_key] + $descendant[$this->depth_key] + 1));
				}
			}
		}

		return $query;
	}
}