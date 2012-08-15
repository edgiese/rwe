<?php if (FILEGEN != 1) die;
// styler for space above.  no js output, just css
class style_zindex extends style_base {
	private $value;
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="0";		// master default
		$this->value=(int)$initstring;
	}

	public function cssOutput($browser) {
		if ($this->value != 0)
			return array("z-index",$this->value);
		else
			return False;	
	}
}
?>
