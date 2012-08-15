<?php if (FILEGEN != 1) die;
// styler for color.  no js output, just css
class style_minheight extends style_base {
	private $value;
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "" || $initstring == "")
			$initstring="0";		// master default
		$this->value=(int)$initstring;
	}

	static function isRequired($html) {
		return ($html == "div" || $html == "body");
	}

	public function cssOutput($browser) {
		return array("min-height",util::addpx($this->value));
	}
}
?>
