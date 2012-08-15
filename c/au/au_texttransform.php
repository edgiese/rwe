<?php if (FILEGEN != 1) die;
///// Access Unit definition file to transform text.  only makes sense as the child of a text object -- no output
class au_texttransform extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $search;
private $replace;

function __construct($tag,$parent,$initstring) {
	parent::__construct($tag,$parent,$initstring);
	$initdata=parent::getInit($initstring,'regex=');
	$regex = $initdata['regex'];
	$delim=$regex[0];
	list($this->search,$this->replace,$dummy)=explode($delim,substr($regex,1),3);
}

public function transform($pgen,$outputdata) {
	dbg("$outputdata\nsearch:{$this->search} replace:{$this->replace}");
	return str_replace($this->search,$this->replace,$outputdata);
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
