<?php
//the AjaxEngine serves as a router to send requests off to an appropriate AjaxObject, to be dealt with. it also handles formatting of
//simple responses of an AjaxObject in to the format required by the client side JS
abstract class AjaxEngine {
	protected $user, $return, $path;

//optionally takes $_GET['path'] passed in to it
	public function __construct($user, $path='') {
		$this->user = $user;
		if($path)
			$this->setPath($path);
	}

//isn't used externally atm, provided in case it needs to be ever, or if a child needs to extend it
	protected function setPath($path) {
		$this->path = explode('/', $path);
		if(!$this->path)
			$this->path = explode('/', $this->defaultPath());
	}

//the processPath() method, called from /ajax.php, is only defined in the extenders of this class - when it's done with its execution, it MUST
//call this method, which takes whatever output vars have been set, and does some stuff on them that the client side JS expects to see
	public function formatOutput() {
//if we're processing a file download request via ajax instead of normal data request
		if(DOWNLOAD_DATA)
			downloadData($this->return['html'], defined('DOWNLOAD_NAME')?DOWNLOAD_NAME:'data.dat');
		else if($this->return['html']) {
//the structure of the client side html is as a series of nested "containers", so only a minimal amount of html needs be sent down for
//each request. header stuff, and navigation stuff, needn't keep being sent. so for each of these "containers" we need to do some things
			if(is_array($this->return['html'])) {
				foreach($this->return['html'] as $container=>$content) {
					$funcs = array();
//look for any "onLoad" functions defined, so the clientside js knows to execute them after attaching the new html in to the document
//they must be uniquely named so we don't keep executing the same function if other parts of the page load separately
					$func_replace = 'function onLoad(';
					while(($p=strpos($this->return['html'][$container], $func_replace))!==false) {
						$funcs[] = 'onLoad_'.time().'_'.randstring(8);
						$this->return['html'][$container] = substr($this->return['html'][$container], 0, $p).'function '.$funcs[count($funcs)-1].'('.substr($this->return['html'][$container], $p+strlen($func_replace));
					}
					if(count($funcs))
						$this->return['js'][$container] = $funcs;
//"hashes" are used to determine if the content of a particular container has actually changed. if it hasn't, the clientside won't
//bother replacing it. this prevents navigations, with CSS classes changed localy by JS, from being replaced *if* the outer container
//should happen to be re-sent but with no actual changes
					$this->return['hashes'][$container] = md5($this->return['html'][$container]);
					$this->return['containers'][] = $container;
				}
			}
		}
		$this->return['version'] = VERSION;
		return $this->return;
	}
}
?>