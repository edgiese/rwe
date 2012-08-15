<?php if (FILEGEN != 1) die;
class au_scrapbook_options extends au_base {
//////////////////////////////////// displays the current scrapbook image //////
// this AU requires au_scrapbook_image to work properly
private $controllername;	// long name of controller 
private $controller;		// scrapbook image au
private $width;
private $height;
private $medpercent;
private $smallpercent;
private $size;			// "normal", "smaller", or "smallest"

function __construct($tag,$parent,$initdata) {
	global $qqu,$qqs;
	
	parent::__construct($tag,$parent,$initdata);
	$init=explode("|",$initdata);
	if (sizeof($init) != 5)
		throw new exception("format for au_scrapbook_options init is imagecontrol|width|height|medpercent|smallpercent");
		
	$this->controllername="scrapbook_image_".$init[0];
	$this->width=(int)$init[1];
	$this->height=(int)$init[2];
	$this->medpercent=(int)$init[3];
	$this->smallpercent=(int)$init[4];
	$this->size=$qqs->getProfile("scrapbookimagesize","normal");
}

private function makeform() {
	$form=new llform($this);
	$form->addFieldset("picsize","Picture Size");
	$form->addFieldset("group","Project");
	$form->addFieldset("update","Update Display");

	$rg=new ll_radiogroup($form,"size",$this->size);
		$rg->addOption("normal","Normal Size");
		$rg->addOption("smaller","Smaller");
		$rg->addOption("smallest","Smallest");
	$form->addControl("picsize",$rg);	
	
	$form->addControl("group",($dd=new ll_dropdown($form,"group")));
	if (isset($this->controller)) {
		// controlling au is available.  populate dropdown with projects
		$sb=$this->controller->getScrapbook($pgen);
		list($imageix,$nImage,$groupix,$nGroup)=$this->controller->getCounts($pgen);
		$groupdata=$sb->getGroups();
		for ($ix=0; $ix<$nGroup; ++$ix) {
			list($idgroup,$groupname)=$groupdata[$ix];
			$dd->addOption($idgroup,$groupname);
			if ($ix == $groupix)
				$dd->setValue($idgroup);
		}
		$dd->addOption(0,"(unclassified pictures)");
		if ($groupix < 0)
			$dd->setValue(0);
	}
	
	$form->addControl("update",new ll_button($form,"update","Update"));
	return $form;
}

public function declareids($idstore,$state) {
	$this->makeform()->declareids($idstore,$this);
}

public function declarestyles($stylegen,$state) {
	$form=$this->makeform();
	$form->declarestyles($stylegen,$this);
	
}

public function processVars($pgen,$brother,$state,$formshortname) {
	global $qq,$qqs;
	
	$form=$this->makeform();
	$form->setFieldsFromRequests();
	$this->size=$form->getValue("size");
	$qqs->setProfile("scrapbookimagesize",$this->size);
}

// find the controlling au for the display
public function initialize($pgen) {
	$this->controller=$pgen->findAU($this->controllername);
	$width=$this->width;
	$height=$this->height;
	if ($this->size == "smaller") {
		$width=int($width*$this->medpercent/100+0.5);
		$height=int($height*$this->medpercent/100+0.5);
	} else if ($this->size == "smallest") {
		$width=int($width*$this->smallpercent/100+0.5);
		$height=int($height*$this->smallpercent/100+0.5);
	}
	$this->controller->setMaximumSize($pgen,$width,$height);
}

public function output($pgen,$brotherid) {
	$form=$this->makeForm();
	echo $form->getDumpStyleFormOutput($this,$brotherid);
}


//////////////////////////////////// end of au /////////////////////////////////
} ?>
