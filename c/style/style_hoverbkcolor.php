<?php if (FILEGEN != 1) die;
// styler for background color on 'hover'.  no js output, just css
class style_hoverbkcolor extends style_base {
	private $color;

	static function isRequired($html) {return True;}
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$this->color=-1;		// master default
		else {
			if ($initstring == "transparent")
				$this->color=-1;
			else		
				$this->color=hexdec($initstring);
		}	
	}

	public function cssOutput($browser) {
		if ($this->color == -1)
			return False;
		return array("background-color",style_bkcolor::color2string($this->color),':hover');
	}
}
?>
