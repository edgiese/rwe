<?php if (FILEGEN != 1) die;
///// Access Unit definition file for form & state testing
class au_image extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $smallscalepercent;
private $link=False;
private $img;
private $usage;

// initstring format:  usage|id|smallscalepercent[|href|linkdescription]
function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	// because pagedef preprocesses images, initdata is never a string.
	$this->usage=$initdata['usage'];
	$this->smallscalepercent=$initdata['smallscalepercent'];
	if (isset($initdata['link']))
		$this->link=new link($this->longname,$initdata['link'],$initdata['linkdescription']);
	$this->img=new image($initdata['id'],$this->longname);
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);	// image
	if ($this->link !== False)
		$idstore->declareHTMLid($this,True,"map");
}

public function declarestyles($stylegen,$state) {
	$this->img->registerStyled($stylegen,$this->usage,$this->getParent()->htmlContainerId());
	if ($this->link !== False)
		$this->link->registerStyled($stylegen,$this->usage,$this->getParent()->htmlContainerId());
}

// does output
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
	list($width,$height)=$this->img->getNaturalSize();
	if (0 == (state::RENDER_BEST & $qqs->getRender()) && $this->smallscalepercent != 100) {
		$width=$width*$this->smallscalepercent/100;
		$height=$height*$this->smallscalepercent/100;
	}
	if ($this->link !== False)
		list($maplong,$mapshort)=$qqi->htmlLongShort($this,"map");
	else
		$maplong=$mapshort="";	
		
	$this->img->requestsize($width,$height);
	echo $this->img->getOutput($mapshort);
	
	if ($this->link !== False) {
		// output the map
		$this->link->setShape(link::SHAPE_RECT,array(0,0,$width,$height));
		echo "<map name=\"{$mapshort}\">{$this->link->getOutput(True)}</map>";
	}
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
