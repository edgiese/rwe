<?php if (FILEGEN != 1) die;
///// Access Unit definition file for form & state testing
class au_emptyrect extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $usage;
private $link;

function __construct($tag,$parent,$initstring) {
	parent::__construct($tag,$parent,$initstring);
	$initdata=parent::getInit($initstring,'usage=emptyrect|link=');
	$this->usage = $initdata['usage'];
	if (isset($initdata['link']))
		$this->link=$initdata['link'];
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"div",$this->usage,$this->getParent()->htmlContainerId());
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqi;
	$linktext=isset($this->link) ? " onclick=\"window.location='{$qqi->hrefPrep($this->link)}';\" style=\"cursor:pointer\"" : '';
	echo "<div{$qqi->idcstr($this->longname)}$linktext></div>";	
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
