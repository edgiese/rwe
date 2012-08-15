<?php if (FILEGEN != 1) die;
class pmod_multi {
////////////// pmod class for multi-div trigger options //////////
private $overlays;
private $recipes;
private $triggers;
private $positionsels;

// returns updated recipe
private function getOvlyElem($ln,$typeoffset,$recipe,&$exclusives,&$overlays) {
	$bPause=($ln[0] == '*');
	if (False === ($i=strpos($ln,'=')))
		throw new exception("illegal show/hide spec syntax");
	$ln=trim(substr($ln,$i+1));
	
	if (0 == preg_match("/^\\s*'([^']+)'\\s*,\\s*([^,]+)\\s*,\\s*(\\d+)\\s*$/",$ln,$matches))
		throw new exception("syntax error in overlay spec.  should be 'selector',method,speed.  is:$ln");
	switch ($matches[2]) {
		case 'show':
			$effect=1;
		break;
		case 'fade':
			$effect=2;
		break;
		case 'updown':
			$effect=3;
		break;
		default:
			throw new exception("unknown effect type {$matches[2]}");
		break;
	}
	$speed=(int)$matches[3];
	
	if (False === ($overlay=array_search($matches[1],$overlays))) {
		$overlay=sizeof($overlays);
		$overlays[$overlay]=(int)$matches[1];
	}
	// if turning an overlay on, turn off all those in any exclusives first
	if ($typeoffset == 0) {
		foreach ($exclusives as $exclusive) {
			if (isset($exclusive[$overlay])) {
				foreach ($exclusive as $ovlyoff=>$true) {
					infolog("dbg","overlayoff testing $ovlyoff vs $overlay");
					if ($ovlyoff != $overlay) {
						infolog("dbg","writing turnoff: $effect,$speed,$ovlyoff");
						$recipe[]=array(50+$effect,$speed,$ovlyoff);
					}
				}
			}
		}
	}
	$effect=$bPause ? -($typeoffset+$effect) : $typeoffset+$effect; 
	$recipe[]=array($effect,$speed,$overlay);
	return $recipe;
}

function __construct($lines) {

	$overlays=array();
	$exclusives=array();
	$ixRecipe=-1;
	$recipes=array();
	$triggers=array();
	$positionsels=array();
		
	foreach ($lines as $line) {
		echo htmlentities($line)."<br />";
		$ln=trim($line);
		if ($ln == "" || $ln[0] == ";")
			continue;		// ignore blank and comment
		if (($i=strpos($ln,' ;')) !== False) {
			// strip off inline comment
			$ln=trim(substr($ln,0,$i));
		}
		
		if (substr($ln,0,9) == 'exclusive') {
			if ($ixRecipe > -1)
				throw new exception("cannot specify an exclusive after starting recipes");
			if (False === ($i=strpos($ln,'=')))
				throw new exception("illegal exclusive spec syntax");
			$ln=trim(substr($ln,$i+1));
			$newexclusive=array();
			while (preg_match("/^\\s*[,]*\\s*'([^']+)'/",$ln,$matches)) {
				if (False === ($overlay=array_search($matches[1],$overlays))) {
					$overlay=sizeof($overlays);
					$overlays[$overlay]=$matches[1];
				}
				$newexclusive[$overlay]=True;
				$ln=substr($ln,strlen($matches[0]));
			}
			if ($ln != '')
				throw new exception("illegal exclusive spec syntax: missing, unclosed quote or garbage at end of line.  line:$ln");
			$exclusives[]=$newexclusive;	
			continue;
		}

		if (substr($ln,0,7) == 'default') {
			if ($ixRecipe >= 0)
				$recipes[$ixRecipe]=$newrecipe;
			++$ixRecipe;
			$newrecipe=array();			
			continue;				
		}

		if (substr($ln,0,7) == 'trigger') {
			if ($ixRecipe >= 0)
				$recipes[$ixRecipe]=$newrecipe;
			++$ixRecipe;
			$newrecipe=array();
			if (False === ($i=strpos($ln,'=')))
				throw new exception("illegal exclusive trigger syntax");
			$ln=trim(substr($ln,$i+1));
			$triggername=$ln;
			if (False !==($i=strpos($ln,'/'))) {
				// this trigger has a positionsel
				$triggername=substr($ln,0,$i);
				$positionsels[$triggername]=substr($ln,$i+1);
			}
			$triggers[$triggername]=$ixRecipe;
			continue;				
		}
		
		if (substr($ln,0,4) == 'show' || substr($ln,0,5) == '*show') {
			$newrecipe=$this->getOvlyElem($ln,0,$newrecipe,&$exclusives,&$overlays);
			continue;
		}
		if (substr($ln,0,4) == 'hide' || substr($ln,0,5) == '*hide') {
			$newrecipe=$this->getOvlyElem($ln,50,$newrecipe,&$exclusives,&$overlays);
			continue;
		}
		if (substr($ln,0,5) == 'delay') {
			if (False === ($i=strpos($ln,'=')))
				throw new exception("illegal delay syntax");
			$ln=trim(substr($ln,$i+1));
			$newrecipe[]=array(100,(int)$ln,0);
			continue;
		}
		if (substr($ln,0,2) == 'do') {
			if (False === ($i=strpos($ln,'=')))
				throw new exception("illegal do syntax");
			$ln=trim(substr($ln,$i+1));
			$newrecipe[]=array(101,(int)$ln,0);
			continue;
		}
		throw new exception("illegal keyword");
	}
	if ($ixRecipe >= 0)
		$recipes[$ixRecipe]=$newrecipe;
	
	infolog_dump("dbg","exclusives",$exclusives);
	infolog_dump("dbg","overlays",$overlays);
	infolog_dump("dbg","triggers",$triggers);
	infolog_dump("dbg","positionsels",$positionsels);
	infolog_dump("dbg","recipes",$recipes);
	$this->overlays=$overlays;
	$this->recipes=$recipes;
	$this->triggers=$triggers;
	$this->positionsels=$positionsels;
}

// returns array of trigger names
public function getTriggerNames() {
	return $this->triggers;
}

public function getOverlays() {
	global $qqi;
	
	$retval=array();
	foreach ($this->overlays as $overlay)
		$retval[]=$qqi->scanSelector($overlay);
	return $retval;
}

public function getRecipes() {
	return $this->recipes;
}

public function getTriggerPositionSel() {
	global $qqi;
	
	$retval=array();
	foreach ($this->positionsels as $ix=>$positionsel)
		$retval[$ix]=$qqi->scanSelector($positionsel);
	return $retval;
}	

//// end of class definition ///////////////////////////////////////////////////
} ?>
