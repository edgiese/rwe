// ecommerce (product based) order routines
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentionally!

/////////////////////////////////////////////////////////// setup routine
// sets up for the 'set mfr' dialog 
//-!ordersetup
//-<jquery.js
//-<fglib.js
function () {
	fg.ecpo={};
	fg.initlib('QQproject','QQsrcbase','QQhrefbase','QQpage','QQdomain','QQencoding');

	$('#<!base_shop!>').click(function(){window.location='shop';});
	fg.ecpo.chaining=false;
	fg.ecpo.needmask=0;
	fg.ecpo.updatechainmask=function(finishing,needmask) {
		if (finishing === true)
			fg.ecpo.needmask &= (~needmask);
		else	
			fg.ecpo.needmask |= needmask;
		if (fg.ecpo.needmask === 0)
			$('#<!base_continue!>').get(0).value='Place the Order';
		else	
			$('#<!base_continue!>').get(0).value='Complete Order Form';
	};
	fg.ecpo.closechaindlg=function(continuing,needmask) {
		if (false === continuing || fg.ecpo.chaining === false) {
			fg.ecpo.chaining=false;
			fg.dlg.hidemodaldialog();
		} else {
			fg.ecpo.updatechainmask(true,needmask);
			if (fg.ecpo.needmask & 1)
				fg.ecpo.changeemail();
			else if (fg.ecpo.needmask & 2)
				fg.ecpo.changebilladdr();
			else if (fg.ecpo.needmask & 4)
				fg.ecpo.changepayment();
			else if (fg.ecpo.needmask & 8)
				fg.ecpo.showterms();
			else if (fg.ecpo.needmask & 16) {
				fg.dlg.hidemodaldialog();	// in case terms dialog still up
				alert('Please select a shipping method (look right above the order total).');
			} else if (fg.ecpo.needmask === 0)
				fg.dlg.hidemodaldialog();
				
		}
	};
	$('#<!base_continue!>').click(function(){
		if (fg.ecpo.needmask === 0) {
			$('#<!orderdlg!>').html('');
			fg.dlg.showmodaldialog('<!orderdlg!>');
			fg.dlg.pausedialog('Checking and Placing Order');
			cd={};
			opts=fg.seedajaxopts(cd,'QQaushort','order');
			$.ajax(opts);
		} else {
			fg.ecpo.chaining=true;
			fg.ecpo.closechaindlg(true,0);
		}	
	});
	fg.ecpo.changepayment=function() {
		fg.ecpo.setuppaymentdlg(true);
		fg.dlg.showmodaldialog('<!paymentdlg!>');
		$('#<!payment_cctype!>').get(0).focus();
	};
	
	fg.ecpo.setuppaymentdlg=function(init) {
		$('#<!payment_cctype!>').change(function(){
			var newvalue=$('#<!payment_cctype!>').get(0).value;
			var digits=/^[0-9]+$/;
			if (digits.test(newvalue)) {
				fg.dlg.pausedialog('Updating Payment Options');
				cd={cctype:newvalue};
				opts=fg.seedajaxopts(cd,'QQaushort','payment');
				$.ajax(opts);
			} else if (newvalue == 'P') {
				fg.ecpo.paypalexpress(1);
			} else
				$('#<!payment_ccnumber!>').focus();
		});
		$('#<!payment_cancel!>').click(function(){
			fg.ecpo.closechaindlg(false);
		});
		$('#<!payment_ok!>').click(function(){
			fg.dlg.pausedialog('Updating Payment Options');
			cd={};
			cd.cctype=$('#<!payment_cctype!>').get(0).value; 	
			cd.ccnumber=$('#<!payment_ccnumber!>').get(0).value; 	
			cd.ccexp=$('#<!payment_ccexp!>').get(0).value; 	
			cd.ccname=$('#<!payment_ccname!>').get(0).value; 	
			opts=fg.seedajaxopts(cd,'QQaushort','payment');
			$.ajax(opts);
			return false;
		});
		$('#<!payment!>').submit(function(){
			return false;
		});
		if (init === true) {
			$('#<!payment_cctype!>').get(0).value='?';
			$('#<!payment_ccnumber!>').get(0).value=''; 	
			$('#<!payment_ccexp!>').get(0).value=''; 	
			$('#<!payment_ccname!>').get(0).value='';
			$('#<!payment!> span+br').remove();
			$('#<!payment!> span').remove();
		}	
	};

	fg.ecpo.changeinstructions=function() {
		fg.dlg.showmodaldialog('<!instructionsdlg!>');
		$('#<!instructions_instructions!>').focus();
	};
	$('#<!instructions_cancel!>').click(function(){
		fg.dlg.hidemodaldialog();
	});
	$('#<!instructions_ok!>').click(function(){
		fg.dlg.pausedialog('Updating Instructions');
		cd={};
		cd.instructions=$('#<!instructions_instructions!>').get(0).value; 	
		opts=fg.seedajaxopts(cd,'QQaushort','instructions');
		$.ajax(opts);
		return false;
	});
	$('#<!instructions!>').submit(function(){
		return false;
	});
	
	fg.ecpo.showdiscounts=function() {
		fg.dlg.showmodaldialog('<!discountsdlg!>');
		$('#<!discounts_cancel!>').focus();
	};
	$('#<!discounts_cancel!>').click(function(){
		fg.dlg.hidemodaldialog();
	});
	fg.ecpo.showthankyou=function() {
		fg.dlg.showmodaldialog('<!thankyoudlg!>');
		$('#<!thankyou_cancel!>').focus();
	};
	$('#<!thankyou_cancel!>').click(function(){
		window.location.reload(true);
	});
	fg.ecpo.showterms=function() {
		fg.dlg.showmodaldialog('<!termsdlg!>');
		$('#<!terms_terms!>').focus();
	};
	$('#<!terms_cancel!>').click(function(){
		fg.dlg.hidemodaldialog();
	});
	$('#<!terms_terms!>,#<!terms_terms!> input').click(function(){
		var cb=$('#<!terms_terms!> input,#<!terms_terms!>').get(0);
		cd={terms:cb.checked ? '1' : '0'};
		opts=fg.seedajaxopts(cd,'QQaushort','terms');
		opts.success=function(body,status) {cb.disabled=false;};
		opts.error=function(xhr,status,exception) {alert('Error occurred updating terms acceptance'); cb.disabled=false;};
		cb.disabled=true;
		$.ajax(opts);
	});
	$('#<!base_terms!>,#<!base_terms!> input').click(function(){
		var cb=$('#<!base_terms!> input,#<!base_terms!>').get(0);
		cd={terms:cb.checked ? '1' : '0'};
		opts=fg.seedajaxopts(cd,'QQaushort','terms');
		opts.success=function(body,status) {cb.disabled=false;};
		opts.error=function(xhr,status,exception) {alert('Error occurred updating terms acceptance'); cb.disabled=false;};
		cb.disabled=true;
		$.ajax(opts);
	});
	fg.ecpo.changebilladdr=function() {
		fg.dlg.showmodaldialog('<!billaddrdlg!>');
		$('#<!billaddr_name!>').focus();
		fg.ecpo.setupbilladdrdlg();
	};
	fg.ecpo.closebilladdrdlg=function(updatemask,maskvalue) {
		$('#<!billaddr!> span+br').remove();
		$('#<!billaddr!> span').remove();
		fg.ecpo.closechaindlg(updatemask,maskvalue);
	};
	fg.ecpo.setupbilladdrdlg=function() {
		$('#<!billaddr_cancel!>').click(function(){fg.ecpo.closebilladdrdlg(false,0);});
		$('#<!billaddr_ok!>').click(function(){
			fg.dlg.pausedialog('Updating Billing Address');
			cd={};
			cd.name=$('#<!billaddr_name!>').get(0).value; 	
			cd.line1=$('#<!billaddr_line1!>').get(0).value; 	
			cd.line2=$('#<!billaddr_line2!>').get(0).value; 	
			cd.city=$('#<!billaddr_city!>').get(0).value; 	
			cd.state2=$('#<!billaddr_state2!>').get(0).value; 	
			cd.zip=$('#<!billaddr_zip!>').get(0).value;
			cd.phone=$('#<!billaddr_phone!>').get(0).value;
			opts=fg.seedajaxopts(cd,'QQaushort','billaddr');
			$.ajax(opts);
			return false;
		});
		$('#<!billaddr!>').submit(function(){
			return false;
		});
	};
	fg.ecpo.setupshipaddrdlg=function() {
		$('#<!shipaddr_cancel!>').click(function(){fg.dlg.hidemodaldialog();});
		$('#<!shipaddr_giftname!>').focus(function() {$('#<!shipaddr_newgift!>').get(0).checked=true;});
		$('#<!shipaddr_newgift!>').click(function() {$('#<!shipaddr_giftname!>').focus();});
		$('#<!shipaddr_ok!>').click(function(){
			fg.dlg.pausedialog('Updating Shipping Address');
			cd={};
			cd.type=$('input[name=type]:checked').get(0).value;
			cd.giftname=$('#<!shipaddr_giftname!>').get(0).value; 	
			opts=fg.seedajaxopts(cd,'QQaushort','shipaddr');
			$.ajax(opts);
			return false;
		});
		$('#<!shipaddr!>').submit(function(){
			return false;
		});
	};
	fg.ecpo.changeshipaddr=function() {
		fg.dlg.showmodaldialog('<!shipaddrdlg!>');
		$('#<!shipaddr_usebilling!>').focus();
		fg.ecpo.setupshipaddrdlg();
	};
	fg.ecpo.setupaltaddrdlg=function() {
		$('#<!altaddr_cancel!>').click(function(){
			fg.dlg.pausedialog('Canceling Shipping Address Update');
			cd={};
			opts=fg.seedajaxopts(cd,'QQaushort','cancelaltaddr');
			$.ajax(opts);
		});
		$('#<!altaddr_ok!>').click(function(){
			fg.dlg.pausedialog('Updating Shipping Address');
			cd={};
			cd.name=$('#<!altaddr_name!>').get(0).value; 	
			cd.line1=$('#<!altaddr_line1!>').get(0).value; 	
			cd.line2=$('#<!altaddr_line2!>').get(0).value; 	
			cd.city=$('#<!altaddr_city!>').get(0).value; 	
			cd.state2=$('#<!altaddr_state2!>').get(0).value; 	
			cd.zip=$('#<!altaddr_zip!>').get(0).value;
			cd.phone=$('#<!altaddr_phone!>').get(0).value;
			opts=fg.seedajaxopts(cd,'QQaushort','altaddr');
			$.ajax(opts);
			return false;
		});
		$('#<!altaddr!>').submit(function(){
			return false;
		});
	};
	fg.ecpo.delalt=function(idship) {
		if (confirm('Are you sure you want to delete this address')) {
			fg.dlg.pausedialog('Deleting Shipping Address');
			cd={idship:idship};
			opts=fg.seedajaxopts(cd,'QQaushort','delaltaddr');
			$.ajax(opts);
		}
	};
	fg.ecpo.editalt=function(idship) {
		fg.dlg.pausedialog('Editing Shipping Address');
		cd={idship:idship};
		opts=fg.seedajaxopts(cd,'QQaushort','editaltaddr');
		$.ajax(opts);
	};
	fg.ecpo.setupshipopt=function() {
		$('#<!base_shipopt!>').change(function() {
			cd={shipopt:this.value};
			opts=fg.seedajaxopts(cd,'QQaushort','shipopt');
			$.ajax(opts);
		});
	};
	fg.ecpo.setupshipopt();
	
	fg.ecpo.numbertimeout=0;
	fg.ecpo.requestingnumbers=false;
	fg.ecpo.repeatrequest=false;
	fg.ecpo.requestnumbers=function() {
		if (fg.ecpo.requestingnumbers === true) {
			fg.ecpo.repeatrequest=true;
			return;
		}
		cd={};
		$('td[id^=z]').each(function(n) {
			var prodid=parseInt($('#'+this.id).text(),10);
			if (isNaN(prodid))
				prodid=0;
			eval('cd.zzz'+this.id.substr(1)+'='+prodid+';');
		});
		opts=fg.seedajaxopts(cd,'QQaushort','numbers');
		opts.success=function(body,status) {
			fg.ecpo.requestingnumbers=false;
			if (true === fg.ecpo.repeatrequest) {
				$('#<!subtotal!>,#<!tax!>,#<!shippingcharge!>,#<!total!>').text('?.??');
				fg.ecpo.requestnumbers();
			}	
		};
		opts.error=function(xhr,status,exception) {
			alert('Error occurred updating numbers');
			fg.ecpo.requestingnumbers=false;
			fg.ecpo.repeatrequest=false;
		};
		fg.ecpo.requestingnumbers=true;
		fg.ecpo.repeatrequest=false;
		$.ajax(opts);
	};
	fg.ecpo.numberchanged=function() {
		if (fg.ecpo.numbertimeout !== 0) {
			clearTimeout(fg.ecpo.numbertimeout);
			fg.ecpo.numbertimeout=0;
		}
		fg.ecpo.numbertimeout=setTimeout(function(){
			fg.ecpo.requestnumbers();
		},750);
		$('#<!subtotal!>,#<!tax!>,#<!shippingcharge!>,#<!total!>').text('?.??');
	};
	fg.ecpo.more=function(id) {
		var n=parseInt($('#z'+id).text(),10);
		if (isNaN(n))
			n=0;
		var nold=n;	
		++n;
		$('#z'+id).text(n);
		fg.ecpo.numberchanged();
	};
	fg.ecpo.fewer=function(id) {
		var n=parseInt($('#z'+id).text(),10);
		if (isNaN(n))
			n=0;
		var nold=n;
		--n;
		if (n < 0)
			n=0;
		if (n != nold) {	
			$('#z'+id).text(n);
			fg.ecpo.numberchanged();
		}		
	};
	fg.ecpo.setupemaildlg=function() {
		$('#<!emailpw_cancel!>').click(function(){
			fg.ecpo.closechaindlg(false);
		});
		$('#<!emailpw_ok!>').click(function(){
			fg.dlg.pausedialog('Updating Email & Password');
			cd={};
			cd.email=$('#<!emailpw_email!>').get(0).value; 	
			cd.password=$('#<!emailpw_password!>').get(0).value;
			if ($('input[name=action]:checked').length > 0) 	
				cd.action=$('input[name=action]:checked').get(0).value;
			else {
				cd.action=(cd.password !== '' && cd.password !== null) ? 'tryagain' : 'noaccount';
			}		
			opts=fg.seedajaxopts(cd,'QQaushort','email');
			$.ajax(opts);
			return false;
		});
		$('#<!emailpw_changepw!>').click(function(){
			fg.ecpo.closechaindlg(false);
			$('#<!password_message!>').text('Change your current password as follows');
			fg.dlg.showmodaldialog('<!passworddlg!>');
			$('#<!password_password!>').focus();
		});
	};
	fg.ecpo.setupemaildlg();
	fg.ecpo.changeemail=function() {
		fg.dlg.showmodaldialog('<!emaildlg!>');
		$('#<!emailpw_email!>').focus();
	};
	
	$('#<!password_cancel!>').click(function(){
		fg.dlg.hidemodaldialog();
	});
	$('#<!password_ok!>').click(function(){
		var pw1=$('#<!password_password!>').get(0).value;
		var pw2=$('#<!password_confirm!>').get(0).value;
		if (pw1 != pw2) {
			alert("Both passwords must match.  Retype one or both of them.");
			$('#<!password_password!>').select().focus();
			return false;
		}
		if (pw1.length < 4) {
			alert("Passwords must be at least four characters long.");
			$('#<!password_password!>').select().focus();
			return false;
		}
		fg.dlg.pausedialog('Updating Password');
		cd={password:pw1};
		opts=fg.seedajaxopts(cd,'QQaushort','password');
		$.ajax(opts);
		return false;
	});

	fg.ecpo.paypalexpress=function(override) {
		fg.dlg.pausedialog('Transferring to Paypal');
		cd={override:override};
		opts=fg.seedajaxopts(cd,'QQaushort','paypalexpress');
		$.ajax(opts);
	};
}//-}

