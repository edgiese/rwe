// filegen edit-related routines
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentinally!


// does setup for the ownerbar--called once at page load
//-!obsetup
//-:arraydef arraydef names of pages to add to page section of menu
//-<jquery.js
function () {
	$("#<#ownerbar_trigger#>").mouseover(function() {
		$("#<#ownerbar_menu#>").css("visibility","visible");
	});		
	$("#<#ownerbar_menu#>").bind("mouseleave",function() {
		$("#<#ownerbar_menu#>").css("visibility","hidden");
	});
	h1s=$("#<#ownerbar_menu#> h1");
	if (typeof($("#<#ownerbar_menu#>").get(0).obhibk) == "undefined") {
		$("#<#ownerbar_menu#>").get(0).obhibk=h1s.css("background-color");
		$("#<#ownerbar_menu#>").get(0).obhitxt=h1s.css("color");
	}	
	h1s.css("color",$("#<#ownerbar_menu#> h2").css("color"));
	h1s.css("background-color",$("#<#ownerbar_menu#> h2").css("background-color"));
	var linkitems=$('li.ownerbar_link');
	var pagelinks=new Array(arraydef);
	for (var i=0; i<linkitems.length; ++i)
		linkitems.get(i).oblink=pagelinks[i];
	delete pagelinks;
	linkitems.mouseover(function() {
		this.savedcolor=$(this).css("color");
		$(this).css("background-color",$("#<#ownerbar_menu#>").get(0).obhibk);
		$(this).css("color",$("#<#ownerbar_menu#>").get(0).obhitxt);
		$(this).css("cursor","pointer");
	});
	linkitems.mouseout(function() {
		$(this).css("background-color","transparent");
		$(this).css("color",this.savedcolor);
	});
	linkitems.click(function() {
		window.location=(this.oblink);		
	});
	delete linkitems;
}//-}

// does setup for watchers -- called once at page load.
//-!watchersetup
//-:watchid watchid watcher id
//-:requesturi requesturi
//-:bodyshort bodyshort shortname of body
//-<jquery.js
function () {
	if (typeof(fg) == 'undefined')
		fg={};
	if (typeof(fg.edit) == 'undefined')
		fg.edit={};
	fg.edit.watchit=function() {
		var cd={};
		cd.q='requesturi';
		cd.r='bodyshort';
		cd.s=4;		// MODE_AJAXWATCHER
		cd.t=4;		// DISPLAY_AJAXONLY
		cd.x=fg.edit.xnum;
		cd.w='watchid';
		var options={url:'QQsrcbaseedit.php',data:cd,dataType:'script'};
		$.ajax(options);
	};

	fg.edit.replace=function(ismodal,tagname) {
		if (ismodal) {
			// this is incredibly cheesy -- strip off html wrapper
			var i=0;
			var inner=fg.edit.lastval;
			while (inner.charAt(i) != '>') {
				++i;
			}	
			inner=inner.substr(i+1);
			i=inner.length-1;
			while (inner.charAt(i) != '<') {
				--i;
			}	
			inner=inner.substr(0,i);		
			top.modalwatch.document.getElementById(tagname).innerHTML=inner;
		} else {
			$('#'+tagname).replaceWith(fg.edit.lastval);
		}	
	};
	fg.edit.xnum=0;
	$("#ownerbar_veil").css($.browser.msie ? "filter" : "opacity",$.browser.msie ? "alpha(opacity=0)" : "0.0").width(5000).height(5000);
	fg.edit.watchit();
}//-}

// does setup for modal editor -- called once at setup
//-!modaleditsetup
//-:aushort auShortName short name of au being edited
//-:requesturi myrequesturi requested uri at edit time
//-<jquery.js
//-<fgeditlib.js
function () {
	fg.edit.ajaxurl='QQsrcbaseedit.php';	
	fg.edit.project='QQproject';
	fg.edit.requesturi='myrequesturi';
	fg.edit.aushort='auShortName';
}//-}

// does setup for text editor block
//-!texteditsetup
//-<fgeditlib.js
function () {
	fg.edit.lastSent=$('#edit_text').text();
	fg.edit.getfull=function() {
		var current=$('#edit_text').val();
		fg.edit.lastSent=current;
		return current;
	};
	// increments are in the format skipstart|deletecount|insertstring
	fg.edit.getincrement=function() {
		var current=$('#edit_text').val();
		var last=fg.edit.lastSent;
		if (current === last) {
			return false;
		}	
			
		var i=0;
		while (i<current.length-1 && i<last.length-1 && current.charAt(i) === last.charAt(i)) {
			++i;
		}
		if (i == current.length-1) {
			// i cannot be last.length.  we tested for that above.  current is smaller than last, with something deleted from end
			++i;
			var count=last.length-i;
			//alert('truncating:'+i.toString()+'|'+count.toString()+'|');
			fg.edit.lastSent=current;
			return i.toString()+'|'+count.toString()+'|';
		} else if (i == last.length-1) {
			// concatenation of new characters onto last
			++i;
			//alert('concatenating:'+i.toString()+'|0|'+current.substr(i));
			fg.edit.lastSent=current;
			return i.toString()+'|0|'+current.substr(i);
		}
		var jc=current.length-2;
		var jl=last.length-2;
		while (jc >= i && jl >= i && current.charAt(jc) === last.charAt(jl)) {
			jc--;
			jl--;
		}
		var dcount=jl-i+1;
		var insert=jc >= i ? current.substr(i,jc-i+1) : '';
		//alert('changing:'+i.toString()+'|'+dcount.toString()+'|'+insert);
		fg.edit.lastSent=current;
		return i.toString()+'|'+dcount.toString()+'|'+insert;
	};
	$('#edit_text').keyup(function() {
		fg.edit.trigger();
	});
}//-}
