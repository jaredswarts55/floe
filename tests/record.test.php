<?php
require_once 'simpletest/autorun.php';
require_once 'simpletest/mock_objects.php';
require_once dirname(__FILE__).'/../src/repository/Record.class.php';

if (!defined('DB_HOST')) {
	define('DB_HOST', 'localhost');
	define('DB_NAME', 'floe_test');
	define('DB_USER', 'default');
	define('DB_PASS', 'launch');
}

class Dog extends Record {
	function __define() {
		$this->property("age", "integer");
		$this->property("breed", "string");
		$this->property("name", "string");
	}
	function isPuppy() {
		return ($this->age < 3);
	}
}

class ModelWithBasicPropertiesTest extends UnitTestCase {

	function setUp() {
		$dogModel = new Dog();
		$adaptor = Storage::init();
		$adaptor->createTable("dogs", $dogModel->properties());
	}

	function testCreateAndStoreNewInstance() {
		$dog = new Dog();
		$dog->age = 2;
		$dog->breed = "Terrier";
		$dog->name = "Jack";
		$this->assertTrue($dog->save());
		$id = $dog->id;
		unset($dog);
		$dog = new Dog(1);
		$this->assertIsA($dog, 'Dog');
		$this->assertEqual(2, $dog->age);
		$this->assertEqual("Terrier", $dog->breed);
		$this->assertEqual("Jack", $dog->name);
		$this->assertTrue($dog->isPuppy());
	}
	
	function testErrorWhenInvalidIdGiven() {
		$this->expectException('RecordNotFound');
		$dog = new Dog(999);
	}
	
	function tearDown() {
		$adaptor = Storage::init();
		$adaptor->dropTable("dogs");
	}

}

class Thing extends Record {
	function __define() {
		$this->property('stringField', 'string');
		$this->property('integerField', 'integer');
		$this->property('floatField', 'float');
		$this->property('dateField', 'datetime');
		$this->property('booleanFieldOn', 'boolean');
		$this->property('booleanFieldOff', 'boolean');
	}
}

class RecordPropertyTypesTest extends UnitTestCase {

	function setUp() {
		$model = new Thing();
		$adaptor = Storage::init();
		$adaptor->createTable("things", $model->properties());
	}

	function testCanManipulateAllPrimitiveTypes() {
		$query = Storage::init();
	
		$typeValues = array(
			'string_field' => 'a string',
			'integer_field' => 33,
			'float_field' => 2.567,
			'date_field' => '2006-09-09',
			'boolean_field_on' => true,
			'boolean_field_off' => false
		);
		
		$query->insert("things", $typeValues);
		
		$query->selectById('things', $query->insertId());
		$object = $query->getRecord();
		$this->assertTrue(is_string($object->stringField));
		$this->assertTrue(is_integer($object->integerField));
		$this->assertEqual(33, $object->integerField);
		$this->assertTrue(is_float($object->floatField));
		$this->assertIdentical(2.567, $object->floatField);
		$this->assertIsA($object->dateField, 'DateTimeType');
		$this->assertEqual('2006-09-09', $object->dateField->format('Y-m-d'));
		$this->assertEqual(true, $object->booleanFieldOn);
		$this->assertEqual(false, $object->booleanFieldOff);

	}
	
	function tearDown() {
		$adaptor = Storage::init();	
		$adaptor->dropTable("things");
	}

}

class Project extends Record {
	function __define() {
		$this->property("name", "string");
		$this->hasMany("tasks");
	}
}

class Task extends Record {
	function __define() {
		$this->property("name", "string");
		$this->belongsTo("project");
	}
}


class OneToManyAssociationTest extends UnitTestCase {

	function setUp() {
		$projectModel = new Project();
		$taskModel = new Task();
		$adaptor = Storage::init();
		$adaptor->createTable("projects", $projectModel->properties());
		$adaptor->createTable("tasks", $taskModel->properties());
	}

	function testCreateAndStoreNewInstanceWithRelations() {
		$project = new Project();
		$project->name = "Default Project";
		
		$task = new Task();
		$task->name = "do something";
		
		$task2 = new Task();
		$task2->name = "something else";
		
		$project->tasks = $task;
		$project->tasks = $task2;
		
		
		
		$this->assertTrue($project->save());
		$id = $project->id;
		unset($project);
		
		$adaptor = Storage::init();
		$adaptor->selectById("projects", $id);
		$proj = $adaptor->getRecord();
		
		$this->assertEqual("Default Project", $proj->name);
		$this->assertEqual(2, count($proj->tasks));
		$this->assertEqual("do something", $proj->tasks[0]->name);
		$this->assertEqual("something else", $proj->tasks[1]->name);
	}
	
