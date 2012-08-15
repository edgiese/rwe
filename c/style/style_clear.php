<?php
// styler for clear attribute
class style_clear extends style_base {
	private $value;
		
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "" || $initstring == "")
			$initstring="none";
		if (False === array_search($initstring,array('none','left','right','both','inherit')))
			throw new exception("illegal value for clear style: $initstring");	
		$this->value=$initstring;		
	}

	public function cssOutput($browser) {
		return array("clear",$this->value);
	}		
}
?>
