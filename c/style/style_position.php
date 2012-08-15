<?php if (FILEGEN != 1) die;
// styler for horizontal and vertical position.  initial values set by css, final values set by js
// writes styles for:  left, top, position
class style_position extends style_base {

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// used to calculate dependencies.  COORDINATE WITH SNIPPETS OR DIE
	private static $calcs;
	private static $dependencies;
	private static function setDependencies() {
	self::$dependencies=array(
	
	// calc target=> array ( function, array of dependencies, array of parameters without 'longname' and asterisks on names)
	"leftToParent"=>array("x",array("^:w"),array("parentpercent","offset")),
	"topToParent"=>array("y",array("^:h"),array("parentpercent","offset")),
	"leftToWindow"=>array("x",array(),array("windowpercent","offset")),
	"topToWindow"=>array("y",array(),array("windowpercent","offset")),
	"leftToObject"=>array("x",array("object:x","object:w"),array("object","objectpercent","offset")),
	"topToObject"=>array("y",array("object:y","object:h"),array("object","objectpercent","offset")),
	"topToMaxOf2"=>array("y",array("object1:y","object1:h","object2:y","object2:h"),array("object1","offset1","object2","offset2")),
	"topToMaxOf3"=>array("y",array("object1:y","object1:h","object2:y","object2:h","object3:y","object3:h"),array("object1","offset1","object2","offset2","object3","offset3")),
	"topToMaxOf4"=>array("y",array("object1:y","object1:h","object2:y","object2:h","object3:y","object3:h","object4:y","object4:h"),array("object1","offset1","object2","offset2","object3","offset3","object4","offset4")),
	"xPercentToParent"=>array("x",array("*:w","^:w"),array("selfpercent","parentpercent","offset")),
	"yPercentToParent"=>array("y",array("*:h","^:h","*:w"),array("selfpercent","parentpercent","offset")),
	"xPercentToWindow"=>array("x",array("*:w"),array("selfpercent","windowpercent","offset")),
	"yPercentToWindow"=>array("y",array("*:h","*:w"),array("selfpercent","windowpercent","offset")),
	"xPercentToObject"=>array("x",array("*:w","object:x","object:w"),array("selfpercent","object","objectpercent","offset")),
	"yPercentToObject"=>array("y",array("*:h","*:w","object:y","object:h"),array("selfpercent","object","objectpercent","offset")),
	"rightToParent"=>array("w",array("*:x","^:w"),array("parentpercent","offset")),
	"bottomToParent"=>array("h",array("*:y","^:h"),array("parentpercent","offset")),
	"rightToWindow"=>array("w",array("*:x"),array("windowpercent","offset")),
	"bottomToWindow"=>array("h",array("*:y"),array("windowpercent","offset")),
	"rightToObject"=>array("w",array("*:x","object:x","object:w"),array("object","objectpercent","offset")),
	"bottomToObject"=>array("h",array("*:y","object:y","object:h"),array("object","objectpercent","offset")),
	"widthFromParent"=>array("w",array("^:w"),array("parentpercent","offset")),
	"heightFromParent"=>array("h",array("^:h"),array("parentpercent","offset")),
	"heightFrom2Children"=>array("h",array("object1:h","object2:h","object1:y","object2:y"),array("object1","object2")),
	"widthFromWindow"=>array("w",array(),array("windowpercent","offset")),
	"heightFromWindow"=>array("h",array(),array("windowpercent","offset")),
	"widthFromObject"=>array("w",array("object:w"),array("object","objectpercent","offset")),
	"heightFromObject"=>array("h",array("object:h"),array("object","objectpercent","offset")),
	"minHeightFromSibling"=>array("h",array("sibling:h","sibling:y","*:y"),array("sibling","offset")),
	"contain3Children"=>array("h",array("c1:h","c1:y","c2:h","c2:y","c3:h","c3:y",),array("c1","c2","c3","offset")),
	"contain2Children"=>array("h",array("c1:h","c1:y","c2:h","c2:y"),array("c1","offset1","c2","offset2"))
	);
	}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private $initstring;
		
	//format:  N+LRS
	//  +relx rely
	//  S x-routine;longname list;args|y-routine;longname list;args
	function __construct($initstring) {
		if ($initstring == "")
			$initstring="N";		// default
		$this->initstring=$initstring;		
	}

	// not required for divs or body marker
	static function isRequired($html) {
		return ($html != "body");
	}
	
