<?php
//ajax.php is the endpoint which all ajax requests are sent to
require_once('config.php');

$return = array();

//when submiting $_FILES via <form enctype="multipart/form-data">, the return JSON must be enclosed in a <textarea> for jQuery.Form
//thus any method in an AjaxObject inheritor which processes such a form post MUST set $this->textarea = true, which makes its way back to this
$return['textarea'] = false;

if($_uid) {
	$me = User::fetch($_uid);
	$company = $me->getCompany();
//with ajax requests, a single param of "path" is used for any and all incoming requests, using / separator, like a directory system
	if($company->getType()==Business::STATE_USER) {
		$engine = new AjaxEngine_User($me, $_GET['path']);
		$return = $engine->processPath();
	}
	else
		$return['error'] = 'Sorry, I don\'t know how to handle you';
}
else
	$return['error'] = 'Sorry, I don\'t know how to handle you';

print ($return['textarea']?'<textarea>':'').json_encode($return).($return['textarea']?'</textarea>':'');
?>