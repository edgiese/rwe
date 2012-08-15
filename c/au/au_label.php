<?php if (FILEGEN != 1) die;
///// Access Unit definition file for text labels
class au_label extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $textid;
private $type;
private $class;
private $usage;
private $link=False;

function __construct($tag,$parent,$initdata) {
	global $qqu;
	
	parent::__construct($tag,$parent,$initdata);
	// because labels are preprocessed by pagedef, initdata will always be an array
	$this->type=$initdata['type'];
	$this->textid=$initdata['id'];
	$this->usage=$initdata['usage'];
	if ($this->type == "a") {
		$text=$qqu->getDisplayText($this->textid);
		$this->link=new link($this->longname,$initdata['href'],$text,$initdata['tip']);
	}
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	if ($this->type == "a")
		$this->link->registerStyled($stylegen,$this->usage,$this->getParent()->htmlContainerId());
	else
		$stylegen->registerStyledId($this->longname,$this->type,$this->usage,$this->getParent()->htmlContainerId());
}

// make this text editable
public function declareOwnerBar($ownerbar) {
	global $qqs;
	if ($qqs->checkAuthorization("clickedit")) {
		$ownerbar->declareClickEdit($this->shortname,$this->longname,"");
	}
}


// register at output time
public function output($pgen,$brotherid) {
	global $qqu,$qqi;

	if (False === $this->link) {
		$idAndClass=$qqi->idcstr($this->longname);
		$text=$qqu->getDisplayText($this->textid);
		echo "<{$this->type}$idAndClass>$text</{$this->type}>";
	} else {
		echo $this->link->getOutput();
	}	
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
