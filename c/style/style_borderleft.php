<?php if (FILEGEN != 1) die;
// styler for background color.  no js output, just css
class style_borderleft extends style_base {
	private $color;		// if -1, transparent
	private $width;		// width in pixels
	private $style;		// see css styles

	static function constructVals($initstring,$defaulthtml) {
		if ($defaulthtml != "")
			$initstring="transparent|0";		// master default
		$init=explode("|",$initstring);
		if (sizeof($init) == 0)
			$init[0]=-1;
		else {
			if ($init[0] == "transparent")
				$init[0]=-1;
			else	
				$init[0]=hexdec($init[0]);
		}		
		if (sizeof($init) < 2)
			$init[1]=1;
		if (sizeof($init) < 3)
			$init[2]="solid";
		return $init;	
	}
		
	function __construct($initstring,$defaulthtml="") {
		list($this->color,$this->width,$this->style)=self::constructVals($initstring,$defaulthtml);	
	}

	static function mycssOutput($side,$color,$width,$style) {
		$color=style_bkcolor::color2string($color);
		$width=util::addpx($width);			
		return array("border-$side","$color $width $style");
	}
	public function cssOutput($browser) {
		return self::mycssOutput("left",$this->color,$this->width,$this->style);
	}
}
?>
