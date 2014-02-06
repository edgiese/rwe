<?php if (FILEGEN != 1) die;
class au_message extends au_base {
///////////////////////////////////// message au definition ////////////////////
private $message;

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$this->message=(int)$data;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	global $qqu;
	$stylegen->registerStyledId($this->longname,$qqu->getTextRegistrationTags(),$this->usage,$this->getParent()->htmlContainerId());
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqu,$qqi;

	$outputdata=message::getMessageInfo($this->message);
	$classes=array();
	if (0 <strlen($qqu->addClasses($classes,$this->classInit)))
		throw new exception("parsing error in class init for {$this->longname}");

	$cbdata=array($pgen,$brotherid);
	$callbacks=array(array(array($this,'creolecallback'),$cbdata));
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	
	echo $qqu->creole2html($outputdata,array(array(array())),array());
	echo "</div>";
}

///////////////////////////////////// end of class definition //////////////////
} ?>
