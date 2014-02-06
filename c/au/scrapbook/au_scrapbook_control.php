<?php if (FILEGEN != 1) die;
class au_scrapbook_control extends au_base {
//////////////////////////////////// displays the current scrapbook image //////
// this AU requires au_scrapbook_image to work properly
private $controllername;	// long name of controller 
private $controller;		// scrapbook image au
private $type;				// type of controller
private $link;				// link we will be using to output with

function __construct($tag,$parent,$initdata) {
	global $qqu;
	
	parent::__construct($tag,$parent,$initdata);
	$init=explode("|",$initdata);
	if (sizeof($init) != 2)
		throw new exception("format for au_scrapbook_control init is imagecontrol|controllertype");
		
	$this->controllername="scrapbook_image_".$init[0];
	$this->type=$init[1];
	// text and href are adjusted later.  have to register now.
	$this->link=new link($this->longname,"","");
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	$this->link->registerStyled($stylegen,"",$this->getParent()->htmlContainerId());
}

// find the controlling au for the display
public function initialize($pgen) {
	$this->controller=$pgen->findAU($this->controllername);
	list($imageix,$nImage,$groupix,$nGroup)=$this->controller->getCounts($pgen);
	switch ($this->type) {
		case "previmage":
			$text="&laquo; prev";
			$group=$groupix;
			$image=($imageix <= 0) ? $nImage-1 : $imageix-1;
		break;
		case "nextimage":
			$text="next &raquo;";
			$group=$groupix;
			$image=($imageix >= $nImage-1) ? 0 : $imageix+1;
		break;
		case "prevgroup":
			$text="&laquo; prev";
			$group=($groupix <= -1) ? $nGroup-1 : $groupix-1;
			$image=0;
		break;
		case "nextgroup":
			$text="next &raquo;";
			$group=($groupix >= $nGroup-1) ? -1 : $groupix+1;
			$image=0;
		break;
		default:
			throw new exception("illegal scrapbook control type:  $this->type");
		break;
	}
	// make them one-based
	if ($group < 0)
		$group="u";
	else
		$group++;
	$image++;		
	
	$href="{$pgen->getPageName()}/$group-$image";	
	$this->link->setparams($href,$text);
}

public function output($pgen,$brotherid) {
	echo $this->link->getOutput();
}


//////////////////////////////////// end of au /////////////////////////////////
} ?>
