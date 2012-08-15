<?php if (FILEGEN != 1) die;
// styler for background color.  no js output, just css
// format:  bkimage url|xlrc|xtbc|scroll,fixed
class style_bkimage extends style_base {
	private $url;		// if blank, no image
	private $bSysFile;	// if True, this is a system file, otherwise, a project file
	private $x;			// "l","r", #+% or px, "x"
	private $y;			// "t","b", #+% or px, "x"
	private $bScroll;	// if true, scroll with image
	
	function __construct($initstring,$defaulthtml="") {
		if ($defaulthtml != "")
			$this->url="";		// master default
		else {
			$init=explode("|",$initstring);
			if (sizeof($init) < 1)
				$init[0]="";
			if (sizeof($init) < 2)
				$init[1]="l";
			if (sizeof($init) < 3)
				$init[2]="t";
			if (sizeof($init) < 4)
				$init[3]="scroll";

			$this->bSysFile=False;
			if (substr($init[0],0,1) == "*") {
				$this->bSysFile=True;
				$init[0]=substr($init[0],1);
			}				
			$this->url=$init[0];
			$this->y=strtolower($init[2]);
			$this->x=strtolower($init[1]);
			$this->bScroll= strtolower($init[3]) == "scroll";		
		}	
	}

	public function cssOutput($browser) {
		global $qq,$qqi;
		
		if ($this->url == "")
			return array("background-image","none");
		$url=$this->url;
		if (substr($url,0,2) == '~/') {
			$imgdir=$qq['production'] ? 'src' : 'create';
			$url="p/{$qq['project']}/$imgdir/".substr($url,2);
		}
		$retval=array(array("background-image","url(\"{$qqi->hrefPrep($url,False,'',idstore::ENCODE_NONE)}\")"));
		$retval[]=array("background-attachment",$this->bScroll ? "scroll" : "fixed");
		if ($this->x == "x" && $this->y == "x") {
			$repeat="repeat";
			$y='top';
			$x='left';
		} else if ($this->x == "x") {
			$repeat="repeat-x";	
			$x="left";
			if ($this->y == "t")
				$y="top";
			else if ($this->y == "b")
				$y="bottom";
			else if ($this->y == "c")
				$y="center";
			else
				$y=$this->y;		
		} else if ($this->y == "x") {
			$repeat="repeat-y";
			$y="top";
			if ($this->x == "l")
				$x="left";
			else if ($this->x == "c")
				$x="center";
			else if ($this->x == "r")
				$x="right";
			else
				$x=$this->x;		
		} else {		
			$repeat="no-repeat";
			if ($this->y == "t")
				$y="top";
			else if ($this->y == "b")
				$y="bottom";
			else if ($this->y == "c")
				$y="center";
			else
				$y=$this->y;		
			if ($this->x == "l")
				$x="left";
			else if ($this->x == "r")
				$x="right";
			else if ($this->x == "c")
				$x="center";
			else
				$x=$this->x;		
		}	
		$retval[]=array("background-repeat",$repeat);
		$retval[]=array("background-position","$x $y");			
		return $retval;
	}
}
?>
