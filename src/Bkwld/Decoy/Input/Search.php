<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Bkwld\Decoy\Exceptions\Exception;
use Config;
use Input;
use Log;
use Bkwld\Library\Utils\String;

/**
 * This class contains logic related to searching from controller
 * index views.  It could have gone in the controller class, but
 * trying to reduce it's bulk
 */
class Search {

	/**
	 * Apply the effect of a search (which is communicated view Input::get('query'))
	 * 
	 * @param  Illuminate\Database\Query\Builder $query
	 * @param  array $config Search config from the controller class definition
	 * @return Illuminate\Database\Query\Builder
	 */
	public function apply($query, $config) {
		
		// Do nothing if no query in the input
		if (!Input::has('query')) return $query;
		
		// Deserialize the query and loop through
		$conditions = json_decode(Input::get('query'));
		$field_keys = array_keys($config);
		if (!is_array($conditions)) throw new Exception('Bad query');
		foreach($conditions as $condition) {
			
			// Get the field name by taking the index and looking up which key it corresponds to
			$field_index = $condition[0];
			$field_key = $field_keys[$field_index];
			$field = is_string($field_key) ? $field_key : $config[$field_key];
			
			// Extract vars for query
			$comparison = $condition[1];
			$input = $condition[2];

			// Use an app-defined query ...
			if (isset($config[$field]) 
				&& is_array(($config[$field])) 
				&& isset($config[$field]['query'])) {
				call_user_func($config[$field]['query'], $query, $comparison, $input);

			// ... or one of the simple, standard ones
			} else $this->condition($query, $field, $comparison, $input);
			
		}
		
		// Return the agumented query
		return $query;
		
	}

	/**
	 * Add a condition to a query
	 * 
	 * @param  Illuminate\Database\Query\Builder $query
	 * @param  string $field The field name from search config
	 * @param  string $comparison The operator string from the search UI
	 * @param  string $input The input for the field
	 * @return Illuminate\Database\Query\Builder
	 */
	private function condition($query, $field, $comparison, $input) {
		switch ($comparison) {
			
			// Not Like
			case '!%*%':
				$comparison = substr($comparison, 1);
				$input = str_replace('*', $input, $comparison);
				return $query->where($field, 'NOT LIKE', $input);
			
			// Like
			case '*%':
			case '%*':
			case '%*%':
				$input = str_replace('*', $input, $comparison);
				return $query->where($field, 'LIKE', $input);
			
			// Defaults
			default:
				return $query->where($field, $comparison, $input);
		}
	}
	
	/**
	 * Make the shorthand options of the search config explicit
	 * 
	 * @param  array $config Search config from the controller class definition
	 * @return array
	 */
	public function longhand($config) {
		$search = array();
		foreach($config as $key => $val) {
			
			// Make locale menu
			if ($val == 'locale') {
				$search['locale'] = [
					'type' => 'select',
					'label' => 'Locale',
					'options' => Config::get('decoy::site.locales'),
				];

			// Not associative assume it's a text field
			} else if (is_numeric($key)) {
				$search[$val] = array('type' => 'text', 'label' => String::titleFromKey($val));
			
			// If value isn't an array, make a default label
			} else if (!is_array($val)) {
				$search[$key] = array('type' => $val, 'label' => String::titleFromKey($key));
			
			// Add the meta array
			} else {

				// Make a default label
				if (empty($val['label'])) $val['label'] = String::titleFromKey($key);

				// Support class static method or variable as options for a select
				if (!empty($val['type']) 
					&& $val['type'] == 'select' 
					&& !empty($val['options']) 
					&& is_string($val['options'])) {
					$val['options'] = $this->longhandOptions($val['options']);
				}

				// Apply the meta data
				$search[$key] = $val;
			}
			
		}
		return $search;
	}

	/**
	 * Parse select options, returning a transformed array with static arrays
	 * or callbacks executed
	 * 
	 * @param  array $options 
	 * @return array
	 */
	private function longhandOptions($options) {

		// Call static method.  You don't pass the paranethesis
		// to static calls
		if (preg_match('#::.+\(\)#', $options)) {
			return call_user_func(substr($options, 0, strlen($options) - 2));

		// Return static variable
		} else if (preg_match('#::\$#', $options)) {
			list($class, $var) = explode('::$', $options);
			return $class::$$var;
		
		// Unknown format
		} else throw new Exception('Could not parse option: '.$options);

	}

	
}