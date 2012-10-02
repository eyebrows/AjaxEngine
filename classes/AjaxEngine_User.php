<?php
//a typical implementation of an AjaxEngine router class. The key method is processPath() which gets called in /ajax.php and passed some $this->path of
//the form "home/question/blah"
class AjaxEngine_User extends AjaxEngine {

	public function defaultPath($add_hash=false) {
		return ($add_hash?'#_=':'').'home/dashboard';
	}

//method containing menu buttons used in the usertype's template file, encapsulates different pages they can access
	public function getMenuButtons() {
		return array(
			'home'=>array(
				'name'=>'Dashboard Home',
			),
			'help'=>array(
				'name'=>'Help',
				'right'=>true,
			),
		);
	}

//core method which routes off any $this->path requests to the relevant AjaxObject::method()
	public function processPath() {
		switch($this->path[0]) {
			case 'home':
//in this instance multiple methods are called, as a user's "desktop" is comprised of several nested containers, some of which can have their content
//changed independently of others
				$object = new AjaxObject_User_Dashboard($this->user);
				$object->content_desktop();
				$object->content_desktop_documents();
				$object->content_home();
				switch($this->path[1]) {
					case 'question':
						$object->content_home_expert($this->path[1]);
						break;
					default:
						$object->content_home_dashboard();
						break;
				}
				break;
			default:
				$this->return['error'] = 'Sorry, I don\'t know how to handle "'.$_GET['path'].'"';
				break;
		}

//it is much prefered if the AjaxObject handling these requests sets its own $this->html etc, collected by AjaxObject::returnStuff(); however
//if needs be for the sake of getting this sorted, $this->return's params (defined in AjaxObject.returnStuff()) may be set expressly
//above by part of the switch statement, such as in the "default:" clause
		if($object && !$this->return)
			$this->return = $object->returnStuff();
		return $this->formatOutput();
	}
}
?>