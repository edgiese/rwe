<?php if (FILEGEN != 1) die;
///// Access Unit definition file for multiple wiki-style entries
class au_miniwiki extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $wikiname;
private $hardcodedentry;	// used when we want to have an au show just a single essay in a miniwiki
private $usage;
private $adminusage;// usage for table of pages and form
private $editdesc;	// if not blank, this piece of text is editable, and this is the description for the menu
private $classDef;	// style[/2]:p,h1; ...
private $classInit;	// h1,p.style[/-+*]; ....

function __construct($tag,$parent,$initdata) {
	global $qqu;
	
	parent::__construct($tag,$parent,$initdata);
	$initdata=parent::getInit($initdata,'wikiname|usage="wiki"|adminusage="wikiadmin"|classdef=""|classinit=""');
	$this->usage=$initdata['usage'];
	$this->adminusage=$initdata['adminusage'];
	$this->wikiname=$initdata['wikiname'];
	if (False !== strpos($this->wikiname,'/')) {
		list ($this->wikiname,$this->hardcodedentry)=explode('/',$initdata['wikiname']);
	}
	$this->editdesc="{$this->wikiname} entry";
	$this->classDef=isset($initdata['classdef']) ? $initdata['classdef'] : '';
	$this->classInit=isset($initdata['classinit']) ? $initdata['classinit'] : '';
}
public function canTakeChildren() {return True;}

private function getTitle($textid) {
	global $qqu;
	
	$creole=$qqu->getEditText($textid);
	$clines=explode("\n",$creole);
	$title=False;
	foreach ($clines as $cline) {
		if (strlen($cline) >= 2 && $cline[0] == '=' && $cline[1] != '=') {
			$title=trim(trim($cline),'=');
			break;
		}
	}
	return $title;
}

private function makeform() {
	$form=new llform($this,'addpage');
	$form->addFieldset("newpage","New Page");

	$form->addControl("newpage",new ll_edit($form,"name",24,24,"Name of Page",False));
	$form->addControl("newpage",new ll_button($form,"Ok","Add Page"));
	return $form;
}


public function declareids($idstore,$state) {
	// always lock the main id.  it is used for editing if nothing else
	$idstore->declareHTMLid($this,True);
	// id of div containing table of wiki pages--used when no page specified in pageExtra
	$idstore->declareHTMLid($this,False,'pagetable');
	$this->makeform()->declareids($idstore);
	
	$idstore->registerAuthBool('addto_'.$this->wikiname,"Add Pages to {$this->wikiname}",False);
	$idstore->registerAuthBool('edit_'.$this->wikiname,"Edit {$this->wikiname} entry",False);
}

public function declarestyles($stylegen,$state) {
	global $qqu;
	$stylegen->registerStyledId($this->longname,$qqu->getTextRegistrationTags(),$this->usage,$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_pagetable','div:table,tr,td,th,a,form,input,fieldset,legend,blockquote,h1,h2',$this->adminusage,$this->getParent()->htmlContainerId());
	au_text::registerExtraTextClasses($stylegen,$this->longname,$this->classDef);
	$form=$this->makeform();
	$form->declarestyles($stylegen,$this->longname.'_pagetable');
}

public function initializeOwnerBar($pgen,$edit) {
	global $qqs;
	
	if ($pgen->getPageExtra() != '' && ($qqs->checkAuth('edit_'.$this->wikiname)))
		$edit->declareEditable($this->shortname,$this->longname,$this->editdesc);
}

public function initialize($pgen) {
	$wmod=new mod_miniwiki();
	// do wiki output if specified in page extra
	$pageextra=$pgen->getPageExtra();
	if ($pageextra != '' && $wmod->nameExists($pageextra)) {
		$textid=$wmod->getTextID($pageextra);
		$title=$this->getTitle($textid);
		if ($title !== False)
			$pgen->setTitleData($title,pagegen::TITLE_SUFFIX);
		return;
	}
}

public function getEditBlock($pageextra) {
	$wmod=new mod_miniwiki();
	$textid=$wmod->getTextID($pageextra);
	return new edit_blocktext($this,$textid);
}

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

public function processVars($originUri) {
	global $qq,$qqs,$qqi,$qqp;
	
	$vals=$this->makeform()->getValueArray();
	$name=$vals['name'];
	$wmod=new mod_miniwiki();
	if ($wmod->nameExists($name)) {
		return message::redirectString("=This Name Already Exists=\nYou can't create this page ($name) because it already exists as a page.  Try another name.\n\n[[$originUri|Go Back]]");
	}
	$wmod->createPage($name);
	
	// in many cases we could just return $originUri/$name, but when user types in non-existent page, $originUri is not the 
	// base page for the wiki.  So strip off any extra data for the originUri before appending the page:
	list($pagename,$pageextra)=$qqp->parseRequest($originUri);
	return $qqi->hrefprep("$pagename/$name");
}


// output with data specified--used by editing functions
public function outputWithData($pgen,$brotherid,$outputdata) {
	global $qqu,$qqi;

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
	global $qqi,$qqu,$qqs;

	$wmod=new mod_miniwiki();
	// do wiki output if specified in page extra
	$pageextra=$pgen->getPageExtra();
	if (isset($this->hardcodedentry))
		$pageextra=$this->hardcodedentry;
	if ($pageextra != '' && $wmod->nameExists($pageextra)) {
		$textid=$wmod->getTextID($pageextra);
		$outputdata=$qqu->getEditText($textid);
		$this->outputWithData($pgen,$brotherid,$outputdata);
		return;
	}
	$pagename=$pgen->getPageName();
	// do table and form output:
	echo "<div{$qqi->idcstr($this->longname)}><h1>Index of Pages in Collection '{$this->wikiname}'</h1>";
	$form=$this->makeForm();
	if ($pageextra != '') {
		echo "<blockquote>The page you specified, {$this->wikiname}/$pageextra, does not exist in this collection.</blockquote>";
		$form->setValue('name',$pageextra);
	}
	if ($qqs->checkAuth('addto_'.$this->wikiname))
		echo $form->getDumpStyleFormOutput('acting',0,'&nbsp;&nbsp;&nbsp;');
	echo '<h2>Available pages:</h2><table><tr><th>Page</th><th>Title</th></tr>';
	$rows=$wmod->getAllNames();
	foreach ($rows as $row) {
		extract($row);
		$link=new link('',"$pagename/$name",$name);
		$textid=$wmod->getTextID($name);
		$title=$this->getTitle($textid);
		if ($title === False)
			$title='(none)';
		echo "<tr><td>{$link->getOutput()}</td><td>$title</tr>";
	}
	echo "</table></div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
