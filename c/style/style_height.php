<?php if (FILEGEN != 1) die;
// styler for horizontal position.  initial values set by css, final values set by js
// writes styles for:  left, right, width, minwidth
class style_height extends style_base {
	private $initstring;

	// format:  C (calced) or H 10px  or S (calced)
		
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="C";		// default
		$this->initstring=$initstring;		
	}

	// not required for divs or body marker
	static function isRequired($html) {
		return ($html != "body");
	}

	public function process($js,$browser,$render,$stype,$selector,$parent) { 
		$initstring=$this->initstring;
		if ($initstring == "C")
			return style_base::PROC_NONE;
		list($fname,$args)=style_position::parsepos($initstring);
		return ($fname != "_fixed_") ? style_base::PROC_POSITION : style_base::PROC_NONE;  
	}
	
	// helper function used by position, height, and width with small variations
	public function getPosString() {
		$initstring=$this->initstring;
		return ($initstring == "C") ? False : $initstring;	
	}

	public function cssOutput($browser) {
		$initstring=$this->initstring;
		if ($initstring == "C")
			return False;
		list($fname,$args)=style_position::parsepos($initstring);
		$pos=($fname == "_fixed_") ? $args[0] : "auto";
		return array("height",util::addpx($pos));
	}
}
?>
