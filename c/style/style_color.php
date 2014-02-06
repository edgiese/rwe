<?php if (FILEGEN != 1) die;
// styler for color.  no js output, just css
class style_color extends style_base {
	private $color;
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="0";		// master default
		$this->color=hexdec($initstring);
	}

	// not required for divs or body marker
	static function isRequired($html) {
		return !($html == "div" || $html == "body");
	}

	public function cssOutput($browser) {
		return array("color",style_bkcolor::color2string($this->color));
	}
}
?>
