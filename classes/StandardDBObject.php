<?php
//can never be instantiated itself as it has no "const TABLE" defined; needs extending by each thing that exists in the DB
//is the root object which all things coming from the db must extend
//provides a lot of functionality, could even be viewed as a form of Factory, and can do a "virtual object"/"placeholder" thing to help speed
//up long lists of things comprised of sql JOINs on multiple tables
abstract class StandardDBObject {

	protected $id=null, $key_field='', $key_value='', $data=array(), $shell_data=array(), $data_loaded=false, $shell_loaded=false;

//descendents can have this comma separated list of other fields a record of this class should be cached under, picked up automatically by ObjectCache
//"id" is done automatically and doesn't need specifying in descendents
//	const OBJECT_CACHE_IDENTIFIERS = 'id';

//instead of calling this directly and, in the case of non-existent $id, getting back an object with no data in it,
//use the ::fetch method which returns false when there's a non-existing $id instead (factory/mapper-like behaviour)
	public function __construct($id, $load_mode=false) {
		if($load_mode=='shell') {
//in this case, $id will be an array of the data to hold, but only partial data from an external "SELECT {fields} FROM ..."
			$this->shell_loaded = true;
			$this->shell_data = $id;
			$this->id = $this->shell_data['id'];
		}
		else if($load_mode=='fetchAll') {
//in this case, $id will be an array of the data to hold, from an internal "SELECT * FROM ..."
			$this->data_loaded = true;
			$this->data = $id;
			$this->id = $this->data['id'];
			if(method_exists($this, 'initExtraData'))
				$this->initExtraData();
		}
		else {
//this is the plain old "fetch a single row" bit
			if(is_array($id))
				list($this->key_field, $this->key_value) = each($id);
			else
				$this->id = $id;
			$this->fetchData();
			if(method_exists($this, 'initExtraData'))
				$this->initExtraData();
		}
	}

	protected function fetchData() {
		$this->data_loaded = true;
		$r_data = ServiceManager::getService('mysql')->query("SELECT * FROM ".(defined('static::TABLE_PREFIX')?static::TABLE_PREFIX:DB_PFX).static::TABLE." WHERE ".($this->key_field?"`".mysql_real_escape_string($this->key_field)."`='".mysql_real_escape_string($this->key_value)."'":"id=".mysql_real_escape_string($this->id)));
		if($r_data && mysql_num_rows($r_data) && ($row = mysql_fetch_assoc($r_data))) {
			$this->data = $row;
			if(!$this->id)
				$this->id = $this->data['id'];
		}
		else
			unset($this->id, $this->key_field, $this->key_value);
	}

//implemented if extra fields need setting such as "ref" in the Lead class
//	protected function initExtraData() {}

	public function getId() {
		return $this->id;
	}

	public function getField($name) {
		if($this->data_loaded) {
			return $this->data[$name];
		}
		else if($this->shell_loaded) {
			if(isset($this->shell_data[$name])) {
				return $this->shell_data[$name];
			}
			else {
				$this->fetchData();
				return $this->data[$name];
			}
		}
	}

	public function getSerializedField($name) {
		$value = $this->getField($name);
		return $value?unserialize($value):false;
	}

//just used to set things in-memory that don't get stored in the DB, such as REF fields for Leads and Users
	public function setField($name, $value) {
		if($this->data_loaded)
			$this->data[$name] = $value;
		else if($this->shell_loaded)
			$this->shell_data[$name] = $value;
	}

	public function saveSerializedField($name, $value) {
		$this->updateData(array(
			$name=>serialize($value),
		));
	}

//this gets called externally, passing in just the fields names desiring to be saved. Note there is *no* concept of passing an entire StandardDBObject
//in to be "saved" by anything, all updates to the database come through this (and ::store), which does lead to external code knowing about inner
//properties of objects, but this has had to evolve gradually from a very old codebase
	public function updateData($update='') {
		if($update && count($update)) {
			if($this->id) {
				ServiceManager::getService('mysql')->query("UPDATE ".(defined('static::TABLE_PREFIX')?static::TABLE_PREFIX:DB_PFX).static::TABLE." SET ".implode(",", self::prepareData($update, 'update'))." WHERE id=".$this->id);
				$this->fetchData();
				return true;
			}
		}
		return false;
	}

	public function deleteRecord() {
		if($this->id)
			ServiceManager::getService('mysql')->query("DELETE FROM ".(defined('static::TABLE_PREFIX')?static::TABLE_PREFIX:DB_PFX).static::TABLE." WHERE id=".$this->id);
	}

