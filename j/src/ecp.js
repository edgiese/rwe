// ecommerce (product based) routines
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentionally!

// registers values for product list to enable ajax calls 
//-!prodlistsetup
//-:overlay overlaylongname ::long name of overlay used to cover prod list while updating
//-:useoverlay 333 ::true if overlay is to be used, false if not
//-<jquery.js
//-<fgecplib.js
function () {
	fg.ecp.prodlistau='QQaushort';
	fg.ecp.prodlistid='<#QQaulong#>';
	fg.ecp.prodlisttitleid='<#QQaulong_title#>';
	fg.ecp.prodlistoverlay='<#overlaylongname#>';
	fg.ecp.prodbUseOverlay=333;
	fg.ecp.page='QQpage';
	fg.ecp.srcbase='QQsrcbase';
	fg.ecp.project='QQproject';
	fg.ecp.bordercolornosel=$('#<#QQaulong#>').css('background-color');
	fg.ecp.bordercolor=$('#<#QQaulong_extra#>').css('border-top-color');
	fg.ecp.borderwidth=$('#<#QQaulong_extra#>').css('border-width');
	fg.ecp.cellbk=$('#<#QQaulong_extra#>').css('background-color');
	fg.ecp.cellbkh=$('#<#QQaulong_extra#>').css('color');
	fg.ecp.setprodspec($('#<#QQaulong_extra#>').text());
}//-}

// registers values for product description to enable ajax calls 
//-!proddescsetup
//-:overlay overlaylongname ::long name of overlay used to cover prod desc while updating
//-:useoverlay 333 ::true if overlay is to be used, false if not
//-:cartctrl text_cartctrl ::shortname of the cart control text au
//-<jquery.js
//-<fgecplib.js
function () {
	fg.ecp.proddescau='QQaushort';
	fg.ecp.proddescid='<#QQaulong#>';
	fg.ecp.cartctrlid='text_cartctrl';
	fg.ecp.page='QQpage';
	fg.ecp.srcbase='QQsrcbase';
	fg.ecp.project='QQproject';
	fg.ecp.descs=new Array();
	fg.ecp.lastdesc=0;
	fg.ecp.proddescoverlay='<#overlaylongname#>';
	fg.ecp.proddescbUseOverlay=333;
}//-}

// registers values for pictures check box -- controls display of prodlist window 
//-!setpicssetup
//-<jquery.js
//-<fgecplib.js
//-<fglib.js
function () {
	fg.ecp.updatepics = function(checkbox) {
		oldvalue=fg.ecp.bImages;
		fg.ecp.bImages=(checkbox.checked) ? 1 : 0;
		if (oldvalue != fg.ecp.bImages)
			fg.ecp.updatedisplay();
	};
	checkbox=$('#<#QQaulong#>').get(0);
	fg.ecp.bImages=(checkbox.checked) ? 1 : 0;
}//-}

// registers values for cart check box -- controls cart contents 
//-!setcartsetup
//-<jquery.js
//-<fgecplib.js
//-<fglib.js
function () {
	fg.ecp.updatecart = function(checkbox) {
		var incart=(checkbox.checked) ? 1 : 0;
		var id=fg.ecp.lastdesc;
		cd={};
		cd.id=id;
		cd.incart=incart;
		cd.u="QQproject";
		cd.q=window.location.pathname;
		cd.r="QQaushort";
		cd.s='';
		cd.t=0;
		cd.v="QQpage";
		
		var options={url:'QQsrcbaseajax.php',data:cd,dataType:'script'};

		options.success=function(body,status) {
			if (fg.ecp.bImages === 0) {
				$('td.zid'+id.toString()+':first').html((incart !== 0) ? 'cart' : '&nbsp;');
			}
			for (i=0; i<fg.ecp.items.length; ++i) {
				if (fg.ecp.items[i].id==id) {
					fg.ecp.items[i].incart=incart;
					break;
				}
			}
			fg.ecp.descs[id][4]=incart;	
			checkbox.disabled=false;
		};
		options.error=function(xhr,status,exception) {
			alert("network or software error occurred in setting cart status. status:"+status+"  exception:"+exception);
			checkbox.checked=!incart;
			checkbox.disabled=false;
		};
		
		$.ajax(options);
		checkbox.disabled=true;
	};
	var checkbox=$('#<#QQaulong#> input').get(0);
	checkbox.checked=0;
	checkbox.disabled=false;
	fg.ecp.cartcheckbox=checkbox;
}//-}

