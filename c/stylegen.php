<?php if (FILEGEN != 1) die;
class stylegen {
//////////////////////////// stylegen class creates all css files /////////////
private $stylefile;			// filename used to generate the stylegenfileobject
private $snippets;			// array of [render][page]=>snippets passed back untouched to js object to build static js for page
private $bDirty;			// if True, this object has changed and needs to be stored 

const STYLING_INACTIVE = 0;
const STYLING_HARDCODED = 1;
const STYLING_DECLARATIONS = 2;

// styleinfo id types
const STYPE_DEFAULT = 0;
// NOTE!!!! CONTAINED MUST BE MAIN STYLE + 1 !!!!!!  Routines assume this!!!!
const STYPE_ID = 1;
const STYPE_CONTAINEDINID = 2;
const STYPE_USAGE = 3;
const STYPE_CONTAINEDINUSAGE = 4;
const STYPE_ADDSTYLE = 5;
const STYPE_CONTAINEDINADDSTYLE = 6;

// the following are generally only set when generating a style file:
private $pstyles;			// stylegenfile object (see next class in this file).  created only when needed
private $page;				// current page
private $browser;			// current browser
private $render;			// current render
private $styleInfo;			// array of $page,$type,$name,$html,$parent,$styler for a given browser and render (and possibly page)
private $hardCodedStyles;	// array of $page,$longname,$htmltype,$stylerclass,$stylerdata
private $stylers;			// array of [stylername]=>(blank styler) 
private $styleQueryProgress=self::STYLING_INACTIVE;
private $dependencies;		// array of file names that we're dependent on

function __construct($project,$filename) {
	// delete all style files in generated directory that start with the project name
	$files=scandir("g");
	$plen=strlen($project);
	
	foreach ($files as $file) {
		if (substr($file,0,$plen) == $project && substr($file,-4) == ".css")
			unlink("g/".$file);
	}
	$this->snippets=array();
	$this->stylefile=$filename;
	$this->dependencies=False;		// initialize to not initialized
}

public function isDirty() {return $this->bDirty;}
public function setClean() {
	global $qq,$qqc;
	
	if (isset($this->pstyles))
		unset($this->pstyles);
	// we might want to look at this in development, but we're really done with it:
	if ($qq['production'])
		$qqc->act("clearmst");
	
	$this->bDirty=False;
}

// returns True if object is up to date, or False otherwise
public function checkDependencies($pagedeftime) {
	if ($this->dependencies === False) {
		return False;
	}	
	foreach ($this->dependencies as $fname) {
		if (!file_exists($fname))
			throw new exception("pagedef file dependency '$fname' no longer exists!");
		if ($pagedeftime < filemtime($fname)) {
			return False;	
		}	
	}
	return True;
}

// returns a selector
private function makeSelector($page,$stype,$name,$parent,$html) {
	global $qqi;
	switch ($stype) {
		case self::STYPE_DEFAULT:
			$retval=$html;
		break;
		case self::STYPE_ID:
			$retval="$html#{$qqi->htmlShortFromLong($name,$page)}";
		break;
		case self::STYPE_CONTAINEDINID:
			$retval="#{$qqi->htmlShortFromLong($name,$page)} $html";
		break;
		case self::STYPE_ADDSTYLE:
		case self::STYPE_USAGE:
			$retval="$html.{$qqi->getClassShort($name)}";
		break;
		case self::STYPE_CONTAINEDINADDSTYLE:
		case self::STYPE_CONTAINEDINUSAGE:
			$retval=".{$qqi->getClassShort($name)} $html";
		break;
		default:
			throw new exception("illegal selector type: $stype");
		break;
	}
	return $retval;
}

// returns file name generated
private function genFile($filename,$render,$browser,$page) {
	global $qqc,$qqp,$qqi,$qqs;

	$this->bDirty=True;
	if (!isset($this->pstyles)) {
		$this->pstyles=new stylegenfile($qqp,$this->stylefile);
		$this->dependencies=$this->pstyles->getDependencies();
	}	
	$qqc->act("clearmst");
	$this->browser=$browser;
	$this->render=$render;
	
	// build an array of all the pages that will be handled by this css file.
	// this is done as an array to simplify creation of multi-page css files
	$pagenames=array($page);
	
	// build an array of default html elements and their styles.  this seeds the styling array for every page	
	$this->styleInfo=array();
	// 'body' is explcitly left off because it is handled differently than other tags. 'html' "em" and "strong" and 'a' and 'span' likewise.
	$allhtml=explode(' ',"blockquote br dd dl div dt form h1 h2 h3 h4 h5 h6 hr img li ol object pre p select strike sub sup table tr td th textarea ul");
	$this->stylers=array();
	
	// default project html values are specified on the default page with an id of '*'
	$defaultstyles=$this->pstyles->getIDStyles('*',$browser,$render);
	
	$stylerfiles=scandir("c/style");
	foreach ($stylerfiles as $sfile) {
		if (substr($sfile,-4) == ".php" && substr($sfile,0,6) == 'style_') {
			$stylername=substr($sfile,6,-4);
			if ($stylername == 'base')
				continue;
			$classname='style_'.$stylername;
			$styler=new $classname("");
			foreach ($allhtml as $html) {
				if ($styler->isRequired($html)) {
					$default='';
					for ($i=0; $i<sizeof($defaultstyles); ++$i) {
						if ($defaultstyles[$i][0] == $html && $defaultstyles[$i][1] == $stylername) {
							$default=$defaultstyles[$i][2];
							break;
						}
					}
					$key="0-*-*-$html-$stylername";
					$this->styleInfo[$key]=array('*',self::STYPE_DEFAULT,'*',$html,'',new $classname($default,$html));
				}
			}
			$this->stylers[$stylername]=$styler;
		}	
	}

	// poll all of the aus for hard coded styles and styled ids.
	$this->render=$render;
	$this->hardCodedStyles=array();
	foreach ($pagenames as $pg) {
		$this->page=$pg;
		list($formclass,$formtag,$formstate,$formdata,$forminit)=$qqp->getFormInfo($pg);
		$recipe=$qqp->getBuildList($pg);
		$bodyau=au_base::treeFromRecipe($recipe,$render,$formclass,$formtag,$formstate,$formdata);
		for ($au=$bodyau; $au != Null; $au=$au->getNextAU()) {
			$this->styleQueryProgress=self::STYLING_HARDCODED;
			$myformstate=(get_class($au) == $formclass) ? $formstate : '';
			$au->hardcodestyles($this,$myformstate);
			$this->styleQueryProgress=self::STYLING_DECLARATIONS;
			$au->declarestyles($this,$myformstate);
		}	
		unset($bodyau);	
	}
	$this->styleQueryProgress=self::STYLING_INACTIVE;

	// javascript processing.  this always needs to be done on a per-page basis, since
	// javascript is separately run for each page.
	foreach ($pagenames as $pg) {
		$js=new js();
		$js->setCurrentPage($pg);
		$positioners=array();
		// first pass:  do single stylers and find out which groups need styling together
		foreach ($this->styleInfo as $si) {
			list($stylepage,$stype,$name,$html,$parent,$styler)=$si;
			// filter out ids not relevant to this page
			if ($stylepage != $pg && $stylepage != '*' && $stylepage != '')
				continue;
			$selector=$this->makeSelector($pg,$stype,$name,$parent,$html);
			// single-styler processing:
			$procflags=$styler->process($js,$browser,$render,$stype,$selector,$parent);
			if (($procflags & (style_base::PROC_SINGLE|style_base::PROC_POSITION)) != 0) {
				if (0 != ($render & (state::RENDER_GOOD|state::RENDER_PRINT)))	
					throw new exception("css javascript processing is illegal for render $render.  done for name=$name html=$html");
			}
			if (($procflags & style_base::PROC_POSITION) != 0) {
				$positioners[]=array($styler,$name,$html,$parent);
			}
		}
		if (sizeof($positioners) > 0) {
			style_position::processPosition($js,$browser,$render,$pg,$positioners);
		}
		unset($positioners);
		// save these snippets to feed back to the js object later
		$this->snippets[$this->getStaticKey($render,$browser,$pg)]=$js->getDynamicSnippets();
		unset($js);
	}

	// build the master style table	
	foreach ($this->styleInfo as $si) {
		list($stylepage,$stype,$name,$html,$parent,$styler)=$si;
		$newstyles=$styler->cssOutput($browser);
		// a styler can return False (no output), array(kw,value,[pseudoclass]) or array(array(kw,value,[pseudoclass]))
		if (False !== $newstyles) {
			if (!is_array($newstyles[0]))
				$newstyles=array($newstyles);
			foreach ($newstyles as $newstyle) {
				if (sizeof($newstyle) == 3)
					list($keyword,$value,$pseudoclass)=$newstyle;
				else {
					list($keyword,$value)=$newstyle;
					$pseudoclass="";
				}	
				$qqc->insert("mst",$stylepage,$stype,$name,$html,$parent,$keyword,$pseudoclass,(string)$value);
			}
		}	
	}
	// we're done with styleInfo.  It's a big table, and the master style table is, too	
	unset($this->styleInfo);


	$qqc->act("appendimportant");
	
	// now we're ready to build the table.  telescope it two ways:  first by value, then by selectors
	$styledata=$qqc->getRows("getstyles",-1);
	$styles=array();
	$selector=$newselector=$oldselector="";
	$separator="";
	for ($ix=0; $ix<sizeof($styledata); ++$ix) {
		extract($styledata[$ix]); // SETS: 
		// body instructions are a special case.
		if ($html == "body") {
			$styles[]=array($html,$keyword,$value);
			$oldselector="force_selector_change";
			continue;
		}
		$newselector=$this->makeSelector($page,$stype,$name,$parent,$html).$pseudoclass;
		if ($stype == self::STYPE_ID || $stype == self::STYPE_CONTAINEDINID)
			$qqi->lockHTMLid($name,"s",$page);
			
		if ($newselector != $oldselector || $keyword != $styledata[$ix-1]["keyword"] || $value != $styledata[$ix-1]["value"]) {
			$selector .= $separator.$newselector;
			$separator=",";
			$oldselector=$newselector;
		}			
		if ($ix >= sizeof($styledata)-1 || $styledata[$ix+1]["keyword"] != $keyword || $styledata[$ix+1]["value"] != $value) {
			$styles[]=array($selector,$keyword,$value);
			$separator="";
			$selector="";
		}
	}
	unset($styledata);
	usort($styles,array(&$this,"cmpstyle"));	
		
	$fp=fopen($filename,"w");
	if ($fp == 0)
		throw new exception("cannot open style file: $file");
	// here is where we can handle any writes that are universal filegen rules
	fwrite($fp,"a{text-decoration:none;color:inherit}em{font-style:italic;font-family:inherit;font-size:inherit}strong{font-weight:bold;font-family:inherit;font-size:inherit}map{display:none}");	

	$lastselector="";
	$separator="";
	for ($ix=0; $ix< sizeof($styles); ++$ix) {
		list($selector,$keyword,$value)=$styles[$ix];
		if ($selector != $lastselector) {
			fwrite($fp,$separator.$selector."{");
			$lastselector=$selector;
			$separator="";
		} else
			$separator=";";
		fwrite($fp,$separator.$keyword.":".$value);
		$separator="}";
	}
	fwrite($fp,"}");		
	fclose($fp);
	
	unset($styles);
}

// sort callback sorts by destinations, keeping indeces stable
public function cmpstyle($a,$b) {
	return $a[0] != $b[0] ? ($a[0] > $b[0] ? +1 : -1) : ($a[1] > $b[1] ? +1 : -1);
}

////////////////////////////////////////////////// AU CALLBACKS for the styling process
// allows low-level access to styles by aus.  html can be tag or * for any/all elements
public function styleByLong($longname,$html,$classname,$formatstring) {
	global $qqi;
	
	if ($this->styleQueryProgress != self::STYLING_HARDCODED)
		throw new exception("call to styleByLong out of context for $longname $htmltype");
	if (!$qqi->isValidLongname($longname,$this->page))	
		throw new exception("attempting to set style for unknown id longname=$longname");
	$this->hardCodedStyles[]=array($this->page,$longname,$html,$classname,$formatstring);	
}

// returns an array of (container or '',array of (html))
private function processHTMLType($htmltype) {
	if (False !== ($i=strpos($htmltype,':'))) {
		$containing=substr($htmltype,0,$i);
		$html=explode(',',substr($htmltype,$i+1));
	} else {
		$containing='';
		$html=explode(',',$htmltype);
	}
	return array($containing,$html);
}

private function processStyles($styles,$name,$containing,$html,$stypeMain,$page='*',$parent='') {
	$stypeContained=$stypeMain+1;
	foreach ($styles as $style) {
		if ($style[0] == $containing) {
			if (!isset($this->stylers[$style[1]]))
				throw new exception("unknown styler {$style[1]} (html $containing) being applied to id $name stype $stypeMain");
			if ($this->stylers[$style[1]]->isRequired($containing)) {
				$styler=$this->pstyles->getStyler($style[1],$style[2]);
			$key="$stypeMain-$page-$name-{$style[0]}-{$style[1]}";
			if (!isset($this->styleInfo[$key]))
				$this->styleInfo[$key]=array($page,$stypeMain,$name,$containing,$parent,$styler);
			} else
				infolog("warning","styler {$style[1]} not needed for name $name stype $stypeMain (html $containing).  Ignored.");	
		} else if (False !== array_search($style[0],$html)) {
			$stype=$containing == '' ? $stypeMain : $stypeContained;
			if (!isset($this->stylers[$style[1]]))
				throw new exception("unknown styler {$style[1]} (html $containing) being applied to id $name stype $stype");
			// the containing html, if non-blank, is vestigal in the contained html at this point.  strip it off.
			// so, for example 'div div' used to specify a contained div in a text object will from now on be identified as div.
			// other contained ids, e.g. 'ol li' are retained.	
			if (substr($style[0],0,strlen($containing)+1) == $containing.' ')
				$style[0]=substr($style[0],strlen($containing)+1);
			$htmlnestedarray=explode(' ',$style[0]);
			$testhtml=$htmlnestedarray[sizeof($htmlnestedarray)-1]; 	
			if ($this->stylers[$style[1]]->isRequired($testhtml)) {
				$styler=$this->pstyles->getStyler($style[1],$style[2]);
				$key="$stype-$page-$name-{$style[0]}-{$style[1]}";
				if (!isset($this->styleInfo[$key])) {
					$this->styleInfo[$key]=array($page,$stype,$name,$style[0],$parent,$styler);
				}	
			} else
				infolog("warning","styler {$style[1]} not needed for name $name stype $stype (html {$style[0]}).  Ignored.");	
		}	
	}
}

public function registerStyledId($longname,$htmltype,$usage,$parent=Null) {
	global $qqi;

	if ($this->styleQueryProgress != self::STYLING_DECLARATIONS)
		throw new exception("call to registerStyleId out of context for $longname $htmltype $usage");
	list($containing,$html)=$this->processHTMLType($htmltype);		
	if ($containing == '') {
		if (sizeof($html) > 1)
			throw new exception("a styled id can only have one main html type");
		$containing=$html[0];
		$html=array();
	}		
	if (!is_string($longname))
		throw new exception("expecting a string for longname but got ".gettype($longname));
	
	// first, check hardcoded styles from the au. these take top priority
	$styles=array();
	foreach($this->hardCodedStyles as $hcs) {
		list($hcspage,$hcsname,$hcshtml,$hcsclass,$hcsvalue)=$hcs;
		if ($hcspage == $this->page && $hcsname == $longname)
			$styles[]=array($hcshtml,$hcsclass,$hcsvalue);
	}
	if (sizeof($styles) > 0)
		$this->processStyles($styles,$longname,$containing,$html,self::STYPE_ID,$this->page,$parent);

	// next, do the styles from the style file		
	$idstyles=$this->pstyles->getIDStyles($longname,$this->browser,$this->render,$this->page);
	$this->processStyles($idstyles,$longname,$containing,$html,self::STYPE_ID,$this->page,$parent);
	
	// finally, make certain that usage styles get applied to this id
	$usageclass=$this->registerUsage($htmltype,$usage);
	$qqi->addClassToID($usageclass,$longname,$this->page);
}

// this function returns the name of the usage style that must be sent to cstr() at output time
public function registerUsage($htmltype,$usage) {
	global $qqi;
	
	if ($this->styleQueryProgress != self::STYLING_DECLARATIONS)
		throw new exception("call to registerStyleUsage out of context for $htmltype $usage");
	$palette=$this->pstyles->paletteFromPage($this->page);
	$usageclass=$palette.'_'.$usage;
	$qqi->declareClass($usageclass);
	list($containing,$html)=$this->processHTMLType($htmltype);
	$styles=$this->pstyles->getPaletteStyles($palette,$usage,$this->browser,$this->render);
	$this->processStyles($styles,$usageclass,$containing,$html,self::STYPE_USAGE);
	return $usageclass;
}


public function registerClass($classname,$htmltype,$cycle=0) {
	global $qqi;
	if ($this->styleQueryProgress != self::STYLING_DECLARATIONS)
		throw new exception("call to registerClass out of context for $classname $html $usage");
	$qqi->declareClass($classname,$cycle);
	$palette=$this->pstyles->paletteFromPage($this->page);
	for ($icycle=0; $icycle<$cycle+1; ++$icycle) {
		$addclass=($cycle == 0) ? $classname : $classname.(string)($icycle+1);	
		list($containing,$html)=$this->processHTMLType($htmltype);		
		$styles=$this->pstyles->getPaletteStyles($palette,'+'.$addclass,$this->browser,$this->render);
		$this->processStyles($styles,$addclass,$containing,$html,self::STYPE_ADDSTYLE);
	} // loop for all cycles	
}


////////////// stylegenfile access functions

// returns a key for static js data
public function getStaticKey($render,$browser,$page) {
	global $qqs;
	$pagename=str_replace('/','_',$page);
	$csstype=$qqs->answerBrowserQuestion(state::QUESTION_CSSTYPE,$browser);
	return "$render-$csstype-$pagename";
}

// returns filename and generates it if necessary
public function getFileName($render,$browser,$page) {
	global $qq,$qqs;
	
	$filename="g/{$qq['project']}-{$this->getStaticKey($render,$browser,$page)}.css";
	
	if (!file_exists($filename))
		$this->genFile($filename,$render,$browser,$page);		
	return $filename;	
}

// used to return snippets to js so it can build the static file for a page
public function getStyleSnippets($statickey) {
	if (!isset($this->snippets[$statickey]))
		return array();		// none were set or needed
	return $this->snippets[$statickey];	
}

public function getRender() {
	if ($this->styleQueryProgress == self::STYLING_INACTIVE)
		throw new exception("get render call out of context");
	return $this->render;
}

}///////////////////////////// end of stylegen definition //////////////////////