	function testPopulateRecordWithArray() {
		$task = new Task();
		$task->populate(array("name"=>"mytask","projectId"=>9));
		
		$this->assertEqual("mytask", $task->name);
		$this->assertEqual(9, $task->projectId);
		
		$task->populate(array("name"=>"mytask2","projectId"=>"9"));
		
		$this->assertEqual("mytask2", $task->name);
		$this->assertEqual(9, $task->projectId);
	}
	
	function tearDown() {
		$adaptor = Storage::init();
		$adaptor->dropTable("projects");
		$adaptor->dropTable("tasks");
	}

}

class Post extends Record {
	
	function __define() {
		$this->property("title", "string");
		$this->hasManyRelations("topics");
	}
	
}

class Topic extends Record {
	
	function __define() {
		$this->property("name", "string");
		$this->hasManyRelations("posts");
	}
	
}

class ManyToManyRelationshipTest extends UnitTestCase {
	
	function setUp() {
		$post = new Post();
		$topic = new Topic();
		$adaptor = Storage::init();
		$adaptor->createTable("posts", $post->properties());
		$adaptor->createTable("topics", $topic->properties());
		$adaptor->createTable("posts_topics", array("post_id" => "integer", "topic_id" => "integer"));
	}
	
	function testCreateAndStoreNewInstance() {
		$post = new Post();
		$post->title = "Hello World";
		
		$topic1 = new Topic();
		$topic1->name = "hello";
		
		$topic2 = new Topic();
		$topic2->name = "world";
		
		$post->topics = $topic1;
		$post->topics = $topic2;
		
		$this->assertTrue($post->save());
		
		$id = $post->id;
		unset($post);
		$adaptor = Storage::init();
		$adaptor->selectById("posts", $id);
		$post = $adaptor->getRecord();
		
		$this->assertEqual("Hello World", $post->title);
		$this->assertEqual("hello", $post->topics[0]->name);
		$this->assertEqual("world", $post->topics[1]->name);
		
		unset($post);
		$adaptor = Storage::init();
		$adaptor->selectById("posts", $id);
		$post = $adaptor->getRecord();
		
		$this->assertEqual("Hello World", $post->title);
		$this->assertTrue($post->save());
		$this->assertEqual("hello", $post->topics[0]->name);
		$this->assertEqual("world", $post->topics[1]->name);
		
		unset($post);
		$adaptor = Storage::init();
		$adaptor->selectById("topics", 1);
		$topic = $adaptor->getRecord();
		
		$this->assertEqual("hello", $topic->name);
		$this->assertEqual("Hello World", $topic->posts[0]->title);
	}

	function tearDown() {
		$adaptor = Storage::init();
		$adaptor->dropTable("posts");
		$adaptor->dropTable("topics");
		$adaptor->dropTable("posts_topics");
	}
	
}


class Player extends Record {
	
	function __define() {
		$this->property('type', 'string'); // shouldn't have to add manually
		$this->property('name', 'string');
	}
	
}

class Footballer extends Player {
	
	function __define() {
		$this->property('club', 'string');
	}
	
}

class Cricketer extends Player {
	
	function __define() {
		$this->property('topScore', 'int');
	}
	
}

class Bowler extends Cricketer {
	
	function __define() {
		$this->property('wicketsTaken', 'int');
	}
	
}


class SingleTableInheritanceTest extends UnitTestCase {
	
	function setUp() {
		$adaptor = Storage::init();
		$adaptor->createTable("players", array('name'=>'string', 'topScore'=>'int', 'wicketsTaken'=>'int', 'club'=>'string', 'type'=>'string'));
	}
	
	function testCanAccessBaseRecord() {
		$player = new Player();
		$player->name = "Ritchie McCaw";
		$this->assertEqual("Player", $player->type);
		$player->save();
		$id = $player->id;
		unset($player);
		$player = new Player($id);
		$this->assertEqual("Ritchie McCaw", $player->name);
		$this->assertEqual("Player", $player->type);
	}
	
	function testCanAccessInheritedRecord() {
		$player = new Cricketer();
		$player->name = "Ricky Ponting";
		$player->topScore = 314;
		$player->save();
		$id = $player->id;
		unset($player);
		$player = new Cricketer($id);
		$this->assertEqual("Ricky Ponting", $player->name);
		$this->assertEqual(314, $player->topScore);
	}
	
