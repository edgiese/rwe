<?php if (FILEGEN != 1) die;
///// Access Unit definition file for an overlay that's turned off and on
class au_overlay extends au_base {
////////////////////////////////////////////////////////////////////////////////

private $usage;
private $routinename;

function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	$initdata=parent::getInit($initdata,'routinename|usage="overlay"');
	$this->routinename=$initdata['routinename'];
	$this->usage = $initdata['usage'];
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"div",$this->usage,$this->getParent()->htmlContainerId());
}

public function declarestaticjs($js) {
	global $qq;
	
	$args=$js->seedArgs($this);
	$args['routinename']=$this->routinename;
	$js->addjs('$','setoverlayroutine',$args);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqi;
	echo "<div{$qqi->idcstr($this->longname)}></div>";	
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
