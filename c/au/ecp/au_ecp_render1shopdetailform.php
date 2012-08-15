<?php if (FILEGEN != 1) die;
///// Access Unit definition file for product description in render 1 shopping logic
class au_ecp_render1shopdetailform extends au_base {
////////////////////////////////////////////////////////////////////////////////

private $checkoutpage;	//specified page to jump to to 'check out'
private $shoppage;		//specified page to jump to to 'continue shopping'

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	
	$initdata=parent::getInit($initdata,'shoppage|checkoutpage');
	$this->checkoutpage=$initdata['checkoutpage'];	
	$this->shoppage=$initdata['shoppage'];	
}


// returns array of (prodid,sendingextra)
private function analyzeExtra($extra) {
	if (False !== ($i=strpos($extra,'/'))) {
		$barcode=substr($extra,0,$i);
		$shopextra=substr($extra,$i+1);
	} else {
		$barcode=$extra;
		$shopextra='';
	}
	$pm=new mod_prodmain;
	$prodid=$pm->idFromBarcode($barcode);
	return array($prodid,$shopextra);
}

// identify utility
public function checkFlag($flag) {
	return ($flag == 'prodnav');
}

// returns prodid (used for other aus) 
public function getProdId($pgen) {
	list($prodid,$shopextra)=$this->analyzeExtra($pgen->getPageExtra());
	return $prodid;	
}

private function makeform() {
	$form=new llform($this,'form');
	
	$form->addFieldset('d','Interested in this Item?');
	$form->addControl('d',new ll_checkbox($form,'incart','Place Item in Cart'));
	$form->addControl('d',new ll_button($form,'checkout','Place an Order'));
	$form->addControl('d',new ll_button($form,'shopmore','Continue Shopping'));
	$form->setExtraValue('shopextra',0);
	$form->setExtraValue('prodid',0);
	return $form;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$this->makeform()->declareids($idstore);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','prodedit',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname);
}


public function processVars($originUri) {
	global $qq,$qqs,$qqi;

	// can't purchase anything without cookies
	if (!$qqs->isStated())
		return message::redirectString(message::MISSINGCOOKIE);
		
	$form=$this->makeForm();
	$vals=$form->getValueArray();

	$prodid=$form->getValue('prodid');
	$shopextra=$form->getValue('shopextra');
	
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;

	$retval=$originUri;
		
	if ($form->wasPressed('checkout')) {
		$retval=$qqi->hrefPrep($this->checkoutpage);
		// even if item not checked here, act as though it were:
		$pm->addToCart($prodid);
	} else if ($form->wasPressed('shopmore')) {
		$retval=$qqi->hrefPrep($this->shoppage)."/$shopextra";
		if ($vals['incart'])
			$pm->addToCart($prodid);
		else
			$pm->removeFromCart($prodid);
	}
		
	return $retval;
}

public function initialize($pgen) {
	global $qqu;
	list($prodid,$shopextra)=$this->analyzeExtra($pgen->getPageExtra());
	$pi=new mod_prodinfo;
	$info=$pi->getProdInfo($prodid);
	$title=$info['title'];
	$title=str_replace('\\\\',' ',$title);
	$title=str_replace('~ ',' ',$title);
	$pgen->setTitleData('Item Detail',pagegen::TITLE_PREFIX);
	$pgen->setTitleData($title,pagegen::TITLE_SUFFIX);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
	list($prodid,$shopextra)=$this->analyzeExtra($pgen->getPageExtra());
	
	$pm=new mod_prodmain;
	
	$form=$this->makeForm();
	$form->useGetMethod();
	$form->setExtraValue('shopextra',$shopextra);
	$form->setExtraValue('prodid',$prodid);
	$form->getControl('incart')->setValue($pm->isItemInCart($prodid));
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	echo $form->getDumpStyleFormOutput('',0,'&nbsp;&nbsp;');
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
