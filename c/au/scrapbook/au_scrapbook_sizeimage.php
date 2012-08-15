<?php if (FILEGEN != 1) die;
class au_scrapbook_sizeimage extends au_base {
//////////////////////////////////// displays the current scrapbook image //////
// this AU requires au_scrapbook_image to work properly
private $controllername;	// long name of controller 
private $controller;		// scrapbook image au
private $width;
private $height;
private $medpercent;
private $smallpercent;

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	global $qqu,$qqs;
	
	parent::__construct($tag,$parent,$initdata);
	$init=explode("|",$initdata);
	if (sizeof($init) != 5)
		throw new exception("format for au_scrapbook_sizeimage init is imagecontrol|width|height|medpercent|smallpercent");
		
	$this->controllername="scrapbook_image_".$init[0];
	$this->width=(int)$init[1];
	$this->height=(int)$init[2];
	$this->medpercent=(int)$init[3];
	$this->smallpercent=(int)$init[4];
}

private function makeform() {
	global $qqs;
	$form=new llform($this);
	$form->addFieldset("picsize","Picture Size");

	$rg=new ll_radiogroup($form,"size","radio","&nbsp;&nbsp;&nbsp;");
		$rg->addOption("normal","Normal Size");
		$rg->addOption("smaller","Smaller");
		$rg->addOption("smallest","Smallest");
	$form->addControl("picsize",$rg);	
	$form->addControl("picsize",new ll_button($form,"Ok","Resize"));
	return $form;
}

public function declareids($idstore,$state) {
	$this->makeform()->declareids($idstore);
	$idstore->registerProfile('scrapbook_imagesize',crud::TYPE_STRING,'','normal');
}

public function declarestyles($stylegen,$state) {
	$form=$this->makeform();
	$form->declarestyles($stylegen,$this->longname);
	
}

public function processVars($originUri) {
	global $qq,$qqs;
	
	$form=$this->makeform();
	$vals=$form->setFieldsFromRequests();
	$size=$form->getValue("size");
	$qqs->setProfile("scrapbook_imagesize",$size);
	return $originUri;
}

// find the controlling au for the display
public function initialize($pgen) {
	global $qqs;
	
	$this->controller=$pgen->findAU($this->controllername);
	$width=$this->width;
	$height=$this->height;
	$size=$qqs->getProfile("scrapbook_imagesize");
	if ($size == "smaller") {
		$width=(int)($width*$this->medpercent/100+0.5);
		$height=(int)($height*$this->medpercent/100+0.5);
	} else if ($size == "smallest") {
		$width=(int)($width*$this->smallpercent/100+0.5);
		$height=(int)($height*$this->smallpercent/100+0.5);
	}
	$this->controller->setMaximumSize($pgen,$width,$height);
}

public function output($pgen,$brotherid) {
	global $qqs;
	
	$form=$this->makeForm();
	$size=$qqs->getProfile("scrapbook_imagesize");
	$form->setValue("size",$size);
	$form->useGetMethod();
	echo $form->getDumpStyleFormOutput('',0,"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
}


//////////////////////////////////// end of au /////////////////////////////////
} ?>
