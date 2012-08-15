<?php
// styler for vertical alignment.  set by css
// writes styles for:  
class style_verticalalign extends style_base {
	private $initstring;
		
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="baseline";		// default
		$this->initstring=$initstring;		
	}

	static function isRequired($html) {
		$retval= False !== array_search($html,array("h1","h2","h3","h4","td","th","p","li","input","img"));
		return $retval;
	}

	public function cssOutput($browser) {
		return array("vertical-align",$this->initstring);
	}		
}
?>
