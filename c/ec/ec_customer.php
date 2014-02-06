<?php if (FILEGEN != 1) die;
class ec_customer {
/////////////////////////////// class to store and retrieve customer information

private $email;
private $password;
private $shipaddresses;
private $idship;
private $ccs;
private $idcc;
private $preferredshipping;
private $bUsedSecondaryPassword;
private $bLoaded;

function __construct() {
	$this->shipaddresses=array(0=>array(False,False,False,False));
	$this->ccs=array();
	$this->idship=0;		// by default use billing address
	$this->preferredshipping=ec_prodorder::SHIP_UNASSIGNED;
	$this->email=False;
	$this->password='';
	$this->bUsedSecondaryPassword=$this->bLoaded=False;
}

private function prepFromLoad($bUsedSecondary) {
	$this->bUsedSecondaryPassword=$bUsedSecondary;
	$this->bLoaded=True;
	// if previous shipping address was a gift, then revert to first non-gift alternate
	if ($this->shipaddresses[$this->idship][3]) {
		$bFound=False;
		foreach ($this->addresses as $idship=>$address) {
			if ($idship > 0 && !$this->addresses[$this->idship][3]) {
				$bFound=True;
				break;
			}
		}
		$this->idship=($bFound) ? $idship : 0;
	}
	// remove any expired credit cards
	$remove=array();
	foreach ($this->ccs as $idcc=>$cc) {
		if (!($cc instanceof data_cc) || $cc->isExpired())
			$remove[]=$idcc;
	}
	foreach ($remove as $idcc)
		unset($this->ccs[$idcc]);
}

// returns customer object or False if auth failed
static function createFromEmail($email,$password) {
	global $qqc;
	
	if (False === ($row=$qqc->getRows('ec/ec_customer::loadfromemail',1,$email)))
		return False;	// email not there

	$pw=md5($password);
	if ($pw != $row['password'] && ($pw != $row['pw2'] || time()+20*60 < $row['timestamp']))
		return False; 	// authentication failure
	
	$bUsedSecondary=($pw != $row['password']);	
	$cust=unserialize($row['object']);
	// perform a few updates before returning object
	$cust->prepFromLoad($bUsedSecondary);
	return $cust;		
}

// emails customer a password.  returns True if successful or False if not
static function emailPassword($email) {
	global $qqu,$qqc;
	
	if (False === ($row=$qqc->getRows('ec/ec_customer::loadfromemail',1,$email)))
		return False;	// email not there
	$temppw='';
	for ($i=0; $i<3; ++$i)
		$temppw .= chr(ord('a')+rand(0,25));
	$temppw .= '-';
	for ($i=0; $i<3; ++$i)
		$temppw .= chr(ord('0')+rand(0,9));
	$message="Here is a temporary password for www.peachbasketonline.com.  Use this exact string:\n\n$temppw\n\nThis password is good for only 20 minutes.\nIf you did not request this password, please contact us.  Some one may be tampering with your account.";
	$qqu->mail($email,'Peach Basket Password',$message);	
	$qqc->act('ec/ec_customer::settemppw',$email,md5($temppw));	
	return True;
}

// returns True if email doesn't exist in the customer email table or a temporary password used for verification
public function needPassword() {
	return (!$this->bLoaded || $this->bUsedSecondaryPassword);
}

// saves all data
public function save() {
	global $qqc;
	if (!is_string($this->email) || $this->password == '')
		return;
	if (strlen($this->password) < 4)
		return;
	$pw=md5($this->password);
	if (False === ($row=$qqc->getRows('ec/ec_customer::loadfromemail',1,$this->email))) {
		$qqc->act('ec/ec_customer::insert',$this->email,$pw,serialize($this));
	} else {
		$qqc->act('ec/ec_customer::update',$this->email,$pw,serialize($this));
	}
	$this->bLoaded=True;
}

public function getPreferredShipping() {return $this->preferredshipping;}
public function setPreferredShipping($newval) {$this->preferredshipping=$newval;}

public function getEmail() {return $this->email;}

// returns True if valid email, or False if not
public function setEmail($newemail) {
	if (False !== filter_var($newemail,FILTER_VALIDATE_EMAIL)) {
		$this->email=$newemail;
		return True;	
	} else
		return False;
}

// returns True if valid password or False if not
public function setPassword($password) {
	if (!is_string($password) || strlen($password) < 4 || strlen($password) > 20)
		return False;
	$this->password=$password;
	return True;	
}

// returns an array of [idship]=>array(data_name,data_address,data_phone):  could be an empty array.  name, if set, is gift recipent
public function getShippingAddresses() {
	$retval=array();
	$defname=$this->shipaddresses[0][0];
	$defphone=$this->shipaddresses[0][2];
	foreach ($this->shipaddresses as $idship=>$sa) {
		list($name,$address,$phone,$giftrecipient)=$sa;
		if ($name === False) {
			$name=$defname;
			if ($phone === False)
				$phone=$defphone;
		}		 	
		$retval[$idship]=array($name,$address,$phone,$giftrecipient);			
	}
	return $retval;
}

// takes a data_address, data_phone and a data_name or False if same as purchaser.  returns new idship
public function addShippingAddress($address,$name=False,$phone=False,$giftrecipient=False) {
	if ($address instanceof data_addresscols && ($name === False || $name instanceof data_name) && ($phone === False || $phone instanceof data_phone)) {
		$idship=sizeof($this->shipaddresses)+1;
		while (isset($this->shipaddresses[$idship]))
			++$idship;
		$this->shipaddresses[$idship]=array($name,$address,$phone,$giftrecipient);	
	} else {
		throw new exception("bad data object format for name, address or phone");
	}
	return $idship;
}

// marks a shipping address as most recently used.  idship=0 means use billing address
public function useShippingAddress($idship=Null) {
	if ($idship !== Null) {
		if (!isset($this->shipaddresses[$idship]))
			throw new exception("illegal value for idship: $idship");
		$this->idship=$idship;	
	}
	return $this->idship;	
}

// returns True or False
public function isActiveShippingAddressGift() {
	return $this->shipaddresses[$this->idship][3];	
}

// updates a shipping address.  note--idship=0 is hardcoded for billing address
public function updateShippingAddress($idship,$name,$address,$phone=False,$giftrecipient='keep') {
	if (!isset($this->shipaddresses[$idship]))
		throw new exception("illegal value for idship: $idship");
	if ($giftrecipient == 'keep')
		$giftrecipient=$this->shipaddresses[$idship][3];	
	if ($address instanceof data_addresscols && ($name === False || $name instanceof data_name) && ($phone === False || $phone instanceof data_phone)) {
		$this->shipaddresses[$idship]=array($name,$address,$phone,$giftrecipient);	
	} else {
		throw new exception("bad data object format for name, address or phone");
	}
}

// deletes a shipping address
public function deleteShippingAddress($idship) {
	if ($idship == 0 || !isset($this->shipaddresses[$idship]))
		throw new exception("illegal value for idship: $idship");
	unset($this->shipaddresses[$idship]);	
}

// returns data_cc or False
public function getActiveCreditCard() {
	return (!isset($this->idcc) || !isset($this->ccs[$this->idcc]) || !($this->ccs[$this->idcc] instanceof data_cc)) ? False : $this->ccs[$this->idcc];
}

// returns an array of [idcc]=>data_cc:  could be an empty array
public function getCreditCards() {
	return $this->ccs;
}

// takes a data_cc. returns idcc
public function addCreditCard($cc) {
	if ($cc instanceof data_cc) {
		$idccnew=1;
		foreach ($this->ccs as $idcc=>$ccold) {
			if ($ccold->equal($cc)) {
				$idccnew=$idcc;
				break;
			} else if ($idccnew < $idcc)
				$idccnew=$idcc+1;
		}
		$this->ccs[$idccnew]=$cc;	
	} else {
		throw new exception("bad data object format for credit card");
	}
	return $idccnew;
}

// marks a credit card as most recently used.  NOTE: credit card must exist already
public function useCreditCard($idcc) {
	if (!isset($this->ccs[$idcc]))
		throw new exception("illegal value for idcc: $idcc");
	$this->idcc=$idcc;	
}

// deletes a credit card
public function deleteCreditCard($idcc) {
	if (!isset($this->ccs[$idcc]))
		throw new exception("illegal value for idcc: $idcc");
	unset($this->ccs[$idcc]);
	if ($idcc == $this->idcc)
		unset($this->idcc);	
}


//////////////////////////////// end of class definition ///////////////////////
} ?>
