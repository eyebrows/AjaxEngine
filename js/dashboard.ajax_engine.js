//a general engine for ajax-heavy content systems, leveraged by usertype-specific additions to do all sorts of stuff
//note that it isn't even fired up from this file, but from dashboard.user.js

//setup some of the basics
function initialiseEngine() {
	ajaxEngineSettings();
	registerHashchangeListener();
	initialiseFileUploads();
	initialiseLinks();
	initialiseForms();

//it uses jquery and its BBQ plugin for managing the browser's history properly and making fragment navigation easier to handle
	var path = $.bbq.getState('_');
	if(!path || path=='')
		$.bbq.pushState('#_='+DEFAULT_PATH);//defined in the template/file.php, ultimately being set in the usertype's AjaxEngine
	else
		$(window).trigger('hashchange');//trigger an initial hashchange event to load whatever's present in the fragment on initial pageload
}

//a helper function for the parts of the engine that do things with links - these are all selectors of the different elements that can be "links"
function ajaxEngineSettings() {
	ajaxEngineSettings.link_patterns = ['a', 'input.button', 'div.button', 'div.virtual_button'];
}

function registerHashchangeListener() {
	$(window).bind('hashchange', function(event) {
		processHashchange(event);
	});
}

function processHashchange(event) {
	if(typeof processHashchange.last=='undefined')
		processHashchange.last = [];
	if(typeof processHashchange.custom_funcs=='undefined')
		processHashchange.custom_funcs = [];
	var path = $.bbq.getState('_');
	if(!path)
		path = DEFAULT_PATH;
//check if the path has actually changed before firing off a request for the data associated with the new path
	if(path!=processHashchange.last['_']) {
		hideMessages();
//"queuedThings" is because when we do a hashchange we clear any Things being displayed, so if something returns from the server with a new path
//and an error message, we need to queue the error message to display *after* the path hashchange has been fired
		queuedThings();
		if(typeof execOnHashChange=='function')
			execOnHashChange(path);
		requestAjax({path:path, fragment:$.bbq.getState()});
	}
	processHashchange.last['_'] = path;
	for(var i in processHashchange.custom_funcs)
		processHashchange.custom_funcs[i].call(this, event);
}

//a wrapper for jquery's ajax functions, encapsulating some of the core things the "ajax engine" overall, provides by default to all other code
//wishing to send/receive stuff via ajax
//firstly, accepts an object of params, and a callback function for the success case
function requestAjax(params, func) {
//displays a loading animation so the user knows something is happening
	loadingAnimation(true);
	$.getJSON('ajax.php', params, function(data) {
//data's back, so disable loading animation
		loadingAnimation(false);
		if(data) {
//if the data that came back included a "path", stick it into the fragment (which will trigger a hashchange event)
			if(data.path) {
				queueThing(data);
				$.bbq.pushState({_:data.path});
			}
			else {
//display a message to the user, such as a success or error message based on the event they tried to perform
				displayThing(data);
			}
//if some html came back, go and figure out which container(s) to stick it in
			if(data.html && data.containers)
				replaceLeafContainerContents(data);
			if(func)
				func.call(this, data);
//version checking in here, to prompt user to reload
		}
	});
}

//wrapper for jquery's ajaxFOrm method, in basically the same way as requestAjax() is for vanilla ajax calls. also contains support for doing file
//downloads, such as exporting data from a list of things, via ajax, without redirecting the browser anywhere
function standardFormPrepare(form, func_success) {
	$(form).submit(function() {
		if(this.elements['download_data'] && this.elements['download_data'].value==1) {
			if(!$('iframe.download_data').length)
				$('body').append($('<iframe name="download_data">').css({display:'none'}));
			this.attr({target:'download_data'});
		}
		loadingAnimation(true);
	}).ajaxForm({dataType:'json', success:function(data) {
		loadingAnimation(false);
		if(data) {
			if(data.path) {
				queueThing(data);
				$.bbq.pushState({_:data.path});
			}
			else if(data.html && data.containers) {
				displayThing(data);
				replaceLeafContainerContents(data);
			}
			else
				displayThing(data);
		}
		if(func_success)
			func_success.call(this, data);
	}});
}

//this is how all forms using this engine should actually be submitted
function standardFormSubmit(form, func_success) {
	standardFormPrepare(form, func_success);
	$(form).submit();
}