// handles errors from the 'payment' dialog 
//-!paymentdlgerror
//-:dlg dlgstring ::html for the updated dialog
//-:focusfield focusfield ::name of the field to set focus to
//-<jquery.js
//-<fglib.js
function () {
	$('#<!paymentdlg!>').html('dlgstring');
	fg.dlg.unpausedialog();
	fg.ecpo.setuppaymentdlg(false);
	$('#focusfield').select().focus();
}//-}

// handles successful update from payment dialog 
//-!newcc
//-:ccstring ccstring ::new credit card description
//-<jquery.js
//-<fglib.js
function () {
	fg.ecpo.closechaindlg(true,4);
	$('#<#ecp_orderpiece_paymentinfo#>').html('ccstring');
}//-}

// handles successful update from 'special instructions' dialog 
//-!newinstructions
//-:instructions instructionstring ::new credit card description
//-<jquery.js
//-<fglib.js
function () {
	fg.dlg.hidemodaldialog();
	$('#<#ecp_orderpiece_instructionsdisplay#>').html('instructionstring'+'&nbsp;');
}//-}

// handles successful update from 'accept terms' dialog or check box 
//-!newacceptance
//-:accepted 9999 ::new acceptance--actually true or false
//-<jquery.js
//-<fglib.js
function () {
	$('#<!base_terms!> input,#<!base_terms!>').get(0).checked=9999;
	$('#<!terms_terms!> input,#<!terms_terms!>').get(0).checked=9999;
	if ($('#<!terms_terms!> input,#<!terms_terms!>').get(0).checked === true) {
		if (fg.ecpo.chaining === true)
			fg.ecpo.closechaindlg(true,8);
		else
			fg.ecpo.updatechainmask(true,8);	
	} else {
		fg.ecpo.updatechainmask(false,8);	
	}	
}//-}

