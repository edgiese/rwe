<?php if (FILEGEN != 1) die;
///// Access Unit definition file for form & state testing
class au_body extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $ownerbar;		// if true, show an ownerbar; otherwise don't
private $edit;			// edit utility object, used to hold owner bar info for display

function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	if ($parent != Null)
		throw new exception("body AU cannot have a parent");
	$this->ownerbar=($initdata != 'noownerbar');	
}

public function declareids($idstore,$state) {
	if ($this->ownerbar) {
		$edit=new edit_main();	
		$edit->declareOBids($idstore,$this);
		$idstore->registerAuthBool('ownerbar','Show owner menu',False);
	}	
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	if ($this->ownerbar) {
		$edit=new edit_main();	
		$edit->declareOBstyles($stylegen,$state,$this);
	}	
	$stylegen->registerStyledId($this->longname,"body","plenum","");
}

// practically the only point of this au:
public function canTakeChildren() {return True;}

public function output($pgen,$sibling) {
	throw new exception("body needs a special output function");
}

public function initializeOwnerBar($pgen,$edit) {
	global $qqs,$qq;

	$watchid= isset($qq['watchid']) ? $qq['watchid'] : ''; 	
	if ($watchid != '' || ($qqs->checkAuth('ownerbar') && $qqs->getRender() != state::RENDER_GOOD)) {
		$edit=new edit_main();
		if ($watchid == '') {	
			// call all the others and finish initialization
			for ($au=$this->getNextAU(); $au != Null; $au=$au->getNextAU()) {
				$au->initializeOwnerBar($pgen,$edit);
			}
		}	
		$edit->initializeOB($pgen,$this,$watchid);
		$this->edit=$edit;
	}	
}

// does output of this plus all children
public function bodyOutput($pgen,$bNeedBS,$bNeedBE) {
	global $qqs,$qqi,$qq;
	
	$watchid= isset($qq['watchid']) ? $qq['watchid'] : ''; 	
		
	echo "<body>";
	if ($bNeedBS)
		echo "<script type=\"text/javascript\" language=\"JavaScript\">bs();</script>";
	else if (!$qqs->isStated())	
		echo "<script type=\"text/javascript\" language=\"JavaScript\"><!-- bs(); --></script>";
	if ($this->ownerbar && ($watchid != '' || ($qqs->checkAuth('ownerbar') && $qqs->getRender() != state::RENDER_GOOD))) {
		$this->edit->outputOB($pgen,$this,$watchid);
	}		
	for ($au=$this->getFirstChild(); $au != Null; $au=$au->getNextSibling())
		$au->output($pgen,"0");
	if ($bNeedBE)
		echo "<script type=\"text/javascript\" language=\"JavaScript\">be();</script>";
	echo "</body>";	
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
