<?php if (FILEGEN != 1) die;
///// Access Unit definition file for form & state testing
class au_div extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $usage;
private $authneeded;	// auth name
private $authdesc;		// auth description

// optional initstring format:  usage|authneeded/description
function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	if ($initdata == "")
		$initdata="div|";	// default usage/function for divs
	$init=explode('|',$initdata);
	if (sizeof($init) == 1)
		$init[1]='';	
	// styling function is initdata
	$this->usage=$init[0];
	if (False !== ($i=strpos($init[1],'/'))) {
		$this->authneeded=substr($init[1],0,$i);
		$this->authdesc=substr($init[1],$i+1);
	} else {
		$this->authneeded=$this->authdesc=$init[1];
	}	
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
	if ($this->authneeded != '')
		$idstore->registerAuthBool($this->authneeded,$this->authdesc,False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"div",$this->usage,$this->getParent()->htmlContainerId());
}

// practically the only point of this au:
public function canTakeChildren() {return True;}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqi, $qqs;
	
	if ($this->authneeded == '' || $qqs->checkAuth($this->authneeded)) {
		ob_start();
		for ($au=$this->getFirstChild(); $au != Null; $au=$au->getNextSibling())
			$au->output($pgen,$brotherid);
		$childoutput=ob_get_clean();	
	
		if ($childoutput != "")
			echo "<div{$qqi->idcstr($this->longname)}>$childoutput</div>";
	}			
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