// handles errors from the 'bill address' dialog 
//-!billaddrdlgerror
//-:dlg dlgstring ::html for the updated dialog
//-:focusfield focusfield ::name of the field to set focus to
//-<jquery.js
//-<fglib.js
function () {
	$('#<!billaddrdlg!>').html('dlgstring');
	fg.dlg.unpausedialog();
	fg.ecpo.setupbilladdrdlg();
	$('#focusfield').select().focus();
}//-}

// handles successful update from 'billing addr' dialog 
//-!newbilladdr
//-:showaddr billaddrdisplaystr ::new display string
//-<jquery.js
//-<fglib.js
function () {
	fg.ecpo.closebilladdrdlg(true,2);
	$('#<#ecp_orderpiece_showbilladdr#>').html('billaddrdisplaystr');
}//-}

// updates tax field 
//-!tax
//-:tax 1.23 ::new display string
//-<jquery.js
//-<fglib.js
function () {
	$('#<!tax!>').text('1.23');
}//-}

// handles successful update from 'shipping addr' dialog 
//-!newshipaddr
//-:showaddr shipaddrdisplaystr ::new display string
//-<jquery.js
//-<fglib.js
function () {
	fg.dlg.hidemodaldialog();
	$('#<#ecp_orderpiece_showshipaddr#>').html('shipaddrdisplaystr');
}//-}

