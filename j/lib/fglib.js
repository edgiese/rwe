if (typeof(fg) == 'undefined')
	var fg={};
	
fg.initlib=function(project,srcbase,hrefbase,page,domain,encoding) {
	fg.qq={};
	fg.qq.project=project;
	fg.qq.srcbase=srcbase;
	fg.qq.hrefbase=hrefbase;
	fg.qq.page=page;
	fg.qq.domain=domain;
	fg.qq.encoding=encoding;
};

fg.seedajaxopts=function(cd,aushort,state,transaction) {
	if (typeof(state) == 'undefined')
		state='';
	if (typeof(transaction) == 'undefined')
		transaction=0;
	cd.q=window.location.pathname;
	cd.r=aushort;
	cd.s=state;
	cd.t=transaction;
	cd.v=fg.qq.page;
	cd.u=fg.qq.project;
	url=fg.qq.encoding+fg.qq.srcbase+'ajax.php';
	return {url:url,data:cd,dataType:'script'};
}; 

if (typeof(fg.dlg) == 'undefined') {
	fg.dlg={};

	fg.dlg.current=null;
	fg.dlg.posel=null;
	
	fg.dlg.setoverlay=function(ovlylab) {
		if (typeof(fg.dlg.ovly) == 'undefined') {
			fg.dlg.ovly=$('#'+ovlylab).remove().get(0);
		}
	};
	fg.dlg.seticonurl=function(url) {
		fg.dlg.iconurl=url;
	};
	
	fg.dlg.showmodaldialog=function(dlglab) {
		if (fg.dlg.current !== null) {
			fg.dlg.hidemodaldialog();
		}
		fg.dlg.current=$('#'+dlglab);
		
		if (fg.dlg.current.length === 0) {
			alert('no dialog named '+dlglab+' found!');
			return;
		}	
		
		if (typeof(fg.dlg.ovly) == 'undefined') {
			fg.dlg.ovly=$('<div>').css('background-color','#000000').css('opacity','0.3').css('filter','alpha(opacity=30)').css('position','fixed').css('left','0px').css('top','0px').css('width','100%').css('height','100%').get(0);
		}
		$(fg.dlg.ovly).appendTo('body');
		var left=($(window).width()-fg.dlg.current.width())/2;
		var top=($(window).height()-fg.dlg.current.height())/2;
		fg.dlg.current.css('display','inline').css('position','fixed').css('left',left+'px').css('top',top+'px').insertAfter(fg.dlg.ovly);
	};
	
	fg.dlg.hidemodaldialog=function() {
		if (fg.dlg.current !== null) {
			fg.dlg.unpausedialog();
			fg.dlg.current.css('display','none');
			$(fg.dlg.ovly).remove();
			fg.dlg.current=null;
		}	
	};
	fg.dlg.pausedialog=function(message) {
		if (typeof(message) == 'undefined')
			message='';
		if (fg.dlg.current === null)
			return;
		if (fg.dlg.posel !== null) {
			fg.dlg.unpausedialog();
		}
		fg.dlg.posel=$('<div>').css('background-color','#ffffff').css('opacity','0.8').css('filter','alpha(opacity=80)').css('position','absolute').css('left','0px').css('top','0px').css('width','100%').css('height','100%');
		if (message !== '') {
			msgsel=$('<p>'+message+'</p>').css('font-family','verdana').css('font-size','14px').css('text-align','center').css('padding','30px 10px 30px 10px').appendTo(fg.dlg.posel);
		}
		if (typeof(fg.dlg.iconurl) == 'undefined') {
			fg.dlg.iconurl=fg.qq.srcbase+'m/img/working.gif';
		}
		fg.dlg.posel.css('background-image','url("'+fg.dlg.iconurl+'")').css('background-position','50% 50%').css('background-repeat','no-repeat').appendTo(fg.dlg.current);
	};
	fg.dlg.unpausedialog=function() {
		if (fg.dlg.current === null)
			return;
		if (fg.dlg.posel !== null) {
			fg.dlg.posel.remove();
			fg.dlg.posel=null;
		}
	};
}	

