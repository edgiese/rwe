<?php if (FILEGEN != 1) die;
///// Access Unit definition file for product-based order module
class au_ecp_order extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $data;

private $prodids;	// array of integer product ids

function __construct($tag,$parent,$initdata,$state='',$data=0) {
	parent::__construct($tag,$parent,$initdata);

	$this->state=$state;
	$this->transaction=(int)$data;
}

public function canTakeChildren() {return True;}

// initialize function sets the list of product ids to display
public function initialize($pgen) {
	global $qqs,$qqj,$qqp;
	$options=$qqp->getPMod('order');
	if (!$qqs->sessionDataExists("ecp_currentcust")) {
		$cust=new ec_customer();
		$qqs->setSessionData("ecp_currentcust",$cust);
	}
	$cust=$qqs->getSessionData("ecp_currentcust");
	$order=mod_prodmain::getSessionOrderObject();

	if ($qqs->sessionDataExists("ecp_transaction")) {
		$transaction=$qqs->getSessionData("ecp_transaction");
		$bTMessagePosted=$transaction->initPage($qqj,$args);	
	} else
		$bTMessagePosted=False;
	
	// dynamic js--set chain mask
	list($chainmask,$messages)=$this->getChainMask($cust,$order);
	$args=$qqj->seedArgs($this);
	$qqj->addjs('$','ecporder::setchainmask',array_merge($args,array('mask'=>$chainmask)));

	// start dialog chain only if we need everything and there was no transaction message	
	if (!$bTMessagePosted && $chainmask == $options->fullFormMask())
		$qqj->addjs('$','ecporder::startchain',$args);
	
	// set this au as master of any children
	for ($au=$this; $au != Null; $au=$au->getParent())
		$auroot=$au;
	for ($au=$auroot; $au != Null; $au=$au->getNextAU()) {
		if ($au instanceof au_ecp_orderpiece)
			$au->setMaster($this);
	}		
}