	public function process($js,$browser,$render,$stype,$selector,$parent) { 
		// reset the flag so that processing can occur for more than one page per call
		$initstring=$this->initstring;
		$first=substr($initstring,0,1);
		if ($first != "S" && $first != "D" && $first != '+')
			return style_base::PROC_NONE;
		$coords=explode("|",trim(substr($initstring,1)));
		list($fname,$args)=self::parsepos($coords[0]);
		if ($fname != "_fixed_")
			return style_base::PROC_POSITION;
		list($fname,$args)=self::parsepos($coords[1]);
		return ($fname != "_fixed_") ? style_base::PROC_POSITION : style_base::PROC_NONE; 		
	}

	// helper function used by position, height, and width with small variations
	public function getPosStrings() {
		$initstring=$this->initstring;
		$first=substr($initstring,0,1);
		if ($first != "S" && $first != "D" && $first != '+')
			return array(False,False);
		return explode("|",trim(substr($initstring,1)));
	}

	// helper function for process
	private static function addCalcs($page,$render,$function,$long,$parent,$paramstring) {
		global $qqi;
		
		if ($paramstring === False)
			return;
		list($snippet,$params)=self::parsepos($paramstring);
		if ($snippet == "_fixed_")
			return;		// no dependency, no call
		$qqi->lockHTMLid($long,"j",$page);		// lock in this id, it will be referred to
		$myparams=array("longname"=>$long);
		if (!isset(self::$dependencies[$snippet]))
			throw new exception("snippet '$snippet' does not appear in positioning dependency array");
		if (self::$dependencies[$snippet][0] != $function)
			throw new exception("function mismatch in positioning.  looking for '$function', but snippet does '{self::$dependencies[$snippet][0]}'");	
		$paramnames=self::$dependencies[$snippet][2];
		// make an array of objects that this calc depends on for easy searching
		$deps=self::$dependencies[$snippet][1];
		$depnames=array();
		$depfuncs=array();
		$mydepkeys=array();	// long:func for all dependencies
		foreach($deps as $dep) {
			list($dn,$func)=explode(":",$dep);
			if ($dn != "*" && $dn != "^") {
				$depnames[]=$dn;
				$depfuncs[]=$func;
			} else {
				$mydepkeys[]=($dn == "*" ? $long : $parent).":".$func;
			}	
		}
		// parameters that are dependencies are passed in by long name.  convert to long name
			
		for ($i=0; $i< sizeof($params); ++$i) {
			$pname=$paramnames[$i];
			if (False !== ($ixdeps=array_search($pname,$depnames))) {
				//$qqi->lockHTMLid($params[$i],"j",$page);
				//$params[$i]=$pgen->shortFromLong($params[$i]);
				$mydepkeys[]=$params[$i].":".$depfuncs[$ixdeps];
			}	
			$myparams[$paramnames[$i]]=$params[$i];
		}
		
		$key=$long.":".$function;
		if (isset(self::$calcs[$key])) {
			infolog("exception","exception:  want to add duplicate key=$key snippet=$snippet");
			infolog_dump("exception","myparams of snippet I want to add",$snippet);
			infolog_dump("exception","mydepkeys of snippet I want to add",$mydepkeys);
			infolog_dump("exception","calcs before dying -- want to add duplicate",self::$calcs);
			throw new exception("trying to set a calculation that has already been set:  long=$long function=$function");
		}			
		self::$calcs[$key]=array($long,$parent,$function,$snippet,$myparams,$mydepkeys); 
	}

