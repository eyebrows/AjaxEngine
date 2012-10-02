<?php
//experimental class to make much quicker loads of huge lists of things with many DB calls
//imaging a list of company records, with columns for number of users, number of calls logged, number of notes logged, this that the other
//instead of pulling users/calls/notes/etc per-company, this pulls all users/calls/etc for all companies in the display, then injects them
//in to the relevant one. So there are way fewer hits on the database using this.
/*
//e.g. where $companies is an array of Company objects
ServiceManager::get('ListReferenceLoader')->loadInto($companies, array(
	new ListReference('User', 'company_id'),
	new ListReference('Call', 'spoke_to_company_id'),
	new ListReference('Note', 'company_id'),
));
*/
class Service_ListReferenceLoader {

	public function loadInto($sdbo_list, $references) {
		$ids = array_keys($sdbo_list);
		foreach($references as $reference) {
			foreach($sdbo_list as $id=>$null)
				$sdbo_list[$id]->prepareInjectionForClass($reference->getClass());
			$objects = $reference->getObjects($ids);
			if($objects)
				foreach($objects as $object)
					$sdbo_list[$object->getField($reference->getField())]->injectReferencedObject($object);
		}
		return $sdbo_list;
	}
}

class ListReference {

	private $class, $field, $objects;

	public function __construct($class, $field) {
		$this->class = $class;
		$this->field = $field;
	}

	public function getObjects($ids) {
		$where[$this->field] = $ids;
		$this->objects = call_user_func($this->class.'::fetchAll', $where);
		return $this->objects;
	}

	public function getClass() {
		return $this->class;
	}

	public function getField() {
		return $this->field;
	}
}
?>