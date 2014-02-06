<?php if (FILEGEN != 1) die;
///// Access Unit definition file for a powermap (enhanced image map)
class au_powermap extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $instance;

// initstring format:  usage|id|smallscalepercent[|href|linkdescription]
function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	// powermap object has all the stuff in it
	$instance=$initdata;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);	// image
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','',$this->getParent()->htmlContainerId());
}

public function declarestaticjs($js) {
	global $qq;
	
	$args=$js->seedArgs($this);
	// select all users button clieck event

	$js->addjs('$','powermap::setup',$args);
}

// does output
public function output($pgen,$brotherid) {
	global $qqs,$qqi;

	echo "<div{$qqi->idcstr($this->longname)}></div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
