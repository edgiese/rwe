// default filegen routines.  these are commonly used snippets.


// example snippet
//-!snippetname
//-:paramname paramsubstring arg element 'paramname' will be substituted for paramsubstring
//-<librarydependency.js
//-+/$filename::snippet puts snippet into specified file
//-+!filename::snippet adds snippet code onto the top of this snippet
//-+*event::snippet adds event snippet onto the same object as the one that was invoked for this one
//-+:event::routine adds object routine snippet onto the same object as the one that was invoked for this one
function () {
}//-}

// creates an cd object and seeds it with data for a call
//-!seedajaxdata
function () {
	cd={};
	cd.u="QQproject";
	cd.q=window.location.pathname;
	cd.r="QQaushort";
	cd.v="QQpage";
}//-}

// creates a routine to turn an overlay's invisibility on or off
//-!setoverlayroutine
//-:routinename routinename ::name of routine to call to turn overlay on and off
//-<jquery.js
function () {
	overlay=$('#<#QQaulong#>').get(0);
	overlay.oncount=0;
	routinename=function(onoff) {		
		if (onoff) {
			overlay.oncount++;
			$('#<#QQaulong#>').css("display","inline");
		} else {
			overlay.oncount=Math.max(0,overlay.oncount-1);
			if (overlay.oncount === 0)
				$('#<#QQaulong#>').css("display","none");
		}
	};
}//-}

