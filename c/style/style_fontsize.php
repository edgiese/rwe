<?php
// styler for font face.  set by css
// writes styles for:  
class style_fontsize extends style_base {
	private $initstring;
		
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="13px";		// default
		$this->initstring=$initstring;		
	}

	static function isRequired($html) {
		$retval= False === array_search($html,array("body","div","img","hr"));
		return $retval;
	}

	public function cssOutput($browser) {
		return array("font-size",util::addpx($this->initstring));
	}		
}
?>
