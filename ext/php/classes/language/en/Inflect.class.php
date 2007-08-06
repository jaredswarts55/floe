<?php
/**
 * @package language
 * @subpackage en
 */
require_once 'language/Inflections.class.php';

/**
 * Performs linguistic transformations based on standard English
 * grammar rules.
 * 
 * Note that this class implements the language.Inflect interface
 * 
 * @package language
 * @subpackage en
 */
class Inflect implements Inflections {

	/**
	 * @param $word string
	 */
	static function toIdentifier($word) {
		return str_replace(" ","", ucwords(str_replace("_"," ",$word)));
	}
	
	/**
	 * @param $word string
	 */
	static function toWords($word) {
	
	}

	/**
	 * @param $word string
	 */
	static function toSentence($word) {
	
	}
	
	/**
	 * map of regular plural rules
	 */
	static private $RegularPluralRules = array(
		'/(x|ch|ss|sh)$/' => '\1es',
		'/([^aeiouy]|qu)y$/' => '\1ies',
		'/([o])$/' => '\1es',
		'/(?:([^f])fe|([lr])f)$/' => '\1\2ves',
		'/man$/' => '\1men',
		'/$/' => 's'
	);
			
	/**
	 * map of irregular plural rules
	 */
	static private $IrregularPluralRules = array(
		'person' => 'people',
		'child' => 'children',
		'octopus' => 'octopi',
		'movie' => 'movies',
		'news' => 'news',
		'status' => 'status',
		'series' => 'series'
	);
		
	/**
	 * map of singular rules
	 */
	static private $SingularRules = array(
		'/(x|ch|ss)es$/' => '\1',
		'/([^aeiouy]|qu)ies$/' => '\1y',
		'/([lr])ves$/' => '\1f',
		'/([^f])ves$/' => '\1fe',
		'/(analy|ba|diagno|parenthe|progno|synop|the)ses$/' => '\1sis',
		'/([ti])a$/' => '\1um',
		'/men$/' => '\1man',
		'/s$/' => ''
	);
	
	/**
	 * Converts a singular word to plural form.
	 * 
	 * @param $word string
	 */
	static function toPlural($word) {
		if (array_key_exists($word, Inflect::$IrregularPluralRules)) {
			return Inflect::$IrregularPluralRules[$word];
		}
		return ($result = Inflect::applyRules($word, Inflect::$RegularPluralRules)) ? $result : $word;
	}
	
	/**
	 * Converts a plural word to singular form.
	 * 
	 * @param $word string
	 */
	static function toSingular($word) {
		if ($key = array_search($word, Inflect::$IrregularPluralRules)) {
			return $key;
		}
		return ($result = Inflect::applyRules($word, Inflect::$SingularRules)) ? $result : $word;
	}
	
	/**
	 * @param $number numeric
	 */
	static function toOrdinal($number) {
	
	}
	
	/**
	 * Applies a map of grammar rules to match a given word.
	 * 
	 * Returns the transformed word, or false if the word was not matched.
	 * 
	 * @param $word string
	 * @param $rules array of grammar rules
	 */
	static private function applyRules($word, $rules) {
		foreach($rules as $rule => $replace) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replace, $word);
			}
		}
		return false;
	}
	

	// ---- old Inflector methods ----

		function decodeUriPart($cPart) {
			return str_replace(" "," ", ucwords(str_replace("-"," ",$cPart)));
		}
	
		function encodeUriPart($cPart) {
			return str_replace(" ", "-", strtolower(preg_replace("/([a-z]+)([A-Z])/","$1-$2", $cPart)));
		}
		
		function toIdentifier($id) {
			return ucfirst(Inflector::camelize(Inflector::singularize($id)));
		}
		
		function propertyToColumn($property_name) {
			 return str_replace("", "_",
strtolower(preg_replace("/([A-Z])/","_$1", $property_name)));
		}

		function columnToProperty($column_name) {
			$word = Inflector::camelize($column_name);
			return strtolower($word[0]).substr($word,1);
		}
		
		function pluralize ($word) {
			$plural_rules = array(
				'/(x|ch|ss|sh)$/'			=> '\1es',       # search, switch, fix, box, process, address
				'/series$/'					=> '\1series',
				'/([^aeiouy]|qu)ies$/'		=> '\1y',
				'/([^aeiouy]|qu)y$/'		=> '\1ies',      # query, ability, agency
				'/(?:([^f])fe|([lr])f)$/'   => '\1\2ves', 	 # half, safe, wife
				'/sis$/'					=> 'ses',        # basis, diagnosis
				'/([ti])um$/'				=> '\1a',        # datum, medium
				'/person$/'					=> 'people',     # person, salesperson
				'/man$/'					=> 'men',        # man, woman, spokesman
				'/child$/'					=> 'children',   # child
				'/s$/'						=> 's',          # no change (compatibility)
				'/$/'						=> 's'
			);
			foreach ($plural_rules as $rule => $replacement) {
				if (preg_match($rule, $word)) {
					return preg_replace($rule, $replacement, $word);
				}
			}
			return false;
		}
	
		function singularize ($word) {
			$word = Inflector::pluralize($word);
			$singular_rules = array(
				'/(x|ch|ss)es$/'		   => '\1',
				'/movies$/'				   => 'movie',
				'/series$/'				   => 'series',
				'/([^aeiouy]|qu)ies$/'     => '\1y',
				'/([lr])ves$/'			   => '\1f',
				'/([^f])ves$/'			   => '\1fe',
				'/(analy|ba|diagno|parenthe|progno|synop|the)ses$/' => '\1sis',
				'/([ti])a$/'				=> '\1um',
				'/people$/'					=> 'person',
				'/men$/'					=> 'man',
				'/status$/'					=> 'status',
				'/children$/'				=> 'child',
				'/news$/'					=> 'news',
				'/s$/'						=> ''
			);
			foreach ($singular_rules as $rule => $replacement) {
				if (preg_match($rule, $word)) {
					return preg_replace($rule, $replacement, $word);
				}
			}
			return false;
		}
		
		function camelize($lower_case_and_underscored_word) {
			return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
		}    
	
		function underscore($sentence_cased_word) {
			return str_replace(' ', '_', $sentence_cased_word);
		}
	
		function humanize($lower_case_and_underscored_word) {
			return ucwords(str_replace("_"," ",$lower_case_and_underscored_word));
		}
		
		function tableize($class_name) {
			return Inflector::pluralize(Inflector::underscore($class_name));
		}
		
		function toTableName($field_name) {
			return Inflector::tableize(str_replace("_id", "", $field_name));
		}
		
		function toClassName($cPart) {
			return str_replace(" ","", ucwords(str_replace("-"," ",$cPart)));
		}
	
		function foreignKey($class_name) {
			return $this->underscore($class_name) . "_id";
		}
		
		function getter($field_name) {
			return "get" . ucwords(Inflector::singularize($field_name));
		}

		function setter($field_name) {
			return "set" . ucwords(Inflector::singularize($field_name));
		}
		
		function finder($field_name) {
			return "findBy" . Inflector::camelize(Inflector::singularize($field_name));
		}
	
	}

?>