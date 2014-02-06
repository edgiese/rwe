<?php if (FILEGEN != 1) die;
// styler for space above.  no js output, just css
class style_paddingleft extends style_base {
	private $value;
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="0";		// master default
		$this->value=$initstring;
	}

	public function cssOutput($browser) {
		return array("padding-left",util::addpx($this->value));
	}
}
?>
