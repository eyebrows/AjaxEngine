<?php
//a class to encapsulate a single point of reference for several different things, or "services", which might be needed at various points
//in the codebase, such a debug, sql logging, or persistent "background" things like an Object Cachers
//is only instantiated inside its own static getService method, in to a global variable, which while generally "a bad thing" works here
//as a simple way of getting what's needed
class ServiceManager {

	private $objects=array();

	public function __construct() {
		$this->initServices();
	}

	public function initServices() {
		$this->addService('debug', new Service_Debug());
		$this->addService('mysql', new Service_MysqlWrapper());
		$this->addService('object_cache', new Service_ObjectCache());
		$this->addService('ListReferenceLoader', new Service_ListReferenceLoader());
	}

	public function addService($name, $object) {
		$this->objects[$name] = $object;
	}

	public function getServiceObject($name) {
		return $this->objects[$name];
	}

//Any Service which itself needs a local copy of a Service (e.g. ObjectCache needing a Debug) should set it in init() and *NOT* its __construct()
//because that causes an infinite loop
	public function init() {
		foreach($this->objects as $key=>$object)
			if(method_exists($object, 'init'))
				$object->init();
	}

//because shorter
	public static function get($name) {
		return self::getService($name);
	}

	public static function getService($name) {
		global $serviceManager;
		if(!$serviceManager) {
			$serviceManager = new ServiceManager();
			$serviceManager->init();
		}
		return $serviceManager->getServiceObject($name);
	}
}
?>