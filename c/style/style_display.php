<?php
// styler for display.  this one has a null default, meaning that if it isn't set, it doesn't appear in final output.
class style_display extends style_base {
	private $value;
		
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="*";
		$this->value=$initstring;		
	}

	public function cssOutput($browser) {
		if ($this->value == '*')
			return False;
		return array("display",$this->value);
	}		
}
?>
