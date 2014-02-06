<?php if (FILEGEN != 1) die;
class link {
/////////  class definition file for all links /////////////////////////////////
const SHAPE_NONE = 0;	// no shape, default
const SHAPE_RECT = 1;
const SHAPE_CIRCLE = 2;
const SHAPE_POLY = 3;

private $href;
private $text;
private $tip;
private $tag;
private $image;
private $accesskey;
private $shape=self::SHAPE_NONE;
private $coords;
private $longname;

function __construct($longname,$href,$text,$tip="",$accesskey="") {
	$this->longname=$longname;
	$this->accesskey=$accesskey;
	$this->href=$href;
	$this->text=$text;
	$this->tip=$tip;
	$this->accesskey=$accesskey;
}

public function registerStyled($stylegen,$usage="",$parentlong) {
	if ($this->longname == "")
		return;
	if ($usage == "")
		$usage="link";
	$stylegen->registerStyledId($this->longname,"a",$usage,$parentlong);
}

public function setParams($href,$text,$tip="",$accesskey="") {
	$this->accesskey=$accesskey;
	$this->href=$href;
	$this->text=$text;
	$this->tip=$tip;
	$this->accesskey=$accesskey;
}

public function setShape($shape,$coords) {
	$this->shape=$shape;
	$this->coords=$coords;
}

// takes an image object
public function setImage($image) {
	$this->image=$image;
}

// returns string
public function getOutput($bAreaOnly=False,$extrakw='') {
	global $qqi;
	
	if ($this->image instanceof image) {
		// substitute image at *** unless there is no text, then substitue whole thing
		if ($this->text == '')
			$this->text='***';
		$this->text=str_replace('***',$this->image->getOutput(),$this->text);	
	}
	
	if ($extrakw != '')
		$extrakw = ' '.$extrakw;
	$id=$this->longname != "" ? $qqi->idcstr($this->longname) : "";
	$href=$qqi->hrefPrep($this->href);
	$tip=$this->tip != "" ? " title=\"{$this->tip}\"" : "";
	$accesskey=$this->accesskey != "" ? " accesskey=\"{$this->accesskey}\"" : "";
	if ($this->shape != self::SHAPE_NONE) {
		if ($this->shape == self::SHAPE_RECT) {
			$shape=" shape=\"rect\"";
		} else if ($this->shape == self::SHAPE_CIRCLE) {
			$shape=" shape=\"circle\"";
		} else if ($this->shape == self::SHAPE_POLY) {
			$shape=" shape=\"poly\"";
		} else
			throw new exception("unknown shape code for link:  $this->shape");
		$shape .= " coords=\"";
		$comma="";
		foreach ($this->coords as $coord) {
			$shape .= $comma.$coord;
			$comma=",";
		}
		$shape .= '"';
	} else
		$shape="";

	if ($bAreaOnly) {
		$retval="<area href=\"{$href}\"{$shape}{$extrakw} alt=\"{$this->text}\" />";
	} else {
		$retval="<a href=\"{$href}\"{$id}{$tip}{$accesskey}{$shape}{$extrakw}>{$this->text}</a>";
	}	
	if (!$bAreaOnly && $this->accesskey != "") {
		// AFTER translating, underline the access key if it exists in the label
		$startpos=strpos($retval,">")+1;
		$endpos=strpos($retval,"<",$startpos);
		$ulpos=stripos($this->accesskey,$retval,$startpos);
		if (False !== $ulpos && $ulpos < $endpos)
			$retval=substr_replace($retval,'<span style="text-decoration:underline">'.substr($retval,$startpos,1).'</span>',$startpos,1);
	}
	return $retval;
}

static function anchorHTML($href,$tip="",$longname="") {
	global $qqi;
	
	$id=$longname != "" ? $qqi->idcstr($longname) : "";
	$href=$qqi->hrefPrep($href);
	$tooltip=$tip != "" ? " title=\"{$tip}\"" : "";
	
	return "<a href=\"$href\"$id$tooltip>";
}

///////// end of class definition //////////////////////////////////////////////
} ?>
