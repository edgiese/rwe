// common filegen routines for edit modules
if (typeof(fg) == 'undefined')
	fg={};
fg.edit={};
fg.edit.xnum=0;
fg.edit.requesting=false;
fg.edit.chainrequest=false;

// trigger function called when server must be notified of changes
fg.edit.trigger=function() {
	if (fg.edit.requesting) {
		fg.edit.chainrequest=true;
		return;
	}	
	fg.edit.requesting=true;
	var cd={};
	cd.x=fg.edit.xnum;
	if (cd.x === 0) {
		cd.v=fg.edit.getfull();
	} else {
		cd.v=fg.edit.getincrement();
		if (cd.v === false) {
			fg.edit.requesting=false;
			fg.edit.chainrequest=false;
			return;		// values have not changed
		}	
	}
	var url=fg.edit.ajaxurl;// set by edit master
	cd.wx=parent.modalwatch.fg.edit.xnum;
	cd.u=fg.edit.project;// set by edit master	
	cd.q=fg.edit.requesturi;// set by edit master
	cd.r=fg.edit.aushort;	// set by edit master
	cd.s=3;					// TYPE_AJAXEDITOR
	cd.t=4;					// DISPLAY_AJAXONLY
	var options={url:url,data:cd,dataType:'script',type:'POST'};

	options.success=function(body,status) {
		fg.edit.requesting=false;
		if (fg.edit.chainrequest) {
			fg.edit.chainrequest=false;
			fg.edit.trigger();
		}	
	};
	options.error=function(xhr,status,exception) {
		fg.edit.requesting=false;
		fg.edit.chainrequest=false;
		alert("network or software error occurred editing. status:"+xhr.status+"  status text:"+xhr.statusText+"   exception:"+exception);
	};
	fg.edit.chainrequest=false;
	$.ajax(options);
};	

// preview toggle
fg.edit.clickpreview=function(cbitem) {
	if (cbitem.disabled)
		return;
	var url=fg.edit.ajaxurl;// set by edit master
	var cd={};
	cd.u=fg.edit.project;// set by edit master	
	cd.q=fg.edit.requesturi;// set by edit master
	cd.r=fg.edit.aushort;	// set by edit master
	cd.s=5;					// MODE_PREVIEWTOGGLE
	cd.t=4;					// DISPLAY_AJAXONLY
	if (cbitem.checked) {
		cd.v=1;
		// force a full upload of everything
		fg.edit.xnum=0;
		fg.edit.trigger();
	} else
		cd.v=0;
	cd.v=cbitem.checked ? 1 : 0;
	cbitem.disabled=true;
	var options={url:url,data:cd,dataType:'script'};
	options.success=function(body,status) {
		cbitem.disabled=false;
	};
	options.error=function(xhr,status,exception) {
		alert("network or software error occurred in adjusting preview. status:"+status+"  exception:"+exception);
		cbitem.disabled=false;
	};
	$.ajax(options);
};
