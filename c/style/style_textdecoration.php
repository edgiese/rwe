<?php
// styler for font decoration.  set by css
// writes styles for:  
class style_textdecoration extends style_base {
	private $bUnderline;
		
	function __construct($initstring) {
		$this->bUnderline=False !== strpos($initstring,"u");		
	}

	static function isRequired($html) {
		$retval= False === array_search($html,array("body","div","img","hr"));
		return $retval;
	}

	public function cssOutput($browser) {
		return array("text-decoration",$this->bUnderline ? "underline" : "none");
	}		
}
?>
