<?php
require_once 'simpletest/autorun.php';
require_once 'simpletest/mock_objects.php';
require_once dirname(__FILE__).'/../../src/repository/store/StorageAdaptor.class.php';
require_once dirname(__FILE__).'/../../src/repository/store/mysql/MysqlQuery.class.php';

class MysqlQueryCriteriaTest extends UnitTestCase {
	
	function testSingleWhereClause() {
		$query = StorageAdaptor::query();
		$query->whereEquals("key", "value");
		$this->assertEqual($query->toSql(), "WHERE key = 'value'");
	}
	
	function testMultiplePredicates() {
		$query = StorageAdaptor::query();
		$query->whereEquals("foo", "bar");
		$query->whereEquals("lol", "rofl");
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' AND lol = 'rofl'");
	}
	
	function testMultiplePredicateOperators() {
		$query = StorageAdaptor::query();
		$query->whereEquals("foo", "bar");
		$query->whereNotEquals("lol", "rofl");
		$query->whereLike("goto", "hell");
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' AND lol != 'rofl' AND goto LIKE '%hell%'");
	}
	
	function testChainedMultiplePredicateOperators() {
		$query = StorageAdaptor::query();
		$query->whereEquals("foo", "bar")->whereNotEquals("lol", "rofl")->whereLike("goto", "hell");
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' AND lol != 'rofl' AND goto LIKE '%hell%'");
	}
	
	function testDefaultOrderByClause() {
		$query = StorageAdaptor::query();
		$query->whereEquals("foo", "bar");
		$query->orderBy('foo');
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' ORDER BY foo DESC");
	}
	
	function testExplicitOrderByClause() {
		$query = StorageAdaptor::query();
		$query->whereEquals("foo", "bar");
		$query->orderBy('foo')->asc();
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' ORDER BY foo ASC");
	}
	
	function testZeroLimitClause() {
		$query = StorageAdaptor::query();
		$query->whereEquals("foo", "bar");
		$query->orderBy('foo')->asc()->limit(0,10);
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' ORDER BY foo ASC LIMIT 0,10");
	}	
	
	function testPositiveLimitClause() {
		$query = StorageAdaptor::query();
		$query->whereEquals("foo", "bar");
		$query->orderBy('foo')->asc()->limit(10,20);
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' ORDER BY foo ASC LIMIT 10,20");
	}
	
}

?>