	function testCanAccessMultipleInheritedRecords() {
		$player = new Cricketer();
		$player->name = "Ricky Ponting";
		$player->topScore = 257;
		$player->save();
		$punter = $player->id;
		unset($player);
		
		$player = new Bowler();
		$player->name = "Andrew Flintoff";
		$player->topScore = 167;
		$player->wicketsTaken = 297;
		$player->save();
		$freddie = $player->id;
		unset($player);
		
		$player = new Footballer();
		$player->name = "David Beckham";
		$player->club = "LA Galaxy";
		$player->save();
		$becks = $player->id;
		unset($player);
		
		$player = new Cricketer($punter);
		$this->assertEqual("Ricky Ponting", $player->name);
		$this->assertEqual(257, $player->topScore);
		$this->assertEqual("Cricketer", $player->type);
		
		$player = new Bowler($freddie);
		$this->assertEqual("Andrew Flintoff", $player->name);
		$this->assertEqual(167, $player->topScore);
		$this->assertEqual(297, $player->wicketsTaken);
		$this->assertEqual("Bowler", $player->type);
		
		$player = new Footballer($becks);
		$this->assertEqual("David Beckham", $player->name);
		$this->assertEqual("LA Galaxy", $player->club);
		$this->assertEqual("Footballer", $player->type);
	}
	
	function tearDown() {
		$adaptor = Storage::init();
		$adaptor->dropTable("players");
	}
	
}

class BaseObj extends Record {
	
	function __define() {
		$this->property('type', 'string'); // shouldn't have to add manually		
		$this->property('name', 'string');
		$this->property('tag', 'string');
		$this->hasMany('relatedObjs');
	}
	
}

class ChildObj extends BaseObj {
	
	function __define() {
		$this->property('numberOfProblems', 'integer');
		$this->hasMany('otherRelatedObjs');	
	}
	
}

class RelatedObj extends Record {
	
	function __define() {
		$this->property('relatedThing', 'string');
		$this->belongsTo('baseObj');
	}
	
}

class OtherRelatedObj extends Record {
	
	function __define() {
		$this->property('otherThing', 'string');
		$this->belongsTo('childObj');
	}
	
}

class SingleTableInheritanceWithRelationshipsTest extends UnitTestCase {
	
	function setUp() {
		$base = new BaseObj();
		//$child = new ChildObj();
		$related = new RelatedObj();
		$other = new OtherRelatedObj();
		$adaptor = Storage::init();
		$adaptor->createTable("base_objs", $base->properties());
		$adaptor->addColumn("base_objs", "number_of_problems", "integer");
		$adaptor->createTable("related_objs", $related->properties());
		$adaptor->createTable("other_related_objs", $other->properties());
	}
	
	function tearDown() {
		$adaptor = Storage::init();
		$adaptor->dropTable("base_objs");
		$adaptor->dropTable("related_objs");
		$adaptor->dropTable("other_related_objs");
	}

	function testHasManyCalledCorrectlyOnParent() {
		$rel1 = new RelatedObj();
		$rel1->relatedThing = "one";
		$rel2 = new RelatedObj();
		$rel2->relatedThing = "two";
		$base = new BaseObj();
		$base->relatedObjs = $rel1;
		$base->relatedObjs = $rel2;
		$this->assertEqual($base->type, "BaseObj");
		$base->save();
		
		$this->assertEqual($base->type, "BaseObj");
		$this->assertEqual(count($base->relatedObjs), 2);
		$this->assertEqual($base->relatedObjs[0]->relatedThing, "one");
		$this->assertEqual($base->relatedObjs[1]->relatedThing, "two");
	}
	
	function testHasManyCalledCorrectlyOnChild() {
		$rel1 = new RelatedObj();
		$rel1->relatedThing = "one";
		$rel2 = new RelatedObj();
		$rel2->relatedThing = "two";
		$rel3 = new OtherRelatedObj();
		$rel3->otherThing = "three";
		$rel4 = new OtherRelatedObj();
		$rel4->otherThing = "four";
		$child = new ChildObj();
		$child->relatedObjs = $rel1;
		$child->relatedObjs = $rel2;
		$child->otherRelatedObjs = $rel3;
		$child->otherRelatedObjs = $rel4;	
		$child->numberOfProblems = 99;
		$child->save();
		
		$this->assertEqual($child->type, "ChildObj");
		$this->assertEqual(count($child->relatedObjs), 2);
		$this->assertEqual($child->relatedObjs[0]->relatedThing, "one");
		$this->assertEqual($child->relatedObjs[1]->relatedThing, "two");
	}	
	
}


class OverloadedPropertyAccess extends Record {
	
