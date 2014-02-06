<?php if (FILEGEN != 1) die;
///// Access Unit definition file for form & state testing
class au_underlaydiv extends au_base {
////////////////////////////////////////////////////////////////////////////////

function __construct($pgen,$tag,$parent,$initdata) {
	parent::__construct($pgen,$tag,$parent,$initdata);
}

public function declareids($idstore,$state) {
	//$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen) {
	//$stylegen->registerStyledId($this,"","html","usage",False);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
