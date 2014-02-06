// polling routine used to auto update pages during development
if (typeof(fg) == 'undefined')
	fg={};
fg.poll={};	

// trigger function called when server must be notified of changes
fg.poll.trigger=function() {
	var cd={};
	var url=fg.poll.url;// set by pagegen
	cd.u=fg.poll.proj;    // set by pagegen
	var options={url:url,data:cd,dataType:'script',type:'GET'};

	options.error=function(xhr,status,exception) {
		alert("network or software error occurred editing. status:"+xhr.status+"  status text:"+xhr.statusText+"   exception:"+exception);
	};
	$.ajax(options);
};

$(function() {fg.poll.trigger()});	
