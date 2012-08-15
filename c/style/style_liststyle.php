<?php if (FILEGEN != 1) die;
class style_liststyle extends style_base {
	private $type;
	private $position;
	private $image;
	
	function __construct($initstring,$defaulthtml="") {
		global $qq,$qqi;
		
		if ($defaulthtml != "")
			$initstring="disc|outside|none";	// master default
		$init=explode("|",$initstring);
		if (sizeof($init) == 0)
			$init[0]="disc";
		if (sizeof($init) < 2)
			$init[1]="outside";
		if (sizeof($init) < 3)
			$init[2]="none";
		if ($init[2] != "none")
			$init[2]="url({$qqi->hrefPrep($init[2],False,'',idstore::ENCODE_NONE)}\")";	
		list($this->type,$this->position,$this->image)=$init;
	}

	// not required for divs or body marker
	static function isRequired($html) {
		return ($html == "li");
	}

	public function cssOutput($browser) {
		return array(
			array("list-style-type",$this->type),
			array("list-style-position",$this->position),
			array("list-style-image",$this->image)
		);	
	}
}
?>