// clears prod list
//-!clearprodlist
//-:description descriptionstring ::description of the new set being downloaded
//-<jquery.js
function () {
	if (typeof(fg.ecp.items) != 'undefined')
		delete fg.ecp.items;
	fg.ecp.items=new Array();
	$('#'+fg.ecp.prodlisttitleid).text('descriptionstring');	
}//-}

//-!addprodlisthtml
//-:onhold onholdnumber ::0 for visible, 1 for on hold
//-:id idnumber ::id of the item
//-:mfr mfrstring ::mfr text (slashed)
//-:incart incartornot ::0 for not in cart or 1 for in cart
//-:title titlestring ::the new html string
//-:price theprice ::the new html string
//-:img imgsrcstring ::the new html string
//-:height imgheight ::height of the image
//-:width imgwidth ::width of the image
//-<jquery.js
function () {
	fg.ecp.items[fg.ecp.items.length]={id:idnumber,onhold:onholdnumber,mfr:'mfrstring',incart:incartornot,title:'titlestring',price:theprice,img:'imgsrcstring',width:imgwidth,height:imgheight};
}//-}

// sets a description
//-!setdesc
//-:id 9999 ::id of the item
//-:desc descstring ::html description
//-:pricestring Price ::description of price (allows for specials)
//-:price 6.99 ::price of item
//-:notice noticemessage ::general notice message for item availability etc.
//-:incart false ::True if in cart or False if not ***WARNING*** watch for duplicates of this!!
function () {
	if (typeof(fg.ecp.descs[9999]) != 'Array')
		fg.ecp.descs[9999]=new Array;
	fg.ecp.descs[9999][0]='descstring';	
	fg.ecp.descs[9999][1]='Price ';
	fg.ecp.descs[9999][2]='6.99';
	fg.ecp.descs[9999][3]='noticemessage';
	fg.ecp.descs[9999][4]=false;
}//-}

// sets a link to the product edit page
//-!seteditlabel
//-:href /dev/pchb/prodedit ::link to edit page
//-:longname label_prodedit ::shortname of where to stick the link
//-:id 11111 ::id of the item
//-<jquery.js
function () {
	var mytext=$('#label_prodedit').text();
	$('#label_prodedit').html('<a href="/dev/pchb/prodedit/11111">'+mytext+'</a>');
}//-}


/////////////////////////////////////////////////////// setmfr
// sets up for the 'set mfr' dialog 
//-!setmfrsetup
//-<jquery.js
//-<fgecplib.js
//-<fglib.js
function () {
	fg.ecp.setmfrs=function () {
		fg.dlg.showmodaldialog('<#QQaulong#>');
	};
	
	$('#<!dlg_cancel!>').click(function(event) {
		fg.dlg.hidemodaldialog();
	});
	
	$('#<!dlg_ok!>').click(function(event) {
		extra=separator='';
		$("#<!dlg_mfrs!> option:selected").each(function() {
			extra += separator + $(this).attr('value');
			separator='_';
		});
		fg.ecp.setprodspec('mfr_'+extra);
		fg.dlg.hidemodaldialog();
	});
}//-}

