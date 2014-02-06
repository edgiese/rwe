<?php if (FILEGEN != 1) die;
class mod_wizard {
///////// WIZARD MODULE /////////////////////////////////////////////////////
private $uid;
private $email;
private $salutation;
private $id;
private $values;

function __construct() {
}

// returns uid
function newEntry($salutation,$email) {
	global $qqc;
	$this->values=array();
	$id=$qqc->insert("mod/wizard::newentry",$email,$salutation,serialize($this->values));
	$seedplus=1;
	do {
		$uid=md5($id.'wizard_seed'.$seedplus);
		++$seedplus;
	} while ($qqc->getRows("mod/wizard::getid",-1,$uid));
	$qqc->act('mod/wizard::updateuid',$id,$uid);
	$this->uid=$uid;
	$this->id=$id;
	$this->email=$email;
	$this->salutation=$salutation;
	return $uid;
}

// returns True or False depending on success
function restoreEntry($uid) {
	global $qqc;
	if (False === ($row=$qqc->getRows("mod/wizard::getid",-1,$uid)))
		return False;
	list($this->id,$this->email,$this->salutation,$varray)=$row;
	$this->values=unserialize($varray);
	return True;
}

// saves previously set items
function saveEntry() {
	global $qqc;
	$qqc->act('mod/wizard::updatevarray',$this->id,serialize($this->values));
}

// returns item value, or default, or False if no value specified
function getItem($name,$default=null) {
	if (!isset($this->values[$name])) {
		if ($default != null)
			return $default;
		return False;	
	}
	return $this->values[$name];
}

function setItem($name,$value) {
	$this->values[$name]=$value;
}

///////// END OF MODULE ////////////////////////////////////////////////////////
} ?>
