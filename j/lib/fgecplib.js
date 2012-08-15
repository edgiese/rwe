if (typeof(fg) == 'undefined')
	var fg={};
if (typeof(fg.ecp) == 'undefined')
	fg.ecp={};

fg.ecp.setprodspec=function(extra) {
	var cd={u:fg.ecp.project,q:window.location.pathname,r:fg.ecp.prodlistau,v:fg.ecp.page};
	var url=fg.ecp.srcbase+"ajax.php";
	
	cd.s='setprodspec';
	cd.t=0;
	cd.extra=extra;
	var options={url:url,data:cd,dataType:'script'};
	options.success=function(body,status) {
		fg.ecp.sort=0;
		fg.ecp.lastdesc=0;
		fg.ecp.updatedisplay();
		if (fg.ecp.prodbUseOverlay)
			$('#'+fg.ecp.prodlistoverlay).css('display','none');
	};
	options.error=function(xhr,status,exception) {
		alert("network or software error occurred in getting image data. status:"+status+"  exception:"+exception);
		if (fg.ecp.prodbUseOverlay)
			$('#'+fg.ecp.prodlistoverlay).css('display','none');
	};
	if (fg.ecp.prodbUseOverlay)
		$('#'+fg.ecp.prodlistoverlay).css('display','inline');
	$.ajax(options);
};

fg.ecp.sortby=function(type) {
	fg.ecp.sort=type;
	fg.ecp.updatedisplay();
};

// string.pad(length,fill=' ',type=0); (0=left 1=right 2=both)
String.prototype.pad = function(l, s, t){
	if (typeof(s) == 'undefined')
		s=' ';
	if (typeof(t) == 'undefined')
		t=0;	
    if ((l -= this.length) <= 0)
    	return this;
	return (new Array(Math.ceil(l / s.length)+1).join(s)).substr(0, t = !t ? l : t == 1 ? 0 : Math.ceil(l / 2)) + this + s.substr(0, l - t);
};

fg.ecp.updatedisplay=function() {
	prodlist=$('#'+fg.ecp.prodlistid);
	
	if (fg.ecp.bImages !== 0) {
		prodlist.html('<p>');
		for (i=0; i<fg.ecp.items.length; ++i) {
			id=fg.ecp.items[i].id;
			imgsrc=fg.ecp.items[i].img;
			width=fg.ecp.items[i].width;
			height=fg.ecp.items[i].height;
			if (imgsrc != '(no image)') {
				prodlist.append('<a href="javascript:fg.ecp.updatedesc('+id+');"><img src="'+imgsrc+'" class="zid'+id+'" width="'+width+'" height="'+height+'" /></a> ');
			}
		}
		prodlist.append('</p>');
	} else {
		newprodlist='<table><tr>';
		sortarray=new Array();
		if (fg.ecp.sort === 1) {
			newprodlist += '<th><a href="javascript:fg.ecp.sortby(0);">*Cart</a></th>';
			for (i=0; i<fg.ecp.items.length; ++i) {
				sortarray[sortarray.length]=fg.ecp.items[i].incart.toString()+(i.toString()).pad(5,'0');
			}
		} else
			newprodlist += '<th><a href="javascript:fg.ecp.sortby(1);">Cart</a></th>';
			
		if (fg.ecp.sort === 2) {
			newprodlist += '<th><a href="javascript:fg.ecp.sortby(0);">*Manufacturer</a></th>';
			maxlen=0;
			for (i=0; i<fg.ecp.items.length; ++i) {
				maxlen=Math.max(maxlen,fg.ecp.items[i].mfr.length);
			}
			for (i=0; i<fg.ecp.items.length; ++i) {
				sortarray[sortarray.length]=fg.ecp.items[i].mfr.pad(maxlen,' ',1)+(i.toString()).pad(5,'0');
			}
		} else
			newprodlist += '<th><a href="javascript:fg.ecp.sortby(2);">Manufacturer</a></th>';

		if (fg.ecp.sort === 3) {
			newprodlist += '<th><a href="javascript:fg.ecp.sortby(0);">*Item</a></th>';
			maxlen=0;
			for (i=0; i<fg.ecp.items.length; ++i) {
				maxlen=Math.max(maxlen,fg.ecp.items[i].title.length);
			}
			for (i=0; i<fg.ecp.items.length; ++i) {
				sortarray[sortarray.length]=fg.ecp.items[i].title.pad(maxlen,' ',1)+(i.toString()).pad(5,'0');
			}
		} else
			newprodlist += '<th><a href="javascript:fg.ecp.sortby(3);">Item</a></th>';

		if (fg.ecp.sort === 4) {
			newprodlist += '<th><a href="javascript:fg.ecp.sortby(0);">*Price</a></th></tr>';
			for (i=0; i<fg.ecp.items.length; ++i) {
				sortarray[sortarray.length]=fg.ecp.items[i].price.toString().pad(8,'0')+(i.toString()).pad(5,'0');
			}
		} else
			newprodlist += '<th><a href="javascript:fg.ecp.sortby(4);">Price</a></th></tr>';
			
		if (fg.ecp.sort === 0) {
			for (i=0; i<fg.ecp.items.length; ++i)
				sortarray[sortarray.length]=i.toString().pad(5,'0');
		}	
		
		sortarray.sort();
		for (ix=0; ix<sortarray.length; ++ix) {
			i=parseInt(sortarray[ix].substr(sortarray[ix].length-5),10);
			cart= (fg.ecp.items[i].incart !== 0) ? 'cart' : '&nbsp;';
			mfr=fg.ecp.items[i].mfr;
			title=fg.ecp.items[i].title;
			if (fg.ecp.items[i].onhold !== 0)
				title='<strong>*on hold* </strong>'+title;
			id=fg.ecp.items[i].id;
			price=fg.ecp.items[i].price;
			strprice=price.toString();
			if (price < 10)
				strprice='0.0'+strprice;
			else if (price < 100)
				strprice='0.'+strprice;
			else
				strprice=strprice.substr(0,strprice.length-2)+'.'+strprice.substr(strprice.length-2);		
			newprodlist += '<tr><td class="zid'+id+'">'+cart+'</td><td class="zid'+id+'">'+mfr+'</td><td class="zid'+id+'"><a href="javascript:fg.ecp.updatedesc('+id+');">'+title+'</a></td><td style="text-align:right" class="zid'+id+'">'+strprice+'</td></tr>';
		}
		prodlist.html(newprodlist+'</table>');
	}
	if (fg.ecp.items.length > 0) {
		if (fg.ecp.lastdesc === 0)
			fg.ecp.updatedesc(fg.ecp.items[0].id);
		else
			fg.ecp.updatedesc(fg.ecp.lastdesc,true);		
	} else
		fg.ecp.updatedesc(0); 
};

