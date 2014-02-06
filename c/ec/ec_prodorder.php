<?php if (FILEGEN != 1) die;
class ec_prodorder {
////////////////////////// class to store and retrieve product order information

private $pids; 		// array of array(prodid,qty)
private $instructions;	// special customer instructions
private $termsaccepted;	// true if terms accepted this order, false if not
private $extra;			// extra order data
private $transactionInfo;	// info from transaction

function __construct() {
	global $qqp;
	
	$this->pids=array();
	$this->instructions='';
	$this->termsaccepted=False;
	$this->extra=array();
	$options=$qqp->getPMod('order');
	$options->seedOrder($this);
}

// if prodid already exists, quantity will be updated.
public function addProduct($prodid,$quantity=1) {
	$this->pids[$prodid]=$quantity;
}

public function removeProduct($prodid) {
	infolog("dbg","removing $prodid");
	if (isset($this->pids[$prodid]))
		unset($this->pids[$prodid]);
}

// returns array of prod ids
public function getCart() {
	return array_keys($this->pids);
}

// returns array of array(qty,prodid,baseprice,discount,subtotal)
public function getOrderArray($bShowZeroQty=True) {
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	foreach ($this->pids as $prodid=>$qty) {
		if (!$bShowZeroQty && $qty == 0)
			continue;
		if (False !== ($info=$pm->getProductInfo($prodid,False))) {
			$baseprice=$info['baseprice'];
			// TODO: implement per-product discounts
			$discount=$qty*0;
			$subtotal=$qty*$baseprice-$discount;
		} else
			$baseprice=$discount=$subtotal=0;
		$items[$prodid]=array($qty,$prodid,$baseprice,$discount,$subtotal);
	}
	// calculate total so we can do group discounts
	// TODO:  make this work better
	
	/*
	$discountgroup=$pi->findGroup("group discount");
	if ($discountgroup !== False) {
		infolog("dbg","discountgroup=$discountgroup");
		$discounttotal=0;
		foreach ($items as $prodid=>$lineitem) {
			if ($pi->isInGroup($discountgroup,$prodid)) {
				infolog("dbg","item $prodid is in group with price {$lineitem[2]}");
				$discounttotal += $lineitem[0]*$lineitem[2];
			}
		}
		infolog("dbg","discounttotal=$discounttotal");
		if ($discounttotal >= 7500) {
			$discountpercent=($discounttotal >= 20000) ? 0.2 : 0.1;
			foreach ($items as $prodid=>&$lineitem) {
				if ($pi->isInGroup($discountgroup,$prodid)) {
					$lineitem[3]=$lineitem[0]*(int)(0.5 + $discountpercent*$lineitem[2]);
					$lineitem[4]=$lineitem[0]*$lineitem[2]-$lineitem[3];
					infolog("dbg","adjusting discount to {$lineitem[3]}");
				}
			}
		}
	}
	*/
	////// FOUNDER'S DAY:
	$discountpercent=0.2;
	foreach ($items as $prodid=>&$lineitem) {
		$lineitem[3]=$lineitem[0]*(int)(0.5 + $discountpercent*$lineitem[2]);
		$lineitem[4]=$lineitem[0]*$lineitem[2]-$lineitem[3];
	}
	// strip off prodid indices.  php can move things in arrays around. to play it safe, start with fresh array
	$retval=array();
	foreach ($this->pids as $prodid=>$qty) {
		if (!$bShowZeroQty && $qty == 0)
			continue;
		$retval[]=$items[$prodid];
	}		
	
	return $retval;	
}

/////// shipping & tax functions functions:

// returns string, could be blank
static function getRestrictionString($prodid) {
	$pi=new mod_prodinfo;
	if (False !== ($info=$pi->getProdInfo($prodid))) {
		if ($info['shape'] == 'huge' || $info['shape'] == 'none' || ($info['flags'] & mod_prodinfo::FLAG_COLD) != 0)
			$retval='Available for store pickup only';
		else
			$retval='';	
	} else
		$retval='';
	return $retval;	
}

const SHIP_UNASSIGNED='?';	// not assigned
const SHIP_PICKUP='C';
const SHIP_USPS='P';
// returns array of (code=>name)
static function getAllShippingOptions() {
	return array(
		self::SHIP_USPS=>'USPS Priority Mail Package',
		self::SHIP_PICKUP=>'Pick up in store'
	);
}

static function getTrackingURL($type) {
	switch ($type) {
		case self::SHIP_USPS:
			return "http://www.usps.com/shipping/trackandconfirm.htm";
		break;
		case self::SHIP_PICKUP:
			return "(order is ready to pick up at store)";
		break;
	}
}

// returns array of (code=>pennies) or False if not enough info is available about customer
public function getShippingOptions($cust) {
	$pi=new mod_prodinfo;
	$bPickupOnly=False;	// innocent until proven guilty
	$weight=0;
	$volume=0.0;
	foreach ($this->pids as $prodid=>$qty) {
		$info=$pi->getProdInfo($prodid);
		if ($qty > 0 && False !== $info) {
			if ($info['shape'] == 'huge' || $info['shape'] == 'none' || ($info['flags'] & mod_prodinfo::FLAG_COLD) != 0) {
				infolog("dbg","found pickup only");
				$bPickupOnly=True;
			}	
		}
		if (!$bPickupOnly && $info !== False) {
			$weight += $qty*$info['weight'];
			if ($info['shape'] == 'rect')
				$thisvolume=$info['dim1']*$info['dim3']*$info['dim3'];
			else if ($info['shape'] == 'cylinder')	
				$thisvolume=$info['dim1']*$info['dim2']*$info['dim2']/(4*3.14);
			if (($info['flags'] & mod_prodinfo::FLAG_COLD) != 0)
				$thisvolume *= 1.2;
			$volume += $qty*$thisvolume;
		}			
	}
	if ($bPickupOnly) {
		$retval=array(self::SHIP_PICKUP=>0);		
	} else {
		$retval=array();
		$opts=self::getAllShippingOptions();
		foreach ($opts as $code=>$desc) {
			switch ($code) {
				case self::SHIP_PICKUP:
					$cost=0;
				break;	
				case self::SHIP_USPS:
					// very rough calculation
					// assume box volume is ~60% usage of 11*11*11 inches (converted to mm^3)
					$nBoxes=1+(int)($volume/11000000);
					$extraWeightPerBoxLbs=max(0,($weight/$nBoxes/454)-1.0);
					infolog("dbg","weight=$weight volume=$volume nBoxes=$nBoxes extra=$extraWeightPerBoxLbs");
					$cost=150+(int)(0.5+$nBoxes*(500+60*$extraWeightPerBoxLbs));
				break;	
			}
			$retval[$code]=$cost;
		}
	}
	return $retval;
}

// returns tax (pennies) or False if not enough info is available about customer
public function calculateTax($cust) {
	if (!($cust instanceof ec_customer))
		return False;
	//array(data_name,data_address,data_phone)
	$shipaddr=$cust->getShippingAddresses();	
	if (sizeof($shipaddr) == 0 || !($shipaddr[0][1] instanceof data_addresscols))
		return False;
	$state=$shipaddr[0][1]->getArgParam(data_addresscols::STATE);
	$orderdata=$this->getOrderArray();
	$pm=new mod_prodmain;
	$tax=0;
	foreach ($orderdata as $od) {
		list($qty,$prodid,$baseprice,$discount,$subtotal)=$od;
		$taxrate=$pm->getTax($prodid,$state);
		$tax += (int)(0.5+$subtotal*($taxrate/10000));
	}		
	return $tax;
}

// estimates total of order.  will give best estimate at the time of call. used for paypal interface
public function estimateTotal($cust) {
	$prods=$this->getOrderArray(False);
	$total=0;
	foreach ($prods as $prodinfo) {
		$total += $prodinfo[4];
	}
	if (False !==($tax=$this->calculateTax($cust)))
		$total += $tax;
	if (False !==($shipping=$this->calculateTax($cust)) && isset($shipping[$cust->getPreferredShipping()])) {
		$total += $shipping[$cust->getPreferredShipping()];
	}
	return $total;	
}

// returns transaction info.  could be empty.
public function getTransactionInfo() {
	return isset($this->transactionInfo) ? $this->transactionInfo : array();
}

// returns instructions
public function getSpecialInstructions() {return $this->instructions;}
public function setSpecialInstructions($instructions) {$this->instructions=$instructions;}

// returns True or False
public function getTermsAccepted() {
	global $qqp;
	$options=$qqp->getPMod('order');
	return (!$options->termsActive()) || $this->termsaccepted;
}
public function setTermsAccepted($termsaccepted) {$this->termsaccepted=(bool)$termsaccepted;}

// arbitrary data store -- used for cv and gift messages, etc.
public function getExtra($id) {
	return (isset($this->extra[$id])) ? $this->extra[$id] : False;	
}
public function setExtra($id,$value) {
	$this->extra[$id]=$value;
}

// returns True if successful or a message if not
public function place($cust) {
	global $qqc,$qqu,$qqp,$qqs;
	
	if (False === ($email=$cust->getEmail()))
		return "No valid email specified.";
	$saddrs=$cust->getShippingAddresses();
	if (!($saddrs[0][0] instanceof data_name))
		return "Invalid customer data.  Bad name.";
	$name=$saddrs[0][0]->output();
	if (!($saddrs[0][1] instanceof data_addresscols))
		return "Invalid customer data.  Bad address.";
	$zip=$saddrs[0][0]->getArgParam(data_addresscols::ZIP);
	if (False === ($cc=$cust->getActiveCreditCard()))
		return "No valid payment information.";
	$shiptype=$cust->getPreferredShipping();
	$shipdesc=$this->getAllShippingOptions();
	$shipopts=$this->getShippingOptions($cust);
	if (!isset($shipopts[$shiptype]))
		return "error in shipping request type.";

	// verify and place transaction
	if ($cc->getArgParam(0) == data_cc::TYPE_PAYPAL) {
		// palpal transaction should already have been preapproved before this point
		if (!$qqs->sessionDataExists("ecp_transaction"))
			return "paypal transaction should have been set up but wasn't somehow";
		$transaction=$qqs->getSessionData("ecp_transaction");
		if (!($transaction instanceof ec_transact_paypal))
			return "paypal transaction not properly set up.";
	} else {
		// set up transaction to verify and accept payment
	}
	//$retval=$transaction->transactPayment($cust,$this);	
	//if (is_string($retval))
	//	return $retval;
	//$this->transactionInfo=$retval;				

	$ordernum=$qqc->insert('ec/ec_prodorder::insert',$cust->getEmail(),$name,$zip,serialize($cust),serialize($this));
	
	// send email with order number
	$message="Thank you for your order.  Your order number is $ordernum\r\n";
	$address=strtr($saddrs[0][1]->output(),array('<br />'=>"\r\n"));
	$message .= "\r\nBill to:\r\n$name\r\n$address\r\n";
	$idship=$cust->useShippingAddress();
	$name=$saddrs[$idship][0]->output();
	$address=strtr($saddrs[$idship][1]->output(),array('<br />'=>"\r\n"));
	$message .= "\r\nShip to:\r\n$name\r\n$address\r\n";
	
	$cardtypes=data_cc::getAllTypes();
	$paymenttype=$cardtypes[$cc->getArgParam(0)];
	$message .= "\r\nPayment Type: $paymenttype\r\n";
	
	$pi=new mod_prodinfo;
	$lines=$this->getOrderArray(False);
	$grandsubtotal=0;
	$message .= "\r\n------------------------------------------";
	foreach ($lines as $litem) {
		list($qty,$prodid,$baseprice,$discount,$subtotal)=$litem;
		$info=$pi->getProdInfo($prodid);
		$title=strtr($qqu->creolelite2html($info['title']),array('&nbsp;'=>' ',"<br />"=>"\r\n"));
		$message .= "\r\n[qty $qty] {$info['mfr']}: $title\r\n";
		$message .= sprintf("           Unit price: %1.2f   Discount: %1.2f\r\n",$baseprice/100,$discount/100);
		$grandsubtotal += $subtotal; 
		
	}
	$message .= "------------------------------------------\r\n";
	$message .= sprintf("Product Subtotal: %1.2f\r\n",$grandsubtotal/100);

	$tax=$this->calculateTax($cust);
	if ($tax > 0)
		$message .= sprintf("Sales Tax: %1.2f\r\n",$tax/100);


	$shipcost=$shipopts[$shiptype];
	$message .= sprintf("Shipping (%s) %1.2f\r\n",$shipdesc[$shiptype],$shipcost/100);
	
	$total=$grandsubtotal+$tax+$shipcost;
	$message .= sprintf("\r\nTOTAL CHARGED ON ORDER: %1.2f\r\n",$total/100);
	$message .= "\r\nWe will email you again when the order ships.\r\n";
	
	$qqu->mail($email,"Your new order #$ordernum",$message);
	
	$options=$qqp->getPMod('order');
	$qqu->mail($options->notifyEmail(),"Order #$ordernum has been placed","The following confirmation email was sent to $email:\r\n".$message);	
	return True;
}

// returns array of array(id,timestamp,email,via,tracking,name,zip)
static function getAllPlacedOrders() {
	global $qqc;
	$rows=$qqc->getRows('ec/ec_prodorder::allorders',-1);
	$retval=array();
	if ($rows !== False) {
		foreach ($rows as $row) {
			$retval[]=array($row['id'],$row['timestamp'],$row['email'],$row['via'],$row['tracking'],$row['name'],$row['zip']);
		}
	}	
	return $retval;
}

// returns array (cust,order,timestamp,email,via,tracking,name,zip)
static function infoFromId($idorder) {
	global $qqc;
	$row=$qqc->getRows('ec/ec_prodorder::info',1,(int)$idorder);
	if ($row === False)
		throw new exception("unknown order id: $idorder");
	$cust=unserialize($row['custobj']);
	$order=unserialize($row['orderobj']);	
	return array($cust,$order,$row['timestamp'],$row['email'],$row['via'],$row['tracking'],$row['name'],$row['zip']);
}

// updates into an id
public function update($id) {
	global $qqc;
	$qqc->act('ec/ec_prodorder::updateorderobj',(int)$id,serialize($this));
}

public function setShippingRecord($id,$via,$tracking,$cust) {
	global $qqc,$qqu;
	$qqc->act('ec/ec_prodorder::updateshipping',(int)$id,$via,$tracking);

	$email=$cust->getEmail();
	$saddrs=$cust->getShippingAddresses();
	$ordernum=$id;
	
	// send email with order number
	$message="Your order #$ordernum has shipped.\r\n";
	$idship=$cust->useShippingAddress();
	$name=$saddrs[$idship][0]->output();
	$address=strtr($saddrs[$idship][1]->output(),array('<br />'=>"\r\n"));
	$message .= "\r\nWe shipped it to:\r\n$name\r\n$address\r\n";

	$message .= "\r\nYou can track the progress of your package at:\r\n\r\n$via\r\n\r\nThe tracking number is: $tracking\r\n";
	
	$qqu->mail($email,"Your order #$ordernum has shipped",$message);

}

static function getUnshippedOrderId($bNewest,$idstart=Null) {
	global $qqc;
	
	if ($idstart == Null) {
		$query= $bNewest ? 'ec/ec_prodorder::firstunproc' : 'ec/ec_prodorder::lastunproc';
		$retval=$qqc->getRows($query,1); 
	} else {
		$query= $bNewest ? 'ec/ec_prodorder::prevunproc' : 'ec/ec_prodorder::nextunproc';
		$retval=$qqc->getRows($query,1,(int)$idstart); 
	}
	return $retval;
}

//////////////////////////////// end of class definition ///////////////////////
} ?>
