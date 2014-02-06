// Calcpos routines
// NOTE:  all of these snippets must also appear in a table in style/style_position.php.
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentinally!

/////////////// positioning snippets:

///////// the following set left and top regardless of width or height of self:

// sets x to a percentage of the parent
// DEPENDENCIES:  parent width
//-!leftToParent
//-:longname longname item to manipulate
//-:parentpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("left",Math.floor(67*($("#<#longname#>").offsetParent().width())/100+0.5+(44))+"px");	
}//-}

// sets y to a percentage of the parent
// DEPENDENCIES:  parent height
//-!topToParent
//-:longname longname item to manipulate
//-:parentpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("top",Math.floor(67*($("#<#longname#>").offsetParent().height())/100+0.5+(44))+"px");	
}//-}

// sets x to a percentage of the WINDOW
// DEPENDENCIES:  window width
//-!leftToWindow
//-:longname longname item to manipulate
//-:windowpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("left",Math.floor(67*($(window).width())/100+0.5+(44))+"px");	
}//-}

// sets y to a percentage of the WINDOW
// DEPENDENCIES:  window height
//-!topToWindow
//-:longname longname item to manipulate
//-:windowpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("top",Math.floor(67*($(window).height())/100+0.5+(44))+"px");	
}//-}

// sets x to a percentage of an object
// DEPENDENCIES:  object x, object width
//-!leftToObject
//-:longname longname item to manipulate
//-:object object ... to tie to
//-:objectpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("left",Math.floor($("#<#object#>").position().left+67*($("#<#object#>").width())/100+0.5+(44))+"px");	
}//-}

// sets y to a percentage of an object
// DEPENDENCIES:  object y, object width
//-!topToObject
//-:longname longname item to manipulate
//-:object object ... to tie to
//-:objectpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("top",Math.floor($("#<#object#>").position().top+67*($("#<#object#>").height())/100+0.5+(44))+"px");	
}//-}

// sets top to the maximum of two different objects
// DEPENDENCIES: height and width of objects
//-!topToMaxOf2
//-:longname longname of item to manipulate
//-:object1 object1 ... first of 2
//-:offset1 11 offset from first bottom
//-:object2 object2 ... second of 2
//-:offset2 22 offset from second bottom
//-<jquery.js
function () {
	var topmax=$("#<#object1#>").position().top+$("#<#object1#>").height()+(11);
	topmax=Math.max(topmax,$("#<#object2#>").position().top+$("#<#object2#>").height()+(22));
	$("#<#longname#>").css("top",topmax+"px");
	delete topmax;	
}//-}

// sets top to the maximum of three different objects
// DEPENDENCIES: height and width of objects
//-!topToMaxOf3
//-:longname longname of item to manipulate
//-:object1 object1 ... first of 3
//-:offset1 11 offset from first bottom
//-:object2 object2 ... second of 3
//-:offset2 22 offset from second bottom
//-:object3 object3 ... third of 3
//-:offset3 33 offset from third bottom
//-<jquery.js
function () {
	var topmax=$("#<#object1#>").position().top+$("#<#object1#>").height()+(11);
	topmax=Math.max(topmax,$("#<#object2#>").position().top+$("#<#object2#>").height()+(22));
	topmax=Math.max(topmax,$("#<#object3#>").position().top+$("#<#object3#>").height()+(33));
	$("#<#longname#>").css("top",topmax+"px");
	delete topmax;	
}//-}

// sets top to the maximum of four different objects
// DEPENDENCIES: height and width of objects
//-!topToMaxOf4
//-:longname longname of item to manipulate
//-:object1 object1 ... first of 4
//-:offset1 11 offset from first bottom
//-:object2 object2 ... second of 4
//-:offset2 22 offset from second bottom
//-:object3 object3 ... third of 4
//-:offset3 33 offset from third bottom
//-:object4 object4 ... fourth of 4
//-:offset4 44 offset from fourth bottom
//-<jquery.js
function () {
	var topmax=$("#<#object1#>").position().top+$("#<#object1#>").height()+(11);
	topmax=Math.max(topmax,$("#<#object2#>").position().top+$("#<#object2#>").height()+(22));
	topmax=Math.max(topmax,$("#<#object3#>").position().top+$("#<#object3#>").height()+(33));
	topmax=Math.max(topmax,$("#<#object4#>").position().top+$("#<#object4#>").height()+(44));
	$("#<#longname#>").css("top",topmax+"px");
	delete topmax;	
}//-}


///////// the following set left and top using self width, allowing for centering or right justification

