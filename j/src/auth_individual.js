// authorization module for individuals
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentinally!

// selects all of the items in a multiple-sel listbox 
//-!selectall
//-:listbox listbox long name of listbox to select all in
//-<jquery.js
function () {
	$("#<!listbox!> option:not(selected)").attr('selected','selected');
}//-}

// updates the authorization items based on selections in form 
//-!updatedisplay
//-:users listusers long name of listbox containing users
//-:functions listfunctions long name of listbox containing functions
//-:data outputdiv div to hold output text
//-+/$ auth_individual::setindividualcheck
//-+/$ auth_individual::setindividualedit
//-<jquery.js
function () {
	var cd={};
	cd.users='';
	var separator='';
	$("#<!listusers!> option:selected").each(function() {
		cd.users += separator + $(this).attr('value');
		separator='|';
	});
	cd.functions='';
	separator='';
	$("#<!listfunctions!> option:selected").each(function() {
		cd.functions += separator + $(this).attr('value');
		separator='|';
	});
	cd.sortby=$("input:checked").attr("value");
	cd.u="QQproject";
	cd.q=window.location.pathname;
	cd.r="QQaushort";
	cd.s='settable';
	cd.t=0;
	cd.v="QQpage";
	var options={url:'QQsrcbaseajax.php',data:cd,dataType:'script'};
	$.ajax(options);
	$("#<!outputdiv!>").html('updating display...');
}//-}


// sets output div to output 
//-!setdata
//-:divtag divtag tag of div
//-:setdata setdata data to set to
//-<jquery.js
function () {
	$("#<!divtag!>").html('setdata');
	calcpos();
}//-}


// sets up the checkbox routine called by all checkboxes on clicking 
//-!setindividualcheck
//-<fglib.js
//-<jquery.js
function () {
	fg.auth_individual_check = function(cbitem) {
		cd={};
		cd.itemid=cbitem.id;
		cd.itemchecked=cbitem.checked;
		cd.u="QQproject";
		cd.q=window.location.pathname;
		cd.r="QQaushort";
		cd.s='setcheck';
		cd.t=0;
		cd.v="QQpage";
		var options={url:'QQsrcbaseajax.php',data:cd,dataType:'script'};
		$('#'+cbitem.id+'_span').text('updating');
		cbitem.disabled=true;
		$.ajax(options);
	};
}//-}

// sets up the edit routine called by all text fields on changing 
//-!setindividualedit
//-<fglib.js
//-<jquery.js
function () {
	fg.auth_individual_edit = function(txtitem) {
		cd={};
		cd.itemid=txtitem.id;
		cd.itemvalue=txtitem.value;
		cd.u="QQproject";
		cd.q=window.location.pathname;
		cd.r="QQaushort";
		cd.s='setedit';
		cd.t=0;
		cd.v="QQpage";
		var options={url:'QQsrcbaseajax.php',data:cd,dataType:'script'};
		$('#'+txtitem.id+'_span').text('updating');
		txtitem.disabled=true;
		$.ajax(options);
	};
}//-}

// restores check box to normal states after successful update 
//-!checkboxupdatesuccess
//-:checkid checkid id of checkbox
//-:spanid spanid id of span containing message
//-<jquery.js
function () {
	$("#checkid").get(0).disabled=false;
	$("#spanid").text("");
}//-}

// restores edit to normal states after successful update 
//-!editupdatesuccess
//-:editid editid id of checkbox
//-:spanid spanid id of span containing message
//-<jquery.js
function () {
	$("#editid").get(0).disabled=false;
	$("#spanid").text("");
}//-}
