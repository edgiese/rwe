// javascript for the hill country needs council resource guide
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentinally!


// does all setup for the page--called once at dom-ready
//-!setup
//-<jquery.js
function () {
	if (typeof(fg) == 'undefined')
		fg={};
	if (typeof(fg.rg) == 'undefined')
		fg.rg={};

	// sets a list of values to a multiple sel box	
	fg.rg.setlistitems=function(list,names) {
		var i=0;
		var newhtml='';
		names.sort();
		if (names.length > 0) {
			for (i=0; i<names.length; ++i) {
				newhtml=newhtml+'<option value="'+names[i]+'">';
				names[i].replace(/_/g,' ');
				newhtml=newhtml+names[i]+'</option>';
			}
		} else {
			newhtml='<option value="?">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
		}
		$(list).html(newhtml);
	};
	
	// moves selected items from one list box to another	
	fg.rg.moveselitems=function(srclist,destlist) {
		var i=0;
		var srcnames=new Array();
		var destnames=new Array();
		
		for (i=0; i<destlist.options.length; ++i) {
			if (destlist.options[i].value != '?')
				destnames[destnames.length]=destlist.options[i].value;
		}	

		for (i=0; i<srclist.options.length; ++i) {
			if (srclist.options[i].value != '?') {
				if (srclist.options[i].selected)
					destnames[destnames.length]=srclist.options[i].value;
				else	
					srcnames[srcnames.length]=srclist.options[i].value;
			}
		}
		fg.rg.setlistitems(srclist,srcnames);
		fg.rg.setlistitems(destlist,destnames);
	};

	// moves selected items from one list box to another	
	fg.rg.sethiddenval=function(name,srclist) {
		var i=0;
		var srcnames=new Array();
		var valstring='';
		var separator='';
		
		for (i=0; i<srclist.options.length; ++i) {
			if (srclist.options[i].value != '?') {
				valstring=valstring+separator+srclist.options[i].value;
				separator=' ';
			}	
		}
		$("#<#QQaulong#>").append('<input type="hidden" name="'+name+'" value="'+valstring+'" />');
	};
	
	
	fg.rg.edit=function(idtoedit) {
		if (fg.rg.editing) {
			alert("You must finish or cancel your current edits before editing or inserting something else.");
			return;
		}
		fg.rg.editing=true;
	
		var editform=$('#<#QQaulong_editdiv#>').get(0);
		var id=idtoedit.toString();
		editform.editedid=id;
		$('#<#QQaulong_edit_editedid#>').val(id);
		if (id == '0') {
			// clear form fields
			$('#<#QQaulong_edit_title#>,#<#QQaulong_edit_desc#>,#<#QQaulong_edit_keywords#>,#<#QQaulong_edit_notes#>').val('');
			// move after the insert button and hide that
			$('#insert_button').after($(editform)).css('display','none');
			$('#<#QQaulong_editdiv#>').css('display','inline');
			$('#<#QQaulong_edit_changedesc#>,#<#QQaulong_edit_changedesc_label#>').css('display','none');
			calcpos();
		} else {
			var cd={u:"QQproject",q:window.location.pathname,r:"QQaushort",v:"QQpage"};
			var url="QQsrcbaseajax.php";
			
			cd.s='editsetup';
			cd.t=0;
			cd.id=id;

			var options={url:url,data:cd,dataType:'html'};
			options.success=function(formhtml,status) {
				var editspec='#rep_'+id;
				var edited=$(editspec).get(0);

				// update the form with the form that we downloaded (with values in place)				
				$('#<#QQaulong_editdiv#>').html(formhtml);
				$("#<#QQaulong_edit_cancel#>").click(fg.rg.editcancel);
				
				// move the edit form after this id and display it.  hide the original which will redisplay on cancel
				$(editspec).after(editform).css('display','none');
				$('#<#QQaulong_editdiv#>').css('display','inline');
				calcpos();
			};
			options.error=function(xhr,status,exception) {
				alert("network or software error occurred in getting edit data. status:"+status+"  exception:"+exception);
				fg.rg.editing=false;
			};
			// this ajax call will set the form variables for editing:
			$.ajax(options);
		}
	};
	
	fg.rg.remove=function(id) {
		if (fg.rg.editing) {
			alert("You must finish or cancel your current edits before deleting anything.");
			return;
		}
		if (confirm("Warning! You are about to delete a resource guide entry.  There is no 'undo' for this action.  OK to proceed, Cancel to abort.")) {
			var params={q:window.location.pathname, r:'QQaushort', s:'', t:0, u:'QQproject', delid:id};
			newlocation='QQsrcbaseform.php?'+$.param(params);
			document.location=newlocation;
		}
	};
	fg.rg.editing=false;
	
	$("#<#QQaulong_addfilter#>").replaceWith('<input id="<#QQaulong_addfilter#>" type="button" value="<<" onclick="fg.rg.moveselitems($(\'#<#QQaulong_keywords#>\').get(0),$(\'#<#QQaulong_filters#>\').get(0));">');
	$("#<#QQaulong_removefilter#>").replaceWith('<input id="<#QQaulong_removefilter#>" type="button" value=">>" onclick="fg.rg.moveselitems($(\'#<#QQaulong_filters#>\').get(0),$(\'#<#QQaulong_keywords#>\').get(0));">');
	$("#<#QQaulong_addexclusion#>").replaceWith('<input id="<#QQaulong_addexclusion#>" type="button" value=">>" onclick="fg.rg.moveselitems($(\'#<#QQaulong_keywords#>\').get(0),$(\'#<#QQaulong_exclusions#>\').get(0));">');
	$("#<#QQaulong_removeexclusion#>").replaceWith('<input id="<#QQaulong_removeexclusion#>" type="button" value="<<" onclick="fg.rg.moveselitems($(\'#<#QQaulong_exclusions#>\').get(0),$(\'#<#QQaulong_keywords#>\').get(0));">');
	
	// add submit routine to the form to update exclusions and filters based on list box
	$("#<#QQaulong#>").submit(function() {
		fg.rg.sethiddenval('jsfilters',$("#<#QQaulong_filters#>").get(0));
		fg.rg.sethiddenval('jsexclusions',$("#<#QQaulong_exclusions#>").get(0));
		return true;
	});

	
	// function to cancel the edit
	fg.rg.editcancel=function() {
		var editform=$('#<#QQaulong_editdiv#>').get(0);
		var editspec=(editform.editedid == '0') ? '#insert_button' : '#rep_'+editform.editedid;			
		var edited=$(editspec).get(0);
		$(edited).css('display','inline');
		$(editform).css('display','none');
		fg.rg.editing=false;
		calcpos();
	};
	
	// add the code to the cancel button of the editing form
	$("#<#QQaulong_edit_cancel#>").click(fg.rg.editcancel);

}//-}