/////////////////////////////////////////////////////// setmfr
// sets up for the 'search' dialog 
//-!searchsetup
//-<jquery.js
//-<fgecplib.js
//-<fglib.js
function () {
	// search dialog
	fg.ecp.search=function () {
		fg.dlg.showmodaldialog('<#QQaulong#>');
		$('#<!dlg_search!>').focus();
	};
	
	$('#<!dlg_cancel!>').click(function(event) {
		fg.dlg.hidemodaldialog();
	});
	
	$('#<!dlg_ok!>').click(function(event) {
		searchin=$("#<!dlg_search!> input:checked").attr("value");
		if (searchin == 'titles')
			searchin='t';
		else if (searchin == 'descriptions')
			searchin='d';
		else
			searchin='td';		
		extra=$('#<!dlg_search!>').attr('value');
		extra=extra.replace(/[^_\w]/g,'_');
		re=/__/g;
		while (re.test(extra))
			extra=extra.replace(re,'_');
		extra=extra.replace(/^_/,'');
		extra=extra.replace(/_$/,'');
		fg.ecp.setprodspec('search'+searchin+'_'+extra);
		fg.dlg.hidemodaldialog();
	});
	$('#<!dlg!> :input').keypress(function(event){
		if (event.keyCode == '13') {
			$('#<!dlg_ok!>').click();
			return false;
		} else if (event.keyCode == '27') {
			$('#<!dlg_cancel!>').click();
			return false;
		}
		return true;
	});
}//-}


/////////////////////////////////////////////////////// aisles
// sets up for the 'browse aisles' dialog 
//-!aislesetup
//-:image /dev/p/pchb/create/floorplan.jpg ::url for background image
//-:cleft 39 ::cancel button left in pixels
//-:ctop 54 ::cancel button top in pixels
//-:cwidth 77 ::cancel button width in pixels
//-:cheight 17 ::cancel button height in pixels
//-<jquery.js
//-<fgecplib.js
//-<fglib.js
function () {
	var mydiv=$('#<#QQaulong#>').get(0);
	$(mydiv).css("background-image",'url(/dev/p/pchb/create/floorplan.jpg)');

	fg.ecp.walkaisles=function () {
		fg.dlg.showmodaldialog('<#QQaulong#>');
	};
	$('<div id="<#QQaulong#>_cancel">').css("position","absolute").css("left","39px").css("top","54px").width(77).height(17).mouseover(function() {
		$("#<#QQaulong#>_cancel").css("background-position","-39px -54px").css("background-image",'url(/dev/p/pchb/create/floorplanovly.jpg)');
	}).mouseout(function() {
		$('#<#QQaulong#>_cancel').css("background-image","none");
	}).click(function() {
		fg.dlg.hidemodaldialog();
	}).appendTo('#<#QQaulong#>');
	
}//-}

// sets up a button for the 'browse aisles' dialog 
//-!aislebuttonsetup
//-:overlay /dev/p/pchb/create/floorplanovly.jpg ::url for background image
//-:key mykey ::key for this button
//-:left 1111 ::left in pixels
//-:top 2222 ::top in pixels
//-:width 3333 ::width in pixels
//-:height 4444 ::height in pixels
//-:image previewimage.jpg ::image name
//-:ileft 5555 :: image left
//-:itop 6666 ::image top
//-:iwidth 7777 ::width
//-:iheight 8888 ::height 
//-<jquery.js
//-<fgecplib.js
//-<fglib.js
function () {
	$('<div id="<#QQaulong#>_mykey">').css("position","absolute").css("left","1111px").css("top","2222px").width(3333).height(4444).mouseover(function() {
		$("#<#QQaulong#>_mykey").css("background-position","-1111px -2222px").css("background-image",'url(/dev/p/pchb/create/floorplanovly.jpg)');
		//$("#<#QQaulong#>_mykey").css("background-color","#ff0000");
		$("#<#QQaulong#>_pmykey").css("display","inline");
	}).mouseout(function() {
		$('#<#QQaulong#>_mykey').css("background-image","none");
		//$('#<#QQaulong#>_mykey').css("background-color","transparent");
		$("#<#QQaulong#>_pmykey").css("display","none");
	}).click(function() {
		fg.ecp.setprodspec('group_mykey');
		fg.dlg.hidemodaldialog();
	}).appendTo('#<#QQaulong#>');
	
	$('<img id="<#QQaulong#>_pmykey" src="previewimage.jpg" height="8888" width="7777" />').css("position","absolute").css("left","5555px").css("top","6666px").css("display","none").appendTo('#<#QQaulong#>');
	
}//-}

