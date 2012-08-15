<?php if (FILEGEN != 1) die;
///// Access Unit definition file for product description navigation block
class au_ecp_prodeditnav extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $data;

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);

	$this->state=$state;
	$this->transaction=(int)$data;
}

public function checkFlag($flag) {
	return ($flag == 'prodnav');
}

public function getProdId($pgen) {
	return (int)$pgen->getPageExtra();
}

/*
const STATE_NONE = '';
*/

private function makeform() {
	$form=new llform($this,'form');
	
	$form->addFieldset('d','Manufacturer');
	$form->addControl('d',new ll_edit($form,'newupc',20,40,''));
	$form->addControl('d',new ll_button($form,'prevnohold','<< No Hold'));
	$form->addControl('d',new ll_button($form,'prevhold','<< Hold'));
	$form->addControl('d',new ll_button($form,'prevnote','<< Note'));
	$form->addControl('d',new ll_checkbox($form,'hold','Hold item from Sale'));
	$form->addControl('d',new ll_edit($form,'notes',40,60,'Notes'));
	$form->addControl('d',new ll_button($form,'nextnohold','No Hold >>'));
	$form->addControl('d',new ll_button($form,'nexthold','Hold >>'));
	$form->addControl('d',new ll_button($form,'nextnote','Note >>'));
	$form->setExtraValue('prodid',0);
	
	return $form;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$this->makeform()->declareids($idstore);
	
	$idstore->registerAuthPage('view product edit page',False);
	$idstore->registerAuthBool('editproddesc',"Edit Product Entries",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','prodedit',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname);
}

public function declarestaticjs($js) {
	global $qq,$qqp,$qqi;

	$args=$js->seedArgs($this);
	$js->addjs('$','ecp::prodeditnavsetup',$args);
}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi,$qqu;
	
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	if (False !== ($prodid=$pm->idFromBarcode($_REQUEST['upc']))) {
		$i=strrpos($originUri,'/');
		$noextra=substr($originUri,0,$i+1);
		$maybeisextra=substr($originUri,$i+1);
		// assume last slash is numeric if it's an id.  this could fail for non-numeric (erroneous) urls that display no valid info--too bad.  this page is for grown-ups
		if (is_numeric($maybeisextra))
			$newuri=$noextra.$prodid;
		else
			$newuri=$originUri.'/'.$prodid;	
		echo "document.location.href='$newuri';";
	}
}

public function processVars($originUri) {
	global $qq,$qqs;

		
	if (!$qqs->checkAuth('editproddesc'))
		return $originUri;		// no authorization.  die silently--this shouldn't happen, since links won't appear if not authorized
	$form=$this->makeForm();
	$vals=$form->getValueArray();

	$prodid=$form->getValue('prodid');
	$pm=new mod_prodmain;
	list($nHold,$nNote,$prevnohold,$prevhold,$prevnote,$nextnohold,$nexthold,$nextnote)=$pm->getNavInfo($prodid);

	$newid=0;
	if ($form->wasPressed('prevnohold')) {
		$newid=$prevnohold;
	} else if ($form->wasPressed('prevhold')) {
		$newid=$prevhold;
	} else if ($form->wasPressed('prevnote')) {
		$newid=$prevnote;
	} else if ($form->wasPressed('nextnohold')) {
		$newid=$nextnohold;
	} else if ($form->wasPressed('nexthold')) {
		$newid=$nexthold;
	} else if ($form->wasPressed('nextnote')) {
		$newid=$nextnote;
	}	

	if (False !== ($info=$pm->getProductInfo($prodid))) {
		$info=array();
		$info['prodhold']=(int)$form->getValue('hold');
		$info['notes']=$form->getValue('notes');
		$pm->setProductInfo($prodid,$info);
	}

	$retval=$originUri;
	if (False !==($i=strrpos($retval,$prodid))) {
		$retval=substr($retval,0,$i).$newid;
	}
	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
//	$transaction=$this->transaction;
	
	$form=$this->makeForm();

	$pm=new mod_prodmain;
		
	// set values
	$prodid=(int)$pgen->getPageExtra();
	$form->setExtraValue('prodid',$prodid);
	
	echo "<div{$qqi->idcstr($this->longname)}>";
		
	if ($prodid <= 0 || False === ($info=$pm->getProductInfo($prodid))) {
		echo "<p>(invalid product id)</p>";
		list($nHold,$nNote,$prevnohold,$prevhold,$prevnote,$nextnohold,$nextnote,$nextnonote)=$pm->getNavInfo($prodid);
	} else {
		$form->setValue("hold",$info['prodhold']);
		$form->setValue("notes",$info['notes']);
		
		list($nHold,$nNote,$prevnohold,$prevhold,$prevnote,$nextnohold,$nextnote,$nextnonote)=$pm->getNavInfo($prodid);
		
		echo "<table style=\"width:100%\"><tr>";
		echo "<td>UPC: {$info['barcodes'][0]}</td>";
		echo "<td style=\"text-align:right\">{$info['invdesc']}</td></tr></table>";
	}

	echo $form->getFormattedOutput("New UPC: <<newupc>><br /><table><tr><td style=\"text-align:center;padding-right:10px\"><<prevnohold>><br /><<prevhold>><br /><<prevnote>><br /></td><td><<!notes>> (Total with Notes: $nNote)<br /><<notes>><br /><<hold>> (Total on Hold: $nHold)</td><td style=\"text-align:center;padding-left:10px\"><<nextnohold>><br /><<nexthold>><br /><<nextnote>><br /></td></tr></table>");
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
