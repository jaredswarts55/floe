<?php
/**
 * An identity map containing objects blown up out of the
 * current request. If an object already exists in scope then
 * no round trip is required to retrieve it.
 */
class LocalScope {
	
	/**
	 * Singleton static instance.
	 */
	static private $self;
	
	/**
	 * Map of current objects.
	 */
	private $map;

	/**
	 * Constructor expects to be passed a storage gateway.
	 */
	function __construct($storage) {
		$this->storage = $storage;
		$this->new = array();
		$this->map = array();
	}
	
	/**
	 * Returns a single static instance of the identity map.
	 */
	function instance() {
		if (!isset(self::$self)) {
			self::$self = new LocalScope(new MysqlGateway(new MysqlConnection));
		}
		return self::$self;
	}
	
	/**
	 * Looks up an object by id. If it doesn't exist in memory, it will be
	 * retrieved from the database.
	 */
	function selectById($table, $id) {
		if (!isset($this->map[$table][$id])) {
			$this->storage->selectById($table, $id);
			$this->map[$table][$id] = $this->storage->getArray();
		}
		return $this->map[$table][$id];
	}
	
	/**
	 * Removes a record from the identity map.
	 */
	function remove($table, $id) {
		unset($this->map[$table][$id]);
	}
	
	/**
	 * Saves an item persistently and assigns autogenerated id as key.
	 */
	function save($table, $properties) {
		if (!isset($properties['id'])) {
			$this->storage->insert($table, $properties);
			$id = $this->storage->insertId();
			$properties['id'] = $id;
		} else {
			$id = $properties['id'];
			$this->storage->update($table, array('id'=>$id), $properties);
		}
		$this->map[$table][$id] = $properties;
		return $properties;
	}
}

?>