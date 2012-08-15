<?php if (FILEGEN != 1) die;
class style_overflow extends style_base {
	private $value;
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="visible";		// master default
		$this->value=$initstring;
	}

	// not required for divs or body marker
	static function isRequired($html) {
		return ($html == "div");
	}

	public function cssOutput($browser) {
		return array("overflow",$this->value);
	}
}
?>
