<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jam_Association_Closuretable_Parent definition
 *
 * @package    Openbuildings\Jam
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
*/
abstract class Kohana_Jam_Association_Closuretable_Parent extends Jam_Association {

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

	public $inverse_of = NULL;

	/**
	 * Load associated model (from database or after deserialization)
	 * @param  Jam_Validated $model 
	 * @param  mixed         $value 
	 * @return Jam_Model
	 */
	public function load_fields(Jam_Validated $model, $value)
	{
		if (is_array($value))
		{
			$value = Jam::build($this->foreign_model)->load_fields($value);
		}

		if ($value instanceof Jam_Model AND $this->inverse_of)
		{
			$value->retrieved($this->inverse_of, $model);
		}
		
		return $value;
	}

	/**
	 * Return a Jam_Query_Builder_Join object to allow a query to join with this association
	 * 
	 * @param  string $alias table name alias
	 * @param  string $type  join type (LEFT, NATURAL)
	 * @return Jam_Query_Builder_Join        
	 */
	public function join($alias, $type = NULL)
	{
		return Jam_Query_Builder_Join::factory($this->branches_table, $type)
			->context_model($this->model)
			->model($this->foreign_model)
			->on($this->branches_table.'.'.$this->descendant_key, '=', ':primary_key')
			->join_table($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
				->on(':primary_key', '=' , $this->branches_table.'.'.$this->ansestor_key)
				->on(DB::expr(':depth', array(':depth' => 1)), '>=', $this->branches_table.'.'.$this->depth_key)
				->context_model($this->model)
			->end();
	}

	/**
	 * Get the belonging model for this association using the foreign key, 
	 * if the data was changed, use the key from the changed data.
	 * Assign inverse_of 
	 * 
	 * @param  Jam_Validated $model      
	 * @param  mixed         $value      changed data
	 * @param  boolean       $is_changed 
	 * @return Jam_Model
	 */
	public function get(Jam_Validated $model, $value, $is_changed)
	{
		if ($is_changed)
		{
			if ($value instanceof Jam_Validated OR ! $value)
				return $value;

			$key = Jam_Association::primary_key($this->foreign_model, $value);
		
			if ($key)
			{
				$item = Jam::find($this->foreign_model, $key);
			}
			elseif (is_array($value))
			{
				$item = Jam::build($this->foreign_model);
			}
			else
			{
				$item = NULL;
			}
		
			if ($item AND is_array($value))
			{
				$item->set($value);
			}
		}
		else
		{
			$item = Jam::all($this->foreign_model)
				->join_table($this->branches_table)
					->on($this->ansestor_key, '=', ':primary_key')
					->on($this->depth_key, '=', DB::expr(':depth', array(':depth' => 1)))
				->end()
				->where($this->branches_table.'.'.$this->descendant_key, '=', $model->id())
				->first();
		}

		return $this->set($model, $item, $is_changed);
	}

	public function build(Jam_Validated $model)
	{
		$item = Jam::build($this->foreign_model);

		$this->set($model, $item, TRUE);

		return $item;
	}

	/**
	 * Perform validation on the belonging model, if it was changed. 
	 * @param  Jam_Model      $model   
	 * @param  Jam_Event_Data $data    
	 * @param  array         $changed 
	 */
	public function model_after_check(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		if ($value = Arr::get($changed, $this->name) AND Jam_Association::is_changed($value))
		{
			if ( ! $model->{$this->name}->is_validating() AND ! $model->{$this->name}->check())
			{
				$model->errors()->add($this->name, 'association', array(':errors' => $model->{$this->name}->errors()));
			}
		}
	}

	/**
	 * Save the related model after the main model, if it was changed
	 * Only save related model if it has been changed, and is not in a process of saving itself
	 * 
	 * @param  Jam_Model      $model   
	 * @param  Jam_Event_Data $data    
	 * @param  boolean        $changed 
	 */
	public function model_after_save(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		if ($value = Arr::get($changed, $this->name))
		{
			if (Jam_Association::is_changed($value) AND $item = $model->{$this->name})
			{
				if ( ! $item->is_saving())
				{
					$this->set($model, $item, TRUE)->save();
				}
				$key = $item->id();
			}
			else
			{
				$key = Jam_Association::primary_key($this->foreign_model, $value);
			}

			$this->erase_query($model)->execute($model->meta()->db());
			$this->set_query($model, $key)->execute($model->meta()->db());
		}
	}

	public function model_before_delete(Jam_Model $model)
	{
		$this->erase_query($model)->execute($model->meta()->db());
	}

	public function erase_query(Jam_Model $model)
	{
		return DB::delete($this->branches_table)
			->where($this->descendant_key, '=', $model->id())
			->where($this->depth_key, '>', 0);
	}

	public function set_query(Jam_Model $model, $ansestor_id)
	{
		$query = DB::insert($this->branches_table)
			->columns(array($this->ansestor_key, $this->descendant_key, $this->depth_key));

		foreach ($this->ansestors_query($ansestor_id)->execute($model->meta()->db()) as $ansestor) 
		{
			$query->values(array($ansestor[$this->ansestor_key], $model->id(), $ansestor[$this->depth_key] + 1));
		}

		return $query;
	}

	public function ansestors_query($ansestor_id)
	{
		return DB::select($this->branches_table.'.*')
			->from($this->branches_table)
			->where($this->descendant_key, '=', $ansestor_id);
	}
}