// updates shipping address withoug closing dialog 
//-!updateshipdisplay
//-:showaddr shipaddrdisplaystr ::new display string
//-<jquery.js
//-<fglib.js
function () {
	$('#<#ecp_orderpiece_showshipaddr#>').html('shipaddrdisplaystr');
}//-}

// sets up alt address dialog.  note that this dialog is different because it can only be invoked via ajax return 
//-!setupaltaddrdlg
//-:dlg dlgstring ::html for the updated dialog
//-:focusfield focusfield ::name of the field to set focus to
//-<jquery.js
//-<fglib.js
function () {
	fg.dlg.hidemodaldialog();	// old dialog
	$('#<!altaddrdlg!>').html('dlgstring');
	fg.ecpo.setupaltaddrdlg();
	fg.dlg.showmodaldialog('<!altaddrdlg!>');
	$('#focusfield').select().focus();
}//-}

// handles errors from the 'alt address' dialog 
//-!altaddrdlgerror
//-:dlg dlgstring ::html for the updated dialog
//-:focusfield focusfield ::name of the field to set focus to
//-<jquery.js
//-<fglib.js
function () {
	$('#<!altaddrdlg!>').html('dlgstring');
	fg.dlg.unpausedialog();
	fg.ecpo.setupaltaddrdlg();
	$('#focusfield').select().focus();
}//-}