// sets a percentage along x to a percentage of the parent
// DEPENDENCIES:  self width, parent width
//-!xPercentToParent
//-:longname longname item to manipulate
//-:selfpercent 57 anchor position on self
//-:parentpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("left",Math.max(0,Math.floor(67*($("#<#longname#>").offsetParent().width())/100-($("#<#longname#>").width())*57/100+0.5+(44)))+"px");	
}//-}

// sets a percentage along y to a percentage of the parent
// DEPENDENCIES:  self height, parent height
//-!yPercentToParent
//-:longname longname item to manipulate
//-:selfpercent 57 anchor position on self
//-:parentpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("top",Math.max(0,Math.floor(67*($("#<#longname#>").offsetParent().height())/100-($("#<#longname#>").height())*57/100+0.5+(44)))+"px");	
}//-}

// sets a percentage along x to a percentage of the WINDOW
// DEPENDENCIES:  self width, window width
//-!xPercentToWindow
//-:longname longname item to manipulate
//-:selfpercent 57 anchor position on self
//-:windowpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("left",Math.max(0,Math.floor(67*($(window).width())/100-($("#<#longname#>").width())*57/100+0.5+(44)))+"px");	
}//-}

// sets a percentage along y to a percentage of the WINDOW
// DEPENDENCIES:  self height, window height
//-!yPercentToWindow
//-:longname longname item to manipulate
//-:selfpercent 57 anchor position on self
//-:windowpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("top",Math.max(0,Math.floor(67*($(window).height())/100-($("#<#longname#>").height())*57/100+0.5+(44)))+"px");	
}//-}

// sets a percentage along x to a percentage of an object
// DEPENDENCIES:  self width, object x, object width
//-!xPercentToObject
//-:longname longname item to manipulate
//-:selfpercent 57 anchor position on self
//-:object object ... to tie to
//-:objectpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("left",Math.max(0,Math.floor($("#<#object#>").position().left+67*($("#<#object#>").width())/100-($("#<#longname#>").width())*57/100+0.5+(44)))+"px");	
}//-}

// sets a percentage along y to a percentage of an object
// DEPENDENCIES:  self height, object y, object width
//-!yPercentToObject
//-:longname longname item to manipulate
//-:selfpercent 57 anchor position on self
//-:object object ... to tie to
//-:objectpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css("top",Math.max(0,Math.floor($("#<#object#>").position().top+67*($("#<#object#>").height())/100-($("#<#longname#>").height())*57/100+0.5+(44)))+"px");	
}//-}


///////////// The following set width and height to effectively allow the setting of right and bottom to external anchors

// effectively sets "right" to a percentage of the parent
// DEPENDENCIES:  self x, parent width
//-!rightToParent
//-:longname longname item to manipulate
//-:parentpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").width(Math.max(0,Math.floor(67*($("#<#longname#>").offsetParent().width())/100+0.5+(44))-$("#<#longname#>").position().left));	
}//-}

// effectively sets "bottom" to a percentage of the parent
// DEPENDENCIES:  self y, parent height
//-!bottomToParent
//-:longname longname item to manipulate
//-:parentpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").height(Math.max(0,Math.floor(67*($("#<#longname#>").offsetParent().height())/100+0.5+(44))-$("#<#longname#>").position().top));	
}//-}

// effectively sets "right" to a percentage of the window
// DEPENDENCIES:  self x, window width
//-!rightToWindow
//-:longname longname item to manipulate
//-:windowpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").width(Math.max(0,Math.floor(67*($(window).width())/100+0.5+(44))-$("#<#longname#>").position().left));	
}//-}

// effectively sets "bottom" to a percentage of the window
// DEPENDENCIES:  self y, window height
//-!bottomToWindow
//-:longname longname item to manipulate
//-:windowpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").height(Math.max(0,Math.floor(67*($(window).height())/100+0.5+(44))-$("#<#longname#>").position().top));	
}//-}

// effectively sets "right" to a percentage of an object
// DEPENDENCIES:  self x, object x, object width
//-!rightToObject
//-:longname longname item to manipulate
//-:object object ... to tie to
//-:objectpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").width(Math.max(0,Math.floor($("#<#object#>").position().left+67*($("#<#object#>").width())/100+0.5+(44))-$("#<#longname#>").position().left));	
}//-}

// effectively sets "bottom" to a percentage of an object
// DEPENDENCIES:  self y, object y, object width
//-!bottomToObject
//-:longname longname item to manipulate
//-:object object ... to tie to
//-:objectpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").height(Math.max(0,Math.floor($("#<#object#>").position().top+67*($("#<#object#>").height())/100+0.5+(44))-$("#<#longname#>").position().top));	
}//-}

