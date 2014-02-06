<?php if (FILEGEN != 1) die;
class au_scrapbook_imagedesc extends au_base {
//////////////////////////////////// displays the current scrapbook image //////
// this AU requires au_scrapbook_image to work properly
private $controllername;	// long name of controller 
private $controller;		// scrapbook image au

function __construct($tag,$parent,$initdata) {
	global $qqu;
	
	parent::__construct($tag,$parent,$initdata);
	$this->controllername="scrapbook_image_".$initdata;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"p","comment",$this->getParent()->htmlContainerId());
}

// find the controlling au for the display
public function initialize($pgen) {
	$this->controller=$pgen->findAU($this->controllername);
}

public function output($pgen,$brotherid) {
	global $qqi;
	echo "<p{$qqi->idcstr($this->longname)}>{$this->controller->description($pgen)}</p>";
}


//////////////////////////////////// end of au /////////////////////////////////
} ?>