public function makeform($name) {
	$form=new llform($this,$name);
	switch ($name) {
		case 'base':
			// controls used on base page, normal render:
			$form->addFieldset('b','');
			$form->addControl('b',new ll_button($form,'continue','Complete Order Form','button'));
			$form->addControl('b',new ll_button($form,'shop','Return to Store','button'));
			$dd=new ll_dropdown($form,'shipopt');
			$dd->addOption('?','(Select Shipping Method)');
			$form->addControl('b',$dd);
			$form->addControl('b',new ll_textarea($form,"instructions",36,3));
			$form->addControl('b',new ll_checkbox($form,'terms',"I accept the terms and conditions."));
			$form->addControl('b',new ll_edit($form,"cv",4,4,'',False));
			$form->addControl('b',new ll_textarea($form,"giftnote",60,5));
			$form->addControl('b',new ll_button($form,'ok','OK','button'));
			$form->addControl('b',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'emailpw':
			$form->addFieldset('b','Contact Email');
			$form->addControl('b',new ll_radioitem($form,'noaccount','action','Use this email and re-enter billing & shipping information'));
			$form->addControl('b',new ll_radioitem($form,'emailpw','action','Send my password to the email address'));
			$form->addControl('b',new ll_radioitem($form,'tryagain','action','Try a different email and password'));
			$form->addControl('b',new ll_edit($form,"email",25,50,'Email:',False));
			$form->addControl('b',new ll_edit($form,"password",20,20,'Returning Customers (only) Enter Password:',True));
			$form->addControl('b',new ll_button($form,'ok','OK','button'));
			$form->addControl('b',new ll_button($form,'cancel','Cancel','button'));
			$form->addControl('b',new ll_button($form,'changepw','Change Password','button'));
		break;
		case 'password':
			$form->addFieldset('b','Change Password');
			$form->addControl('b',new ll_static($form,"message"));
			$form->addControl('b',new ll_edit($form,"password",20,20,'New Password:',True));
			$form->addControl('b',new ll_edit($form,"confirm",20,20,'Retype Password to confirm:',True));
			$form->addControl('b',new ll_button($form,'ok','OK','button'));
			$form->addControl('b',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'billaddr':
			$form->addFieldset('b','Billing Address');
			$form->addControl('b',new ll_edit($form,"name",28,40,'Full Name:',False));
			$form->addControl('b',new ll_edit($form,"line1",28,40,'Address:',False));
			$form->addControl('b',new ll_edit($form,"line2",28,40,'Address line 2:',False));
			$form->addControl('b',new ll_edit($form,"city",16,40,'City:',False));
			$dd=new ll_dropdown($form,'state2','State:');
			$dd->setOptionDisplayVal(data_address::getStates(False));
			$form->addControl('b',$dd);
			$form->addControl('b',new ll_edit($form,"zip",5,10,'ZIP code:',False));
			$form->addControl('b',new ll_edit($form,"phone",14,14,'Phone:',False));
			$form->addControl('b',new ll_button($form,'ok','Save Billing Address','button'));
			$form->addControl('b',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'shipaddr':
			$form->addFieldset('b','');
			$form->addControl('b',new ll_radioitem($form,'usebilling','type',''));
			$form->addControl('b',new ll_radioitem($form,'newalternate','type',''));
			$form->addControl('b',new ll_radioitem($form,'newgift','type',''));
			$form->addControl('b',new ll_edit($form,"giftname",28,40,'New Gift for:',False));
			$form->addControl('b',new ll_button($form,'ok','Save Shipping Address','button'));
			$form->addControl('b',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'altaddr':
			$form->addFieldset('b','Alternate Shipping/Gift Address');
			$form->addControl('b',new ll_edit($form,"name",28,40,'Full Name:',False));
			$form->addControl('b',new ll_edit($form,"line1",28,40,'Address:',False));
			$form->addControl('b',new ll_edit($form,"line2",28,40,'Address line 2:',False));
			$form->addControl('b',new ll_edit($form,"city",16,40,'City:',False));
			$dd=new ll_dropdown($form,'state2','State:');
			$dd->setOptionDisplayVal(data_address::getStates(False));
			$form->addControl('b',$dd);
			$form->addControl('b',new ll_edit($form,"zip",5,10,'ZIP code:',False));
			$form->addControl('b',new ll_edit($form,"phone",14,14,'Phone:',False));
			$form->addControl('b',new ll_button($form,'ok','Save Shipping Address','button'));
			$form->addControl('b',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'payment':
			$form->addFieldset('p',"Payment Method");
			$dd=new ll_dropdown($form,"cctype");
			$dd->setOptionArray(data_cc::getTypes());
			$form->addControl('p',$dd);
			$ctrl=new ll_edit($form,"ccnumber",22,22,'Card Number:');
			$form->addControl('p',$ctrl);
			$ctrl=new ll_edit($form,"ccexp",5,5,'Expiration Date (mm/yy):');
			$form->addControl('p',$ctrl);
			$ctrl=new ll_edit($form,"ccname",28,28,'Name on Card:');
			$form->addControl('p',$ctrl);
			$form->addControl('p',new ll_button($form,'ok','Save Payment Method','button'));
			$form->addControl('p',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'instructions':
			$form->addFieldset('s',"Special Instructions");
			$form->addControl('s',new ll_textarea($form,"instructions",36,3));
			$form->addControl('s',new ll_button($form,'ok','Save Special Instructions','button'));
			$form->addControl('s',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'discounts':
			$form->addFieldset('s','');
			$form->addControl('s',new ll_button($form,'cancel','Done','button'));
		break;
		case 'thankyou':
			$form->addFieldset('s','');
			$form->addControl('s',new ll_button($form,'cancel','Done','button'));
		break;
		case 'terms':
			$form->addFieldset('s','');
			$form->addControl('s',new ll_checkbox($form,'terms',"I accept the terms and conditions."));
			$form->addControl('s',new ll_button($form,'cancel','Done','button'));
		break;
		default:
			throw new exception("illegal form name $name");
		break;
	}
	return $form;
}

private function getFormNames() {return array('base','payment','instructions','discounts','thankyou','terms','billaddr','shipaddr','altaddr','emailpw','password');} 
private function getDlgNames() {return array('paymentdlg','instructionsdlg','discountsdlg','thankyoudlg','termsdlg','billaddrdlg','shipaddrdlg','altaddrdlg','emaildlg','orderdlg','passworddlg');}
private function getItemNames() {return array('subtotal','tax','shippingcharge','total');}

public function declareids($idstore,$state) {
	$names=$this->getDlgNames();
	foreach($names as $name)
		$idstore->declareHTMLid($this,True,$name);
	$names=$this->getItemNames();
	foreach($names as $name)
		$idstore->declareHTMLid($this,True,$name);
	$names=$this->getFormNames();
	foreach($names as $name)
		$this->makeform($name)->declareids($idstore);
	$idstore->registerEncodedPage();
}

public function declarestyles($stylegen,$state) {
	$names=$this->getDlgNames();
	foreach($names as $name)
		$stylegen->registerStyledId($this->longname.'_'.$name,'div:h3,h4,h5,p,td,th,div div,a,li,h3 a,td a','orderdlg',$this->getParent()->htmlContainerId());
	$names=$this->getFormNames();
	foreach($names as $name)
		$this->makeform($name)->declarestyles($stylegen,$this->longname);
	$stylegen->registerClass('formerror','span');
}

public function declarestaticjs($js) {
	global $qq;
	
	$args=$js->seedArgs($this);
	$js->addjs('$','ecporder::ordersetup',$args);
	// the following define or use labels not seen in setup:
	$args['***']='';	// turns off parameter match checking
	$js->scanjsdependencies('ecporder::setmaskdlg',$args);
	$js->scanjsdependencies('ecporder::setcvdlg',$args);
	$js->scanjsdependencies('ecporder::setgiftdlg',$args);
}

// returns output for dialog.  errlevel=0 is base dialog, =1 is all error options shown, and =2 is no 'email password'
private function emailFormOutput($cust,$order,$errlevel,$bademail='') {
	global $qqi,$qqp;
	
	$form=$this->makeform('emailpw');
	$emailerror='';
	$email=False;
	if ($bademail != '') {
		$form->setValue('email',$bademail);
		if ($errlevel == 0)
			$emailerror="<span{$qqi->cstr('formerror')}>Enter a valid email address</span><br />";
	} else if (False !== ($email=$cust->getEmail()))
		$form->setValue('email',$email);
	$format="<<!email>><br />$emailerror<<email>><br /><<!password>><br /><<password>><br /><<ok>>&nbsp;&nbsp;&nbsp;<<cancel>>";
	$options=$qqp->getPMod('order');
	if ($options->payPalExpress())
		$format .= "<br /><br /><strong>OR:</strong><br /><br /><img onclick='fg.ecpo.paypalexpress(0)' src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' border='0' align='top' alt='PayPal'/>";
	if ($email !== False)
		$format .= "&nbsp;&nbsp;<<changepw>>";
	if ($errlevel > 0) {
		$form->setValue('tryagain',True);
		$errorstring='The email and password does not match any in our records.';
		if ($errlevel == 1)
			$extraformat='<<noaccount>><br /><<emailpw>><br /><<tryagain>><br />';
		else {
			$errorstring .= '<br />We could not send the password to the email you specified.';	
			$extraformat='<<noaccount>><br /><<tryagain>></br />';
		}	
		$format='<p>'.$errorstring.'<br />What do you want to do?</br></p>'.$extraformat.$format;	
	}
	return $form->getOutputFormStart().$form->getOutputFieldsetStart('b').$form->getFormattedOutput($format,False).$form->getOutputFieldsetEnd('b').$form->getOutputFormEnd();
}


// returns table output for products
static function productTable($cust,$order,$bByEdit=False) {
	global $qqu,$qqi;

	$pi=new mod_prodinfo;
	$lines=$order->getOrderArray(True);
	if (sizeof($lines) == 0) {
		$output="<p>You don't have anything in your cart.  Got to the <a href=\"shop\">Shopping Page</a> and put one or more items in your cart to purchase them.</p>";
	} else {
		$qtyhdr= $bByEdit ? '' : '<th>Adjust</th>';
		$output="<table><tr><th>Qty</th>$qtyhdr<th>Item</th><th>Unit Price</th><th>Disc.</th><th>Amount</th></tr>";
		foreach ($lines as $litem) {
			list($qty,$prodid,$baseprice,$discount,$subtotal)=$litem;
			$info=$pi->getProdInfo($prodid);
			$title=$qqu->creolelite2html($info['title']);
			if (strlen($restriction=ec_prodorder::getRestrictionString($prodid)) > 0)
				$title .= " &bull; <em>$restriction</em>";
			if ($bByEdit) {
				$output .= "<tr><td><input type=\"edit\" name=\"qty[$prodid]\" value=\"$qty\" size=\"3\" maxlength=\"5\" /></td>";
			} else	
				$output .= "<tr><td id=\"z$prodid\">$qty</td><td style=\"text-align:center;line-height:20px\"><a href=\"javascript:fg.ecpo.more($prodid)\">more</a><br /><a href=\"javascript:fg.ecpo.fewer($prodid)\">fewer</a></td>";
			$output .= "<td>{$info['mfr']}: $title</td>";
			$baseprice=sprintf('%1.2f',$baseprice/100);
			$discount=sprintf('%1.2f',$discount/100);
			$subtotal=sprintf('%1.2f',$subtotal/100);
			$output .= "<td style=\"text-align:right\">$baseprice</td><td style=\"text-align:right\">$discount</td><td style=\"text-align:right\">$subtotal</td></tr>";
		}
		$output .= '</table>';
	}
	return $output;
}

// creates a mask of needed dialogs.  returns array(mask,messages)
private function getChainMask($cust,$order) {
	$mask=0;
	$messages=array();
	if (!$order->getTermsAccepted()) {
		$mask |= 8;
		$messages[]='You need to accept the terms.';
	}	
	$preferredshipping=$cust->getPreferredShipping();
	$shipopt=$order->getShippingOptions($cust);
	if ($preferredshipping == ec_prodorder::SHIP_UNASSIGNED || !isset($shipopt[$preferredshipping])) {
		$mask |= 16;
		$messages[]='You need to choose a shipping option';
	}
	if (False === $cust->getEmail()) {
		$mask |= 1;
		$messages[]='You need to specify an email address so we can contact you.';
	}
	$saddr=$cust->getShippingAddresses();
	if (!($saddr[0][0] instanceof data_name) || !$saddr[0][0]->isComplete()) {
		$mask |= 2;
		$messages[]='You need to specify a billing name.';
	}
	if (!($saddr[0][1] instanceof data_addresscols) || !$saddr[0][1]->isComplete()) {
		$mask |= 2;
		$messages[]='You need to specify a billing address.';
	}
	if (!($saddr[0][2] instanceof data_phone) || !$saddr[0][2]->isComplete()) {
		$mask |= 2;
		$messages[]='You need to specify a billing phone for shipping and billing inquiries.';
	}
	if (False === ($cc=$cust->getActiveCreditCard()) || !$cc->isComplete()) {
		$mask |= 4;
		$messages[]='You need to specify a payment method.';
	}
	return array($mask,$messages);
}

// returns table output for totals
public function totalTable($cust,$order) {
	global $qqi;

	$form=$this->makeform('base');	
	$lines=$order->getOrderArray(True);
	$output='<table>';
	$subtotal=0;
	foreach ($lines as $litem) {
		$subtotal += $litem[4];
	}
	$showsubtotal=sprintf('%1.2f',$subtotal/100);
	$output .= "<td style=\"text-align:right\">Subtotal</td><td{$qqi->idcstr($this->longname.'_subtotal')} style=\"text-align:right;min-width:50px\">$showsubtotal</td></tr>";

	$tax=$order->calculateTax($cust);
	$showtax= ($tax === False) ? '?.??' : sprintf('%1.2f',$tax/100);
	$output .= "<tr><td style=\"text-align:right\">Tax (Depends on billing address)</td><td{$qqi->idcstr($this->longname.'_tax')} style=\"text-align:right\">$showtax</td></tr>";

	$shipping=False;
	$shipdesc=$order->getAllShippingOptions();
	$shipopt=$order->getShippingOptions($cust);

	// patch for peach basket special
	if ($subtotal > 3500) {
		$newshipopt=array();
		foreach ($shipopt as $shiptype=>$cost)
			$newshipopt[$shiptype]=0;
		$shipopt=$newshipopt;
	}
		
	$dd=$form->getControl('shipopt');
	$preferredcode=$cust->getPreferredShipping();
	$dd->setValue(ec_prodorder::SHIP_UNASSIGNED);
	$showshipping='?.??';
	foreach ($shipopt as $code=>$cost) {
		infolog("dbg","code=$code cost=$cost");
		$dd->addOption($code,$shipdesc[$code].sprintf(' (%1.2f)',$cost/100));
		if ($code == $preferredcode) {
			$shipping=$cost;
			$showshipping=sprintf('%1.2f',$shipping/100);
			$dd->setValue($code);
		}	
	}
	$output .= "<tr><td style=\"text-align:right\">{$form->getFormattedOutput('<<shipopt>>',False)} </td><td{$qqi->idcstr($this->longname.'_shippingcharge')} style=\"text-align:right\">$showshipping</td></tr>";

	if ($shipping === False || $tax === False)
		$showtotal='?.??';
	else {
		$total=$subtotal+$tax+$shipping;
		$showtotal=sprintf('%1.2f',$total/100);
	}	
	$output .= "<tr><td style=\"text-align:right\">Total Order Amount</td><td{$qqi->idcstr($this->longname.'_total')} style=\"text-align:right\">$showtotal</td></tr>";
	$output .= '</table>';
	return $output;
}

// returns form output as string
private function shipAddrFormOutput($cust,$order,$state='',$trans=0) {
	$saddrs=$cust->getShippingAddresses();
	$nalternate=sizeof($saddrs)-1;
	$idcurship=$cust->useShippingAddress();
	$format='<table>';
	if ($nalternate > 0) {
		$colspan=' colspan="2"';
		foreach ($saddrs as $idship=>$sa) {
			if ($idship == 0)
				continue;
			$checked=($idcurship == $idship) ? ' checked="checked"' : '';
			$format .= "<tr><td><input type=\"radio\" name=\"type\" value=\"{$idship}\"$checked /></td>";
			$addressout=$this->getShippingAddress($cust,False,$idship);
			$format .= "<td>$addressout</td>";
			$format .= "<td><a href=\"javascript:fg.ecpo.editalt($idship)\">Edit</a>&nbsp;&nbsp;<a href=\"javascript:fg.ecpo.delalt($idship)\">Delete</a></td></tr>";
		}
	} else {
		$colspan='';
	}
	$format .= "<tr><td><<usebilling>></td><td$colspan>Ship to Billing Address</td></tr>";
	$format .= "<tr><td><<newalternate>></td><td$colspan>New Alternate Shipping Address</td></tr>";
	$format .= "<tr><td><<newgift>></td><td$colspan><<!giftname>> <<giftname>></td></tr></table><<ok>>&nbsp;&nbsp;&nbsp;<<cancel>>";
	$form=$this->makeform('shipaddr');
	$form->setValue('usebilling',($idcurship == 0));
	return $form->getFormattedOutput($format,True,$state,$trans);
}

// returns shipping addresses, html output ready with slashes.  form can be False if no fields need to be set
public function getShippingAddress($cust,$form,$idship) {
	$saddrs=$cust->getShippingAddresses();
	if (!isset($saddrs[$idship]))
		throw new exception("undefined ship index $idship");
	list($name,$address,$phone,$giftrecipient)=$saddrs[$idship];

	if ($name instanceof data_name) {
		if ($form !== False)
			$form->setValue('name',$name->output());
		$retval=$name->output();
	} else
		$retval='&nbsp;';	
	$retval=($name instanceof data_name) ? $name->output() : '&nbsp;';
	if ($idship != 0 && $giftrecipient)
		$retval .= ' (gift)';
	if ($address instanceof data_addresscols) {
		$addrstr=$address->output();
		if ($form !== False) {
			foreach (array(data_addresscols::LINE1=>'line1',data_addresscols::LINE2=>'line2',data_addresscols::CITY=>'city',data_addresscols::STATE=>'state2',data_addresscols::ZIP=>'zip') as $param=>$tag) {
				$form->setValue($tag,$address->getArgParam($param));
			}
		}	
		$retval .= '<br />'.$address->output();
	}
	if ($phone instanceof data_phone) {
		if ($form !== False)
			$form->setValue('phone',$phone->output());
		$retval .= '<br />Phone: '.$phone->output();
	}
	return $retval;
}

// adds existing credit cards to a credit card form
private function addOldCreditCards($cust,$form) {
	$ccs=$cust->getCreditCards();
	$dd=$form->getControl('cctype');
	foreach ($ccs as $idcc=>$cc) {
		if ($cc->getArgParam(0) == data_cc::TYPE_PAYPAL)
			continue;	// only allow one 'paypal' entry
		$dd->addOption($idcc,$cc->output1());
	}
}

// does processing for two very similar situations with an address-related dialog
private function processAddressDlg($js,$page,$cust,$order,$idship,$dlgname) {
	global $qqi;
	$newname=new data_name();
	if (True !== ($errors=$newname->verifyAndSet($_REQUEST['name'])))
		$all_errors=$errors;
	else
		$all_errors=array();	
	$newaddr=new data_addresscols(True);
	if (True !== ($errors=$newaddr->verifyAndSet(array_merge($_REQUEST,array('country'=>'USA')),True)))
		$all_errors=array_merge($all_errors,$errors);
	$newphone=new data_phone();
	if (True !== ($errors=$newphone->verifyAndSet($_REQUEST['phone'])))			
		$all_errors=array_merge($all_errors,$errors);
	$form=$this->makeform($dlgname);
	if (sizeof($all_errors) > 0) {
		// errors occurred.  redisplay form with error messages
		foreach (array('name','line1','line2','city','state2','zip','phone') as $field) {
			$form->setValue($field,$_REQUEST[$field]);
		}	
		$output=addslashes($form->getAnnotatedOutput($all_errors,$qqi->cstr('formerror')));
		$errorfields=array_keys($all_errors);
		$fieldlong="{$this->longname}_{$dlgname}_".substr($errorfields[0],1);
		$focusfield=$qqi->htmlShortFromLong($fieldlong,$page);					
		$args=$js->seedArgs($this);
		echo $js->snippetToCode("ecporder::{$dlgname}dlgerror",array_merge($args,array('dlg'=>$output,'focusfield'=>$focusfield)),$page);
		return;
	}	
	$cust->updateShippingAddress($idship,$newname,$newaddr,$newphone);
	$addroutput=$this->getShippingAddress($cust,$form,$idship);
	$args=$js->seedArgs($this);
	if ($dlgname == 'billaddr') {
		$snippet='ecporder::newbilladdr';
	} else {
		$snippet='ecporder::newshipaddr';
		if ($idship == 0) {
			echo $js->snippetToCode($snippet,array_merge($args,array('showaddr'=>addslashes($addroutput))),$page);
			$addroutput='(Use Billing Address)';
			$snippet='ecporder::newbilladdr';
		}	
	}
	echo $js->snippetToCode($snippet,array_merge($args,array('showaddr'=>addslashes($addroutput))),$page);
	$table=$this->totalTable($cust,$order);
	echo $js->snippetToCode('ecporder::newtotals',array_merge($args,array('table'=>addslashes($table))),$page);
}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi,$qqu;
	
	$action=$this->state;
	
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	$cust=$qqs->getSessionData("ecp_currentcust");
	$order=mod_prodmain::getSessionOrderObject();
	$args=$js->seedArgs($this);

	switch ($action) {
		case 'email':
			switch ($_REQUEST['action']) {
				case 'noaccount':
					if ($cust->setEmail($_REQUEST['email'])) {
						$querypw=($cust->needPassword()) ? 'true' : 'false'; 
						echo $js->snippetToCode('ecporder::newemail',array_merge($args,array('email'=>addslashes($_REQUEST['email']),'querypw'=>$querypw)),$page);
					} else {
						$dlgout=$this->emailFormOutput($cust,$order,0,$_REQUEST['email']);
						echo $js->snippetToCode('ecporder::emaildlgerror',array_merge($args,array('dlg'=>addslashes($dlgout))),$page);
					}	
				break;
				case 'emailpw':
					if ($cust->emailPassword($_REQUEST['email'])) {
						echo "fg.ecpo.closechaindlg(false);alert('Your password has been emailed to the email address: {$_REQUEST['email']}');";
					} else {
						$dlgout=$this->emailFormOutput($cust,$order,2,$_REQUEST['email']);
						echo $js->snippetToCode('ecporder::emaildlgerror',array_merge($args,array('dlg'=>addslashes($dlgout))),$page);
					}	
				break;
				case 'tryagain':
					if (False !== ($newcust=ec_customer::createFromEmail($_REQUEST['email'],$_REQUEST['password']))) {
						$qqs->setSessionData("ecp_currentcust",$newcust);
						echo "window.location.reload(true);";						
					} else {
						$dlgout=$this->emailFormOutput($cust,$order,1,$_REQUEST['email']);
						echo $js->snippetToCode('ecporder::emaildlgerror',array_merge($args,array('dlg'=>addslashes($dlgout))),$page);
					}
				break;
				default: 
					throw new exception("required action type not set");
				break;
			}	
			
		break;
		case 'password':
			if (isset($_REQUEST['password'])) {
				$cust->setPassword($_REQUEST['password']);
			}
			echo 'fg.ecpo.closechaindlg(true,0)';
		break;
		case 'billaddr':
			$this->processAddressDlg($js,$page,$cust,$order,0,'billaddr');
		break;
		case 'shipaddr':
			if (!isset($_REQUEST['type']))
				throw new exception("required input not set");
			$newshipcode=$_REQUEST['type'];
			if ($newshipcode == 'usebilling' || is_numeric($newshipcode)) {
				// these cases do not require a new address--use existing one
				$idship=($newshipcode == 'usebilling') ? 0 : (int)$newshipcode;
				$bCreating=False;
			} else {
				// create a new alternate address
				$address=new data_addresscols(True);
				$name=new data_name;
				if ($bGift=($newshipcode == 'newgift')) {
					$name->setFromSingle($_REQUEST['giftname'],True);
				} else {
					$saddrs=$cust->getShippingAddresses();
					if ($saddrs[0][0] instanceof data_name)
						$name=$saddrs[0][0];
				}	
				$idship=$cust->addShippingAddress($address,$name,False,$bGift);
				$bCreating=True;
			}
			$cust->useShippingAddress($idship);

			if ($bCreating || (isset($_REQUEST['edit']) && $_REQUEST['edit'] != '0')) {
				// fill out & bring up alternate text dialog--that's all
				$form=$this->makeform('altaddr');
				$this->getShippingAddress($cust,$form,$idship);
				$output=addslashes($form->getAnnotatedOutput(array(),$qqi->cstr('formerror')));
				$focusfield=$qqi->htmlShortFromLong($this->longname.'_altaddr_line1',$page);					
				echo $js->snippetToCode('ecporder::setupaltaddrdlg',array_merge($args,array('dlg'=>$output,'focusfield'=>$focusfield)),$page);
			} else {
				// end the dialog and update shipping options
				$addroutput=($idship == 0) ? '(Use Billing Address)' : $this->getShippingAddress($cust,False,$idship);
				echo $js->snippetToCode('ecporder::newshipaddr',array_merge($args,array('showaddr'=>addslashes($addroutput))),$page);
				$table=$this->totalTable($cust,$order);
				echo $js->snippetToCode('ecporder::newtotals',array_merge($args,array('table'=>addslashes($table))),$page);
			}	
		break;
		case 'altaddr':
			$idship=$cust->useShippingAddress();
			$this->processAddressDlg($js,$page,$cust,$order,$idship,'altaddr');
			$dlgout=$this->shipAddrFormOutput($cust,$order);	
			echo $js->snippetToCode('ecporder::updateshipaddrdlg',array_merge($args,array('dlg'=>addslashes($dlgout))),$page);
		break;
		case 'cancelaltaddr':
			// if address is blank, go back to using billing address
			$saddrs=$cust->getShippingAddresses();
			$idship=$cust->useShippingAddress();
			if ($idship != 0) {
				$address=$saddrs[$idship][1];
				if (!($address instanceof data_addresscols) || $address->isBlank()) {
					$cust->useShippingAddress(0);
					$cust->deleteShippingAddress($idship);
					$idship=0;
				}	
				$addroutput=($idship == 0) ? '(Use Billing Address)' : $this->getShippingAddress($cust,False,$idship);
				echo $js->snippetToCode('ecporder::newshipaddr',array_merge($args,array('showaddr'=>addslashes($addroutput))),$page);
				$table=$this->totalTable($cust,$order);
				echo $js->snippetToCode('ecporder::newtotals',array_merge($args,array('table'=>addslashes($table))),$page);
				$dlgout=$this->shipAddrFormOutput($cust,$order);	
				echo $js->snippetToCode('ecporder::updateshipaddrdlg',array_merge($args,array('dlg'=>addslashes($dlgout))),$page);
			}
		break;
		case 'delaltaddr':
			if (isset($_REQUEST['idship']) && 0 != ($idship=(int)$_REQUEST['idship'])) {
				$idoldship=$cust->useShippingAddress();
				if ($idoldship == $idship) {
					$cust->useShippingAddress(0);
					$addroutput='(Use Billing Address)';
					echo $js->snippetToCode('ecporder::updateshipdisplay',array_merge($args,array('showaddr'=>addslashes($addroutput))),$page);
					$table=$this->totalTable($cust,$order);
					echo $js->snippetToCode('ecporder::newtotals',array_merge($args,array('table'=>addslashes($table))),$page);
				}	
				$cust->deleteShippingAddress($idship);
				$dlgout=$this->shipAddrFormOutput($cust,$order);	
				echo $js->snippetToCode('ecporder::updateshipaddrdlg',array_merge($args,array('dlg'=>addslashes($dlgout))),$page);
			}
		break;
		case 'editaltaddr':
			if (isset($_REQUEST['idship']) && 0 != ($idship=(int)$_REQUEST['idship'])) {
				// fill out & bring up alternate text dialog
				$form=$this->makeform('altaddr');
				$this->getShippingAddress($cust,$form,$idship);
				$output=addslashes($form->getAnnotatedOutput(array(),$qqi->cstr('formerror')));
				$focusfield=$qqi->htmlShortFromLong($this->longname.'_altaddr_name',$page);					
				echo $js->snippetToCode('ecporder::setupaltaddrdlg',array_merge($args,array('dlg'=>$output,'focusfield'=>$focusfield)),$page);
			}
		break;
		case 'payment':
			if (is_numeric($_REQUEST['cctype'])) {
				// user specified an existing credit card.
				$ccs=$cust->getCreditCards();
				if (!isset($ccs[(int)$_REQUEST['cctype']]))
					throw new exception("illegal credit card index requested");
				$cc=$ccs[(int)$_REQUEST['cctype']];	
			} else {
				// read data from fields
				$cc=new data_cc();
				$errorsOrTrue=$cc->verifyAndSet($_REQUEST,True);
				if ($errorsOrTrue !== True) {
					// errors in form.  update form and go back.
					$form=$this->makeform('payment');
					$this->addOldCreditCards($cust,$form);
					foreach (array('cctype','ccnumber','ccexp','ccname') as $field) {
						$form->setValue($field,$_REQUEST[$field]);
					}	
					$output=addslashes($form->getAnnotatedOutput($errorsOrTrue,$qqi->cstr('formerror')));
					$errorfields=array_keys($errorsOrTrue);
					$fieldlong=$this->longname.'_payment_'.substr($errorfields[0],1);
					$focusfield=$qqi->htmlShortFromLong($fieldlong,$page);					
					$dlgdiv=$qqi->htmlShortFromLong($this->longname.'_paymentdlg',$page);					
					echo $js->snippetToCode('ecporder::paymentdlgerror',array_merge($args,array('dlgdiv'=>$dlgdiv,'dlg'=>$output,'focusfield'=>$focusfield)),$page);
					return;
				}
				// credit card data is good.  look for identical card in existing ones and delete if match found, then add
				$cust->useCreditCard($cust->addCreditCard($cc));
			}
			// update display with data from cc and close out dialog.
			$ccstring=addslashes($cc->output3());
			echo $js->snippetToCode('ecporder::newcc',array_merge($args,array('ccstring'=>$ccstring)),$page);
		break;
		case 'instructions':
			if (isset($_REQUEST['instructions'])) {
				$order->setSpecialInstructions(stripslashes($_REQUEST['instructions']));
			}
			$instructions=addslashes(strtr($order->getSpecialInstructions(),array("\n" =>'<br />',"\r\n" =>'<br />')));
			echo $js->snippetToCode('ecporder::newinstructions',array_merge($args,array('instructions'=>$instructions)),$page);
		break;
		case 'shipopt':
			if (isset($_REQUEST['shipopt'])) {
				$cust->setPreferredShipping($_REQUEST['shipopt']);
				$table=$this->totalTable($cust,$order);
				echo $js->snippetToCode('ecporder::newshipopt',array_merge($args,array('table'=>addslashes($table))),$page);
			}
		break;
		case 'terms':
			if (isset($_REQUEST['terms'])) {
				$order->setTermsAccepted($_REQUEST['terms']);
			}
			echo $js->snippetToCode('ecporder::newacceptance',array_merge($args,array('accepted'=>$order->getTermsAccepted() ? 'true' : 'false')),$page);
		break;
		case 'numbers':
			foreach($_REQUEST as $index=>$value) {
				if (substr($index,0,3) == 'zzz' && is_numeric(substr($index,3))) {
					$prodid=(int)substr($index,3);
					$count=(int)$value;
					infolog("dbg","prodid=$prodid count=$count");
					$order->addProduct($prodid,$count);
				}
			}
			$table=self::productTable($cust,$order);
			echo $js->snippetToCode('ecporder::newproducts',array_merge($args,array('table'=>addslashes($table))),$page);
			$table=$this->totalTable($cust,$order);
			echo $js->snippetToCode('ecporder::newtotals',array_merge($args,array('table'=>addslashes($table))),$page);
		break;
		case 'order':
			$prods=$order->getOrderArray(False);
			if (sizeof($prods) <= 0) {
				echo "alert('There is nothing in your order.  Increase quantities of items above zero or add more items to the shopping cart.');fg.dlg.hidemodaldialog();";
				return;
			}
			if (isset($_REQUEST['cv'])) {
				$order->setExtra('cv',$_REQUEST['cv']);
			}	
			if (isset($_REQUEST['giftnote'])) {
				$order->setExtra('giftnote',stripslashes($_REQUEST['giftnote']));
			}
			list($chainmask,$messages)=$this->getChainMask($cust,$order);
			if ($chainmask != 0) {
				$args=$js->seedArgs($this);
				echo $js->snippetToCode('ecporder::setchainmask',array_merge($args,array('mask'=>$chainmask)));

				$output="<h3>You have errors in your form</h3><p>Please correct the following:<br />".implode('<br />',$messages)."</p>";
				$form=$this->makeform('base');
				$output .= $form->getFormattedOutput('<<ok>>');
				echo $js->snippetToCode('ecporder::setmaskdlg',array_merge($args,array('dlg'=>addslashes($output))));
				return;
			}
			$cc=$cust->getActiveCreditCard();
			if ($cc->getArgParam(0) != data_cc::TYPE_PAYPAL && !$order->getExtra('cv')) {
				$output="<h3>Enter Card Verification Code</h3><p>Please enter the card verification number from your credit card.<br />(For your protection, we do not store these codes between orders.)<br /></p>";
				$output .= "<p><img src=\"{$qqi->hrefPrep('m/img/visa_cvv2.gif',False,$page,idstore::ENCODE_BYSOURCE)}\" /> <img src=\"{$qqi->hrefPrep('m/img/amex_cvv2.gif',False,$page,idstore::ENCODE_BYSOURCE)}\" /> </p>";
				$form=$this->makeform('base');
				$output .= $form->getFormattedOutput('CVV2 Code: <<cv>><br /><<ok>> <<cancel>>');
				echo $js->snippetToCode('ecporder::setcvdlg',array_merge($args,array('dlg'=>addslashes($output))));
				return;
			}
			if ($cust->isActiveShippingAddressGift() && !isset($_REQUEST['giftnote'])) {
				$saddrs=$cust->getShippingAddresses();
				$idship=$cust->useShippingAddress();
				$name=$saddrs[$idship][0];
				$output="<h3>Enter a gift message for {$name->output()}</h3>";
				$form=$this->makeform('base');
				if ($note=$order->getExtra('giftnote'))
					$form->setValue('giftnote',$note);
				$output .= $form->getFormattedOutput('<<giftnote>><br /><<ok>> <<cancel>>');
				echo $js->snippetToCode('ecporder::setgiftdlg',array_merge($args,array('dlg'=>addslashes($output))));
				return;
			}
			// HERE IS WHERE YOU VERIFY TRANSACTION.
			sleep(2);
			$result=$order->place($cust);
			if ($result !== True) {
			} else {
				$cust->save();
			}
			// put out thank you message and password update form.  set all quantities to zero
			foreach ($prods as $prod) {
				list($qty,$prodid,$baseprice,$discount,$subtotal)=$prod;
				$order->addProduct($prodid,0);
			}
			echo 'fg.ecpo.showthankyou();';	
		break;
		case 'paypalexpress':
			$total=$order->estimateTotal($cust);
			if ($total == 0) {
				echo "fg.dlg.hidemodaldialog();alert('You aren\\'t ordering anything.  Put something on the order before contacting Paypal.');";
				return;
			}
			if (!$qqs->sessionDataExists("ecp_transaction")) {
				$transaction=new ec_transact_paypal();
				$qqs->setSessionData("ecp_transaction",$transaction);
			} else {
				$transaction=$qqs->getSessionData("ecp_transaction");
				if (!($transaction instanceof ec_transact_paypal)) {
					// eliminate old transaction if it's not a paypal compatible one
					$transaction=new ec_transact_paypal();
					$qqs->setSessionData("ecp_transaction",$transaction);
				}
			}
			$returnURL=llform::buildFormURL($this,$originUri,'fullexpress',0,array('continue'=>'1'),$page);
			$cancelURL=llform::buildFormURL($this,$originUri,'fullexpress',0,array('continue'=>'0'),$page);
			$paymentAmount=sprintf("%1.2f",$total/100);
			$retval=$transaction->setupExpressCheckout($paymentAmount,$returnURL,$cancelURL,'USD','Sale',$_REQUEST['override'] == 1 ? $cust : Null);
			if (is_string($retval))
				echo "fg.dlg.hidemodaldialog();alert('".addcslashes($retval,"\0..\37!@\177..\377")."');";
		break;
	}
}

// paypal express return implemented as a form call
public function processVars($originUri) {
	global $qq,$qqs;
	switch ($this->state) {
		case 'fullexpress':
			if (isset($_REQUEST['continue']) && $_REQUEST['continue'] == '1') {
				// get data from paypal and fill out form with it
				if (!$qqs->sessionDataExists("ecp_transaction"))
					return message::redirectString("Paypal transaction cannot be determined.  Return to Order Screen and Try again Please");
				$transaction=$qqs->getSessionData("ecp_transaction");
				$cust=$qqs->getSessionData("ecp_currentcust");
				$order=mod_prodmain::getSessionOrderObject();
				$retval=$transaction->getOrderInfoFromPaypal($cust,$order);
				if (is_string($retval))
					return message::redirectString($retval);
			}
		break;
		default:
			throw new exception("unknown state:  {$this->state}");
		break;
	}
	return $originUri;
}


// does output of dialogs--all hidden
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qqu,$qqp;
	
	$cust=$qqs->getSessionData("ecp_currentcust");
	$order=mod_prodmain::getSessionOrderObject();
	$options=$qqp->getPMod('order');

	// payment dialog		
	echo "<div{$qqi->idcstr($this->longname.'_paymentdlg')}>";
	$form=$this->makeform('payment');
	$this->addOldCreditCards($cust,$form);
	echo $form->getAnnotatedOutput();
	echo "</div>";

	// special instructions dialog		
	echo "<div{$qqi->idcstr($this->longname.'_instructionsdlg')}>";
	$form=$this->makeform('instructions');
	$form->setValue('instructions',$order->getSpecialInstructions());
	echo $form->getAnnotatedOutput();
	echo "</div>";
	
	// specials info dialog
	if (False !== ($au=$options->getDiscountsAU($this))) {
		echo "<div{$qqi->idcstr($this->longname.'_discountsdlg')}>";
		$au->output($pgen,$brotherid);
		$form=$this->makeform('discounts');
		echo $form->getDumpStyleFormOutput();
		echo "</div>";
	}
	
	// thank-you dialog
	$au=$options->getThankYouAU($this);
	echo "<div{$qqi->idcstr($this->longname.'_thankyoudlg')}>";
	$au->output($pgen,$brotherid);
	$form=$this->makeform('thankyou');
	echo $form->getDumpStyleFormOutput();
	echo "</div>";

	// terms acceptance dialog
	if (False !== ($au=$options->getTermsAU($this))) {
		echo "<div{$qqi->idcstr($this->longname.'_termsdlg')}>";
		$au->output($pgen,$brotherid);
		$form=$this->makeform('terms');
		$form->setValue('terms',$order->getTermsAccepted());
		echo $form->getDumpStyleFormOutput();
		echo "</div>";
	}
	
	// billing address dialog		
	echo "<div{$qqi->idcstr($this->longname.'_billaddrdlg')}>";
	$billaddrform=$this->makeform('billaddr');
	$billaddr=$this->getShippingAddress($cust,$billaddrform,0);
	echo $billaddrform->getAnnotatedOutput();
	echo "</div>";

	// shipping address dialog
	echo "<div{$qqi->idcstr($this->longname.'_shipaddrdlg')}>";
	echo $this->shipAddrFormOutput($cust,$order);
	echo "</div>";

	// alternate address dialog -- contents filled out by ajax call
	echo "<div{$qqi->idcstr($this->longname.'_altaddrdlg')}>";
	echo "</div>";

	// final order dialog -- contents filled out by ajax call
	echo "<div{$qqi->idcstr($this->longname.'_orderdlg')}>";
	echo "</div>";

	// email dialog
	echo "<div{$qqi->idcstr($this->longname.'_emaildlg')}>";
	echo $this->emailFormOutput($cust,$order,0);
	echo "</div>";
	
	// password dialog
	echo "<div{$qqi->idcstr($this->longname.'_passworddlg')}>";
	$form=$this->makeform('password');
	echo $form->getDumpStyleFormOutput();
	echo "</div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
