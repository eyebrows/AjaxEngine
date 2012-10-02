<?php
//for caching of objects in memory so we don't keep hitting the DB for the same things over and over and over
//essentially contains an array of objects like $objects[CLASS_NAME][record.id] = Object; can optionally index records on other fields, not just the id,
//which get defined in the object definition themselves
class Service_ObjectCache {

	const ENABLED = true;
	const DEBUG = false;

	private $objects, $debug;

	public function init() {
		$this->debug = ServiceManager::getService('debug');
	}

	public function addObject($object) {
		if(self::DEBUG)
			$this->debug->logSqlMessage('Caching object '.get_class($object).':id:'.$object->getId());
		$this->objects[get_class($object)]['id'][$object->getId()] = $object;
		$this->addObjectNonPrimaryIdentifiers($object);
	}

	private function addObjectNonPrimaryIdentifiers($object) {
		$r = new ReflectionClass(get_class($object));
		if($identifiers = $r->getConstant('OBJECT_CACHE_IDENTIFIERS'))
			foreach(explode(',', $identifiers) as $identifier)
				if($object->getField($identifier)) {
					if(self::DEBUG)
						$this->debug->logSqlMessage('Also caching above object under :'.$identifier.':'.$object->getField($identifier));
					$this->objects[get_class($object)][$identifier][$object->getField($identifier)] = $object;
				}
	}

	public function checkForObject($class, $id) {
		if(is_numeric($id) && $id>0 && (int)$id==$id)
			return $this->checkForObjectById($class, $id);
		else if(is_array($id) && count($id)==1) {
			list($identifier, $value) = each($id);
			return $this->checkForObjectByNonPrimaryIdentifier($class, $identifier, $value);
		}
		return false;
	}

	private function checkForObjectById($class, $id) {
		if(self::DEBUG)
			$this->debug->logSqlMessage('Checking for object '.$class.':id:'.$id.'...');
		if($this->objects[$class]['id'][$id]) {
			if(self::DEBUG)
				$this->debug->logSqlMessage('Retreiving object from :id');
			return $this->objects[$class]['id'][$id];
		}
		else if(self::DEBUG)
			$this->debug->logSqlMessage('Object not found');
		return false;
	}

	private function checkForObjectByNonPrimaryIdentifier($class, $identifier, $value) {
		$r = new ReflectionClass($class);
		if($identifiers = $r->getConstant('OBJECT_CACHE_IDENTIFIERS'))
			if(in_array($identifier, explode(',', $identifiers)) && $value) {
				if(self::DEBUG)
					$this->debug->logSqlMessage('Checking for object '.$class.':'.$identifier.':'.$value.'...');
				if($this->objects[$class][$identifier][$value]) {
					if(self::DEBUG)
						$this->debug->logSqlMessage('Retreiving object from :'.$identifier);
					return $this->objects[$class][$identifier][$value];
				}
				else if(self::DEBUG)
					$this->debug->logSqlMessage('Object not found');
			}
		return false;
	}

	public function isEnabled() {
		return self::ENABLED;
	}
}
?>