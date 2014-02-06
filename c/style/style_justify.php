<?php
// styler for font face.  set by css
// writes styles for:  
class style_justify extends style_base {
	private $justify;
		
	function __construct($initstring) {
		$this->justify=$initstring;
		if ($initstring == "")
			$this->justify="l";		
	}

	static function isRequired($html) {
		$retval= False === array_search($html,array("body","div","img","hr"));
		return $retval;
	}

	public function cssOutput($browser) {
		$alignments=array("c"=>"center","l"=>"left","r"=>"right","j"=>"justify");
		if (!isset($alignments[$this->justify]))
			throw new exception("illegal justification code: $this->justify");
		return array("text-align",$alignments[$this->justify]);
	}		
}
?>
