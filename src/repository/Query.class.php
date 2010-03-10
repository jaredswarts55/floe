<?php
/**
 * This file is part of Floe, a graceful PHP framework.
 * Copyright (C) 2005-2010 Mark Rickerby <http://maetl.net>
 *
 * See the LICENSE file distributed with this software for full copyright, disclaimer
 * of liability, and the specific limitations that govern the use of this software.
 *
 * $Id$
 * @package repository
 */

require_once dirname(__FILE__).'/../language/en/Inflect.class.php';
require_once 'store/mysql/MysqlQuery.class.php';

/**
 * Criteria based select query interface.
 * 
 * @package repository
 */
class Query {
	
	protected $limitLower;
	
	protected $limitUpper;
	
	protected $orderBy;
	
	protected $orderDir;
	
	protected $whereClauses;
	
	protected $selectFields;
	
	protected $tableNames;
	
	public $tableName;

	/**
	 * Factory method for returning a Query object supporting the default storage adaptor.
	 */
	static function instance() {
		return new MysqlQuery();
	}
	
	function __construct() {
		$this->selectFields = array();
		$this->whereClauses = array();
		$this->tableNames = array();
	}
	
	/**
	 * Select given columns from the target table.
	 *
	 * @return Query
	 * @todo need to Inflect::underscore this column
	 */
	function select($column="*") {
		if (func_num_args() > 1) {
			$columns = func_get_args();
			$this->selectFields = array_merge($this->selectFields, $columns);
		} else {
			if (is_array($column))  {
				$this->selectFields = array_merge($this->selectFields, $column);	
			} else {
				$this->selectFields[] = $column;				
			}
		}
		return $this;
	}
	
	/**
	 * Select a column with result as given alias.
	 *
	 * @return Query
	 */
	function selectAs($column,$alias) {
	    if ($column instanceof Query) {
	       return $this->select("($column) AS $alias");
	    } else {
	       return $this->select("$column AS $alias");
	    }
	}

	/**
	 * Select a count of the given column.
	 *
	 * If no column given, defaults to <code>id</code>.
	 *
	 * @todo move to MysqlSpecific query object
	 * @return Query
	 */
	function selectCount($column="id") {
		return $this->select("COUNT($column) AS count");
	}
	
	/**
	 * Add a table name.
	 *
	 * @return Query
	 */
	function from() {
		$this->tableNames = func_get_args();
		if (!isset($this->tableName)) $this->tableName = $this->tableNames[0];
		return $this;
	}
	
	/**
	 * Static factory for generating a criteria object.
	 *
	 * Criteria objects represent predicates in a where clause,
	 * consisting of a field to match on, the value to match against, 
	 * and an operator to express the match.
	 *
	 * <code>Query::criteria("title", "=", "My Title");</code>
	 * <code>Query::criteria("count", ">=", 999);</code>
	 * @todo fix isJoin parameter hack	
	 * @return stdClass criteria object
	 */
	static function criteria($field, $operator, $value, $isJoin=false) {
		$criteria = new stdClass;
		$criteria->field = Inflect::underscore($field);
		$criteria->operator = $operator;
		$criteria->value = $value;
		$criteria->isJoin = $isJoin;
		return $criteria;
	}

	/**
	 * Add a generic where predicate, matching value against the given operator.
	 *
	 * @param string $field name of the field to match against
	 * @param string $operator match operator (=, etc)
	 * @return Query	
	 */
	function where($field, $operator, $value) {
		$this->whereClauses[] = self::criteria($field, $operator, $value, true);
		return $this;
	}
	
	/**
	 * Add join connection between PK and FK. Eg. trailers.movie_id=movies.id
	 * @todo fix isJoin parameter hack
	 *
	 * @param $left, the left key
	 * @param $right, the right key
	 * @return Query
	 */
	function whereJoin($left, $right) {
	    $this->whereClauses[] = self::criteria($left, "=", $right, true);
	    return $this;
	}
	
	/**
	 * Add an equals clause to the query.
	 *
	 * @return Query
	 */
	function whereEquals($key, $value) {
		$this->whereClauses[] = self::criteria($key, "=", $value);
		return $this;		
	}

	/**
	 * Add a not-equals clause to the query.
	 *
	 * @return Query
	 */	
	function whereNotEquals($key, $value) {
		$this->whereClauses[] = self::criteria($key, "!=", $value);
		return $this;		
	}

	/**
	 * Column matches given value using LIKE.
	 *
	 * @return Query
	 */	
	function whereLike($key, $value) {
		$this->whereClauses[] = self::criteria($key, "LIKE", "%$value%");
		return $this;				
	}
	
  /**
   * Column matches given value using NOT LIKE.
   *
   * @return Query
   */ 
  function whereNotLike($key, $value) {
    $this->whereClauses[] = self::criteria($key, "NOT LIKE", "%$value%");
    return $this;       
  }

	/**
   * Column is greater than given value
   *
   * @return Query
   */ 
  function whereGreaterThan($key, $value) {
    $this->whereClauses[] = self::criteria($key, ">", $value);
    return $this;   
  }
  
  /**
   * Column is greater than or equal to given value
   *
   * @return Query
   */ 
  function whereGreaterThanEqual($key, $value) {
    $this->whereClauses[] = self::criteria($key, ">=", $value);
    return $this;   
  }

  /**
   * Column is less than given value.
   *
   * @return Query
   */
  function whereLessThan($key, $value) {
    $this->whereClauses[] = self::criteria($key, "<", $value);
    return $this;   
  }

  /**
   * Column is less than or equal to given value.
   *
   * @return Query
   */
  function whereLessThanEqual($key, $value) {
    $this->whereClauses[] = self::criteria($key, "<=", $value);
    return $this;   
  }
	
	/**
	 * Add a range based criteria to the query.
	 *
	 * @return Query
	 */	
	function whereWithinRange($key, $lower, $upper) {
	    $operator = (is_string($lower)) ? "BETWEEN '$lower' AND" : "BETWEEN $lower AND";
		$upper = (is_string($upper)) ? "'$upper'" : $upper;
		$this->whereClauses[] = self::criteria($key, $operator, $upper);
		return $this;
	}
	
	/**
	 * Add a range based criteria to the query.
	 *
	 * @return Query
	 */	
	function whereNotWithinRange($upper, $lower) {
	    $operator = (is_string($upper)) ? "NOT BETWEEN '$upper' AND" : "NOT BETWEEN $upper AND";
		$this->whereClauses[] = self::criteria($key, $operator, $lower);
		return $this;		
	}

	/**
	 * Order the query results by the given column.
	 *
	 * @return Query
	 */	
	function orderBy($field) {
		$this->orderBy = Inflect::underscore($field);
		return $this;
	}
	
	/**
	 * Limit the query results to given range.
	 *
	 * @return Query
	 */	
	function limit($lower, $upper) {
		$this->limitLower = (integer)$lower;
		$this->limitUpper = (integer)$upper;
		return $this;
	}
	
	/**
	 * Sort query results in descending order.
	 *
	 * @return Query
	 */	
	function desc() {
		$this->orderDir = 'DESC';
		return $this;
	}
	
	/**
	 * Sort query results in ascending order.
	 *
	 * @return Query
	 */	
	function asc() {
		$this->orderDir = 'ASC';
		return $this;
	}
	
}

?>