<?php if (FILEGEN != 1) die;
///// Access Unit definition file for description record edits--product based ecommerce
class au_ecp_prodeditdesc extends au_base {
////////////////////////////////////////////////////////////////////////////////
function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
}

private function makeform() {
	$form=new llform($this,'form');
	
	$form->addFieldset('mfr','Manufacturer');
	$form->addControl('mfr',new ll_dropdown($form,'mfrsel'));
	$form->addControl('mfr',new ll_edit($form,'mfrname',60,128,'New Manufacturer'));
	
	$form->addFieldset('title','Title');
	$form->addControl('title',new ll_edit($form,'title',60,128));
	
	$form->addFieldset('desc','Description');
	$form->addControl('desc',new ll_textarea($form,'desc',75,8));
	
	$form->addFieldset('groups','Groups');
	$form->addControl('groups',new ll_listbox($form,'groups','Groups',12));

	$form->addFieldset('shape','Shape');
	$form->addControl('shape',new ll_radioitem($form,'cylinder','shape','Cylinder'));
	$form->addControl('shape',new ll_edit($form,'cheight',5,5,'Height (mm):'));
	$form->addControl('shape',new ll_edit($form,'circ',5,5,'Circumference (mm):'));
	$form->addControl('shape',new ll_radioitem($form,'rect','shape','Rectangular Prism'));
	$form->addControl('shape',new ll_edit($form,'height',5,5,'Height (mm):'));
	$form->addControl('shape',new ll_edit($form,'width',5,5,'Width (mm):'));
	$form->addControl('shape',new ll_edit($form,'depth',5,5,'Depth (mm):'));
	$form->addControl('shape',new ll_radioitem($form,'huge','shape','Huge (cannot be shipped)'));
	$form->addControl('shape',new ll_radioitem($form,'none','shape','None (Not assigned yet)'));

	$form->addFieldset('weight','Weight & Shipping Attributes');
	$form->addControl('weight',new ll_edit($form,'weight',5,5,'Weight (g):'));
	$form->addControl('weight',new ll_checkbox($form,'glass','Glass/Fragile'));
	$form->addControl('weight',new ll_checkbox($form,'cold','Frozen/Refrigerated'));

	$form->addFieldset('update','Do all updates (except image)');
	$form->addControl('update',new ll_button($form,'ok','Update'));

	$form->addFieldset('image','Main Image');
	$form->addControl('image',new ll_file($form,'file',5*1024*1024));
	$form->addControl('image',new ll_button($form,'upload','Upload'));

	$form->setExtraValue('prodid',0);
	
	return $form;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$this->makeform()->declareids($idstore);
		
	$idstore->registerAuthBool('editproddesc',"Edit Product Entries",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','prodedit',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname);
}


public function processVars($originUri) {
	global $qq,$qqs;

	if (!$qqs->checkAuth('editproddesc'))
		return $originUri;		// no authorization.  die silently--this shouldn't happen, since links won't appear if not authorized
	$form=$this->makeForm();
	$vals=$form->getValueArray();
	
	$pi=new mod_prodinfo;
	
	$id=(int)$form->getValue('prodid');
	if ($id <= 0)
		return $originUri;		// invalid id--no updates will be done

	if (False === ($info=$pi->getProdInfo($id)))
		$pi->initProdDesc($id);
			
	if ($form->wasPressed('upload')) {
		// images are always done in isolation
		$file=$form->getControl('file');
		$image=image::createFromUpload($file,'main product image',320,240);
		if ($image instanceof image)
			$pi->setMainImg($id,$image->getId());
	} else {
		if ('' != ($txtitle=$form->getValue('mfrname'))) {
			if (!$pi->isMfr($txtitle))
				$pi->addMfr($txtitle);
			$mfr=$txtitle;	
		} else {
			// set from drop down
			$mfr=(int)$form->getValue('mfrsel');
		}
		$pi->setMfr($id,$mfr);
		$pi->setTitle($id,$form->getValue('title'));
		$pi->setDesc($id,$form->getValue('desc'));
		$groups=$pi->getAllGroups();
		foreach ($groups as $gid=>$name) {
			if (isset($vals['groups'][$gid]))
				$pi->addItemToGroup($gid,$id);
			else
				$pi->removeItemFromGroup($gid,$id);
		}	
		if ($vals['cylinder']) {
			$pi->setShape($id,'cylinder',max(1,(int)$vals['cheight']),max(1,(int)$vals['circ']),0);
		} else if ($vals['rect']) {
			$pi->setShape($id,'rect',max(1,(int)$vals['height']),max(1,(int)$vals['width']),max(1,(int)$vals['depth']));
		} else if ($vals['huge']) {
			$pi->setShape($id,'huge',0,0,0);
		} else
			$pi->setShape($id,'none',0,0,0);
		$pi->setWeight($id,$vals['weight']);		

		$flags=0;
		if ($vals['glass'])
			$flags |= mod_prodinfo::FLAG_FRAGILE;		
		if ($vals['cold'])
			$flags |= mod_prodinfo::FLAG_COLD;
		$pi->setFlags($id,$flags);
	}
	// always go back to original form:
	return $originUri;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
//	$transaction=$this->transaction;
	
	$form=$this->makeForm();
	
	// set values
	if (False === ($controller=$pgen->findFlaggedAU('prodnav')))
		throw new exception('Product description au needs a product navigator to specify a product');
	$prodid=$controller->getProdId($pgen);
	
	if ($prodid > 0) {
		$pi=new mod_prodinfo;
		$dd=$form->getControl('mfrsel');
		$dd->setOptionArray($pi->getMfrList());
		$info=$pi->getProdInfo($prodid);
		if ($info !== False) {
			$form->setValue('title',$info['title']);
			$form->setValue('desc',$info['desc']);
			$form->setValue('mfrsel',$pi->isMfr($info['mfr']));
		}
		$lbg=$form->getControl('groups');
		$groups=$pi->getAllGroups();
		$lbg->addOptions($groups);
		$selgroups=$pi->getOwningGroups($prodid);
		$lbg->setValue($selgroups);
		if ($info['shape'] == 'cylinder') {
			$form->setValue('cylinder',True);
			$form->setValue('cheight',$info['dim1']);
			$form->setValue('circ',$info['dim2']);
		} else if ($info['shape'] == 'rect') {
			$form->setValue('rect',True);
			$form->setValue('height',$info['dim1']);
			$form->setValue('width',$info['dim2']);
			$form->setValue('depth',$info['dim3']);
		} else if ($info['shape'] == 'huge')
			$form->setValue('huge',True);
		else
			$form->setValue('none',True);
		$form->setValue('weight',$info['weight']);
		$form->setValue('glass',($info['flags'] & mod_prodinfo::FLAG_FRAGILE) ? True : False);	
		$form->setValue('cold',($info['flags'] & mod_prodinfo::FLAG_COLD) ? True : False);	
	}
	$form->setExtraValue('prodid',$prodid);
	
	echo "<div{$qqi->idcstr($this->longname)}>";	
	echo $form->getDumpStyleFormOutput('',0);
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
