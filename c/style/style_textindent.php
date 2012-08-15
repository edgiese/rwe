<?php
// styler for paragraph indentation.  set by css
// writes styles for:  
class style_textindent extends style_base {
	private $value;
		
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="0px";
		$this->value=$initstring;		
	}

	static function isRequired($html) {
		// we're not going to allow "indents" on headers.  allow them later if desired
		$retval= False === array_search($html,array("body","div","img","hr","li","table","tr","h1","h2","h3","h4","blockquote"));
		return $retval;
	}

	public function cssOutput($browser) {
		return array("text-indent",util::addpx($this->value));
	}		
}
?>
