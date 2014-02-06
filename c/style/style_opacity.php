<?php if (FILEGEN != 1) die;
// styler for horizontal position.  initial values set by css, final values set by js
// writes styles for:  left, right, width, minwidth
class style_opacity extends style_base {
	private $opacity;
		
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="100";		// default
		$this->opacity=(int)$initstring;
		if ($this->opacity < 0 || $this->opacity > 100)
			throw new exception ("illegal opacity value: {$this->opacity}");		
	}

	static function isRequired($html) {
		return ($html != "body");
	}

	public function cssOutput($browser) {
		global $qqs;
	
		$opacity=$this->opacity;
		if ($opacity == 100)
			return False;
		if ($qqs->answerBrowserQuestion(state::QUESTION_OPACITYBYFILTER,$browser))
			return array("filter","alpha(opacity=$opacity)");
		$fracopacity= ($opacity < 10) ? "0.0".$opacity : (($opacity == 100) ? "1.0" : "0.".$opacity);	
		return array("opacity",$fracopacity);
	}		
}
?>
