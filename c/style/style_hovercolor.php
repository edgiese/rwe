<?php if (FILEGEN != 1) die;
// styler for text color on 'hover'.  no js output, just css
class style_hovercolor extends style_base {
	private $color;

	static function isRequired($html) {return ($html == 'a');}
	
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
		return array("color",style_bkcolor::color2string($this->color),':hover');
	}
}
?>