/////////////////////////// The following set width and height as a percentage of parent, window, or object
// sets x to a percentage of the parent
// DEPENDENCIES:  parent width
//-!widthFromParent
//-:longname longname item to manipulate
//-:parentpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").width(Math.floor(67*($("#<#longname#>").offsetParent().width())/100+0.5+(44))+"px");	
}//-}

// sets y to a percentage of the parent
// DEPENDENCIES:  parent height
//-!heightFromParent
//-:longname longname item to manipulate
//-:parentpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").height(Math.floor(67*($("#<#longname#>").offsetParent().height())/100+0.5+(44))+"px");	
}//-}

// sets x to a percentage of the WINDOW
// DEPENDENCIES:  window width
//-!widthFromWindow
//-:longname longname item to manipulate
//-:windowpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").width(Math.floor(67*($(window).width())/100+0.5+(44))+"px");	
}//-}

// sets y to a percentage of the WINDOW
// DEPENDENCIES:  window height
//-!heightFromWindow
//-:longname longname item to manipulate
//-:windowpercent 67 starting percentage
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").height(Math.floor(67*($(window).height())/100+0.5+(44))+"px");	
}//-}

// sets width to a percentage of an object's
// DEPENDENCIES:  object x, object width
//-!widthFromObject
//-:longname longname item to manipulate
//-:object object ... to tie to
//-:objectpercent 67 scale of object to calculate width from
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").width(Math.floor(67*($("#<#object#>").width())/100+0.5+(44))+"px");	
}//-}

// sets height to a percentage of an object's
// DEPENDENCIES:  object y, object height
//-!heightFromObject
//-:longname longname item to manipulate
//-:object object ... to tie to
//-:objectpercent 67 scale of object to calculate width from
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").height(Math.floor(67*($("#<#object#>").height())/100+0.5+(44))+"px");	
}//-}

// sets minimum height to a sibling's height relative to the two positions
// DEPENDENCIES:  object y, object height, self y
//-!minHeightFromSibling
//-:longname longname item to manipulate
//-:sibling sibling ... to tie to
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	$("#<#longname#>").css('min-height',$("#<#sibling#>").height()+$("#<#sibling#>").position().top-$("#<#longname#>").position().top+(44)+"px");	
}//-}

// technically does not set height, but makes one child relative so parent's height will be calculated automatically from greater of two absolutely positioned children
// DEPENDENCIES:  object y, object height
//-!heightFrom2Children
//-:longname longname item to manipulate
//-:object1 object1 ... to tie to
//-:object2 object2 ... to tie to
//-<jquery.js
function () {
	if ($("#<#object1#>").height() > $("#<#object2#>").height()) {
		// if we're changing height of parent, we need to recalculate sizes
		if ($("#<#object1#>").css("position") == "absolute") {
			$("#<#object1#>").css("position","relative");
			calcpos();
		}	 
		$("#<#object2#>").css("position","absolute");
	} else {
		if ($("#<#object2#>").css("position") == "absolute") {
			$("#<#object2#>").css("position","relative");
			calcpos(); 
		}	
		$("#<#object1#>").css("position","absolute");
	}
}//-}

// sets minimum height to tallest of 3 children
// DEPENDENCIES:  3 object's y and h (NOTE: will work best if all 3 children have same starting y)
//-!contain3Children
//-:longname longname item to manipulate
//-:c1 cfirst ... to tie to
//-:c2 csecond ... to tie to
//-:c3 cthird ... to tie to
//-:offset 44 offset in pixels
//-<jquery.js
function () {
	var minheight=Math.max($("#<#cfirst#>").height()+$("#<#cfirst#>").position().top,$("#<#csecond#>").height()+$("#<#csecond#>").position().top);
	minheight=Math.max(minheight,$("#<#cthird#>").height()+$("#<#cthird#>").position().top);
	$("#<#longname#>").css('min-height',minheight+(44)+"px");	
}//-}

// sets minimum height to tallest of 2 children with possibly different starting ys
// DEPENDENCIES:  3 object's y and h
//-!contain2Children
//-:longname longname item to manipulate
//-:c1 cfirst ... to tie to
//-:offset1 444 offset in pixels
//-:c2 csecond ... to tie to
//-:offset2 555 offset in pixels
//-<jquery.js
function () {
	var minheight=Math.max($("#<#cfirst#>").height()+$("#<#cfirst#>").position().top+(444),$("#<#csecond#>").height()+$("#<#csecond#>").position().top+(555));
	$("#<#longname#>").css('min-height',minheight+"px");	
}//-}
 
