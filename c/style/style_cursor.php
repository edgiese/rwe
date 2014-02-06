<?php
// styler for cursor.  default needs no output.
class style_cursor extends style_base {
	private $value;
		
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="sysauto";
		$this->value=$initstring;		
	}

	static function isRequired($html) {
		return True;
	}

	public function cssOutput($browser) {
		if ($this->value != "sysauto")
			return array("cursor",$this->value);
		else
			return False;	
	}		
}
?>
