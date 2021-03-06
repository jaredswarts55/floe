<?php
require_once 'simpletest/autorun.php';
require_once 'simpletest/mock_objects.php';
require_once dirname(__FILE__).'/../src/repository/Query.class.php';

class MysqlQueryCriteriaTest extends UnitTestCase {

	function testWildcardFieldsFromEmptyParameter() {
		$query = Query::init();
		$query->select()->from("articles");
		$this->assertEqual($query->__toString(), "SELECT * FROM articles");
	}
	
	function testWildcardFieldsWithNoSelectGiven() {
		$query = Query::init();
		$query->from("articles");
		$this->assertEqual($query->__toString(), "SELECT * FROM articles");
	}
	
	function testMultipleColumnsSelectAsArray() {
		$query = Query::init();
		$query->select(array("title","summary"))->from("articles");
		$this->assertEqual($query->__toString(), "SELECT title,summary FROM articles");
	}
	
	function testMultipleColumnsSelectAsArgs() {
		$query = Query::init();
		$query->select("title", "summary", "updated")->from("articles");
		$this->assertEqual($query->__toString(), "SELECT title,summary,updated FROM articles");
	}	
	
	function testChainedColumnsSelect() {
		$query = Query::init();
		$query->select("title")->select("summary")->from("articles");
		$this->assertEqual($query->__toString(), "SELECT title,summary FROM articles");
	}
	
	function testFieldSelectWithWhereClause() {
		$query = Query::init();
		$query->select(array("title","summary"))->from("articles")->whereEquals("title", "Hello");
		$this->assertEqual($query->__toString(), "SELECT title,summary FROM articles WHERE title = 'Hello'");
	}
	
	function testSingleWhereClause() {
		$query = Query::init();
		$query->whereEquals("key", "value");
		$this->assertEqual($query->toSql(), "WHERE key = 'value'");
	}
	
	function testMultiplePredicates() {
		$query = Query::init();
		$query->whereEquals("foo", "bar");
		$query->whereEquals("lol", "rofl");
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' AND lol = 'rofl'");
	}
	
	function testMultiplePredicateOperators() {
		$query = Query::init();
		$query->whereEquals("foo", "bar");
		$query->whereNotEquals("lol", "rofl");
		$query->whereLike("goto", "hell");
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' AND lol != 'rofl' AND goto LIKE '%hell%'");
	}
	
	function testChainedMultiplePredicateOperators() {
		$query = Query::init();
		$query->whereEquals("foo", "bar")->whereNotEquals("lol", "rofl")->whereLike("goto", "hell");
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' AND lol != 'rofl' AND goto LIKE '%hell%'");
	}
	
	function testDefaultOrderByClause() {
		$query = Query::init();
		$query->whereEquals("foo", "bar");
		$query->orderBy('foo');
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' ORDER BY foo DESC");
	}
	
	function testExplicitOrderByClause() {
		$query = Query::init();
		$query->whereEquals("foo", "bar");
		$query->orderBy('foo')->asc();
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' ORDER BY foo ASC");
	}
	
	function testZeroLimitClause() {
		$query = Query::init();
		$query->whereEquals("foo", "bar");
		$query->orderBy('foo')->asc()->limit(0,10);
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' ORDER BY foo ASC LIMIT 0,10");
	}	
	
	function testPositiveLimitClause() {
		$query = Query::init();
		$query->whereEquals("foo", "bar");
		$query->orderBy('foo')->asc()->limit(10,20);
		$this->assertEqual($query->toSql(), "WHERE foo = 'bar' ORDER BY foo ASC LIMIT 10,20");
	}
	
	function testTableAliases() {
		$query = Query::init();
		$query->select("i.field")->from("items i");
		$query->whereEquals("foo", "bar");
		$this->assertEqual($query->__toString(), "SELECT i.field FROM items i WHERE foo = 'bar'");
	}
	
	function testColumnAliasesAsGiven() {
		$query = Query::init();
		$query->select("i.field AS fsharp", "i.foo AS fb")->from("items i");
		$query->whereEquals("foo", "bar");
		$this->assertEqual($query->__toString(), "SELECT i.field AS fsharp,i.foo AS fb FROM items i WHERE foo = 'bar'");
	}	
	
	function testSelectCountFunction() {
		$query = Query::init();
		$query->count()->from("things");
		$this->assertEqual($query->__toString(), "SELECT COUNT(id) AS count FROM things");
	}
	
	function testSelectCountFieldFunction() {
		$query = Query::init();
		$query->count("name")->from("things");
		$this->assertEqual($query->__toString(), "SELECT COUNT(name) AS name FROM things");
	}
	
	function testSelectDistinctFieldFunction() {
		$query = Query::init();
		$query->distinct("name")->from("things");
		$this->assertEqual($query->__toString(), "SELECT DISTINCT(name) AS name FROM things");
	}
	
	function testWhereJoinFunction() {
	    $query = Query::init();
	    $query->select()->from('movies','trailers')->whereJoin('trailer.id','movie.id');
	    $this->assertEqual($query->__toString(),"SELECT * FROM movies,trailers WHERE trailer.id = movie.id");
	}
	
	function testCustomWhereClause() {
		$query = Query::init();
		$query->select()->from('things')->where('key', '=', '1');
	    $this->assertEqual($query->__toString(),"SELECT * FROM things WHERE key = 1");		
	}
	
	function testGroupByClause() {
		$query = Query::init();
		$query->from("things")->groupBy('name')->having('key', '=', '1');
		$this->assertEqual($query->__toString(), "SELECT * FROM things GROUP BY name HAVING key = 1");
	}
	
}

?>