	public static function store($insert='') {
		if($insert && count($insert)) {
			$insert = self::prepareData($insert, 'insert');
			ServiceManager::getService('mysql')->query("INSERT INTO ".(defined('static::TABLE_PREFIX')?static::TABLE_PREFIX:DB_PFX).static::TABLE." (".implode(",", $insert['fields']).") VALUES (".implode(",", $insert['values']).")");
			return static::fetch(mysql_insert_id());
		}
	}

//factory-style method for fetching a record from the db, checking with the ObjectCache first
	public static function fetch($id) {
		if(ServiceManager::getService('object_cache')->isEnabled()) {
			$found = ServiceManager::getService('object_cache')->checkForObject(get_called_class(), $id);
			if($found)
				return $found;
		}
		$return = new static($id);
		if(!$return->getId())
			unset($return);
		if($return && ServiceManager::getService('object_cache')->isEnabled())
			ServiceManager::getService('object_cache')->addObject($return);
		return $return;
	}

//this is to make database stuff much quicker for "lists of things". idea is, we pull out just the fields used in the listing in the initial SQL
//and pass those in to here, storing it as "shell data". if any getField() or subsequent thing tries to access data not in the shell
//we can go and do a proper fetch of the real record from the db. so as long as all fields used in a listing are in the initial "shell data" passed
//in, "lists of things" should be way way quicker
	public static function createShell($data) {
		$object = new static($data, 'shell');
		return $object;
	}

	public function addShellObject($property, $shell, $is_array=false) {
		if($is_array)
			$this->{$property}[] = $shell;
		else
			$this->$property = $shell;
	}

	public static function fetchAll($where='', $order='') {
		if(is_array($where))
			$where = implode(" AND ", self::prepareData($where));
		$r_data = ServiceManager::getService('mysql')->query("SELECT *".(defined('static::SORT_FIELD')?",".static::SORT_FIELD." AS sort":'')." FROM ".(defined('static::TABLE_PREFIX')?static::TABLE_PREFIX:DB_PFX).static::TABLE.($where?" WHERE ".$where:'')." ORDER BY ".($order?$order:"`id`"));
		if($r_data && mysql_num_rows($r_data)) {
			$return = array();
			while($row = mysql_fetch_assoc($r_data))
				$return[$row['id']] = new static($row, 'fetchAll');
			return $return;
		}
		return false;
	}

/*
takes in an array of the form array(key=>value[, ...]);
where "key" corresponds to a field in the DB and "value" a value to use in a SELECT WHERE ... or an INSERT/UPDATE statement
if "value" is an array it's taken to be in a WHERE clause and needs an "IN ()" operator, otherwise "=" is used as operator - unless the last char
in "key" is a bang, in which case !=/NOT IN() are used
*/
	public static function prepareData($data, $mode='select') {
		$non_escape = array(
			'NOW',
			'FROM_UNIXTIME',
			'MD5',
			'DATE',
		);
		$return = array();
		foreach($data as $k=>$v) {
//for SELECTs to get a != operator in
			if($mode=='select' && substr($k, strlen($k)-1, 1)=='!') {
				$k = substr($k, 0, strlen($k)-1);
				$operator = '!=';
			}
			else
				$operator = '=';
			$return['fields'][$k] = preg_match('/DATE\([a-z0-9_ ]+\)/i', $k)?$k:"`".mysql_real_escape_string($k)."`";
//take the VALUE and add mysql_real_escape_string and enclose in 'quotes', IF VALUE is not an array
			if(!is_array($v))
				$return['values'][$k] = preg_match('/^('.implode(')|(', $non_escape).')\(/i', $v)?$v:"'".mysql_real_escape_string($v)."'";
//if we're doing an INSERT we don't need to amalgamate the keys and values; if we're doing an UPDATE we need them joining on "=" operator
//and if it's a SELECT we also do but need a mechanism to support alternate operators too
			if($mode=='select')
				$return['values'][$k] = $return['fields'][$k].(is_array($v)?' '.($operator=='!='?'NOT IN':'IN').' ('.implode(', ', $v).')':$operator.$return['values'][$k]);
			else if($mode=='update')
				$return['values'][$k] = $return['fields'][$k].'='.$return['values'][$k];
		}
//if we're doing an INSERT, return both the FIELDS and VALUES, otherwise just the amalgamated FIELDS=VALUES thing
		return $mode=='insert'?$return:$return['values'];
	}
}
?>