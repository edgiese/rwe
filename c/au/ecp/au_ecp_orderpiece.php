<?php if (FILEGEN != 1) die;
///// Access Unit definition file for product-based order module
class au_ecp_orderpiece extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $master;		// master au--set by the master at initialize
private $wrapper;

function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	if (strlen($initdata) > 0)
		$this->wrapper=$initdata;
}
public function setMaster($au) {$this->master=$au;}

private function getIds() {
	return array('showemail'=>'span','instructionsdisplay'=>'span','showbilladdr'=>'span','showshipaddr'=>'span','paymentinfo'=>'span',
	  'totaltable'=>'div:table,tr,td,th','producttable'=>'div:p,table,tr,td,th,a',
	  'emailchange'=>'a','specialinstructionsbutton'=>'a','billaddrbutton'=>'a','shipaddrbutton'=>'a','paymentbutton'=>'a','termsbutton'=>'a');
}

public function declareids($idstore,$state) {
	$ids=$this->getIds();
	if (isset($ids[$this->tag])) {
		$idstore->declareHTMLid($this,True);
	}	
}

public function declarestyles($stylegen,$state) {
	$ids=$this->getIds();
	if (isset($ids[$this->tag])) {
		$wrapper=(isset($this->wrapper))? $this->wrapper : $ids[$this->tag];
		$stylegen->registerStyledId($this->longname,$wrapper,'orderpiece',$this->getParent()->htmlContainerId());
	}	
}


// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
	$cust=$qqs->getSessionData("ecp_currentcust");
	$order=mod_prodmain::getSessionOrderObject();
	$master=$this->master;
	if (!isset($this->wrapper) || $this->wrapper=='') {
		$ids=$this->getIds();
		if (isset($ids[$this->tag])) {
			$wrapper=$ids[$this->tag];
			if (False !== ($i=strpos($wrapper,':')))
				$wrapper=substr($wrapper,0,$i);
		}
	} else
		$wrapper=$this->wrapper;

	switch ($this->tag) {
		case 'showemail':
			if (False === ($email=$cust->getEmail()))
				$email='(contact email not set)';
			echo "<{$wrapper}{$qqi->idcstr($this->longname)}>$email</{$wrapper}>";
		break;
		case 'emailchange':
			echo "<td><a onclick=\"fg.ecpo.changeemail()\">change</a></tr></table>";		
		break;
		case 'continuebutton':
			$form=$this->master->makeForm('base');
			echo $form->getFormattedOutput('<<continue>>',False);
		break;
		case 'shopbutton':
			$form=$this->master->makeForm('base');
			echo $form->getFormattedOutput('<<shop>>',False);
		break;
		case 'discountsbutton':
			echo "<a onclick=\"fg.ecpo.showdiscounts()\">review our specials and discounts</a>";
		break;
		case 'instructionsdisplay':
			$instructions=$order->getSpecialInstructions();
			if (strlen($instructions) > 60)
				$instructions=substr($instructions,0,50).'...';
			$instructions=nl2br($instructions);
			echo "<{$wrapper}{$qqi->idcstr($this->longname)}>$instructions&nbsp;</{$wrapper}>";
		break;
		case 'specialinstructionsbutton':
			echo "<a onclick=\"fg.ecpo.changeinstructions()\">change</a>";
		break;
		case 'showbilladdr':
			$billaddr=$master->getShippingAddress($cust,False,0);
			echo "<{$wrapper}{$qqi->idcstr($this->longname)}>$billaddr</{$wrapper}>";
		break;
		case 'billaddrbutton':
			echo "<a onclick=\"fg.ecpo.changebilladdr()\">change</a>";		
		break;
		case 'showshipaddr':
			$idaddr=$cust->useShippingAddress();
			if ($idaddr === 0)
				$shipaddr='(Use Billing Address)';
			else
				$shipaddr=$master->getShippingAddress($cust,False,$idaddr);	
			echo "<{$wrapper}{$qqi->idcstr($this->longname)}>$shipaddr</{$wrapper}>";
		break;
		case 'shipaddrbutton':
			echo "<a onclick=\"fg.ecpo.changeshipaddr()\">change</a>";
		break;
		case 'paymentinfo':
			$payment=(False === ($cc=$cust->getActiveCreditCard())) ? '(Payment Type not Set)' : $cc->output3();
			echo "<{$wrapper}{$qqi->idcstr($this->longname)}>$payment</{$wrapper}>";
		break;
		case 'paymentbutton':
			echo "<a onclick=\"fg.ecpo.changepayment()\">change</a>";		
		break;
		case 'termsctrl':
			$form=$master->makeform('base');
			$form->setValue('terms',$order->getTermsAccepted());
			echo $form->getFormattedOutput('<<terms>>',False);
		break;
		case 'termsbutton':
			echo '<a onclick="fg.ecpo.showterms()">view</a>';
		break;
		case 'producttable':
			if ($order === False) {
				echo "<p>You must have cookies enabled for this site before you can place an order.</p>";
			} else {
				echo "<{$wrapper}{$qqi->idcstr($this->longname)}>".au_ecp_order::productTable($cust,$order)."</{$wrapper}>";
			}
		break;
		case 'totaltable':
			echo "<{$wrapper}{$qqi->idcstr($this->longname)}>{$master->totalTable($cust,$order)}</{$wrapper}>";
		break;
		default:
			throw new exception("unknown order piece {$this->tag}");
		break;
	}
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