// handles updates to the 'ship address' dialog 
//-!updateshipaddrdlg
//-:dlg dlgstring ::html for the updated dialog
//-<jquery.js
//-<fglib.js
function () {
	$('#<!shipaddrdlg!>').html('dlgstring');
	fg.dlg.unpausedialog();
	fg.ecpo.setupshipaddrdlg();
}//-}

// handles updates to the 'shipping options' drop down.  this resets focus, so don't use for other changes to table 
//-!newshipopt
//-:table tablestring ::html for the updated table of options
//-<jquery.js
//-<fglib.js
function () {
	$('#<#ecp_orderpiece_totaltable#>').html('tablestring');
	fg.ecpo.setupshipopt();
	fg.ecpo.updatechainmask(($('#<!base_shipopt!>').get(0).value != '?'),16);	
}//-}

// handles updates to the 'price total table' 
//-!newtotals
//-:table tablestring ::html for the updated table of options
//-<jquery.js
//-<fglib.js
function () {
	if (fg.ecpo.repeatrequest === false) {
		$('#<#ecp_orderpiece_totaltable#>').html('tablestring');
		fg.ecpo.setupshipopt();
	}	
}//-}

// handles updates to the 'product table' 
//-!newproducts
//-:table tablestring ::html for the updated table of options
//-<jquery.js
//-<fglib.js
function () {
	if (fg.ecpo.repeatrequest === false)
		$('#<#ecp_orderpiece_producttable#>').html('tablestring');
}//-}

