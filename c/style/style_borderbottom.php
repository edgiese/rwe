<?php if (FILEGEN != 1) die;
class style_borderbottom extends style_base {
	private $color;		// if -1, transparent
	private $width;		// width in pixels
	private $style;		// see css styles

	function __construct($initstring,$defaulthtml="") {
		list($this->color,$this->width,$this->style)=style_borderleft::constructVals($initstring,$defaulthtml);	
	}

	public function cssOutput($browser) {
		return style_borderleft::mycssOutput("bottom",$this->color,$this->width,$this->style);
	}
}
?>
