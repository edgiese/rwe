<?php if (FILEGEN != 1) die;
///// Access Unit definition file for shopping browser
class au_ecp_render1shopnav extends au_base {
////////////////////////////////////////////////////////////////////////////////

private $infopage;		// page to go to for display of prod info

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$initdata=parent::getInit($initdata,'infopage');
	$this->infopage=$initdata['infopage'];	
}

// initialize function sets the list of product ids to display
// returns list(array of ids,description of list)
static function readExtra($extra,$pm,$pi) {
	global $qqs;
	if ($extra == '') {
		$extra='group_1';
	}
	
	if (0 === stripos($extra,'mfr_')) {
		$mfrs=explode('_',substr($extra,4));
		$prodids=array();
		$description='All products for manufacturer: ';
		$separator='';
		$more=0;
		foreach ($mfrs as $mfrstr) {
			if (0 != ($mfr=(int)$mfrstr) && False !== $pi->isMfr($mfr)) {
				$prodids=array_merge($prodids,$pi->getMfrIds($mfr));
				if (strlen($description) < 50)
					$description .= $separator.$pi->getMfrText($mfr);
				else
					$more++;	 
				$separator=', ';
			}
		}
		if ($more > 0)
			$description .= " (+$more more)";
		// found what we want.  exit.
		return array($prodids,$description);
	}

	if (0 === stripos($extra,'search')) {
		$extra=substr($extra,6);
		if (False !== stripos(substr($extra,0,2),'t') || False !== stripos(substr($extra,0,2),'d')) {
			$bTitles=False;
			$bDescs=False;
			if ($extra[0] == 't' || $extra[0] == 'T') {
				$bTitles=True;
				$extra=substr($extra,1);
			}	
			if ($extra[0] == 'd' || $extra[0] == 'D') {
				$bDescs=True;
				$extra=substr($extra,1);
			}
			if ($extra[0] == '_') {
				$extra=substr($extra,1);
			} else {
				// error.  by setting both to false, we'll skip the search
				$bTitles=$bDescs=False;
			}	
		} else {
			// options not specified.  assume search of both titles and descriptions
			$bTitles=$bDescs=True;
			if ($extra[0] == '_')
				$extra=substr($extra,1);
		}
		if ($bTitles || $bDescs) {
			$description='Search ';
			if ($bTitles)
				$description .= "titles" . ($bDescs ? '/' : '');
			if ($bDescs)
				$description .= "descriptions";
			$description .= " for: ";		
			$separator='';		
			$keywords=explode('_',$extra);
			$prodids=array();
			$accumTitle=array();
			$accumDesc=array();
			foreach ($keywords as $keyword) {
				$keyword=trim(preg_replace('/[^0-9a-zA-Z]/','',$keyword));
				$description .= $separator.$keyword;
				$separator=', ';
				if ($bTitles) {
					$new=utilrare1::getKeywordCounts($keyword,util::SRC_PRODTITLE);
					foreach ($new as $id=>$count) {
						$count=min($count,10);
						if (isset($accumTitle[$id]))
							$accumTitle[$id] += 100+$count;
						else	
							$accumTitle[$id] = $count;
					}
				}	
				if ($bDescs) {
					$new=utilrare1::getKeywordCounts($keyword,util::SRC_PRODDESC);
					foreach ($new as $id=>$count) {
						if (isset($accumDesc[$id]))
							$accumDesc[$id] += 100+$count;
						else	
							$accumDesc[$id] = $count;
					}
				}
			}
			// stored ids are text ids.  we need prod ids
			$accum=array();
			// accumulate counts as negative numbers to avoid having to do an array_reverse when done sorting
			foreach ($accumTitle as $textid=>$count) {
				if (False !== ($prodid=$pi->prodIdFromTitleId($textid))) {
					// a search match in the title counts 10 times as much toward sort scoring as a match in the text
					if (isset($accum[$prodid]))
						$accum[$prodid] += (-10)*$count;
					else
						$accum[$prodid]= -10*$count;	
				}
			}
			foreach ($accumDesc as $textid=>$count) {
				if (False !== ($prodid=$pi->prodIdFromDescId($textid))) {
					if (isset($accum[$prodid]))
						$accum[$prodid] -= $count;
					else
						$accum[$prodid]= -$count;	
				}
			}
			asort($accum,SORT_NUMERIC);
			$nItems=0;
			foreach ($accum as $id=>$count) {
				$prodids[]=$id;
				if (++$nItems > 100)
					break;
			}	
			// found what we want.  exit.
			return array($prodids,$description);
		}	
	}
	
	if (0 === stripos($extra,'group_')) {
		$groupid=substr($extra,6);
		if (!is_numeric($groupid)) {
			$groupid=$pi->findGroup($groupid);
		} else
			$groupid=(int)$groupid;
		if ($groupid != 0 && False !== ($info=$pi->getGroupInfo($groupid))) {
			$prodids=$pi->getGroupItems($groupid);
			$description="Items in group: {$info[0]}";
			// found what we want.  exit.
			return array($prodids,$description);
		}	
	}
	
	if (0 === stripos($extra,'cart') && strlen($extra) == 4) {
		$prodids=$pm->getCartItems($groupid);
		return array($prodids,'Items in your shopping cart');
	}

	if (0 === stripos($extra,'upc_')) {
		$upc=substr($extra,4);
		$description="UPC: $upc";
		$prodid=$pm->idFromBarcode($upc);
		if (False === $prodid) {
			return array(array(),$description);
		} else
			return array(array($prodid),$description);
	}
	
	// if we got here, we're going to show them nothing.  the default.
	return array(array(),'No Items Selected');
}


