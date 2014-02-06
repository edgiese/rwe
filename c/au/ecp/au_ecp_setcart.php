<?php if (FILEGEN != 1) die;
///// Access Unit definition file for shopping browser checkbox 'add to cart' box
class au_ecp_setcart extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $prompt;

// usage: initdate=prompt
function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$this->prompt=strlen($initdata) > 0 ? $initdata : 'Put in Cart';
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'label:input','setcart',$this->getParent()->htmlContainerId());
}

public function declarestaticjs($js) {
	global $qq;
	
	$args=$js->seedArgs($this);
	$js->addjs('$','ecp::setcartsetup',$args);
}

public function processAjax($js,$originUri,$page) {
	
	if (!isset($_REQUEST['id']) || !isset($_REQUEST['incart']))
		throw new exception("required id and/or incart status not set");
	$prodid=$_REQUEST['id'];
	$incart=$_REQUEST['incart'];
	$pm=new mod_prodmain;
	if ($incart && !$pm->isItemInCart($prodid))
		$pm->addToCart($prodid);
	else if (!$incart && $pm->isItemInCart($prodid)) 	
		$pm->removeFromCart($prodid);
}	


public function output($pgen,$brotherid) {
	global $qqi;
	
	echo "<p><label{$qqi->idcstr($this->longname)}><input type=\"checkbox\" onclick=\"fg.ecp.updatecart(this)\"/>{$this->prompt}</label></p>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
