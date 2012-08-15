<?php
// styler for font face.  set by css
// writes styles for:  
class style_letterspacing extends style_base {
	private $value;
		
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="normal";
		$this->value=$initstring;		
	}

	static function isRequired($html) {
		$retval= False === array_search($html,array("body","div","img","hr"));
		return $retval;
	}

	public function cssOutput($browser) {
		$spacing=$this->value;
		return array("letter-spacing",util::addpx($spacing));
	}		
}
?>