	function __define() {
		$this->property('name', 'string');
		$this->property('title', 'string');
		$this->property('rawField', 'string');
		$this->property('wrappingValue', 'string');
	}
	
	function setTitle($title) {
		$this->name = $title;
		$this->setProperty('title', $title);
	}
	
	function setWrappingValue($value) {
		$this->rawField = $value;
		$this->setProperty('wrappingValue', $value);
	}
	
	function getWrappingValue() {
		return strtolower($this->getProperty('wrappingValue'));
	}
	
}

class OverloadedPropertyAcessorsTest extends UnitTestCase {
	
	function testCanOverloadPropertyAccessors() {
		$model = new OverloadedPropertyAccess();
		$model->title = "The Lion Roared";
		$this->assertEqual($model->name, $model->title);
		$input = "The Fox Jumped";
		$model->wrappingValue = $input;
		$this->assertEqual($input, $model->rawField);
		$result = $model->wrappingValue;
		$this->assertEqual(strtolower($input), $result);
	}
	
}

class Toggle extends Record {
	
	function __define() {
		$this->property("state1", "boolean");
		$this->property("state2", "boolean");
		$this->property("state3", "boolean");
		$this->property("state4", "boolean");
	}
	
}

class BooleanCastingTest extends UnitTestCase {
	
	function setUp() {
		$db = Storage::init();
		$t = new Toggle();
		$db->createTable("toggles", $t->properties());
	}
	
	function testMutateBooleanProperties() {
		$t = new Toggle();
		$t->state1 = false;
		$t->state2 = 0;
		$t->state3 = "0";
		$t->state4 = null;
		$this->assertFalse($t->state1);
		$this->assertFalse($t->state2);
		$this->assertFalse($t->state3);
		$this->assertFalse($t->state4);
		$t->state1 = true;
		$t->state2 = 1;
		$t->state3 = "1";
		$t->state4 = "true";
		$this->assertTrue($t->state1);
		$this->assertTrue($t->state2);
		$this->assertTrue($t->state3);
		$this->assertTrue($t->state4);
		$t->save();
		$this->assertTrue($t->state1);
		$this->assertTrue($t->state2);
		$this->assertTrue($t->state3);
		$this->assertTrue($t->state4);
		$t = new Toggle($t->id);
		$t->populate(array("state1"=>false, "state2"=>0, "state3"=>"0", "state4"=>null));
		$this->assertFalse($t->state1);
		$this->assertFalse($t->state2);
		$this->assertFalse($t->state3);
		$this->assertFalse($t->state4);
	}
	
	function tearDown() {
		$db = Storage::init();
		$db->dropTable("toggles");
	}
	
}

class ColouredPencil extends Record {
	
	function __define() {
		$this->property('colour', 'Colour');
	}
	
}

class ColourType {
	private $value;
	
	function __construct($value='000000') {
		$this->value = strtoupper($value);
	}
	
	function red() {
		return substr($this->value, 0, 2);
	}
	
	function green() {
		return substr($this->value, 2, 2);
	}
	
	function blue() {
		return substr($this->value, 4, 2);
	}
	
}

class PropertyCastToCustomValueObject extends UnitTestCase {
	
	function testValueObjectCastFromDefinedField() {
		$pencil = new ColouredPencil();
		$pencil->colour = "ff99cc";
		$this->assertIsA($pencil->colour, 'ColourType');
		//$this->assertEqual($pencil->colour->red(), 'FF');
		//$this->assertEqual($pencil->colour->green(), '99');
		//$this->assertEqual($pencil->colour->blue(), 'CC');
		
		//$pencil = new ColouredPencil();
		// todo support real value objects
		//$pencil->colour = new ColourType();
		//$this->assertEqual($pencil->colour->red(), '00');
	}
	
}

class GraphitePencil extends Record {
	
	function __define() {
		$this->property('lead', 'PencilLead');
	}
	
}

class PencilLeadType {
	private $value;
	
	function __construct($value=false) {
		if ($value) $this->value = $value;
	}
	
	function sharpen() {
		$this->value = true;
	}
	
	function isSharp() {
		return $this->value;
	}
	
}

class PropertyCastToDependentCompoundObject extends UnitTestCase {
	
	function testValueObjectCastFromDefinedField() {
		//$pencil = new GraphitePencil();
		//$this->assertFalse($pencil->lead->isSharp());
		//$pencil->lead->sharpen();
		//$this->assertTrue($pencil->lead->isSharp());
		//todo support dependent types with state, as well as value objects
	}
	
}

?>