//"data" comes back from an ajax request and contains an element called "containers" which must be an *ordered* list of which containers
//in the "html" array to insert in to the document. hence this can just shoot through them in order with zero complications
function replaceLeafContainerContents(data, container_index) {
	if(!container_index)
		container_index = 0;
	var current = data.containers[container_index];
	if($('#'+current+'_container').length) {
		if($('#'+current+'_container').attr('hash')!=data.hashes[current]) {
			$('#'+current+'_container > .fader').fadeOut('fast', function() {
				$('#'+current+'_container > .fader').html(data.html[current]);
				$('#'+current+'_container').attr({'hash':data.hashes[current]});
				initialiseLinks('#'+current+'_container > .fader');
				initialiseForms('#'+current+'_container > .fader');
				$('#'+current+'_container > .fader').fadeIn('fast', function() {
					if(data.js && typeof data.js[current]!='undefined')
						for(j in data.js[current]) {
							var func = data.js[current][j];
							eval(func+'.call();');
						}
					if(data.containers[container_index+1])
						replaceLeafContainerContents(data, container_index+1);
				});
			});
		}
		else
			if(data.containers[container_index+1])
				replaceLeafContainerContents(data, container_index+1);
	}
}

//any links of the form <a href="#_=path/here"> will be parsed by this so their onclick pushes in to the fragment, causing a hashchange, and in term
//triggering a requestAjax() to go get the associated data
function initialiseLinks(within, check_current) {
	var selectors = [];
	for(var i=0;i<ajaxEngineSettings.link_patterns.length;i++)
		selectors[selectors.length] = (within?within+' ':'')+ajaxEngineSettings.link_patterns[i];
	$(selectors.join(',')).not('.href').unbind('click').click(function() {
		var href = $(this).attr('href');
		if(href=='#reload') {
			$.bbq.removeState('question');
			window.location.reload();
		}
		else if(href=='#back')
			window.history.back();
		else {
			var question = $.bbq.getState('question');
			var fragment = $.deparam.fragment(href);
			if(question && !fragment['force'])
				displayThing({alert:'If you navigate away from this page, your in-progress question will be lost. <a href="'+href+'&force=true">Continue</a>, or open the link in a <a href="'+href+'" class="href" onclick="hideMessages();" target="_blank">New Window</a>?', seconds:12, highlight:true});
			else {
				$.bbq.pushState(fragment);
				$.bbq.removeState('force', 'question');
			}
		}
		return false;
	});
	if(check_current!==false)
		checkCurrentLinks();
}

//similarly, whenever any <form>s are loaded, make sure they behave in line with the engine
function initialiseForms(within) {
	$((within?within+' ':'')+'form').each(function() {
		standardFormPrepare(this);
	});
}

//adds/removes a couple classes from any link on the page which has a href that's a substring of the current path - so "current link" can be style
//differently even if it's not in a changed container and the html for it never actually gets reloaded
function checkCurrentLinks() {
	if($.bbq) {
		var path = $.bbq.getState('_');
		var selectors = [];
		for(var i=0;i<ajaxEngineSettings.link_patterns.length;i++)
			selectors[selectors.length] = ajaxEngineSettings.link_patterns[i];
		$(selectors.join(',')).each(function() {
			var href = $(this).attr('href');
			if(href && href.indexOf('#')!=-1) {
				var fragment = $.deparam.fragment(href);
				if(fragment['_']==path) {
					$(this).addClass('current_link');
					$(this).removeClass('current_path');
					$(this).blur();
				}
				else if(path && path.indexOf(fragment['_']+'/')==0) {
					$(this).addClass('current_path');
					$(this).removeClass('current_link');
				}
				else
					$(this).removeClass('current_link current_path');
			}
		});
	}
}

//a rather complex means of displaying a loading animation based on how many ajax requests we just fired off, so when they return it can be counted
//down and the loading animation will only disappear when the last one comes back. most scripts only request one thing at a time, mind
function loadingAnimation(display) {
	if(typeof loadingAnimation.count=='undefined')
		loadingAnimation.count = 0;
	if(display===true || (typeof display=='number' && display>0)) {
		loadingAnimation.count+=(display==true?1:display);
		$('#loading').not(':visible').fadeIn('fast');
	}
	else if(display===false || (typeof display=='number' && display==0)) {
		if(display===false)
			--loadingAnimation.count;
		else
			loadingAnimation.count = display;
		if(loadingAnimation.count<=0) {
			loadingAnimation.count = 0;
			$('#loading:visible').fadeOut('fast');
		}
	}
	$('#loading').attr({items:loadingAnimation.count});
}

//the rest of this file is for displaying graphical alerts to the user. it's rather legacy and hasn't been touched for a while
function queueThing(data) {
	if(typeof queuedThings.queue=='undefined')
		queuedThings.queue = 0;
	if(data.okay || data.alert || data.error)
		queuedThings.queue = data;
}

