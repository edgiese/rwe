<?php if (FILEGEN != 1) die;
// styler for horizontal position.  initial values set by css, final values set by js
// writes styles for:  left, right, width, minwidth
class style_width extends style_base {
	private $initstring;
		
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="100%";		// default
		$this->initstring=$initstring;		
	}

	// not required for divs or body marker
	static function isRequired($html) {
		return ($html != "body");
	}

	public function process($js,$browser,$render,$stype,$selector,$parent) { 
		$initstring=$this->initstring;
		list($fname,$args)=style_position::parsepos($initstring);
		return ($fname != "_fixed_") ? style_base::PROC_POSITION : style_base::PROC_NONE;  
	}

	// helper function used by position, height, and width with small variations
	public function getPosString() {
		return $this->initstring;
	}

	public function cssOutput($browser) {
		$initstring=$this->initstring;
		if ($initstring == "100%")
			return False;
		list($fname,$args)=style_position::parsepos($initstring);
		$pos=($fname == "_fixed_") ? $args[0] : "100px";
		return array("width",util::addpx($pos));
	}		
}
?>
