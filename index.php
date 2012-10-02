<?php
//all endpoints (points of entry in to "the system", such as index.php, scripts third parties POST leads to, the ajax.php file which
//responds to ajax requests, cron scripts, so on and so on) should have one single include: this one; keeps things simple
//it defines a few constants based on our environment, includes autoloader, db connection, user auth, and calls session_start()
require_once('config.php');

//an authenticated user; we need to determine which type of user, and load the correct layout template
if($_uid) {

//all objects, such as User, representing stuff from the DB, extend StandardDBObject, which provides a lot of functionality
	$me = User::fetch($_uid);
	$company = $me->getCompany();
//load a usertype-specific template and set a usertype-specific AjaxEngine (primarily a router class for ajax requests) up
	if($company->getType()==Business::STATE_USER) {
		$template = 'ajax.user';
		$engine = new AjaxEngine_User($me);
	}
//  ...
}
else {
//legacy code to deal with display of non-logged in users (standard front end of the website) would be here
	$template = 'site';
}

//Load the template
ob_start();
include(ROOT.'/templates/'.$template.'.php');
$_ = ob_get_contents();
ob_end_clean();

print $_;
?>