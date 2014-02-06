<?php if (FILEGEN != 1) die;
class au_flash extends au_base {
//////////////////////////////////// AU to display a flash file ////////////////
private $file;
private $width;
private $height;
private $usage;

// initstring format:  usage|filename|width|height
function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	$init=explode("|",$initdata);
	if (sizeof($init) != 4)
		throw new exception("illegal init string for au_flash_$tag.  Must be:  usage|filename|width|height");
	list($this->usage,$this->file,$this->width,$this->height)=$init;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);	// flash object
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"object",$this->usage,$this->getParent()->htmlContainerId());
}

// does output
public function output($pgen,$brotherid) {
	global $qq,$qqs,$qqi;
	$file=$qqi->resourceFileURL($this->file);
	echo "<object{$qqi->idcstr($this->longname)} type=\"application/x-shockwave-flash\" data=\"$file\" width=\"{$this->width}\" height=\"{$this->height}\"><param name=\"movie\" value=\"$file\" /><param name=\"wmode\" value=\"transparent\"></object>";
}

//////////////////////////////////// end of au definition //////////////////////
} ?>
