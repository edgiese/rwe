<?php if (FILEGEN != 1) die;
class edit_blocktext implements edit_blockinterface {
///////////////////////////////////// edit module for wiki text ////////////////
private $textid;
private $au;

public function __construct($au,$textid) {
	$this->textid=$textid;
	$this->au=$au;
}

public function js($au,$aushort,$js,$args) {
	$js->addjs('$','edit::texteditsetup',array_merge(array('aushort'=>$aushort),$args));
}

public function getOriginalValue() {
	global $qqu;
	return $qqu->getEditText($this->textid);
}

// perform updates based on the form created with formOutput
public function makeChanges() {
	global $qqu, $qqs;
	$newtext=get_magic_quotes_gpc() ? stripslashes($_REQUEST['edittext']) : $_REQUEST['edittext'];
	$qqs->logEvent('edittext',(string)$this->textid); 
	$qqu->updateText($this->textid,$newtext);
}

// outputs the html for a form to edit the item
public function formOutput($au,$currentval) {
	echo "<textarea id=\"edit_text\" name=\"edittext\", rows=\"24\", cols=\"30\">{$currentval}</textarea>";
}

public function applyIncrementalChange($old,$delta) {
	list($skip,$delete,$middle)=explode('|',$delta,3);
	if ($skip > 0) {
		$first=substr($old,0,$skip);
		$last=substr($old,$skip);
	} else {
		$first='';
		$last=$old;
	}
	if ($delete == -1 || $delete >= strlen($last))
		$last='';
	else if ($delete > 0)
		$last=substr($last,$delete);
	return "$first$middle$last";	
}

public function applyAbsoluteChange($new) {
	return $new;
}

public function isStandardOutput() {return True;}

public function incrementalOutput() {throw new exception("logic error:  this routine should not be called");}

public function absoluteOutput() {throw new exception("logic error:  this routine should not be called");}

///////////////////////////////////// end of interface definition //////////////
} ?>
