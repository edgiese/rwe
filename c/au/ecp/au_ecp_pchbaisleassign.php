<?php if (FILEGEN != 1) die;
///// Access Unit definition file for 'assign aisles' dialog box -- used for initial development
class au_ecp_pchbaisleassign extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $presets;

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$this->presets=array(
		'(Custom)'=>False,
		'Small Jar'=>array('shape'=>'cylinder','height'=>'90','width'=>'110','depth'=>'0','weight'=>'150','glass'=>'0','fragile'=>'0'),
	);
}

private function makeform() {
	$form=new llform($this,'update');
	
	$form->addFieldset('u');
	$form->addFieldset('x');
	$form->addFieldset('a');
	$form->addFieldset('b');
	$form->addFieldset('c');
	$form->addFieldset('d');

   	$preset=new ll_dropdown($form,'preset');
   	foreach ($this->presets as $name=>$presets) {
   		$preset->addOption($name,$name);
	}
	$form->addControl('x',$preset);
	
   	$form->addControl('u',new ll_listbox($form,'groups','Groups',8));

	$form->addControl('a',new ll_radioitem($form,'cylinder','shape','Cylinder'));
	$form->addControl('a',new ll_edit($form,'cheight',5,5,'Height:'));
	$form->addControl('a',new ll_edit($form,'circ',5,5,'Circumference:'));
	$form->addControl('a',new ll_static($form,'oldcyl',''));

	$form->addControl('b',new ll_radioitem($form,'rect','shape','Rectangular Prism'));
	$form->addControl('b',new ll_edit($form,'height',5,5,'Height:'));
	$form->addControl('b',new ll_edit($form,'width',5,5,'Width:'));
	$form->addControl('b',new ll_edit($form,'depth',5,5,'Depth:'));
	$form->addControl('b',new ll_static($form,'oldrect',''));

	$form->addControl('c',new ll_radioitem($form,'huge','shape','Huge'));
	                    
	$form->addControl('d',new ll_edit($form,'weight',5,5,'Weight:'));
	$form->addControl('d',new ll_checkbox($form,'glass','Glass/Fragile'));
	$form->addControl('d',new ll_checkbox($form,'cold','Frozen/Refrigerated'));
	$form->addControl('d',new ll_button($form,'ok','Set'));

	return $form;
}

private function makeselectform() {
	$form=new llform($this);
	
	$pi=new mod_prodinfo;
	$form->addFieldset('s','');

	$form->addControl('s',new ll_static($form,'location','Current Location:'));
   	$aisle=new ll_dropdown($form,'aisle','Aisle:');
	$aisle->setOptionArray($pi->getAllGroups(1));
	$form->addControl('s',$aisle);
	$form->addControl('s',new ll_edit($form,'upc',20,30,'UPC:'));
	$form->addControl('s',new ll_checkbox($form,'auto','Auto Submit'));
	$form->addControl('s',new ll_button($form,'ok','Read'));


	return $form;
}

public function declareids($idstore,$state) {
	// containing div:
	//$idstore->declareHTMLid($this,$this->bAutoLock);
	$this->makeform()->declareids($idstore);
	$this->makeselectform()->declareids($idstore);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname.'_update','form:table,td,th','dialog',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname.'_update');
	$stylegen->registerStyledId($this->longname,'form:table,td,th','dialog',$this->getParent()->htmlContainerId());
	$this->makeselectform()->declarestyles($stylegen,$this->longname);
}

