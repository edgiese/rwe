<?php if (FILEGEN != 1) die;
class au_scrapbook_image extends au_base {
//////////////////////////////////// displays the current scrapbook image //////
// this AU is the main data holder for a number of others
private $sb;		// scrapbook
private $sbdesc;	// scrapbook description (used to self-identify)
private $image;		// image object
private $comment;	// comment for this image
private $groupname;
private $idgroup;
private $groupix;
private $imageix;
private $nGroup;
private $nImage;
private $bInitialized=False;

function __construct($tag,$parent,$initdata) {
	global $qqu;
	
	parent::__construct($tag,$parent,$initdata);
	
	// initdata is just the description
	$this->sb=new mod_scrapbook($initdata);
	$this->sbdesc=$initdata;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"img","image",$this->getParent()->htmlContainerId());
}

public function initialize($pgen) {
	// this function can be called more than once because of dependencies
	if ($this->bInitialized)
		return;
		
	// get the image and project number
	$extra=$pgen->getPageExtra();
	if ($extra == "")
		$extra=1;
	if (False === strpos($extra,"-"))
		$extra .= "-1";	
	list($groupspec,$imagespec)=explode("-",$extra);
	if (!isset($imagespec) || $imagespec == "" || (int)$imagespec <= 0) {
		$iximage=0;
	} else
		$iximage=(int)$imagespec-1;

	$groupdata=$this->sb->getGroups();
	$this->nGroup=sizeof($groupdata);
	if ($groupspec == "u" || $groupspec == "U") {
		$this->groupname="(unclassified)";
		$this->idgroup=Null;
		$this->groupix=-1;
	} else {			
		if (!isset($groupspec) || $groupspec == "" || (int)$groupspec <= 0) {
			// default group is 1
			$ixgroup=0;
		} else
			$ixgroup=(int)$groupspec-1;
		if ($ixgroup >= sizeof($groupdata))
			$ixgroup=0;
		list($this->idgroup,$this->groupname)=$groupdata[$ixgroup];
		$this->groupix=$ixgroup;
	}		
	$images=$this->sb->getGroupImages($this->idgroup);
	if ($iximage >= sizeof($images))
		$iximage=0;
	$this->imageix=$iximage;
	$this->nImage=sizeof($images);	
	list($this->image,$submittedby,$submitdate,$this->comment,$source)=$images[$iximage];
	// change name of image to match ours:
	$this->image->setLongName($this->longname);
	$this->bInitialized=True;
}


public function getGroupJournal($pgen) {
	if (!$this->bInitialized)
		$this->initialize($pgen);
	if ($this->idgroup == 0)
		return "";
	return $this->sb->getGroupJournal($this->idgroup);
}

// returns array of (imageix,nImage,groupix,nGroup).  groupix is -1 for unclassified images  
public function getCounts($pgen) {
	if (!$this->bInitialized)
		$this->initialize($pgen);
	return array($this->imageix,$this->nImage,$this->groupix,$this->nGroup);	
}

public function getScrapbook($pgen) {
	if (!$this->bInitialized)
		$this->initialize($pgen);
	return $this->sb;
}

public function description($pgen) {
	if (!$this->bInitialized)
		$this->initialize($pgen);
	return $this->comment;
}

public function setMaximumSize($pgen,$width,$height) {
	if (!$this->bInitialized)
		$this->initialize($pgen);
	$this->image->setMaximumSize($width,$height);
}

public function output($pgen,$brotherid) {
	echo $this->image->getOutput();
}


//////////////////////////////////// end of au /////////////////////////////////
} ?>
