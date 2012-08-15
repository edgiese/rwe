<?php if (FILEGEN != 1) die;
// class definition file for style aggregator
class style_aggregator {
////////////////////////////////////////////////////////////////////////////////
const HTMLAGG=1;	// if an html aggregator
const STYLERAGG=2;	// if a styled styler aggregator

private $type;
private $styles;  // array of arrays(keyword=>array of stylers)
private $stylesneeded; 	// array of (keyword=>array of classnames)
private $containerhtml="";// html of container element, if there is one

function __construct($html) {
	// set arrays of html keyword-specific styling needs
	$stylers=array("color","bkcolor","position","width","height","font","fontsize","fontattribs","justify","opacity",
					"margintop","marginbottom","marginleft","marginright","paddingtop","paddingbottom","paddingleft","paddingright",
					"minwidth","minheight","zindex","textdecoration","hide","overflow","textindent","bkimage","liststyle",
					"letterspacing","wordspacing","whitespace","lineheight","borderleft","bordertop","borderright","borderbottom",
					"display","clear","verticalalign");
	// commented out for now: "verticalalign");
	$this->stylesneeded=array();
	$this->styles=array();
	
	if (is_string($html)) {
		// this is an aggregator for an id element
		$this->type=self::HTMLAGG;
		if (False !== ($i=strpos($html,":"))) {
			// a container or a style
			if (substr($html,0,$i) == "*style") {
				// a style.
				$sels=array();
			} else {
				// a container.
				$sels=array(substr($html,0,$i));
				$this->containerhtml=$sels[0];
			}
			$sels=array_merge($sels,explode(",",substr($html,$i+1)));
		} else {
			// single element.  first in array with nothing following
			$sels=array($html);
		}		
		foreach ($sels as $sel) {
			if (False !== ($i=strrpos($sel," "))) {
				// this is a nested element.  required stylers will be based on last keyword
				$htmlelement=substr($sel,$i+1);
			} else
				$htmlelement=$sel;	// normal case
			foreach ($stylers as $styler) {
				$class="style_".$styler;
				if (version_compare(PHP_VERSION, '5.3.0') === 1) {
					eval('$bNeeded= $class::isRequired($htmlelement);');
				} else {
					$dummy=new $class("");
					$bNeeded=$dummy->isRequired($htmlelement);
					unset($dummy);
				}
				if ($bNeeded)
					$this->stylesneeded[$sel][]=$styler;			
			}
		}
	} else {
		// this is an aggregator for a style
		$this->type=self::STYLERAGG;
	}	
}

// adds another style if appropriate
public function applyStyle($stylerinfo) {
	list($html,$classname,$initdata)=$stylerinfo;
	
	if ($html == "*" && $classname="*" && $initdata="*") {
		// iterator has run out of styles to suggest.
		if ($this->containerhtml != "*class") {
			// don't assign defaults for classes
			//  for a normal styler, use defaults for all remaining
			foreach ($this->stylesneeded as $html=>$classes) {
				foreach ($classes as $classname) {
					// because of nested styles (eg, div img) send the styler just the last one for setting defaults
					if (False !== ($i=strrpos($html," "))) {
						// this is a nested element.  required stylers will be based on last keyword
						$htmlelement=substr($html,$i+1);
					} else
						$htmlelement=$html; // normal case
					$classname="style_".$classname;
					$this->styles[$html][]=new $classname("",$htmlelement);
				}
			}
		}	
		$this->stylesneeded=array();		// empty out everything
		return;
	}
	
	// look for desired classes and fill them
	if (!isset($this->stylesneeded[$html]))
		return;
	if (False === ($ix=array_search($classname,$this->stylesneeded[$html])))
		return;
	// we need this one.  add styler to array
	$classname="style_".$classname;
	$this->styles[$html][]=new $classname($initdata);
	// unset the needed array so we won't set it again
	unset($this->stylesneeded[$html][$ix]);
	if (sizeof($this->stylesneeded[$html]) == 0)
		unset($this->stylesneeded[$html]);
}

// returns true when item has been fully styled
public function isStyled() {
	return (sizeof($this->stylesneeded) == 0);
}

// returns the number of stylers in this aggregator that need styling data themselves to function properly
public function getStyledStylers() {
	// TODO:  implement this
	return array();
}

public function getHtml() {
	$retval="|";
	foreach ($this->stylesneeded as $html=>$styles)
		$retval .= $html."|";
	return $retval;	
}

// hleps build a master list of all stylers that need to process something or output js
public function accumulateallprocessedstylers($allstylers,$long,$parent,$html) {
	foreach ($this->styles as $html=>$style) {
		// first style is a container for the others in an html sense
		foreach ($style as $s) {
			if ($s->needsProcessing()) {
				$class=get_class($s);
				if ($this->containerhtml != "") {
					$htmlparent=($html == $this->containerhtml) ? $parent : $long;
				} else
					$htmlparent=$parent;	 
				$allstylers[]=array($s,$long,$html,$htmlparent,$this);
			}
		}
	}
}

// helps build a master list of all css styles and data about them so that the css file can be written
public function createMasterStyleTable($page,$long) {
	global $qqc,$qqi;
	
	foreach ($this->styles as $html=>$style) {
		if ($this->containerhtml == "" || $html == $this->containerhtml) {
			// first element is not contained
			$bContainedElement=False;
		} else {
			$bContainedElement=True;
			// for nested styles (eg, div img) strip off the initial one if it's the same as the container's.
			// technically this case isn't a "nested style" but it's needed to avoid ambiguity in setting styles for divs in divs, for example
			if (False !== ($i=strpos($html," "))) {
				// this is a nested element.  required stylers will be based on last keyword
				if (substr($html,0,$i) == $this->containerhtml)
					$html=substr($html,$i+1);
			}
		}		
		$shortname= substr($long,0,1) == "." ? $qqi->getClassShort($long) : $qqi->htmlShortFromLong($long,$page);
		foreach ($style as $s) {
			$newstyles=$s->cssOutput($browser);
			if (False !== $newstyles) {
				if (is_array($newstyles[0])) {
					foreach ($newstyles as $newstyle) {
						if (sizeof($newstyle) == 3)
							list($keyword,$value,$pseudoclass)=$newstyle;
						else {
							list($keyword,$value)=$newstyle;
							$pseudoclass="";
						}	
						$qqc->insert("mst",$page,$long,$shortname,$bContainedElement,$html,$keyword,$pseudoclass,$value);
					}
				} else {
					if (sizeof($newstyles) == 3)
						list($keyword,$value,$pseudoclass)=$newstyles;
					else {
						list($keyword,$value)=$newstyles;
						$pseudoclass="";
					}	
					$qqc->insert("mst",$page,$long,$shortname,$bContainedElement,$html,$keyword,$pseudoclass,(string)$value);
				}
			}	
		}
	}
}


// end of class definition /////////////////////////////////////////////////////
}
?>