function queuedThings() {
	if(typeof queuedThings.queue=='undefined')
		queuedThings.queue = 0;
	if(queuedThings.queue!=0) {
		displayThing(queuedThings.queue);
		queuedThings.queue = 0;
	}
}

function displayThing(data) {
	if(data.okay)
		displayOkay(data);
	if(data.alert)
		displayAlert(data);
	if(data.error)
		displayError(data);
}

function hideMessages() {
	hideMessage('alert');
	hideMessage('okay');
	hideMessage('error');
}

function displayAlert(message) {
	displayMessage('alert', message.alert, message.seconds, message.persist, message.css_class, message.highlight);
}

function hideAlert() {
	hideMessage('alert');
}

function displayOkay(message) {
	displayMessage('okay', message.okay, message.seconds, message.persist, message.css_class, message.highlight);
}

function hideOkay() {
	hideMessage('okay');
}

function displayError(message) {
	displayMessage('error', message.error, message.seconds, message.persist, message.css_class, message.highlight);
}

function hideError() {
	hideMessage('error');
}

var hide_message = [];

function displayMessage(thing, message, seconds, persist, css_class, highlight) {
	var selector_block = '.block_messages'+(css_class?'.'+css_class:'')+' .message_'+thing;
	var selector_fixed = '.fixed_messages'+(css_class?'.'+css_class:'')+' .message_'+thing;
//	if(highlight)
//		message+='<a href="#" onclick="hideMessages();return false;" class="href close"><img src="images/dashboard/message.close.png" title="Close Message"></a>';
	$(selector_block+','+selector_fixed).html(message).attr({
		persist:persist?'true':'',
		highlight:highlight?'true':''
	});
	initialiseLinks(selector_block+','+selector_fixed);
	initialiseForms(selector_block+','+selector_fixed);
//	if(highlight)
//		$('#content_container .fader,#block_main .block_left,#block_top').animate({opacity:0.3}, 'fast');
	$(selector_block).slideDown('fast', function() {
		clearTimeout(hide_message[thing+':'+css_class]);
		if(!message.persist)
			hide_message[thing+':'+css_class] = setTimeout(function() {
				hideMessage(thing, css_class);
			}, seconds>0?1000*seconds:8000);
	});
	$(selector_fixed).fadeIn('fast', function() {
		clearTimeout(hide_message[thing+':'+css_class]);
		if(!message.persist)
			hide_message[thing+':'+css_class] = setTimeout(function() {
				hideMessage(thing, css_class);
			}, seconds>0?1000*seconds:8000);
	});
}

function hideMessage(thing, css_class) {
	var selector_block = '.block_messages'+(css_class?'.'+css_class:'')+' .message_'+thing;
	var selector_fixed = '.fixed_messages'+(css_class?'.'+css_class:'')+' .message_'+thing;
	var e_block = $(selector_block+':visible');
	var e_fixed = $(selector_fixed+':visible');
	if(e_block.attr('persist')!='true')
		e_block.slideUp('fast');
//	if(e_block.attr('highlight')=='true')
//		$('#content_container .fader,#block_main .block_left,#block_top').animate({opacity:1}, 'fast');
	if(e_fixed.attr('persist')!='true')
		e_fixed.fadeOut('fast');
//	if(e_fixed.attr('highlight')=='true')
//		$('#content_container .fader,#block_main .block_left,#block_top').animate({opacity:1}, 'fast');
	clearTimeout(hide_message[thing+':'+css_class]);
}

function goToByScroll(id) {
	$('html,body').animate({scrollTop:$('#'+id).offset().top}, 'slow');
}

function initialiseFileUploads() {
	$('input[type=file]').each(function() {
		$(this).parent().append(
			$('<a>').attr({'href':'#', 'class':'button theme_button_colour fakefile'}).html('Browse...')
		);
		this.parentNode.file = this;
		this.parentNode.onmousemove = function(e) {
			if(typeof e == 'undefined')
				e = window.event;
			if(typeof e.pageY == 'undefined' &&  typeof e.clientX == 'number' && document.documentElement) {
				e.pageX = e.clientX + document.documentElement.scrollLeft;
				e.pageY = e.clientY + document.documentElement.scrollTop;
			};

			var ox = oy = 0;
			var elem = this;
			if(elem.offsetParent) {
				ox = elem.offsetLeft;
				oy = elem.offsetTop;
				while(elem = elem.offsetParent) {
					ox += elem.offsetLeft;
					oy += elem.offsetTop;
				};
			};

			var x = e.pageX - ox;
			var y = e.pageY - oy;
			var w = this.file.offsetWidth;
			var h = this.file.offsetHeight;

			this.file.style.top = y - (h / 2)  + 'px';
			this.file.style.left = x - (w - 30) + 'px';
		};
	});
}