///////////////////////////////////// sets a new description
fg.ecp.updatedesc=function(newid,bForce) {
	if (typeof(bForce) != 'boolean')
		bForce=false;
	if (newid == fg.ecp.lastdesc && !bForce)
		return;
	if (fg.ecp.lastdesc !== 0) {
		if (fg.ecp.bImages !== 0) {
			$('.zid'+fg.ecp.lastdesc.toString()).css('border-color',fg.ecp.bordercolornosel);
		} else {
			$('.zid'+fg.ecp.lastdesc.toString()).css('background-color',fg.ecp.cellbk);
		}
		fg.ecp.lastdesc=0;
	}
	if (fg.ecp.bImages !== 0) {
		$('.zid'+newid.toString()).css('border-color',fg.ecp.bordercolor);
	} else {
		$('.zid'+newid.toString()).css('background-color',fg.ecp.cellbkh);
	}
	if (typeof(fg.ecp.descs[newid]) != 'undefined') {
		$('#'+fg.ecp.proddescid).html(fg.ecp.descs[newid][0]);
		$('#'+fg.ecp.cartctrlid+' span:first').text(fg.ecp.descs[newid][2]);
		$('#'+fg.ecp.cartctrlid+' blockquote').html(fg.ecp.descs[newid][3]);
		if (typeof(fg.ecp.cartcheckbox) != 'undefined') {
			fg.ecp.cartcheckbox.checked=fg.ecp.descs[newid][4];
		}	
		fg.ecp.lastdesc=newid;
	} else {
		var cd={u:fg.ecp.project,q:window.location.pathname,r:fg.ecp.proddescau,v:fg.ecp.page};
		var url=fg.ecp.srcbase+"ajax.php";
		
		cd.s='setproddesc';
		cd.t=0;
		cd.id=newid;
		var options={url:url,data:cd,dataType:'script'};
		options.success=function(body,status) {
			fg.ecp.updatedesc(newid);
			if (fg.ecp.proddescbUseOverlay)
				$('#'+fg.ecp.proddescoverlay).css('display','none');
		};
		options.error=function(xhr,status,exception) {
			alert("network or software error occurred in getting image data. status:"+status+"  exception:"+exception);
			if (fg.ecp.proddescbUseOverlay)
				$('#'+fg.ecp.proddescoverlay).css('display','none');
		};
		$.ajax(options);
		if (fg.ecp.proddescbUseOverlay)
			$('#'+fg.ecp.proddescoverlay).css('display','inline');
	}
};
