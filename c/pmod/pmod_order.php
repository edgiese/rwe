<?php if (FILEGEN != 1) die;
class pmod_order {
////////////// pmod class to read in variables and arrays for options //////////
private $notifyEmail;
private $discounts;
private $terms;
private $thankyou;
private $fullFormMask;
private $autoCart;
private $cartctrllabel;
private $editpage;
private $linklabel;
private $payPalExpress;

function __construct($lines) {

	$this->fullFormMask=0x1f;

	$this->v=array();
	$arrayEnd='';
	foreach ($lines as $line) {
		echo htmlentities($line)."<br />";
		$ln=trim($line);
		if ($ln == "" || substr($ln,0,1) == ";")
			continue;		// ignore blank and comment
		if (($i=strpos($ln,' ;')) !== False) {
			// strip off inline comment
			$ln=trim(substr($ln,0,$i));
		}

		
		// every line must have an '='
		if (False === ($epos=strpos($ln,'=')))
			throw new exception("syntax error:  missing =");
		$tag=trim(substr($ln,0,$epos));
		$value=trim(substr($ln,$epos+1));
		switch ($tag) {
			case 'notifyEmail':
				if (False === filter_var($value, FILTER_VALIDATE_EMAIL))
					throw new data_exception("$value is not a valid email address for notifyEmail");
				$this->notifyEmail=$value;	
			break;
			case 'discounts':
				$this->discounts=$value;	
			break;
			case 'terms':
				$this->terms=$value;	
			break;
			case 'thankYou':
				$this->thankyou=$value;	
			break;
			case 'fullFormMask':
				$this->fullFormMask=(int)$value;
			break;
			case 'autoCart':
				$this->autoCart=explode(',',$value);
			break;
			case 'cartctrllabel':
				$this->cartctrllabel=$value;
			break;
			case 'editpage':
				$this->editpage=$value;
			break;
			case 'linklabel':
				$this->linklabel=$value;
			break;
			case 'payPalExpress':
				$this->payPalExpress=$value;
			break;
		}	
	} // loop for all lines in file
	if (!isset($this->notifyEmail))
		throw new exception("notify email not set for orders");
	if (!isset($this->thankyou))
		throw new exception("thank-you text tag not set for orders");
}

// returns email.  must exist
public function notifyEmail() {return $this->notifyEmail;}
public function fullFormMask() {return $this->fullFormMask;}

// returns true if paypal express option is on, false if not
public function payPalExpress() {return isset($this->payPalExpress);}

public function seedOrder($order) {
	if (!isset($this->autoCart))
		return;
	foreach ($this->autoCart as $prodid) {
		if (False !== ($i=strpos('=',$prodid))) {
			$count=(int)substr($prodid,$i+1);
			$prodid=(int)substr($prodid,0,$i);
		} else {
			$count=0;
			$prodid=(int)$prodid;
		}
		$order->addProduct($prodid,$count);
	}	
}

// modifies array in place
public function addJSParams(&$args,$page='') {
	global $qqi;
	
	$args['cartctrl']=$qqi->htmlShortFromLong($this->cartctrllabel,$page);
	$args['href']=$qqi->hrefPrep($this->editpage);
	$args['longname']=$qqi->htmlShortFromLong($this->linklabel,$page);
}

// returns au of discounts text or False if none specified
public function getDiscountsAU($parentau) {
	if (!isset($this->discounts)) {
		return False;
	}

	for ($au=$parentau->getFirstChild(); $au != Null; $au=$au->getNextSibling()) {
		if ($au->getTag() == $this->discounts) {
			$retval=$au;
			break;
		}
	}
	if (!isset($retval))
		throw new exception("tag specified as '{$this->discounts}' not found");
	return $retval;		
}

public function termsActive() {return isset($this->terms);}

// returns au of terms text or False if none specified
public function getTermsAU($parentau) {
	if (!isset($this->terms)) {
		return False;
	}

	for ($au=$parentau->getFirstChild(); $au != Null; $au=$au->getNextSibling()) {
		if ($au->getTag() == $this->terms) {
			$retval=$au;
			break;
		}
	}
	if (!isset($retval))
		throw new exception("tag specified as '{$this->terms}' not found");
	return $retval;		
}

// returns au of thank-you text
public function getThankYouAU($parentau) {
	for ($au=$parentau->getFirstChild(); $au != Null; $au=$au->getNextSibling()) {
		if ($au->getTag() == $this->thankyou) {
			$retval=$au;
			break;
		}
	}
	if (!isset($retval))
		throw new exception("tag specified as '{$this->discounts}' not found");
	return $retval;		
}


//// end of class definition ///////////////////////////////////////////////////
} ?>
