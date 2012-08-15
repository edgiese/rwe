// powermap routines
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentionally!

// sets up powermap arrays 
//-!setup
//-<jquery.js
//-<fglib.js
function () {
	var mydiv=$('#<#QQaulong#>').get(0);
	$(mydiv).css("background-image",'url(/dev/p/pchb/create/floorplan.jpg)');
	$('<div id="z2091">').css("position","absolute").css("left","39px").css("top","54px").width(77).height(17).mouseover(function() {
		$("#z2091").css("background-position","-39px -54px").css("background-image",'url(/dev/p/pchb/create/floorplanovly.jpg)');
		return True;
	}).mouseout(function() {
		$('#z2091').css("background-image","none");
		return True;
	}).appendTo('#<#QQaulong#>');

	
}//-}

