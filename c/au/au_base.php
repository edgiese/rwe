<?php if (FILEGEN != 1) die;
class au_base {
// base class for all access units (au) ////////////////////////////////////////

protected $tag;
protected $longname;	// unique identifier for this au:  combination of human readable string and db id
protected $shortname;	// unique identifier for this au in pagedef's namespace.  used for forms and possibly other things
private $firstChild;	// au of first child in list or Null if none
private $nextSibling;	// au of next sibling or Null if none
private $parent;		// au of parent or Null if top level
protected $bAutoLock;

function __construct($tag,$parent,$initdata) {
	global $qqp;
	
	$this->shortname=$qqp->getAUShortName(get_class($this),$tag,$initdata);
	$this->tag=$tag;		
	$this->nChildren=0;
	$this->firstChild=Null;
	$this->nextSibling=Null;
	$this->parent=$parent;
	// all au classes start with au_.  strip this off for the long name
	$this->longname=substr(get_class($this),3);
	if ($tag != "")
		$this->longname .= "_".$tag;
	// parent will only be null for the body anchor.  for all others, we're doing this:
	if ($parent != Null) {
		if (!$parent->canTakeChildren())
			throw new exception ("Trying to add children to {$this->parent->longname} which cannot take them");
		$parent->appendChild($this);	
	}
}

// create and validate an initdata array based on a template
static function getInit($initd,$template) {
	$idbyname=is_array($initd);
	$templatestrings=explode('|',$template);
	// build template items.  each one is array(name, bRequired, bDefNull, defValue)
	$templateitems=array();
	$minsize=0;
	foreach ($templatestrings as $ix=>$ts) {
		$ts=trim($ts);
		if ($ts[0] == '*') {
			if ($minsize > 0)
				throw new exception("illegal template string -- only one * to specify min size allowed");
			$minsize=$ix;
			$ts=substr($ts,1);	
		}
		$bDefNull=False;
		$defValue='';
		if (False !== ($i=strpos($ts,'='))) {
			$name=substr($ts,0,$i);
			$bRequired=False;
			if ($i==strlen($ts)-1) {
				$bDefNull=True;
			} else {
				$bDefNull=False;
				$defValue=trim(substr($ts,$i+1),'"');
			}
		} else {
			$name=$ts;
			$bRequired=True;
		}
		$index= ($idbyname) ? $name : $ix;	
		$templateitems[$index]=array($name,$bRequired,$bDefNull,$defValue);
	}
	
	// now loop through input data array and build/validate result
	// approach depends on what kind of initdata we have
	$procinitd=array();
	if ($idbyname) {
		// initd is an array much like the output array.  It needs to be verified
		foreach ($initd as $name=>$item) {
			if (!isset($templateitems[$name]))
				throw new exception("initd had illegal type $name.  template: $template");
			$procinitd[$name]=$item;	
		}
		// now set default values where necessary
		foreach ($templateitems as $ti) {
			list($name,$bRequired,$bDefNull,$defValue)=$ti;
			if (!isset($procinitd[$name])) {
				if ($bRequired) {
					infolog_dump("errortrap","procinitd",$procinitd);
					throw new exception("initd missing required value.  type $name template: $template");
				}	
				if (!$bDefNull)
					$procinitd[$name]=$defValue;	
			}
		} 
	} else {
		// initd is a string separated by |.  First, make certain that minimum is present.
		$initditems=explode('|',$initd,sizeof($templateitems));
		if (sizeof($initditems) < $minsize)
			throw new exception("initstring $initd needed $minsize fields but did not have them.  template: $template");
		foreach ($initditems as $ix=>$item) {
			list($name,$bRequired,$bDefNull,$defValue)=$templateitems[$ix];
			$procinitd[$name]=$item;
		}
		// if there are any items that are not set, set them to defaults
		for ($ix=sizeof($initditems); $ix < sizeof($templateitems); ++$ix) {
			list($name,$bRequired,$bDefNull,$defValue)=$templateitems[$ix];
			if ($bRequired)
				throw new exception("missing required value for $name in initstring $initd template: $template");
			if (!$bDefNull) {
				$procinitd[$name]=$defValue;
			}		
		}			
	}
	return $procinitd;	
}

public function setAutoLock($bAutoLock) {$this->bAutoLock=$bAutoLock;}

public function getTag() {return $this->tag;}
public function getLongName() {return $this->longname;}
public function getShortName() {return $this->shortname;}
public function isGenerator() {return False;}

public function getNextSibling() {return $this->nextSibling;}
public function appendChild($child) {
	if (!$this->canTakeChildren())
		throw new exception("cannot append a child to this au: {$this->longname}");
	if ($this->firstChild == Null)
		$this->firstChild=$child;
	else
		$this->firstChild->appendSibling($child);
}
public function appendSibling($newsibling) {
	if ($this->nextSibling == Null) {
		$this->nextSibling=$newsibling;
		return;
	}
	// walk to end of list
	for ($sibling=$this; $sibling->getNextSibling() != Null; $sibling=$sibling->getNextSibling())
			;
	$sibling->appendSibling($newsibling);	
}
public function getParent() {return $this->parent;}
public function getFirstChild() {return $this->firstChild;}
public function getNextAU() {
	if ($this->firstChild != Null)
		return $this->firstChild;
	if ($this->nextSibling != Null)
		return $this->nextSibling;
	// climb down the tree until there's a parent with a sibling	
	$parent=$this->parent;	
	while ($parent != Null) {
		if ($parent->getNextSibling() != Null)
			return $parent->getNextSibling();
		$parent=$parent->parent;	
	}
	return Null;	
}
public function canTakeChildren() {return False;}
public function htmlContainerId() {
	if (!$this->canTakeChildren())
		throw new exception("no container html is possible--cannot take children:  $this->longname");
	// default is the name of the au.
	return $this->longname;	
}

// used to find controller au's, etc.
public function checkFlag($flag) {return False;}

// called to get the static javascript for an au.  also an opportunity to register for
// dynamic js, which is done at initialize or during output
public function declarestaticjs($js) {}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {}

// called when a form returns with variables or a link is called with parameters
public function processVars($originUri) {}

// called when a feed is requested from feed.php
public function processFeed($subtype) {}

// called after the tree is filled out.  a chance to verify relationships, etc.
public function initialize($pgen) {}

// called after initialize to set items into ownerbar menu
public function initializeOwnerBar($pgen,$edit) {}

// returns the edit block object or False if not editable
public function getEditBlock($pageextra) {return False;}

// called after initialization if ids need to be declared
public function declareids($idstore,$state) {}

public function declarestyles($stylegen,$state) {}

// set format for styles that are hardcoded
public function hardcodestyles($stylegen,$state) {}

// does output of this plus all children
public function output($pgen,$brotherid) {}

// does output of this plus all children with special edit data as source
public function outputWithData($pgen,$brotherid,$outputdata) {
	throw new exception("this au is not capable of edit output: {$this->longname}");
}

// returns the head of a fully formed au tree
static function treeFromRecipe($recipe,$render,$formclass='',$formtag='',$formstate='',$formdata='') {
	global $qqu;

//	infolog_dump("debug","recipe",$recipe);
	$accumchild=0;	
	foreach ($recipe as $ri) {
		if (sizeof($ri) != 6)
			continue;		// empty list item
		list($childlevel,$classname,$tag,$initstring,$rendermask,$bAutoLock)=$ri;
//		infolog("debug","$childlevel,$classname,$tag,$initstring,$rendermask,".($bAutoLock ? 'True' : 'False'));
		// skip aus that do not match the render mask.  aulist must be carefully constructed to
		// make certain that these skips do not lead to other problems
		if (($rendermask & $render) == 0) {
			// skipped items still can change child levels, and these accrue
			$accumchild += $childlevel;
			continue;
		}
		$childlevel += $accumchild;
		$accumchild=0;			
		if (!class_exists($classname))
			throw new exception("illegal class name in recipe:  $classname");
		if ($childlevel == 2) {
			// this is a technically illegal value that marks the top of the recipe.  only a "body" AU is allowed here
			$topau=$newau=new $classname("body",Null,"");
			if (!($newau instanceof au_body))
				throw new exception("top au must be a body au instead of $classname");
		} else {
			if (!isset($lastau))
				throw new exception("illegal top au:  must be body type, child level 2");
			if ($childlevel > 1)
				throw new exception("illegal child level increase: $childlevel");
			$parent=$lastau->getParent();	
			if ($childlevel < 0) {
				for ($i=$childlevel; $i < 0; ++$i) {
					$parent=$parent->getParent();
					if ($parent == Null)
						throw new exception("unbalanced childlevels -- bumped the top");
				}	
			} else if ($childlevel == 1) {
				$parent=$lastau;
			}
			
			// create the new au
			if ($formclass == $classname && $formtag == $tag) {
				$state=$formstate;
				$data=$formdata;
			} else {
				$state="";
				$data=0;
			}
			$newau=new $classname($tag,$parent,$initstring,$state,$data);
			$newau->setAutoLock($bAutoLock);
		}				
		$lastau=$newau;
	}
	if (!isset($topau))
		throw new exception("logic error");
	return $topau;	
}

///////////////////////////////////////////////////////////////////////////// end of definition
} ?>
