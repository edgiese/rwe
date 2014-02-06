<?php if (FILEGEN != 1) die;
// styler for color.  no js output, just css
class style_minwidth extends style_base {
	private $value;
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="0";		// master default
		$this->value=$initstring;
	}

	// not required for divs or body marker
	static function isRequired($html) {
		return ($html == "div" || $html == "body");
	}

	public function cssOutput($browser) {
		return array("min-width",util::addpx($this->value));
	}
}
?>