/// this is a private class used only by stylegen:
class stylegenfile {
/////////////////////////////////////// start of class definition:
private $ids;		// array of (browserq,rendermask,id,page,htmlstring,stylername,value)
private $palettes;	// array of [palette]=>array(browserq,rendermask,usageorclass,htmlstring,stylername,value)
private $colors;		// array of [colorname]=>color
private $usepalette;	// array of [pagename, *, or '@'.palette]=>palette name (either palette to use or fallback palette)
private $usageequiv;	// array of usage equivalents
private $dependencies;	// array of file date dependencies

function __construct($pagedef,$filename) {
	ob_start();
		
	echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head><meta http-equiv="content-type" content="text/html; charset=windows-1250">
<title>Make Filegen Styles</title>
<style type="text/css">
body {font-family: courier new;font-size:15px;}
body {background-color: #F0FFE0; }
p.section {background-color:yellow;}
p.query {background-color:#CCFFFF; border: 1px solid black; padding: 10px; margin: 20px;}
td {border: 1px solid black; padding 2px;}
table{border-collapse:collapse}
</style></head><body>
EOT;
	$margintypes=array("left","right","top","bottom");
	$this->dependencies=array($filename);
	
	try {
		$lines=file($filename);
		$palette="";
		$ubermask=$mask=7;
		$browserq=$browsera=0;
		$ids=$usages=False;		// must specify a new one
		$pages=False;	// by default, no page specified
		$html="";
		$this->usageequiv=array();
		$this->ids=array();
		$this->palettes=array();
		$this->usepalette=array();
		$extrapages=array();
		$browserquestions=state::enumerateBrowserQuestions();
	
		for ($lineix=0; $lineix<sizeof($lines); ++$lineix) {
			$ln=trim($lines[$lineix]);
			echo htmlentities($ln)."<br />";
			if ($ln == '')
				continue;
			// strip off ending comment if any	
			if (False !== ($i=strpos($ln," ;")) || False !== ($i=strpos($ln,"\t;")))
				$ln=rtrim(substr($ln,0,$i));			
			
			$first=$ln[0];
			if ($first == ";")
				continue;
	
			// page spec
			if ($first == "!") {
				$value=substr($ln,1);
				if (False === ($i=strpos($value,'@'))) {
					throw new exception("a page specification line must have an @ to specify palette");
				}
				$palettename=substr($value,$i+1);
				if (substr($value,0,$i) == '*') {
					// default page
					$this->usepalette['*']=$palettename;
					$pages=array("*"); 
				} else {	
					$pages=array_merge($extrapages,explode(',',substr($value,0,$i)));
					for ($j=0; $j< sizeof($pages); ++$j) {
						$pages[$j]=$pagedef->checkAndNormalizePage($pages[$j]);
						$this->usepalette[$pages[$j]]=$palettename;
					}
				}	
				$ids=$usages=False;		// must specify a new one
				$html='';	
				$palette='';
				$browserq=0;
				$mask=$ubermask;
				$extrapages=array();
				echo "<p class=\"section\"><strong>Setting Page={$pages[0]}</strong></p>";
				continue;
			}
				
			// extra pages, because this list can be long:
			if ($first == '+') {
				$value=substr($ln,1);
				$extrapages=array_merge($extrapages,explode(',',$value));
				continue;
			}

			// palette			
			if ($first == '@') {
				// new palette spec
				$ids=$usages=False;
				$html='';
				$pages=False;	// page no longer defined
				$value=substr($ln,1);
				if (False !== ($i=strpos($value,'@'))) {
					// this palette specifies a fallback palette
					$this->usepalette['@'.substr($value,0,$i)]=substr($value,$i+1);
					$value=substr($value,0,$i);
				}
				$palette=$value;
				$browserq=0;
				$mask=$ubermask;
				echo "<p class=\"section\"><strong>Setting Palette=$palette</strong></p>";
				continue;
			}	
			
			// color or id
			if ($first == '#') {
				// either an include, a color definition or an id spec, depending on whether we're in a page spec or not
				if (substr($ln,0,9) == '#include ') {
					$inclfilename=trim(substr($ln,9));
					if ($inclfilename[0] == '*') {
						$inclfilename="l/p/styles/".substr($inclfilename,1);
					} else {
						$inclfilename="p/{$qq['project']}/".$inclfilename;
					}	
					$this->dependencies[]=$inclfilename;
					if (!file_exists($inclfilename))
						throw new exception("included file name '$inclfilename' does not exist");
					$incllines=file($inclfilename);
					$lines=array_merge(array_slice($lines,0,$lineix+1),$incllines,array_slice($lines,$lineix+1));
					continue;
				}
				if ($pages === False || substr($ln,0,2) == '##') {
					// color definition
					if (substr($ln,0,2) == '##')
						$ln=substr($ln,1);
					list($name,$color)=explode("=",substr($ln,1));
					if (!isset($color))
						throw new exception("syntax error in color definition");
					if (substr($color,0,2) == '##') {
						// colors can themselves refer to color, but only if already defined.  it's cheap
						if (!isset($this->colors[substr($color,2)]))
							throw new exception("undefined color name: ".substr($color,2));
						$color=$this->colors[substr($color,2)];	
					}
					// first color definition gets the worm--just like everything else works in this file.
					if (!isset($this->colors[$name]))
						$this->colors[$name]=$color;
					continue;
				} else {
					$value=substr($ln,1);
					$html="";
					if (False !== ($i=strpos($value,"/"))) {
						// a slash allows item and html to be specified together
						$html=str_replace(",","|",substr($value,$i+1));
						$value=substr($value,0,$i);
					}
					$ids=explode(',',$value);
					$mask=$ubermask;
					continue;
				}
			}
			
			// usage or override style (starts with '+')
			if ($first == '.') {
				$value=substr($ln,1);
				$html="";
				if (False !== ($i=strpos($value,"/"))) {
					// a slash allows item and html to be specified together
					$html=str_replace(",","|",substr($value,$i+1));
					$value=substr($value,0,$i);
				}
				// commas or vertical bars are both ok
				$value=str_replace(",","|",$value);
				$usages=explode("|",$value);
				$mask=$ubermask;
				continue;
			}

			// html
			if ($first == "/") {
				if ($ids === False && $usages === False && $classes === False)
					throw new exception("cannot specify html:  no context");
				// commas or vertical bars are both ok
				$html=str_replace(",","|",substr($ln,1));
				$mask=$ubermask;
				continue;
			}
			
			// render mask
			if ($first == '%') {
				if ($ln[1] == '%') {
					$bUber=True;
					$ln=substr($ln,1);
				} else
					$bUber=False;
				if (False !== ($cmdpos=strpos($ln,':'))) {
					$value=(int)substr($ln,1,$cmdpos-1);
					$ln=substr($ln,$cmdpos+1);
					$savedmask=$mask;
				} else	
					$value=(int)substr($ln,1);
					
				if ($value < 1 || $value > 15)
					throw new exception("illegal render mask: $value");	
				$mask=$value;
				if ($bUber)
					$ubermask=$value;
				// only continue if no ':'.  This will fall through to command stuff	
				if ($cmdpos === False)	
					continue;	
			}

			// browser question
			if ($first == '^') {
				$value=substr($ln,1);
				if ($value != '0') {
					if (False === ($i=strpos($value,'=')))
						throw new exception("illegal browser question format.  should be ^question=answer");
					$browsera=substr($value,$i+1);	
					$value=substr($value,0,$i);	
					if (!isset($browserquestions[$value]))
						throw new exception("illegal browser question: $value");
					$value=$browserquestions[$value];
				}
				$browserq=(int)$value;	
				continue;	
			}
			
			// usage equivalence table
			if ($first == "$") {
				list($usagename,$altname)=explode("=",substr($ln,1));
				if (!isset($altname))
					throw new exception("illegal equivalence syntax");
				$this->usageequiv[]=array($usagename,$altname);
				continue;
			}
			
			// must be a styler/value pair
			if ($html == "")
				throw new exception("no html specified.");
			// cheesy! but I want to use the first of ":" or " " as a separator.  if neither is there, then fail
			$i=strpos($ln,":");
			$j=strpos($ln," ");
			if ($i === False)
				$i=10000;
			if ($j === False)
				$j=10000;
			$i=min($i,$j);
			if ($i === 10000)
				throw new exception("a style/value pair must be separated with a space or a colon");	
			$class=substr($ln,0,$i);
			$data=substr($ln,$i+1);
			
			if ($class == "margin" || $class == "padding") {
				$margins=explode(" ",$data);
				$classes=array();
				$arguments=array();
				foreach ($margins as $m) {
					$typeval=explode(":",$m);
					if (sizeof($typeval) != 2)
						throw new exception("syntax error in margin or padding:  argument must be in form of l:20 or whatever");
					$tstr=$typeval[0];	
					for ($i=0; $i<strlen($tstr); ++$i) {
						$typestr=strtolower(substr($tstr,$i,1));
						if (False === ($typeint=strpos("lrtb",$typestr)))
							throw new exception("margin and padding arguments must be one of L, R, T, or B");
						$classes[]=$class.$margintypes[$typeint];
						$arguments[]=$typeval[1];	
					}	
				} // loop for all different types				
			} else if ($class == "border") {
				$borders=explode(" ",$data);
				$classes=array();
				$arguments=array();
				foreach ($borders as $b) {
					$typeval=explode(":",$b);
					if (sizeof($typeval) != 2)
						throw new exception("syntax error in border:  argument must be in form of r:000000|1|solid or whatever");
					$tstr=$typeval[0];	
					for ($i=0; $i<strlen($tstr); ++$i) {
						$typestr=strtolower(substr($tstr,$i,1));
						if (False === ($typeint=strpos("lrtb",$typestr)))
							throw new exception("border arguments must be one of L, R, T, or B");
						$classes[]=$class.$margintypes[$typeint];
						$arguments[]=$typeval[1];	
					}	
				} // loop for all different types				
			} else {
				// default case--just one item, but put it in an array to simplify code below
				$classes=array($class);
				$arguments=array($data);
			}
			if ($usages !== False) {
				if ($palette == '')
					throw new exception("did not specify a palette before doing usage specification");
				if (!isset($this->palettes[$palette]))
					$this->palettes[$palette]=array();
				for ($i=0; $i<sizeof($classes); ++$i) {	
					foreach ($usages as $u) {
						$this->palettes[$palette][]=array($browserq,$browsera,$mask,$u,$html,$classes[$i],$arguments[$i]);
					}
				}	
			} else if ($ids !== False) {
				// must be ids, we checked earlier
				for ($i=0; $i<sizeof($classes); ++$i) {	
					foreach ($ids as $id) {
						foreach ($pages as $page)
							$this->ids[]=array($browserq,$browsera,$mask,$id,$page,$html,$classes[$i],$arguments[$i]);
					}
				}
			} else
				throw new exception("must specify either an id or a usage before providing styling data");
			// if there was a single-line mask, reset to what the old one was	
			if (isset($savedmask)) {
				$mask=$savedmask;
				unset($savedmask);
			}	
		} // for all lines in file
		
		// double check that all palettes specified actually exist
		var_dump($this->usepalette);
		foreach ($this->usepalette as $type=>$palette) {
			if (!isset($this->palettes[$palette]))
				throw new exception("palette '$palette' was referenced but never defined");
		}
		
		$dump_and_quit=False;
		if ($dump_and_quit) {
			echo "<hr /><h1>Results of Style File Read:</h1><h2>ID Table</h2>";
			echo "<table><tr><th>Br. Q.</th><th>Br. A.</th><th>Mask</th><th>ID</th><th>Page</th><th>HTML</th><th>Class</th><th>Value</th></tr>";
			foreach ($this->ids as $iddata) {
				list($browserq,$browsera,$mask,$id,$page,$html,$class,$value)=$iddata;
				echo "<tr><td>$browserq</td><td>$browsera</td><td>$mask</td><td>$id</td><td>$page</td><td>$html</td><td>$class</td><td>$value</td></tr>";
			}
			echo "</table>";
			foreach ($this->palettes as $palette=>$pdata) {
				echo "<h2>Usage Table: palette=$palette</h2>";
				echo "<table><tr><th>Br. Q.</th><th>Br. A.</th><th>Mask</th><th>Usage</th><th>HTML</th><th>Class</th><th>Value</th></tr>";
				foreach ($pdata as $pd) {
					list($browserq,$mask,$usage,$html,$class,$value)=$pd;
					echo "<tr><td>$browserq</td><td>$browsera</td><td>$mask</td><td>$usage</td><td>$html</td><td>$class</td><td>$value</td></tr>";
				}
				echo "</table>";
			}	
			echo "<h2>Color Equivalence Table</h2><table><tr><th>Name</th><th>Color</th></tr>";
			foreach ($this->colors as $name=>$color) {
				echo "<tr><td>$name</td><td>$color</td></tr>";
			}
			echo "</table>";

			echo "<h2>Palette Usage Table</h2><table><tr><th>Page/Palette</th><th>Base/Fallback Palette</th></tr>";
			foreach ($this->usepalette as $page=>$palette)		
				echo "<tr><td>$page</td><td>$palette</td></tr>";
			echo "</table>";
			
			echo "<h2>Usage Equivalences</h2><table><tr><th>Usage</th><th>Base Usage</th></tr>";
			foreach ($this->usageequiv as $ue)		
				echo "<tr><td>{$ue[0]}</td><td>{$ue[1]}</td></tr>";
			echo "</table>";
		}
		
		
		echo "</body></html>";
		if ($dump_and_quit)
			exit();	

	} catch (Exception $e) {
		ob_end_flush();
		echo "<strong>Exception occurred:</strong><br />";
		echo '<table border="1">';
		echo "<tr><td>Message</td><td>{$e->getMessage()}</td></tr>";
		echo "<tr><td>Code</td><td>{$e->getCode()}</td></tr>";
		echo "<tr><td>File and Line</td><td>{$e->getFile()} Line {$e->getLine()}</td></tr>";
		$trace=str_ireplace("\n","<br />",$e->getTraceAsString());
		echo "<tr><td>Stack Trace</td><td>{$trace}</td></tr>";
		echo "</table></body></html>";
		exit;
	}
	ob_end_clean();

}

// parent object uses these
public function getDependencies() {return $this->dependencies;}

// returns array of html=>array(stylername,value) for an id.  one html per row.
public function getIDStyles($id,$browser,$render,$page='*') {
	global $qqs;

	$alreadydidhtmlandstyler=array();		// used to prevent re-assignment of values 
	
	$retval=array();
	// if page is specified, make a first pass and see what's set for it
	if ($page != '*') {
		foreach ($this->ids as $iddata) {
			list($browserq,$browsera,$rendermask,$idrow,$pagerow,$htmlstring,$stylername,$value)=$iddata;
			if ($idrow != $id)
				continue;
			if ($browserq != 0 && $qqs->answerBrowserQuestion($browserq,$browser) != $browsera)
				continue;
			if (($render & $rendermask) == 0)
				continue;
			if ($page != $pagerow && $pagerow != '*')
				continue;
			$htmlarray=explode('|',$htmlstring);
			foreach ($htmlarray as $html) {	
				$key=$html.'_'.$stylername;
				if (!isset($alreadydidhtmlandstyler[$key])) {
					$alreadydidhtmlandstyler[$key]=True;	
					$retval[]=array($html,$stylername,$value);
				}
			}		 		
		}
	} else
	
	foreach ($this->ids as $iddata) {
		list($browserq,$browsera,$rendermask,$idrow,$pagerow,$htmlstring,$stylername,$value)=$iddata;
		if ($idrow != $id)
			continue;
		if ($browserq != 0 && $qqs->answerBrowserQuestion($browserq,$browser) != $browsera)
			continue;
		if (($render & $rendermask) == 0)
			continue;
		if ($page != '*')
			continue;
		$htmlarray=explode('|',$htmlstring);
		// only add this styler if it wasn't already specified
		foreach ($htmlarray as $html) {
			$key=$html.'_'.$stylername;
			if (!isset($alreadydidhtmlandstyler[$key])) {
				$alreadydidhtmlandstyler[$key]=True;	
				$retval[]=array($html,$stylername,$value);
			}
		} // loop for all htmls to add		 		
	} // loop for all iddata
	return $retval;
}

public function getPaletteStyles($startpalette,$usage,$browser,$render) {
	global $qqs;
	
	$retval=array();
	$alreadydidusage=array();				// used to prevent endless loops of usage equivalents
	$alreadydidhtmlandstyler=array();		// used to prevent re-assignment of values 
	
	// keep going until there are no more usage equivalents
	while ($usage != '') {
		$palette=$startpalette;
		// keep going until there are no more subpalettes
		while ($palette != '') {
			$styles=$this->palettes[$palette];
			foreach ($styles as $style) {
				list($browserq,$browsera,$rendermask,$usageorclass,$htmlstring,$stylername,$value)=$style;
				if ($usageorclass != $usage)
					continue;
				if ($browserq != 0 && $qqs->answerBrowserQuestion($browserq,$browser) != $browsera)
					continue;
				if (($render & $rendermask) == 0)
					continue;
				$htmlarray=explode('|',$htmlstring);
				foreach ($htmlarray as $html) {
					$key=$html.'_'.$stylername;
					if (!isset($alreadydidhtmlandstyler[$key])) {
						$alreadydidhtmlandstyler[$key]=True;
						$retval[]=array($html,$stylername,$value);
					}
				}		 		
			}
			// move to base palette if one is set
			$palette=(isset($this->usepalette['@'.$palette])) ? $this->usepalette['@'.$palette] : ''; 
		}
		
		$oldusage=$usage;
		$usage='';
		foreach ($this->usageequiv as $ue) {
			if ($oldusage == $ue[0]) {
				if (!isset($alreadydidusage[$ue[1]])) {
					$alreadydidusage[]=$ue[1];
					$usage=$ue[1];
				}		
				break;	
			}
		} // loop to look for another usage
	}
	return $retval;
}


public function paletteFromPage($page) {
	if (isset($this->usepalette[$page]))
		return $this->usepalette[$page];
	if (!isset($this->usepalette['*']))
		throw new exception("no default palette specified for project");
	return $this->usepalette['*'];		
}

// does color substitutions.  may do other preprocessing in the future
public function getStyler($name,$value) {
	$classname='style_'.$name;
	if (0 !== preg_match('/##([\\w]+)/',$value,$matches)) {
		if (!isset($this->colors[$matches[1]]))
			throw new exception("unknown color {$matches[1]} in styler '$name' with value '$value'");
		$value=str_replace('##'.$matches[1],$this->colors[$matches[1]],$value);	
	}
	return new $classname($value);
}

 
////////////////////////////// end of class definition /////////////////////////
} ?>
