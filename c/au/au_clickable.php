<?php if (FILEGEN != 1) die;
///// Access Unit definition file for clickable invisible region
class au_clickable extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $link;
private $width;
private $height;

function __construct($tag,$parent,$initstring) {
	parent::__construct($tag,$parent,$initstring);
	$initdata=parent::getInit($initstring,'width|height|link');
	$this->link=$initdata['link'];
	$this->height=$initdata['height'];
	$this->width=$initdata['width'];
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'img','clickable',$this->getParent()->htmlContainerId());
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqi,$qq;
	$linktext=isset($this->link) ? " onclick=\"window.location='{$qqi->hrefPrep($this->link)}';\" style=\"cursor:pointer\"" : '';
	echo "<img{$qqi->idcstr($this->longname)} src=\"{$qqi->hrefPrep('m/img/cleardot.gif',False)}\" height=\"{$this->height}\" width=\"{$this->width}\"$linktext>";	
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
