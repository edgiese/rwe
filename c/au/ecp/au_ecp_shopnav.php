<?php if (FILEGEN != 1) die;
///// Access Unit definition file for shopping browser
class au_ecp_shopnav extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $data;

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$idstore->declareHTMLid($this,False,'piclabel');
	$idstore->declareHTMLid($this,True,'pics');
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','shopnav',$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_piclabel','label','shopnav',$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_pics','input','shopnav',$this->getParent()->htmlContainerId());
}

public function declarestaticjs($js) {
	global $qq;
	
	$args=$js->seedArgs($this);
	$js->addjs('$','ecp::shopnavsetup',$args);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqi;
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	echo "<p>".link::anchorHTML("javascript:fg.ecp.search()",$tip="Search Products")."Search for Keywords</a></p>";
	echo "<p>".link::anchorHTML("javascript:fg.ecp.setmfrs()",$tip="Set Mfr")."Set Mfr</a></p>";
	echo "<p>".link::anchorHTML("javascript:fg.ecp.walkaisles()",$tip="Walk Aisles")."Walk the Aisles</a></p>";
	echo "<p>".link::anchorHTML("javascript:fg.ecp.setprodspec('all')",$tip="All Products")."All Products</a></p>";
	echo "<p><label{$qqi->idcstr($this->longname.'_piclabel')}><input{$qqi->idcstr($this->longname.'_pics')} type=\"checkbox\" onchange=\"fg.ecp.updatepics(this)\"/>Pictures</label></p>";
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
