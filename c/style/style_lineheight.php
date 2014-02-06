<?php
// styler for line height.  set by css
// writes styles for:  
class style_lineheight extends style_base {
	private $initstring;
		
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="normal";		// default
		$this->initstring=$initstring;		
	}

	static function isRequired($html) {
		$retval= False === array_search($html,array("body","div","img","hr"));
		return $retval;
	}

	public function cssOutput($browser) {
		return array("line-height",util::addpx($this->initstring));
	}		
}
?>
