// ==UserScript==
// @name           Yahoo! Keyboard Search
// @namespace      com.clickontyler.com.hack.ykeyboard
// @include        http://search.yahoo.com/search?*
// ==/UserScript==

var the_pos = null;
var ignore  = false;
var caret   = "<strong style='font-size:200%;'>&#187;</strong> ";

var GM_JQ  = document.createElement('script');
GM_JQ.src  = 'http://s3.amazonaws.com/tylerhall/jquery-1.2.1.min.js';
GM_JQ.type = 'text/javascript';
document.getElementsByTagName('head')[0].appendChild(GM_JQ);

function GM_wait() {
    if(typeof unsafeWindow.jQuery == 'undefined') { window.setTimeout(GM_wait,100); }
	else { $ = unsafeWindow.jQuery; doJQuery(); }
}
GM_wait();

function doJQuery() {
	the_pos = $("#web ol li:first div:first");
	the_pos.prepend(caret);
	$("a:first", the_pos).get(0).focus();
	document.addEventListener('keypress', handler, true);
}


function handler(e) {
	if(e.which == 106 && !ignore) {
		new_pos = the_pos.parent().next();
		if(new_pos.length) {
			$("strong:first", the_pos).remove();
			the_pos = $("div:first", new_pos);
			the_pos.prepend(caret);
			$("a:first", the_pos).get(0).focus();
		} else { window.location = $("#pg-next").get(0).href; }
	} else if(e.which == 107 && !ignore) { // K
		new_pos = the_pos.parent().prev();
		if(new_pos.length) {
			$("strong:first", the_pos).remove();
			the_pos = $("div:first", new_pos);
			the_pos.prepend(caret);
			$("a:first", the_pos).get(0).focus();
		} else { window.location = $("#pg-prev").get(0).href; }
	}
	else if(e.which == 47) {
		$("#yschsp").focus().select();
		ignore = true;
	} else if(e.which == 0) {
		$("#yschsp").blur();
		ignore = false;
	}
}