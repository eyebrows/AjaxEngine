//trigger the dashboard.ajax_engine.js to actually fire up its initialisation methods
$(document).ready(function() {
	$.ajaxSetup({cache:false});
	initialiseEngine();

	if(NOTIFICATIONS)
		fetchNotifications();
});

//any custom functions would then go in here, although most would be per-container-content (e.g. one to fetch "news items" if a container with
//a boxout for latest news was loaded) and thus would actually be set in <script> blocks in the relevant AjaxObject::method() so the functions stayed
//together with the data/html it was related to, rather than being "global" in here
