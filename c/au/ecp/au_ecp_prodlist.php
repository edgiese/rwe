<?php if (FILEGEN != 1) die;
///// Access Unit definition file for shopping list control
class au_ecp_prodlist extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $action;
private $data;

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);

	$this->action=$state;
	$this->transaction=(int)$data;
}

// one child is allowed:  the overlay
public function canTakeChildren() {return True;}

public function declareids($idstore,$state) {
	// main div
	$idstore->declareHTMLid($this);
	// containing html element--either table or p (not formatted directly, but used by js)
	$idstore->declareHTMLid($this,True,'container');
	$idstore->declareHTMLid($this,True,'extra');
	$idstore->declareHTMLid($this,True,'title');
	$idstore->registerAuthBool('viewitemsonhold',"View Items on Hold",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div:p,img,th,td,a','prodedit',$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_extra','h3','prodedit',$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_title','h2','prodedit',$this->getParent()->htmlContainerId());
}

public function declarestaticjs($js) {
	global $qq;
	
	$args=$js->seedArgs($this);
	if (Null != ($overlay=$this->getFirstChild())) {
		$args['overlay']=$overlay->getLongName();
		$args['useoverlay']='true';
	} else {
		// lame long-to-short conversion in js required a valid long name even if we don't have one, so use this au's name
		$args['overlay']=$this->longname;
		$args['useoverlay']='false';
	}	
	$js->addjs('$','ecp::prodlistsetup',$args);
}

///////////// this au provides description and id to other aus
public function checkFlag($flag) {
	return ($flag == 'prodnav');
}

// returns prodid (used for other aus) 
public function getProdId($pgen) {
	$prodid=False;
	if ($pgen->getPageExtra() != '' && substr($pgen->getPageExtra(),0,4) == 'upc_') {
		$extra=substr($pgen->getPageExtra(),4);
		$pm=new mod_prodmain;
		$prodid=$pm->idFromBarcode($extra);
	}
	return $prodid === False ? -1 : $prodid;	
}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi,$qqu;
	
	$action=$this->action;
	
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	list($prodids,$description)=au_ecp_render1shopnav::readExtra($_REQUEST['extra'],$pm,$pi);
	
	$args=$js->seedArgs($this);
	
	// code for images:
	echo $js->snippetToCode('ecp::clearprodlist',array_merge($args,array('description'=>addslashes($description))),$page);
	
	$itemdata=array();
	foreach ($prodids as $id) {
		$maininfo=$pm->getProductInfo($id);
		if ($maininfo['prodhold']) {
			if (!$qqs->checkAuth('viewitemsonhold'))
				continue;
			$itemdata['onhold']='1';	
		} else
			$itemdata['onhold']='0';	
		$info=$pi->getProdInfo($id);
		if ($info !== False && $info['imageid'] != 0) {
			$img=new image($info['imageid']);
			$img->setMaximumSize(80,160);
			list($itemdata['img'],$itemdata['width'],$itemdata['height'])=$img->getInfo();
			$itemdata['img']=addslashes($itemdata['img']);
		} else {
			$itemdata['img']='(no image)';
			$itemdata['width']=$itemdata['height']=0;
		}	
		$itemdata['id']=(string)$id;
		$itemdata['mfr']=addslashes($info['mfr']);
		$itemdata['incart']=$pm->isItemInCart($id) ? '1' : '0';
		
		$itemdata['title']=addslashes($qqu->creolelite2html($info['title']));
		$itemdata['price']=$maininfo['baseprice'];
		echo $js->snippetToCode('ecp::addprodlisthtml',array_merge($args,$itemdata),$page);
	}		
}


// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;

	// output containers that will be manipulated for content later:	
	echo "<h2{$qqi->idcstr($this->longname.'_title')}></h2>";
	echo "<div{$qqi->idcstr($this->longname)}><h3{$qqi->idcstr($this->longname.'_extra')}>{$pgen->getPageExtra()}</h3></div>";

	// output overlay
	if (Null != ($overlay=$this->getFirstChild()))	
		$overlay->output($pgen,$brotherid);
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
