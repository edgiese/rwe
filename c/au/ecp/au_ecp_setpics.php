<?php if (FILEGEN != 1) die;
///// Access Unit definition file for shopping browser checkbox 'set pictures' box
class au_ecp_setpics extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $prompt;

// usage: initdate=prompt
function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$this->prompt=strlen($initdata) > 0 ? $initdata : 'Pictures';
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'label:input','setpics',$this->getParent()->htmlContainerId());
}

public function declarestaticjs($js) {
	global $qq;
	
	$args=$js->seedArgs($this);
	$js->addjs('$','ecp::setpicssetup',$args);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqi;
	
	echo "<p><label{$qqi->idcstr($this->longname)}><input type=\"checkbox\" onclick=\"fg.ecp.updatepics(this)\"/>{$this->prompt}</label></p>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
