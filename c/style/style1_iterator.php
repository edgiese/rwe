<?php if (FILEGEN != 1) die;
// class definition file for style iterator
class style_iterator {
////////////////////////////////////////////////////////////////////////////////
private $hardcodedstyles;	// no multiple htmls in any of the lists here--once keyword per array element!
private $longnamestyles;
private $pstyles;
private $ixSource;		// where we're looking
private $altusages;
private $html;			// array of html items to search and report
private $stylegen;		// stylegen object, provides style pairs

function __construct($stylegen,$render,$palette,$hardcodedstyles) {
	$this->render=$render;
	$this->hardcodedstyles=array();
	foreach ($hardcodedstyles as $hcs) {
		list($page,$longname,$html,$classname,$initstring)=$hcs;
		$hs=explode("|",$html);
		foreach ($hs as $h)
			$this->hardcodedstyles[]=array($longname,$h,$classname,$initstring);
	}
	$this->stylegen=$stylegen;
	$this->longnamestyles=array();
	$this->pstyles=$this->addPalette(array(),$palette,$render);
	
	// sometime in the future this array might get so large that it has to be split up.  but that is probably a very long time from now
	$this->altusages=array();
}

// recursive function to add palatte data.  takes palatte array, name to add, and position, and returns updated array
private function addPalette($palarray,$palette,$render) {
	$rows=$this->stylegen->getPaletteRows($palette,$render);
	if ($rows === False)
		return $palarray;	// nothing to do
	
	$newpalarray=array();	
	foreach ($rows as $row) {
		extract($row); //SETS: usage,html,class,data
		if ($class == "palette") {
			$newpalarray=$this->addPalette($newpalarray,$data,$render);
		} else {
			if (substr($usage,0,1) == "*") {
				// this is an id assignment, not a usage
				$hs=explode("|",$html);
				foreach ($hs as $h)
					$this->longnamestyles[]=array(substr($usage,1),$h,$class,$data);
			} else {
				$hs=explode("|",$html);
				foreach ($hs as $h)
					$newpalarray[]=array($usage,$h,$class,$data);
			}	
		}				
	} // for all returned rows
	return array_merge($palarray,$newpalarray);		
}

// constants for sources
const HARDCODED=0;
const LONG=1;
const PALETTE=2;

// sets up the iterator to iterate through styles for a given styled block
public function set($render,$longname,$usage,$agg) {
	$this->longname=$longname;
	$this->usage=$usage;
	// if there are spaces in the html string, this means that there are nested entries
	// we will attempt to format, for example, a |div img| as an |img| before going to defaults
	$html=$agg->getHtml();
	$this->html=array(array("",$html));	// first entry is always the full string, and blank is a signal to use html string that matched it
	if (False !== strpos($html," ")) {
		$pieces=explode("|",trim($html,"|"));
		foreach ($pieces as $piece) {
			// chip away at the thing until there are no spaces left
			$smallpiece=$piece;
			while (False !== ($i=strpos($smallpiece," "))) {
				$smallpiece=substr($smallpiece,$i+1);
				$this->html[]=array($piece,"|$smallpiece|");
			}
		}
	}
	reset($this->html);
	reset($this->hardcodedstyles);
	reset($this->longnamestyles);
	reset($this->pstyles);
	reset($this->altusages);
		
	$this->ixSource=self::HARDCODED;
}

// returns the next styler.  exception if there are none more to return
// returns an array of html, styler class and data strings
public function nextStyler() {
	list($styledhtml,$searchhtml)=current($this->html);
	
	if ($this->ixSource == self::HARDCODED) {
		while (False != ($hcstyle=current($this->hardcodedstyles))) {
			next($this->hardcodedstyles);
			list($longname,$html,$classname,$initstring)=$hcstyle;
			if ($longname == $this->longname && False !== strpos($searchhtml,"|$html|")) {
				if ($styledhtml == "")
					$styledhtml=$html;
				$retval=array($styledhtml,$classname,$initstring);
				break;
			}
		}
		if (!isset($retval))
			$this->ixSource=self::LONG;	// fall through to next level
	}	
	if ($this->ixSource == self::LONG) {
		while (False != ($lnstyle=current($this->longnamestyles))) {
			next($this->longnamestyles);
			list($longname,$html,$classname,$initstring)=$lnstyle;
			if ($longname == $this->longname && False !== strpos($searchhtml,"|$html|")) {
				if ($styledhtml == "")
					$styledhtml=$html;
				$retval=array($styledhtml,$classname,$initstring);
				break;
			}
		}	
		if (!isset($retval))
			$this->ixSource=self::PALETTE;	// fall through to next level
	}
	if ($this->ixSource == self::PALETTE) {
		while (False != ($pstyle=current($this->pstyles))) {
			next($this->pstyles);
			list($usage,$html,$classname,$initstring)=$pstyle;
			if ($usage == $this->usage && False !== strpos($searchhtml,"|$html|")) {
				if ($styledhtml == "")
					$styledhtml=$html;
				$retval=array($styledhtml,$classname,$initstring);
				break;
			}
		}
		if (!isset($retval)) {
			// if we get here, only thing left to do is to try another usage equivalent
			while (False != ($usagerow=current($this->altusages))) {
				next($this->altusages);
				extract($usagerow); //SETS: $usage,$alternate
				if ($usage == $this->usage) {
					// found another alternate.  go through the palette again
					reset($this->pstyles);
					$this->usage=$alternate;
					// we're banking on a sane level of usage redirection here to prevent stack overflow:
					return $this->nextStyler();
				}
			}
			// look for parent styles
			next($this->html);
			if (False !== current($this->html)) {
				reset($this->hardcodedstyles);
				reset($this->longnamestyles);
				reset($this->pstyles);
				reset($this->altusages);
					
				$this->ixSource=self::HARDCODED;
				return $this->nextStyler();
			}
			// this forces default assignments for anything remaining.  that finishes this aggregator!
			$retval=array("*","*","*");
		}	  
	}
	return $retval;
}

// end of class definition /////////////////////////////////////////////////////
}
?>
