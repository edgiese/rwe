<?php
// styler for font face.  set by css
// writes styles for:  
class style_fontattribs extends style_base {
	private $bBold;
	private	$bItalic;
	private $bSmallCaps;
		
	function __construct($initstring) {
		$this->bBold=False !== strpos($initstring,"b");		
		$this->bItalic=False !== strpos($initstring,"i");		
		$this->bSmallCaps=False !== strpos($initstring,"s");		
	}

	static function isRequired($html) {
		$retval= False === array_search($html,array("body","div","img","hr"));
		return $retval;
	}

	public function cssOutput($browser) {
		return array(
			array("font-style",$this->bItalic ? "italic" : "normal"),
			array("font-variant",$this->bSmallCaps ? "small-caps" : "normal"),
			array("font-weight",$this->bBold ? "bold" : "normal")
		);	
	}		
}
?>
