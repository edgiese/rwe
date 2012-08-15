// javascript for the image management module
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentinally!

// does all setup for the page--called once at dom-ready
//-!setup
//-<jquery.js
function () {
	if (typeof(fg) == 'undefined')
		fg={};
	if (typeof(fg.rg) == 'undefined')
		fg.image={};

	// sets a list of values to a multiple sel box	
	fg.image.prv=function(imageid,scaled) {
		// get checkbox id from imageid
		var checkbox=$('#'+(scaled === 0 ? 'f_' : 's_')+imageid).get(0);
		if (checkbox.checked) {
			var cd={u:"QQproject",q:window.location.pathname,r:"QQaushort",v:"QQpage"};
			var url="QQsrcbaseajax.php";
			
			cd.s='previewimage';
			cd.t=0;
			cd.id=imageid;
			cd.scl=scaled;
			var options={url:url,data:cd,dataType:'script'};
			options.success=function(body,status) {
				checkbox.disabled=false;
				calcpos();
			};
			options.error=function(xhr,status,exception) {
				alert("network or software error occurred in getting image data. status:"+status+"  exception:"+exception);
				checkbox.disabled=false;
				calcpos();
			};
			$.ajax(options);
			checkbox.disabled=true;
		} else {
			$('#'+(scaled === 0 ? 'fir_' : 'sir_')+imageid).remove();
		}	
	};
	
	// inserts a new row after an image	
	fg.image.insert=function(imageid,scaled,imagehtml) {
		var row=$('#row_'+imageid);
		if (scaled !== 0) {
			var unsc=$('#fir_'+imageid);
			if (unsc.length > 0)
				row=unsc;
		}
		row.after(imagehtml);
	};
	
	// whenever anything is entered into filterstring, set 'byname'
	$('#<#QQaulong_filterstring#>').keypress(function(e) {
		$("input[value='byname']").get(0).checked=true;
	});


}//-}