// handles errors from the 'email' dialog 
//-!emaildlgerror
//-:dlg dlgstring ::html for the updated dialog
//-<jquery.js
//-<fglib.js
function () {
	$('#<!emaildlg!>').html('dlgstring');
	fg.dlg.unpausedialog();
	fg.ecpo.setupemaildlg();
	$('#<!emailpw_email!>').select().focus();
}//-}

// handles updates to the 'email string' 
//-!newemail
//-:email emailstring ::new email address
//-:querypw params.querypw ::1 if we should query for passwords or else 0
//-<jquery.js
//-<fglib.js
function () {
	$('#<#ecp_orderpiece_showemail#>').html('emailstring');
	if (params.querypw === true) {
		$('#<!password_message!>').html('Provide us with a password to sign in <br />and save typing on your next order!');
		fg.dlg.showmodaldialog('<!passworddlg!>');
		$('#<!password_password!>').focus();
		fg.ecpo.updatechainmask(true,1);
	} else
		fg.ecpo.closechaindlg(true,1);
}//-}

// handles updates to the chain mask 
//-!setchainmask
//-:mask 255 ::new chain mask
//-<jquery.js
//-<fglib.js
function () {
	fg.ecpo.needmask=255;
	if (fg.ecpo.needmask === 0)
		$('#<!base_continue!>').get(0).value='Place the Order';
	else	
		$('#<!base_continue!>').get(0).value='Complete Order Form';
}//-}

