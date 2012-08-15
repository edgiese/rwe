<?php if (FILEGEN != 1) die;
class au_scrapbook_groupdesc extends au_base {
//////////////////////////////////// displays the current scrapbook image //////
// this AU requires au_scrapbook_image to work properly
private $controllername;	// long name of controller 
private $controller;		// scrapbook image au
private $creole;			// creole text of description or "" if unclassified

function __construct($tag,$parent,$initdata) {
	global $qqu;
	
	parent::__construct($tag,$parent,$initdata);
	$this->controllername="scrapbook_image_".$initdata;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);	// image
}

public function declarestyles($stylegen,$state) {
	global $qqu;
	$stylegen->registerStyledId($this->longname,$qqu->getTextRegistrationTags(),"scrapbook",$this->getParent()->htmlContainerId());
}

// find the controlling au for the display
public function initialize($pgen) {
	$this->controller=$pgen->findAU($this->controllername);
	$this->creole=$this->controller->getGroupJournal($pgen);
}

public function output($pgen,$brotherid) {
	global $qqu,$qqi;

	echo "<div{$qqi->idcstr($this->longname)}>";
	if ($this->creole == "") {
		echo "<h1>Unclassified Pictures</h1>";
		echo "<p>Here are some miscellaneous pictures from various projects for you to enjoy.</p>";
	} else
		echo $qqu->creole2html($this->creole);
	echo "</div>";
}


//////////////////////////////////// end of au /////////////////////////////////
} ?>
