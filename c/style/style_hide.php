<?php
// styler for font decoration.  set by css
// writes styles for:  
class style_hide extends style_base {
	private $hidelevel;
		
	function __construct($initstring) {
		$this->hidelevel=(int)$initstring;		
	}

	public function cssOutput($browser) {
		if ($this->hidelevel == 0)
			return False;
		if ($this->hidelevel == 1)	
			return array("visibility","hidden");
		return array("display","none");	
	}		
}
?>