///////////////////////////////////////////// aisle assignment routines
// sets up for the 'search' dialog 
//-!aisleassignsetup
//-<jquery.js
//-<fgecplib.js
//-<fglib.js
function () {
	fg.ecp.updatingupc=false;
	fg.ecp.timeoutid=0;
	
	fg.ecp.updateupc = function(upc) {
		if (fg.ecp.updatingupc === true && fg.ecp.timeoutid === 0) {
			fg.ecp.timeoutid=window.setTimeout('fg.ecp.updateupc("'+upc+'")',100);
			return;
		}
		/*
		cd={};
		cd.upc=upc;
		cd.u="QQproject";
		cd.q=window.location.pathname;
		cd.r="QQaushort";
		cd.s='';
		cd.t=0;
		cd.v="QQpage";
		
		var options={url:'QQsrcbaseajax.php',data:cd,dataType:'script'};
		
		options.success=function(body,status) {
			fg.ecp.updatingupc=false;
		};
		options.error=function(xhr,status,exception) {
			alert("network or software error occurred in getting image data. status:"+status+"  exception:"+exception);
			fg.ecp.updatingupc=false;
		};
		
		$.ajax(options);
		fg.ecp.updatingupc=true;
		*/
		$('#<!ok!>').click();
	};
	fg.ecp.upceditchange=function() {
		if (fg.ecp.timeoutid !== 0)
			window.clearTimeout(fg.ecp.timeoutid);
		var upc=$('#<!upc!>').get(0).value;	
		fg.ecp.timeoutid=window.setTimeout('fg.ecp.updateupc("'+upc+'")',100);
	};

	// clear upc box and set up for next scan	
	$('#<!upc!>').get(0).value='';
	$('#<!upc!>').keyup(fg.ecp.upceditchange).focus();
}//-}

///////////////////////////////////////////// edit prod-nav UPC scanning routine
// sets up for the 'search' dialog 
//-!prodeditnavsetup
//-<jquery.js
//-<fgecplib.js
//-<fglib.js
function () {
	fg.ecp.updatingupc=false;
	fg.ecp.timeoutid=0;
	
	fg.ecp.updateupc = function(upc) {
		if (fg.ecp.updatingupc === true && fg.ecp.timeoutid === 0) {
			fg.ecp.timeoutid=window.setTimeout('fg.ecp.updateupc("'+upc+'")',100);
			return;
		}
		cd={};
		cd.upc=upc;
		cd.u="QQproject";
		cd.q=window.location.pathname;
		cd.r="QQaushort";
		cd.s='';
		cd.t=0;
		cd.v="QQpage";
		
		var options={url:'QQsrcbaseajax.php',data:cd,dataType:'script'};
		
		options.success=function(body,status) {
			fg.ecp.updatingupc=false;
		};
		options.error=function(xhr,status,exception) {
			alert("network or software error occurred in getting image data. status:"+status+"  exception:"+exception);
			fg.ecp.updatingupc=false;
		};
		
		$.ajax(options);
		fg.ecp.updatingupc=true;
	};
	fg.ecp.upceditchange=function() {
		if (fg.ecp.timeoutid !== 0)
			window.clearTimeout(fg.ecp.timeoutid);
		var upc=$('#<!form_newupc!>').get(0).value;	
		fg.ecp.timeoutid=window.setTimeout('fg.ecp.updateupc("'+upc+'")',100);
	};

	// clear upc box and set up for next scan	
	$('#<!form_newupc!>').get(0).value='';
	$('#<!form_newupc!>').keyup(fg.ecp.upceditchange).focus();
}//-}