private function makeform() {
	$form=new llform($this,'form');
	
	$form->addFieldset('m','Manufacturer');
	$form->addControl('m',new ll_dropdown($form,'mfr',''));
	$form->addControl('m',new ll_button($form,'setmfr','Set Manufacturer'));
	$form->addFieldset('s','Search for Keywords');
	$form->addControl('s',new ll_edit($form,'searchstring',20,30,""));
	$form->addControl('s',new ll_checkbox($form,'titles','Search Titles'));
	$form->addControl('s',new ll_checkbox($form,'descs','Search Descriptions'));
	$form->addControl('s',new ll_button($form,'setsearch','Do Search'));
	$form->addFieldset('g','Group');
	$form->addControl('g',new ll_dropdown($form,'group',''));
	$form->addControl('g',new ll_button($form,'setgroup','View Group'));
	$form->setExtraValue('pagebase',"");
	return $form;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$this->makeform()->declareids($idstore);
	$idstore->registerAuthBool('viewitemsonhold',"View Items on Hold",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','shopnav',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname);
}


public function processVars($originUri) {
	global $qq,$qqs;

	$form=$this->makeForm();
	$vals=$form->getValueArray();

	$pm=new mod_prodmain;

	$newid=0;
	if ($form->wasPressed('setmfr')) {
		$retval="{$vals['pagebase']}/mfr_{$vals['mfr']}";
/*
		$mfrstring=$separator='';
		foreach ($vals['mfr'] as $mfr) {
			$mfrstring .= $separator.$mfr;
			$separator='_';
		}
		$retval="{$vals['pagebase']}/mfr_{$mfrstring}";
*/		
	} else if ($form->wasPressed('setsearch')) {
		$sopts=($vals['titles'] ? 't' : '') . ($vals['descs'] ? 'd' : '') . '_';
		$retval="{$vals['pagebase']}/search$sopts".str_replace(' ','_',utilrare1::getSearchableText($vals['searchstring'],True));
	} else if ($form->wasPressed('setgroup')) {
		$retval="{$vals['pagebase']}/group_{$vals['group']}";
	} else
		$retval=$originUri;	

	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qqu;
	
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	list($prodids,$description)=$this->readExtra($pgen->getPageExtra(),$pm,$pi);
	
	$form=$this->makeForm();
	$form->useGetMethod();
	$form->setExtraValue('pagebase',$qqi->hrefPrep($pgen->getPageName()));
	$form->getControl('mfr')->setOptionArray($pi->getMfrList());
	$form->getControl('group')->setOptionArray($pi->getAllGroups());
	
	// output form
	echo $form->getDumpStyleFormOutput('',0,'&nbsp;&nbsp;');
	echo "<p>".link::anchorHTML($pgen->getPageName()."/all",$tip="Show All Products")."All Products</a> &bull;".link::anchorHTML($pgen->getPageName()."/cart",$tip="Show Products in Cart")."Show Items in Cart</a></p>";
	
	echo "<h2>Showing $description</h2>";
	
	// output list
	echo "<div{$qqi->idcstr($this->longname)}>";
	if (sizeof($prodids) == 0) {
		echo "<p>(No Products Selected.  Use form above to select some.)</p>";
	} else {
		echo "<table>";
		foreach ($prodids as $id) {
			if (False !== ($info=$pi->getProdInfo($id))) {
				$maininfo=$pm->getProductInfo($id);
				if ($maininfo['prodhold']) {
					if (!$qqs->checkAuth('viewitemsonhold'))
						continue;
					$info['title']=' ***Item on Hold*** '.$info['title'];	
				}
				$href=$this->infopage."/{$maininfo['barcodes'][0]}/{$pgen->getPageExtra()}";
				echo "<tr>";
				echo "<td>{$info['mfr']}</td>";
				echo "<td>".($pm->isItemInCart($id) ? "In Cart" : "&nbsp;")."</td>";
				echo "<td>".link::anchorHTML($href,"Product Details").$qqu->creolelite2html($info['title'])."</a></td>";
				echo "</tr>";
			}
		}
		echo "</table>";
	}
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
