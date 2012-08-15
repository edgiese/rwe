<?php
// styler for font face.  set by css
// writes styles for:  
class style_font extends style_base {
	private $initstring;
		
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="basic_sans_serif";		// default
		$this->initstring=$initstring;		
	}

	static function isRequired($html) {
		$retval= False === array_search($html,array("body","div","img","hr"));
		return $retval;
	}

	private function getStackString($stackname) {
		$stacks=array(
			"arial"=>"Arial",
			"verdana"=>"Verdana,DejaVu Sans",
			"times"=>"Times,Times New Roman,Nimbus Roman No9 L",
			"arial_black"=>"Arial Black,Arial",
			"impact"=>"Impact,DejaVu Sans Condensed",
			"arial_narrow"=>"Arial Narrow,DejaVu Sans Condensed",
			"courier"=>"Courier,Courier New,DejaVu Sans Mono",
			"palatino"=>"Palatino Linotype,Baskerville,Georgia,Century Schoolbook L,Times,Times New Roman",
			"trebuchet"=>"'Trebuchet MS',Arial,serif,DejaVu Sans Condensed",
			"comic"=>"'Comic Sans MS','URW Chancery L'",
			"georgia"=>"Georgia,Times,Times New Roman,Nimbus Roman No9 L",
			// last one:			
			"basic_sans_serif"=>"Microsoft Sans Serif,Monaco,DejaVu Sans,Nimbus Sans L"
		);
		if (!isset($stacks[$stackname]))
			throw new exception("unknown font stack name:  $stackname");
		return $stacks[$stackname];	
	}
	
	public function cssOutput($browser) {
		$stackstring=$this->getStackString($this->initstring);
		return array("font-family",$stackstring);
	}		
}
?>
