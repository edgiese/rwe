<?php if (FILEGEN != 1) die;
///// Access Unit definition file for product description
class au_ecp_proddesc extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $tid;	// creole template text id
private $usage;
private $classDef;	// style[/2]:p,h1; ...
private $classInit;	// h1,p.style[/-+*]; ....
private $moreopts;	// additional options (pmod id)

function __construct($tag,$parent,$initdata) {
	global $qqu;
		
	parent::__construct($tag,$parent,$initdata);
	$initdata=parent::getInit($initdata,'template|usage|moreopts=""|classdef=""|classinit=""');
	$this->moreopts=$initdata['moreopts'];
	$this->tid=$qqu->getTextIndex($initdata['template']);
	$this->usage=$initdata['usage'];
	$this->classDef=$initdata['classdef'];
	$this->classInit=$initdata['classinit'];
	
	if ($this->tid <= 0)
		throw new exception("illegal or nonexistent template in prodinfo initstring: {$initdata['template']}");	
	if (!$qqu->isCreole($this->tid))
		throw new exception("expected creole text for prod info template, but didn't get it.  text id=$id");
}

// one child is allowed:  the overlay
public function canTakeChildren() {return True;}


public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$idstore->registerAuthBool('editproddesc',"Edit Product Entries",False);
}

public function declarestyles($stylegen,$state) {
	global $qqu;
	$stylegen->registerStyledId($this->longname,$qqu->getTextRegistrationTags(),$this->usage,$this->getParent()->htmlContainerId());
	au_text::registerExtraTextClasses($stylegen,$this->longname,$this->classDef);
}

public function declarestaticjs($js) {
	global $qq,$qqp,$qqi;
	
	// prod desc may not be interacting with javascript.
	if (0 != strlen($this->moreopts)) {
		$options=$qqp->getPMod('order');
		$args=$js->seedArgs($this);
		if (Null != ($overlay=$this->getFirstChild())) {
			$args['overlay']=$overlay->getLongName();
			$args['useoverlay']='true';
		} else {
			// lame long-to-short conversion in js required a valid long name even if we don't have one, so use this au's name
			$args['overlay']=$this->longname;
			$args['useoverlay']='false';
		}
		$options->addJSParams($args);	
		$js->addjs('$','ecp::proddescsetup',$args);
	}	
}

public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi,$qqu,$qqp;
	
	if (!isset($_REQUEST['id']))
		throw new exception("required id not set");
	$prodid=$_REQUEST['id'];
	
	$pm=new mod_prodmain;
	if (False === ($info=$pm->getProductInfo($prodid)))
		throw new exception("information for id $prodid not found");
	$output=$this->getOutputString($prodid);
	if ($info['prodhold']) {
		$notice='Product on hold--not for sale';
	} else if ($info['discontinued']) {
		$notice='This product has been discontinued';
	} else {
		$notice=ec_prodorder::getRestrictionString($prodid);
		$separator=strlen($notice) ? ' &bull; ' : '';	
		if ($info['onhand'] <= 0)
			$notice .= "{$separator}OUT OF STOCK--ON ORDER";
		else if ($info['onhand'] < $info['minqty'])
			$notice .= "{$separator}STOCK LOW--ON ORDER";
	}		
	if ($notice == '')
		$notice='&nbsp;';
	$args=array(
		'desc'=>addslashes($output),
		'pricestring'=>'Price ',
		'price'=>(string)sprintf("$%01.2f",$info['baseprice']/100),
		'notice'=>$notice,
		'incart'=>$pm->isItemInCart($prodid) ? 'true' : 'false',
		'id'=>$prodid
	);
	echo $js->snippetToCode('ecp::setdesc',$args,$page);

	if ($qqs->checkAuth('editproddesc')) {
		$options=$qqp->getPMod('order');
		$args=array('id'=>$prodid);
		$options->addJSParams($args,$page);
		echo $js->snippetToCode('ecp::seteditlabel',$args,$page);
	}
}

public function getOutputString($prodid) {
	global $qqu,$qqi;
	
	$template=$qqu->getEditText($this->tid);
	$classes=array();
	if (0 <strlen($message=$qqu->addClasses(&$classes,$this->classInit)))
		throw new exception("parsing error in class init for {$this->longname}: $message");
		
	// perform product based substitutions
	$pi=new mod_prodinfo;
	$info=$pi->getProdInfo($prodid);
	if ($info !== False) {
		$template=str_replace('<<mfr>>',$info['mfr'],$template);
		$template=str_replace('<<title>>',$info['title'],$template);
		$template=str_replace('<<desc>>',$info['desc'],$template);
		$template=str_replace('<<imageid>>',$info['imageid'],$template);
		$retval=$qqu->creole2html($template,array(),$classes);
	} else {
		$retval="No Product Info Available.";
	}
	return $retval;		
}

public function output($pgen,$brotherid) {
	global $qqi;

	if (False === ($controller=$pgen->findFlaggedAU('prodnav')))
		throw new exception('Product description au needs a product navigator to specify a product');
	$prodid=$controller->getProdId($pgen);
		
	echo "<div{$qqi->idcstr($this->longname)}>{$this->getOutputString($prodid)}</div>";
	
	// output overlay
	if (Null != ($overlay=$this->getFirstChild()))	
		$overlay->output($pgen,$brotherid);
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
