<?php if (FILEGEN != 1) die;
// styler for background color.  no js output, just css
class style_bkcolor extends style_base {
	private $color;
	
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

	static function color2string($color) {
		if ($color < 0)
			$color="transparent";
		else {
			$color=dechex($color);
			$n=strlen($color);
			if ($n < 6)
				$color="#".substr("000000",0,6-$n).$color;
			else
				$color="#".$color;
		}
		return $color;			
	}
	
	public function cssOutput($browser) {
		return array("background-color",self::color2string($this->color));
	}
}
?>