	// recursive function to output a calc preceded by all the calcs it depends on
	private static function outputCalc($js,$keysbeingdone,$key) {
		
		list($long,$parent,$function,$snippet,$myparams,$mydepkeys)=self::$calcs[$key];
		// look through any dependencies and put out the ones we're depdent on first
		foreach ($mydepkeys as $depkey) {
			if (isset(self::$calcs[$depkey])) {
				//infolog("verify","found dependency:  $key depends on $depkey");
				// got to do this one first.  but, first, check to make certain we aren't in a circular dependency
				if (False !== array_search($depkey,$keysbeingdone))
					throw new exception("detected circular positioning depedency containing $depkey while processing $long function $function");
				$keysbeingdone[]=$depkey;	
				self::outputCalc($js,$keysbeingdone,$depkey);	
			}
		}
		// output the calculation
		$js->addjs("calcpos","calcpos::$snippet",$myparams);
		// we won't do this one again...
		unset(self::$calcs[$key]);
	}

	
	// because there are order dependencies, this function takes care of all js positioning at once
	// for all position and size stylers
	static function processPosition($js,$browser,$render,$page,$stylers) {
		self::setDependencies();
		// create an array of snippet calls and parameter lists
		self::$calcs=array();
		for ($ix=0; $ix< sizeof($stylers); ++$ix) {
			list($styler,$longname,$html,$parent)=$stylers[$ix];
			// we are not going to calculate any nested html items because they have no unique id to handle them
			// if you wanted to do this, you'd have to get a little more clever about how you generated the snippets--more clever than i feel right now
			if (False !== strpos($html," ")) {
				$stylertype=get_class($styler);
				// this warning is wrongly triggered when div div's get positioned as L and R.  No calculations there!
				// infolog("warning","calculation of class $stylertype ignored for longname=$longname html='$html'.  Maybe you'd better set a specific value!");
				continue;
			}
			//if ($longname == $parent || substr($longname,0,1) == ".") {
			//	$stylertype=get_class($styler);
			//	infolog("warning","calculation of class $stylertype ignored for longname=$longname html='$html'.  There is no unique long name for this item");
			//	continue;
			//}
				
			if ($styler instanceof style_position) {
				list($first,$second)=$styler->getPosStrings();
				self::addCalcs($page,$render,"x",$longname,$parent,$first);
				self::addCalcs($page,$render,"y",$longname,$parent,$second);
			} else if ($styler instanceof style_width) {
				$posstring=$styler->getPosString();
				self::addCalcs($page,$render,"w",$longname,$parent,$posstring);
			} else if ($styler instanceof style_height) {
				$posstring=$styler->getPosString();
				self::addCalcs($page,$render,"h",$longname,$parent,$posstring);
			}			
		} // loop for all stylers in processing list

		//infolog_dump("verify","calcs after creation",self::$calcs);

		// put out the calculations to the file, making certain that dependents are done after independents
		if (sizeof(self::$calcs) > 0) {
			$js->addjscode("be","$(document).ready(calcpos); $(window).resize(calcpos);");
			$bNeedEnd=True;
			$js->addjscode("calcpos","if (this.busy) {alert('reentry detected'); return;} this.busy=1;");
		} else
			$bNeedEnd=False;	
		while (sizeof(self::$calcs) > 0) {
			reset(self::$calcs);
			$key=key(self::$calcs);
			self::outputCalc($js,array($key),$key);
		}
		if ($bNeedEnd) {
			$js->addjscode("calcpos","this.busy=0;");
		}
	} 

	// provides common syntax processing for fixed and calculated coordinates for position, width, and height
	// expected syntax:  function(arg1,arg2,arg3) or 100px (or similar)
	// returns array with function name, and array of arguments.  for hardcoded values, uses function name _fixed_
	static function parsepos($input) {
		// hard-code "function name" for fixed amounts
		if (False === ($i=strpos($input,"(")))
			return array("_fixed_",array($input));
		// check for trailing parenthesis	
		$input=rtrim($input);
		if (substr($input,-1,1) != ")")
			throw new exception("unmatched parentheses in position function: $input");
		// chop input into pieces	
		$name=substr($input,0,$i);	
		$input=substr($input,$i+1,-1);
		$pieces=explode(",",$input);
		return array($name,$pieces);	
	}

	public function cssOutput($browser) {
		$initstring=$this->initstring;
		if ($initstring == "N")
			return array(array('position','static'),array('float','none'));
		if ($initstring == "L")
			return array(array('position','static'),array('float','left'));
		if ($initstring == "R")
			return array(array('position','static'),array('float','right'));
		$first=substr($initstring,0,1);
		$init=trim(substr($initstring,1));
		if ($first != "S" && $first != "D" && $first != '+')
			throw new exception("illegal initstring for position:  $initstring");
			
		// left, top, and relative:
		$keywords=array('S'=>'fixed','D'=>'absolute','+'=>'relative');	
		$coords=explode("|",$init);
		$retval=array(array("position",$keywords[$first]));
		
		// left
		list($fname,$args)=self::parsepos($coords[0]);
		$pos=($fname == "_fixed_") ? $args[0] : 0;
		$retval[]=array("left",util::addpx($pos));
		
		// top
		list($fname,$args)=self::parsepos($coords[1]);
		$pos=($fname == "_fixed_") ? $args[0] : 0;
		$retval[]=array("top",util::addpx($pos));
		
		return $retval;				
	}
}
?>