public function declarestaticjs($js) {
	global $qq,$qqp,$qqi;

	$args=$js->seedArgs($this);
	$js->addjs('$','ecp::aisleassignsetup',$args);
}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi,$qqu;

	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	// this is a development-only form.  no nice checking.
	if (False !== $prodid=$pm->idFromBarcode($vals['upc'])) {
		// update info for item:
		
		// remove item from any location groups
		$locations=$pi->getAllGroups(1);
		foreach ($locations as $gid=>$name) {
			$pi->removeItemFromGroup($gid,$prodid);
		}	
		// add back in:	
		$pi->addItemToGroup($vals['aisle'],$prodid);
		
		if ($vals['cylinder']) {
			$pi->setShape($prodid,'cylinder',(int)$vals['cheight'],(int)$vals['circ'],0);
		} else if ($vals['rect']) {
			$pi->setShape($prodid,'rect',(int)$vals['height'],(int)$vals['width'],(int)$vals['depth']);
		} else if ($vals['huge']) {
			$pi->setShape($prodid,'huge',0,0,0);
		}
		$pi->setWeight($prodid,$vals['weight']);		

		$flags=0;
		if ($vals['glass'])
			$flags |= mod_prodinfo::FLAG_FRAGILE;		
		if ($vals['cold'])
			$flags |= mod_prodinfo::FLAG_COLD;
		$pi->setFlags($prodid,$flags);
		
		$info=array('notes'=>$vals['note']);
		$pm->setProductInfo($prodid,$info);
	}
	
	// set up defaults for next display:
	if (!$qqs->sessionDataExists($this->longname)) {
		$sdata=array('aisle'=>2091,'upc'=>-1,'level'=>-1,'autosubmit'=>True);
	} else
		$sdata=$qqs->getSessionData($this->longname);

	$sdata['aisle']=$vals['aisle'];
	$sdata['glass']=$vals['glass'];
	$sdata['cold']=$vals['cold'];
	// maintain arrays of values to create links of last used cylinder and rectangular prism dimensions
	if ($vals['cylinder'] && $vals['cheight'] > 0 && $vals['circ'] > 0) {
		$bFound=False;
		foreach ($sdata['oldcyl'] as $oc) {
			if ($oc[0] == $vals['cheight'] && $oc[1] == $vals['circ']) {
				$bFound=True;
				break;
			}
		}
		if (!$bFound) {
			array_unshift($sdata['oldcyl'],array($vals['cheight'],$vals['circ']));	
			if (sizeof($sdata['oldcyl']) > 5)
				array_pop($sdata['oldcyl']);
		}
	} else if ($vals['rect'] && $vals['height'] > 0 && $vals['width'] > 0 && $vals['depth']) {
		$bFound=False;
		foreach ($sdata['oldrect'] as $oc) {
			if ($oc[0] == $vals['height'] && $oc[1] == $vals['width'] && $oc[2] == $vals['depth']) {
				$bFound=True;
				break;
			}
		}
		if (!$bFound) {
			array_unshift($sdata['oldrect'],array($vals['height'],$vals['width'],$vals['depth']));	
			if (sizeof($sdata['oldrect']) > 5)
				array_pop($sdata['oldrect']);
		}
	}
	foreach (array('cheight','circ','height','width','depth','weight') as $param) {
		if (isset($vals[$param]) && (int)$vals[$param] != 0)
			$sdata['old'.$param]=$vals[$param];
		else if (isset($sdata['old'.$param]))
			unset($sdata['old'.$param]);	
	}
	$sdata['shape']=$_REQUEST['shape'];
	$qqs->setSessionData($this->longname,$sdata);
	
	$retval=$originUri;	
	return $retval;
	
}

