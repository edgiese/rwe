<?php
// styler for word spacing.  set by css
class style_wordspacing extends style_base {
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
		return array("word-spacing",util::addpx($spacing));
	}		
}
?>
