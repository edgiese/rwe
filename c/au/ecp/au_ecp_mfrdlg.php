<?php if (FILEGEN != 1) die;
///// Access Unit definition file for 'set manufacturer' dialog box
class au_ecp_mfrdlg extends au_base {
////////////////////////////////////////////////////////////////////////////////
function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
}

// note:  these forms are used only to simplify output.  no forms submitted to this au.
private function makeform() {
	$form=new llform($this,'dlg');
	$form->addFieldset('d','');
	$form->addControl('d',new ll_listbox($form,'mfrs','Manufacturers:'));
	$form->addControl('d',new ll_button($form,'ok','Ok','button'));
	$form->addControl('d',new ll_button($form,'cancel','Cancel','button'));
	return $form;
}

public function declareids($idstore,$state) {
	// containing div:
	$idstore->declareHTMLid($this);
	// force lock on all ids:
	$this->makeform()->declareids($idstore,True);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','dialog',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname);
}

public function declarestaticjs($js) {
	global $qq;
	
	$args=$js->seedArgs($this);
	$js->addjs('$','ecp::setmfrsetup',$args);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;

	$pi=new mod_prodinfo;
	
	$form=$this->makeform();
	$mfrsform->getControl('mfrs')->addOptions($pi->getMfrList());
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	echo $mfrsform->getFormattedOutput('<<!mfrs>><br /><<mfrs>><br /><<ok>>&nbsp&nbsp&nbsp<<cancel>>');
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
