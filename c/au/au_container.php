<?php if (FILEGEN != 1) die;
///// Access Unit definition
class au_container extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $function;
private $template;
private $html;

function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	$init=explode("|",$initdata);
	if (sizeof($init) != 3)
		throw new exception("bad init syntax for container.  should be 'html|function|template' but was $initdata");
	list($this->html,$this->function,$this->template)=$init;
	if ($this->function == "")
		$this->function="container";	// default usage/function for container
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,$this->html,$this->function,$this->getParent()->htmlContainerId());
}

public function canTakeChildren() {return True;}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqi;
	
	$childoutput=array();
	$childkey=array();
	$ix=0;
	for ($au=$this->getFirstChild(); $au != Null; $au=$au->getNextSibling()) {
		ob_start();
		$au->output($pgen,"0");
		$childoutput[]=ob_get_clean();
		++$ix;
		$childkey[]="\\$ix";
	}		
	$output=$this->template;
	$idstring=$qqi->idcstr($this->longname);
	$output=str_replace("\\i",$idstring,$output);
	$output=str_replace($childkey,$childoutput,$output);
	echo $output;	
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
