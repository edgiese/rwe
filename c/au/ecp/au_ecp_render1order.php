<?php if (FILEGEN != 1) die;
///// Access Unit definition file for product-based order module
class au_ecp_render1order extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $data;

function __construct($tag,$parent,$initdata,$state='',$data=0) {
	parent::__construct($tag,$parent,$initdata);

	$this->state=$state;
	$this->transaction=(int)$data;
}
public function canTakeChildren() {return True;}

public function initialize($pgen) {
	global $qqs;
	if (!$qqs->sessionDataExists("ecp_currentcust")) {
		$cust=new ec_customer();
		$qqs->setSessionData("ecp_currentcust",$cust);
	}
}

private function getStates() {return array('base','email','billing','shipping','payment','terms','confirm','finish');}

// returns form if string 'name' specified, or array of form names for render 1 (False) or other (True)
private function makeform($state) {
	
	if ($state == '')
		$state='base';		
	$form=new llform($this,$state);
	switch ($state) {
		case '':
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
		case 'email':
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
		case 'billing':
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
			$form->addControl('b',new ll_button($form,'ok','Set Billing Address','button'));
			$form->addControl('b',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'shipping':
			$form->addFieldset('b','');
			$form->addControl('b',new ll_radioitem($form,'usebilling','type',''));
			$form->addControl('b',new ll_radioitem($form,'newalternate','type',''));
			$form->addControl('b',new ll_radioitem($form,'newgift','type',''));
			$form->addControl('b',new ll_edit($form,"giftname",28,40,'New Gift for:',False));
			$form->addControl('b',new ll_button($form,'ok','Set Shipping Address','button'));
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
			$form->addControl('b',new ll_button($form,'ok','Set Shipping Address','button'));
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
			$form->addControl('p',new ll_button($form,'ok','Set Payment Method','button'));
			$form->addControl('p',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'terms':
			$form->addFieldset('s','');
			$form->addControl('s',new ll_checkbox($form,'terms',"I accept the terms and conditions."));
			$form->addControl('s',new ll_button($form,'cancel','Done','button'));
		break;
		case 'confirm':
			$form->addFieldset('s',"Special Instructions");
			$form->addControl('s',new ll_textarea($form,"instructions",36,3));
			$form->addControl('s',new ll_button($form,'ok','Set Special Instructions','button'));
			$form->addControl('s',new ll_button($form,'cancel','Cancel','button'));
		break;
		case 'discounts':
			$form->addFieldset('s','');
			$form->addControl('s',new ll_button($form,'cancel','Done','button'));
		break;
		case 'finish':
			$form->addFieldset('s','');
			$form->addControl('s',new ll_button($form,'cancel','Done','button'));
		break;
		default:
			throw new exception("illegal form name $name");
		break;
	}
	return $form;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$this->makeform($state)->declareids($idstore);
	$idstore->registerEncodedPage();
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div:h3,h4,h5,p,td,th,div div,a,li,h3 a,td a','order',$this->getParent()->htmlContainerId());
	$this->makeform($state)->declarestyles($stylegen,$this->longname);
	$stylegen->registerClass('formerror','span');
}

public function processVars($originUri) {
	global $qq,$qqs;

}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qqu,$qqp;
	
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	$cust=$qqs->getSessionData("ecp_currentcust");
	$order=mod_prodmain::getSessionOrderObject();
	$options=$qqp->getPMod('order');
	$form=$this->makeForm($this->state);

	switch ($this->state) {
		case 'base':
		case '':
			echo "<div{$qqi->idcstr($this->longname)}>";
			echo au_ecp_order::productTable($cust,$order,True);
			echo "</div>";
		break;
		
		default:
			echo "<div{$qqi->idcstr($this->longname)}>{$form->getDumpStyleFormOutput()}</div>";
		break;	
	}
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