public function processVars($originUri) {
	global $qq,$qqs,$qqu;

	$form=$this->makeselectform();
	$vals=$form->getValueArray();
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;

	// set up defaults for next display:
	if (!$qqs->sessionDataExists($this->longname)) {
		$sdata=array('aisle'=>$aisle,'upc'=>-1,'autosubmit'=>True);
	} else
		$sdata=$qqs->getSessionData($this->longname);
	
	$sdata['aisle']=$vals['aisle'];
	$sdata['autosubmit']=$vals['auto'];
	
	if (isset($_REQUEST['upc'])) {
		// this is a development-only form.  no nice checking.
		$sdata['upc']=$_REQUEST['upc'];
	} else if ($id=$pm->idFromBarcode($sdata['upc'])) {
		$iform=$this->makeform();
		$vals=$iform->getValueArray();
		if ($vals['preset'] != '(Custom)') {
			$save=$vals['groups'];
			$vals=$this->presets[$vals['preset']];
			$vals['groups']=$save;
			$vals[$vals['shape']]=True;
			$vals['cheight']=$vals['height'];
			$vals['circ']=$vals['width'];
		}	
		$groups=$pi->getAllGroups();
		foreach ($groups as $gid=>$name) {
			if (isset($vals['groups'][$gid])) {
				$pi->addItemToGroup($gid,$id);
			} else
				$pi->removeItemFromGroup($gid,$id);
		}
		if ($vals['cylinder']) {
			$pi->setShape($id,'cylinder',max(1,(int)$vals['cheight']),max(1,(int)$vals['circ']),0);
			$vals['shape']='cylinder';
			$vals['dim1']=$vals['cheight'];
			$vals['dim2']=$vals['circ'];
			$vals['dim3']=0;
		} else if ($vals['rect']) {
			$pi->setShape($id,'rect',max(1,(int)$vals['height']),max(1,(int)$vals['width']),max(1,(int)$vals['depth']));
			$vals['shape']='rect';
			$vals['dim1']=$vals['height'];
			$vals['dim2']=$vals['width'];
			$vals['dim3']=$vals['depth'];
		} else if ($vals['huge']) {
			$pi->setShape($id,'huge',0,0,0);
			$vals['shape']='huge';
			$vals['dim1']=$vals['dim2']=$vals['dim3']=0;
		} else {
			$pi->setShape($id,'none',0,0,0);
			$vals['shape']='none';
			$vals['dim1']=$vals['dim2']=$vals['dim3']=0;
		}	
		$pi->setWeight($id,$vals['weight']);		

		$flags=0;
		if ($vals['glass'])
			$flags |= mod_prodinfo::FLAG_FRAGILE;		
		if ($vals['cold'])
			$flags |= mod_prodinfo::FLAG_COLD;
		$pi->setFlags($id,$flags);

		foreach (array('groups','shape','dim1','dim2','dim3','weight','glass','cold') as $index)
			$sdata['last'][$index]=$vals[$index];	
	}
		
	$qqs->setSessionData($this->longname,$sdata);
	$retval=$originUri;	
	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qq,$qqs,$qqi,$qqu;

	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	$form=$this->makeselectform();

	$level=-1;
	if ($qqs->sessionDataExists($this->longname)) {
		$sdata=$qqs->getSessionData($this->longname);
		$aisle=$form->getControl('aisle');
		$form->getControl('aisle')->setValue((int)$sdata['aisle']);
		$form->getControl('auto')->setValue($sdata['autosubmit']);
		$sdata=$qqs->getSessionData($this->longname);
		if (False !== ($prodid=$pm->idFromBarcode($sdata['upc']))) {
			$info=$pi->getProdInfo($prodid);
			$main=$pm->getProductInfo($prodid);
			if ($main['prodhold']) {
				$level=2;
			} else {
				$locations=$pi->getOwningGroups($prodid,1);
				if (false === array_search($sdata['aisle'],$locations) || $info['shape'] == 'none' || $info['weight'] == 0) {
					$level=1;
				} else
					$level=0;
			}		
		}
	} else {
		$autosubmit=$form->getControl('auto');
		$autosubmit->setValue(True);
	}
	
	echo "<table><tr>";
	echo "<td>{$form->getOutputFormStart()}{$form->getDumpstyleFieldsetOutput('s')}{$form->getOutputFormEnd()}</td>";
	
	if ($level > -1) {
		$color=($level == 0) ? '#8f8' : (($level == 1) ? '#ff8' : '#f88' );
		echo "<td style=\"background-color:$color; min-width:450px;\">";
		$link=$qqi->hrefprep("prodedit/$prodid");
		echo "<h2 style=\"font-size:15px; font-weight:bold;\">{$sdata['upc']} (<a href=\"{$link}\">{$prodid}</a>)</h2>";
		echo '<h3 style="font-size:17px; font-weight:bold; padding-top:6px">'.addslashes($qqu->creolelite2html($info['title'])).'</h3>';
		if ($info['imageid'] != 0) {
			$image=new image($info['imageid']);
			$image->setMaximumSize(150,250);
			echo '<p style="margin:6px; padding:8px; border:2px solid black; background-color:#fff;text-align:center">'.$image->getOutput().'</p>';
		}
		if ($level == 1) {
			$iform=$this->makeform();
			
			$lbg=$iform->getControl('groups');
			$groups=$pi->getAllGroups();
			$lbg->addOptions($groups);
			$selgroups=$pi->getOwningGroups($prodid);
			if (is_array($sdata['last'])) {
				$sd=$sdata['last'];
				foreach ($sd['groups'] as $gid=>$true) {
					if (false === array_search($gid,$selgroups))
						$selgroups[]=$gid;
				}
				if (!isset($info['shape']) || $info['shape']=='') {
					$info['shape']=$sd['shape'];
					$info['dim1']=$sd['dim1'];
					$info['dim2']=$sd['dim2'];
					$info['dim3']=$sd['dim3'];
				}
				if (!isset($info['weight']) || $info['weight']=='' || $info['weight']==0) {
					$info['weight']=$sd['weight'];
					if ($sd['glass'])
						$info['flags'] |= mod_prodinfo::FLAG_FRAGILE; 
					if ($sd['cold'])
						$info['flags'] |= mod_prodinfo::FLAG_COLD; 
				}						
			}
			
			$lbg->setValue($selgroups);
			if ($info['shape'] == 'cylinder') {
				$iform->setValue('cylinder',True);
				$iform->setValue('cheight',$info['dim1']);
				$iform->setValue('circ',$info['dim2']);
			} else if ($info['shape'] == 'rect') {
				$iform->setValue('rect',True);
				$iform->setValue('height',$info['dim1']);
				$iform->setValue('width',$info['dim2']);
				$iform->setValue('depth',$info['dim3']);
			} else if ($info['shape'] == 'huge')
				$iform->setValue('huge',True);
			$iform->setValue('weight',$info['weight']);
			$iform->setValue('glass',($info['flags'] & mod_prodinfo::FLAG_FRAGILE) ? True : False);	
			$iform->setValue('cold',($info['flags'] & mod_prodinfo::FLAG_COLD) ? True : False);	
			
			echo "{$iform->getOutputFormStart()}";
			echo '</tr><tr><td colspan="2" style="background-color:'.$color.'"><table><tr>';
			echo "<td rowspan=\"2\" style=\"background-color:transparent\">{$iform->getDumpstyleFieldsetOutput('u')}</td><td colspan=\"4\">{$iform->getDumpstyleFieldsetOutput('x')}</td></tr><tr>";
			echo "<td style=\"background-color:transparent\">{$iform->getDumpstyleFieldsetOutput('a')}</td>";
			echo "<td style=\"background-color:transparent\">{$iform->getDumpstyleFieldsetOutput('b')}</td>";
			echo "<td style=\"background-color:transparent\">{$iform->getDumpstyleFieldsetOutput('c')}</td>";
			echo "<td style=\"background-color:transparent\">{$iform->getDumpstyleFieldsetOutput('d')}</td>";
			echo "{$iform->getOutputFormEnd()}";
			echo "</tr></table>";
		} else if ($level == 2) {
			echo "<p><b>Product is on Hold</b><br>Note: {$main['notes']}</p>";
		}
		echo "</td>";
	} else
		echo '<td>UPC Code Not found</td>';
	
	echo "</tr></table>";	
	echo "</div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