// fires off the dialog chain 
//-!startchain
//-<jquery.js
//-<fglib.js
function () {
	fg.ecpo.chaining=true;
	fg.ecpo.closechaindlg(true,0);
}//-}

// handles 'order not ready (mask)' dialog updates 
//-!setmaskdlg
//-:dlg dlgoutput ::new chain mask
//-<jquery.js
//-<fglib.js
function () {
	$('#<!orderdlg!>').html('dlgoutput');
	$('#<!base_ok!>').click(function() {
		if (fg.ecpo.needmask & 16)
			fg.dlg.hidemodaldialog();
		fg.ecpo.chaining=true;
		fg.ecpo.closechaindlg(true,0);
		return false;
	});
}//-}

// handles 'card verification' dialog updates 
//-!setcvdlg
//-:dlg dlgoutput ::new chain mask
//-<jquery.js
//-<fglib.js
function () {
	$('#<!orderdlg!>').html('dlgoutput');
	$('#<!base_cancel!>').click(function() {
		fg.dlg.hidemodaldialog();
	});
	$('#<!base_ok!>').click(function() {
		fg.dlg.pausedialog('Checking and Placing Order');
		cd={cv:$('#<!base_cv!>').get(0).value};
		opts=fg.seedajaxopts(cd,'QQaushort','order');
		$.ajax(opts);
		return false;
	});
	$('#<!base_cv!>').keypress(function(event){
		if (event.keyCode == '13') {
			$('#<!base_ok!>').click();
			return false;
		} else if (event.keyCode == '27') {
			$('#<!base_cancel!>').click();
			return false;
		}
		return true;
	}).focus();
}//-}

// handles 'gift note' dialog updates 
//-!setgiftdlg
//-:dlg dlgoutput ::new chain mask
//-<jquery.js
//-<fglib.js
function () {
	$('#<!orderdlg!>').html('dlgoutput');
	$('#<!base_cancel!>').click(function() {
		fg.dlg.hidemodaldialog();
	});
	$('#<!base_ok!>').click(function() {
		fg.dlg.pausedialog('Checking and Placing Order');
		cd={giftnote:$('#<!base_giftnote!>').get(0).value};
		opts=fg.seedajaxopts(cd,'QQaushort','order');
		$.ajax(opts);
		return false;
	});
	$('#<!base_giftnote!>').focus();
}//-}

////////////////////////////////////////////////////// code for the order detail window
//////////////////////////////////////////////////////
/////////////////////////////////////////////////////////// setup routine
// sets up for the 'set mfr' dialog 
//-!orderdetailsetup
//-<jquery.js
//-<fglib.js
function () {
	fg.initlib('QQproject','QQsrcbase','QQhrefbase','QQpage','QQdomain','QQencoding');
	fg.ecpo={};
	$('input[name=ok]').click(function() {
		cd={};
		cd.via=$('input[name=via]').get(0).value;
		cd.tracking=$('input[name=tracking]').get(0).value;
		cd.idorder=fg.ecpo.idorder;
		var opts=fg.seedajaxopts(cd,'QQaushort','shipping');
		opts.success=function(body,status) {$('input[name=ok]').get(0).disabled=false;};
		$.ajax(opts);
		this.disabled=true;
	});
	$('input[id^=zb]').keyup( function() {
		var evalue=this.value;
		var idsuffix=this.id.substr(2);
		var svalue=$('#za'+idsuffix).text();
		if (evalue == svalue)
			$('#zc'+idsuffix).text('GOOD').css('color','#FFFFFF').css('background-color','#00CC00');
		else	
			$('#zc'+idsuffix).text('BAD').css('color','#FFFFFF').css('background-color','#ee0000');
		return true;	
	});	
}//-}

//-!setorderdetailid
//-:id theorderid ::the order id
//-<jquery.js
//-<fglib.js
function () {
	fg.ecpo.idorder=theorderid;
}//-}

//-!ordershipped
//-<jquery.js
//-<fglib.js
function () {
	alert('Shipping has been updated.');
	$('#zcv').text('(erased)');
}//-}
