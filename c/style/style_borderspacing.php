<?php if (FILEGEN != 1) die;

// for tables, 0* means no space and collapse borders; 0 means no collapse, and n (or nx|ny) means that many pixels of extra space between cells 
class style_borderspacing extends style_base {
	private $value;
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$initstring="0*";		// master default
		$this->value=$initstring;
	}

	// not required for divs or body marker
	static function isRequired($html) {
		return ($html == "table");
	}

	public function cssOutput($browser) {
		$value=$this->value;
		// this approach allows some odd syntaxes to be legal, but that's ok I guess
		if (False !== strpos($value,'*')) {
			$value=str_replace('*','',$value);
			$collapse='collapse';
		} else
			$collapse='separate';
			
		if (False !== ($i=strpos($value,'|'))) {
			$x=(int)substr($value,0,$i);
			$y=(int)substr($value,$i+1);
			$spacing="{$x}px {$y}px";
		} else
			$spacing=(int)$value.'px';
			
			
		return array(array("border-collapse",$collapse),array('border-spacing',$spacing));
	}
}
?>
