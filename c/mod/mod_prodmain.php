<?php if (FILEGEN != 1) die;
class mod_prodmain {
//////////////////////////////// class to maintain a base list of product ids.  all basic list maintenance comes off this module

// returns id of item or False if doesn't exist
public function invItemExists($invid) {
	global $qqc;
	
	return $qqc->getRows('mod/prodmain::lookupinvid',1,$invid);
}

public function getAllIds($bActiveOnly=False) {
	global $qqc;
	
	return $qqc->getCols($bActiveOnly ? "mod/prodmain::allActiveIds" : "mod/prodmain::allIds",-1);
}


// returns id or False
public function idFromBarcode($barcode) {
	global $qqc;
	
	return $qqc->getCols("mod/prodmain::idfrombarcode",1,$barcode);
}

// returns array of (invid,invdesc,prodhold,notes,baseprice,firstsold,lastsold,onhand,minqty,discontinued)
private function processIncomingInfo($info) {
	$retval=array();
	if (isset($info['invid'])) {
		$retval[]=$info['invid'];
		unset($info['invid']);
	} else
		throw new exception("missing inventory id");
		
	if (isset($info['invdesc'])) {
		$retval[]=$info['invdesc'];
		unset($info['invdesc']);
	} else
		$retval[]='(No inventory Description)';
		
	if (isset($info['prodhold'])) {
		$retval[]=$info['prodhold'];
		unset($info['prodhold']);
	} else
		$retval[]=0;
		
	if (isset($info['notes'])) {
		$retval[]=$info['notes'];
		unset($info['notes']);
	} else
		$retval[]="";

	if (isset($info['baseprice'])) {
		$retval[]=$info['baseprice'];
		unset($info['baseprice']);
	} else
		$retval[]=0;

	if (isset($info['firstsold'])) {
		$retval[]=$info['firstsold'];
		unset($info['firstsold']);
	} else
		$retval[]="2000-01-01";

	if (isset($info['lastsold'])) {
		$retval[]=$info['lastsold'];
		unset($info['lastsold']);
	} else
		$retval[]='2000-01-01';

	if (isset($info['onhand'])) {
		$retval[]=$info['onhand'];
		unset($info['onhand']);
	} else
		$retval[]=0;

	if (isset($info['minqty'])) {
		$retval[]=$info['minqty'];
		unset($info['minqty']);
	} else
		$retval[]=0;

	if (isset($info['discontinued'])) {
		$retval[]=$info['discontinued'];
		unset($info['discontinued']);
	} else
		$retval[]=False;

	return $retval;	
}

private function addBarcodes($id,$info) {
	global $qqc;
	if (isset($info['barcodes'])) {
		if (!is_array($info['barcodes']))
			throw new exception("barcodes, if set must be an array of strings"); 		
		$qqc->act("mod/prodmain::deletebarcodes",(string)$id);
		foreach ($info['barcodes'] as $barcode) {
			if ($barcode != "(No Bar Code)")
				$qqc->insert("mod/prodmain::insertbarcode",(string)$id,$barcode);
		}				
	}
}

// returns product id
public function addProduct($info) {
	global $qqc;
	list($invid,$invdesc,$prodhold,$notes,$baseprice,$firstsold,$lastsold,$onhand,$minqty,$discontinued)=$this->processIncomingInfo($info);
	$id=$qqc->insert("mod/prodmain::insert",$invid,$invdesc,$prodhold,$notes,$baseprice,$firstsold,$lastsold,$onhand,$minqty,$discontinued);
	$this->addBarcodes($id,$info);
}

// returns info array or False
public function getProductInfo($id,$bGetBarcodes=True) {
	global $qqc;
	$retval=$qqc->getRows('mod/prodmain::getinfo',1,(string)$id);
	if ($retval !== False && $bGetBarcodes) {
		$retval['barcodes']=$qqc->getCols('mod/prodmain::getbarcodes',-1,(string)$id);
		if ($retval['barcodes'] === False)
			$retval['barcodes']=array("(No Bar Code)");
	}
	return $retval;
}

public function setProductInfo($id,$info) {
	global $qqc;
	if (False === ($oldinfo=$this->getProductInfo($id,False)))
		throw new exception("updating unknown product id $id");
	if (!isset($info['invid']))
		$info['invid']=$oldinfo['invid'];	
	if (!isset($info['invdesc']))
		$info['invdesc']=$oldinfo['invdesc'];	
	if (!isset($info['prodhold']))
		$info['prodhold']=$oldinfo['prodhold'];
	if (!isset($info['notes']))
		$info['notes']=$oldinfo['notes'];
	if (!isset($info['baseprice']))
		$info['baseprice']=$oldinfo['baseprice'];
	if (!isset($info['firstsold']))
		$info['firstsold']=$oldinfo['firstsold'];
	if (!isset($info['lastsold']))
		$info['lastsold']=$oldinfo['lastsold'];
	if (!isset($info['onhand']))
		$info['onhand']=$oldinfo['onhand'];
	if (!isset($info['minqty']))
		$info['minqty']=$oldinfo['minqty'];
	if (!isset($info['discontinued']))
		$info['discontinued']=$oldinfo['discontinued'];
			
	list($invid,$invdesc,$prodhold,$notes,$baseprice,$firstsold,$lastsold,$onhand,$minqty,$discontinued)=$this->processIncomingInfo($info);
	$qqc->act("mod/prodmain::setinfo",(string)$id,$invid,$invdesc,$prodhold,$notes,$baseprice,$firstsold,$lastsold,$onhand,$minqty,$discontinued);
	
	$this->addBarcodes($id,$info);
}

// returns array of (nHold,nNote,prevnohold,prevhold,prevnote,nextnohold,nextnote,nextnonote)
public function getNavInfo($id) {
	global $qqc;
	
	$nHold=$qqc->getValue("mod/prodmain::numholds");
	$nNote=$qqc->getValue("mod/prodmain::numnotes");

	$prevnohold=$qqc->getRows("mod/prodmain::prevnohold",1,(string)$id);
	if ($prevnohold === False)
		$prevnohold='0';	
	$prevhold=$qqc->getRows("mod/prodmain::prevhold",1,(string)$id);
	if ($prevhold === False)
		$prevhold='0';	
	$prevnote=$qqc->getRows("mod/prodmain::prevnote",1,(string)$id);
	if ($prevnote === False)
		$prevnote='0';
		
	$topid=(string)(1+(int)$qqc->getValue("mod/prodmain::maxid"));
	
	$nextnohold=$qqc->getRows("mod/prodmain::nextnohold",1,(string)$id);
	if ($nextnohold === False)
		$nextnohold=$topid;	
	$nexthold=$qqc->getRows("mod/prodmain::nexthold",1,(string)$id);
	if ($nexthold === False)
		$nexthold=$topid;	
	$nextnote=$qqc->getRows("mod/prodmain::nextnote",1,(string)$id);
	if ($nextnote === False)
		$nextnote=$topid;
		
	return array($nHold,$nNote,$prevnohold,$prevhold,$prevnote,$nextnohold,$nexthold,$nextnote);
}

/////////////////////////////////////////// shopping cart access functions
static function getSessionOrderObject() {
	global $qqs;
	if (!$qqs->isStated())
		return False;
	if (!$qqs->sessionDataExists("ecp_currentprodorder")) {
		$cart=new ec_prodorder();
		$qqs->setSessionData("ecp_currentprodorder",$cart);
	}
	return $qqs->getSessionData("ecp_currentprodorder");
}

public function addToCart($prodid) {
	if (False === ($cart=self::getSessionOrderObject()))
		return;
	$cart->addProduct($prodid);
}

public function removeFromCart($prodid) {
	if (False === ($cart=self::getSessionOrderObject()))
		return;
	$cart->removeProduct($prodid);
}

public function isItemInCart($prodid) {
	if (False === ($cart=self::getSessionOrderObject())) {
		return False;
	}	
	$ids=$cart->getCart();
	return (False !== array_search((int)$prodid,$ids));
}

public function getCartItems() {
	if (False === ($cart=self::getSessionOrderObject()))
		return False;
	return $cart->getCart();
}

////////////////////////////////////////////////////////////////// sync support functions

// returns old count of discontinued items
public function syncPrep() {
	global $qqc;
	
	$retval=$this->countDiscontinued();
	if ($qqc->getValue('mod/prodmain::syncprepcheck') == 0) {
		$qqc->act('mod/prodmain::syncprep1');
		$qqc->act('mod/prodmain::syncprep2');
		$qqc->act('mod/prodmain::syncprep3');
		$qqc->act('mod/prodmain::syncprep4');
		$qqc->act('mod/prodmain::syncprep5');
		$qqc->act('mod/prodmain::syncprep6');
	}	
	$qqc->act('mod/prodmain::markalldis');
	return ;
}

// does 'auto-hold' for items with certain conditions, notably zero-price
public function autoHold() {
	global $qqc;
	$qqc->act('mod/prodmain::autoholdzeroprice');
}

// returns current count of discontinued items
public function countDiscontinued() {
	global $qqc;
	return $qqc->getValue('mod/prodmain::countdiscontinued');
}

// returns True if successful, false if not
public function syncCommit() {
	global $qqc;
	
	$qqc->act('mod/prodmain::synccommit1');
	$qqc->act('mod/prodmain::synccommit2');
	$qqc->act('mod/prodmain::synccommit3');
	return True;
}

// returns True if successful, false if not
public function syncRollback() {
	global $qqc;
	
	$qqc->act('mod/prodmain::syncrollback1');
	$qqc->act('mod/prodmain::syncrollback2');
	$qqc->act('mod/prodmain::syncrollback3');
	$qqc->act('mod/prodmain::syncrollback4');
	$qqc->act('mod/prodmain::syncrollback5');
	$qqc->act('mod/prodmain::syncrollback6');
	return True;
}

///////////////////////////////////////////// punch list support routines

// returns array of on hold items sorted by priority or False if none
public function onHoldItems($bSortByInvDesc=False) {
	global $qqc;
	
	$retval=$bSortByInvDesc ? $qqc->getCols('mod/prodmain::onholditemsbyinvdesc',-1) : $qqc->getCols('mod/prodmain::onholditems',-1);
	if ($retval === False)
		$retval=array();
	return $retval;	
}

// returns array of on hold items that are marked 'added by sync'
public function getAllAddedBySync() {
	global $qqc;
	
	$retval=$qqc->getCols('mod/prodmain::addedbysync',-1);
	if ($retval === False)
		$retval=array();
	return $retval;	
}

/////////////////////////////////////////////// tax rates
public function addTax($id,$state,$rate) {
	global $qqc;

	$this->clearTax($id,$state);
	$qqc->act('mod/prodmain::addtax',$id,$state,$rate);	
}

public function clearTax($id,$state) {
	global $qqc;
	$qqc->act('mod/prodmain::cleartax',$id,$state);	
}

// returns tax rate in percent * 100  (e.g. 825=8.25%)
public function getTax($id,$state) {
	global $qqc;
	$retval=$qqc->getRows('mod/prodmain::gettax',1,$id,$state);
	return $retval === False ? 0 : $retval;
}

//////////////////////////////// end of class definition ///////////////////////
} ?>
