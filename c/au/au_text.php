<?php if (FILEGEN != 1) die;
///// Access Unit definition file for wiki-style text display
class au_text extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $textid;
private $usage;
private $editdesc;	// if not blank, this piece of text is editable, and this is the description for the menu
private $classDef;	// style[/2]:p,h1; ...
private $classInit;	// h1,p.style[/-+*]; ....

function __construct($tag,$parent,$initdata) {
	global $qqu;
		
	parent::__construct($tag,$parent,$initdata);
	// these objects are preprocessed by pagedef, so they are always initialized with an array
	$this->usage=$initdata['usage'];
	if (False === ($i=strpos($initdata['textinfo'],'/'))) {
		$this->editdesc='';
		$name=$initdata['textinfo'];
	} else {
		$name=substr($initdata['textinfo'],0,$i);
		$this->editdesc=substr($initdata['textinfo'],$i+1);
	}
	$id=$qqu->getTextIndex($name);
	$this->classDef=isset($initdata['classdef']) ? $initdata['classdef'] : '';
	$this->classInit=isset($initdata['classinit']) ? $initdata['classinit'] : '';
	
	if ($id <= 0)
		throw new exception("illegal value for id in au_text_$tag initstring: $id");	
	// register a short name for the text output for formatting
	// long name is just au name.  This is an html generator and has no real formatting of its own.
	// the registration allows items contained in this string to be referenced in formatting
	
	// get the text object or set the initial value from the initdata
	if (!$qqu->isCreole($id))
		throw new exception("expected creole text for au_text, but didn't get it.  text id=$id");
	$this->textid=$id;	
}

public function canTakeChildren() {return True;}

public function declareids($idstore,$state) {
	// always lock the main id.  it is used for editing if nothing else
	$idstore->declareHTMLid($this,True);
	if ($this->editdesc != '') {
		$idstore->registerAuthBool('edit_'.$this->longname,'Edit '.$this->editdesc,False);
		$idstore->registerAuthBool('editall','Edit anything',False);
	}
}

// this function is set up so it can be used from other aus using similarly formatted extra text styles
static function registerExtraTextClasses($stylegen,$longname,$classDef) {
	if (strlen($classDef) == 0)
		return;
	$classes=explode(';',$classDef);
	foreach ($classes as $cd) {
		if (False === ($i=strpos($cd,':')))
			throw new exception("class definition in $longname is missing :");
		$fullclassnames=explode(',',substr($cd,0,$i));
		$htmls=explode(',',substr($cd,$i+1));
		foreach ($fullclassnames as $fcn) {	
			if (False !== ($j=strpos($fcn,'/'))) {
				$classname=substr($fcn,0,$j);
				$cycle=substr($fcn,$j+1);
				if (!is_numeric($cycle))
					throw new exception("class definition in $longname has non-numeric cycle in class $classname");
				$cycle=(int)$cycle;	
			} else {
				// if no slash, assume that the cycle is 0
				$cycle=0;
				$classname=$fcn;
			}
			foreach ($htmls as $html) {
				$stylegen->registerClass($classname,$html,$cycle);
			} // foreach html tag
		} // for each full class name			
	} // for each class definition
}

public function declarestyles($stylegen,$state) {
	global $qqu;
	$stylegen->registerStyledId($this->longname,$qqu->getTextRegistrationTags(),$this->usage,$this->getParent()->htmlContainerId());
	self::registerExtraTextClasses($stylegen,$this->longname,$this->classDef);
}

public function initializeOwnerBar($pgen,$edit) {
	global $qqs;
	
	if ($this->editdesc != '' && ($qqs->checkAuth('edit_'.$this->longname) || $qqs->checkAuth('editall')))
		$edit->declareEditable($this->shortname,$this->longname,$this->editdesc);
}

public function getEditBlock($pageextra) {return new edit_blocktext($this,$this->textid);}

public function creolecallback($name,$argdata,$cbdata) {
	if ($name != 'tag')
		return False;
	if (!isset($argdata['tag']))
		return '{[au error: tag not specified]}';

	for ($child=$this->getFirstChild(); $child != Null; $child=$child->getNextSibling()) {
		if ($child->getTag() == $argdata['tag']) {
			// found a match--output it
			ob_start();
			$child->output($cbdata[0],$cbdata[1]);
			$output=ob_get_clean();	
		}
	}
	if (!isset($output))
		$output="{[au error: tag {$argdata['tag']} not found]}";
	return $output;	
}

// output with data specified--used by editing functions
public function outputWithData($pgen,$brotherid,$outputdata) {
	global $qqu,$qqi;

	for ($child=$this->getFirstChild(); $child != Null; $child=$child->getNextSibling()) {
		if ($child instanceof au_texttransform) {
			$outputdata=$child->transform($pgen,$outputdata);
		}
	}
	$classes=array();
	if (0 <strlen($message=$qqu->addClasses($classes,$this->classInit)))
		throw new exception("parsing error in class init for {$this->longname}: $message");

	$cbdata=array($pgen,$brotherid);
	$callbacks=array(array(array($this,'creolecallback'),$cbdata));
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	
	echo $qqu->creole2html($outputdata,$callbacks,$classes);
	echo "</div>";
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqu;
	
	$outputdata=$qqu->getEditText($this->textid);
	$this->outputWithData($pgen,$brotherid,$outputdata);
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
