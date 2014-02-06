<?php if (FILEGEN != 1) die;
///// Access Unit definition file for sync of inventory--peach basket style
class au_ecp_pchbsync extends au_base {
////////////////////////////////////////////////////////////////////////////////

private $state;		// transaction state
private $data;		// transaction data

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$this->state=$state;
	$this->data=$data;
}

private function makeform($state) {
	$form=new llform($this,'form');

	if (!is_string($state))
		$state='';	
	$form->addFieldset('d','Sync Files');

	$args=array(
		''=>array('barcodes',5*1024*1024,'Barcode Report'),
		'collecting'=>array('barcodes',5*1024*1024,'Barcode Report'),
		'gotprices'=>array('status',5*1024*1024,'Inventory Status Report'),
		'gotbarcodes'=>array('prices',5*1024*1024,'Price Report')
	);
	if ($state != 'confirming' && $state != 'finished') {
		if (!isset($args[$state]))
			throw new exception("unknown state $state");	
		$form->addControl('d',new ll_file($form,$args[$state][0],$args[$state][1],$args[$state][2]));
		$form->addControl('d',new ll_button($form,'ok','Upload File & Process'));
	} else {
		$form->addControl('d',new ll_button($form,'ok','Commit'));
		$form->addControl('d',new ll_button($form,'cancel','Rollback'));
	}	
	
	return $form;
}


public function declareids($idstore,$state) {
	// containing div:
	$idstore->declareHTMLid($this,$this->bAutoLock);
	$this->makeform($state)->declareids($idstore);
	
	if ($state == '')
		$idstore->registerAuthPage('view sync page',False);
	$idstore->registerAuthBool('syncinventory',"Perform Inventory Sync",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div','dialog',$this->getParent()->htmlContainerId());
	$this->makeform($state)->declarestyles($stylegen,$this->longname);
}

// returns next line of report or False if no more lines.  ix returned as one past the line's index
private function getNextReportLine(&$lines,&$ix) {
	$bHeader=$bFirstRule=False;
	while ($ix < sizeof($lines)) {
		$ln=$lines[$ix++];
		if (strlen(trim($ln)) == 0)
			continue;
		if ($bHeader) {
			if (trim($ln) != str_repeat('-',132))
				continue;
			if (!$bFirstRule) {
				$bFirstRule=True;
				continue;
			}
			$bHeader=False;
			continue;	
		} 	
		if (False !== strpos($ln,'Date') && False !== strpos($ln,"THE PEACH BASKET") && False != strpos($ln,"Report#")) {
			// ran into a header.  skip until we're done
			$bHeader=True;
			continue;
		}
		/// found a legitimate data line	
		return $ln;	
	}
	return False;
}

public function processVars($originUri) {
	global $qq,$qqs;

	// if no authorization, die silently.  page should not have been visible anyway.
	if (!$qqs->checkAuth('syncinventory'))
		return $originUri;

	if ($this->data != 0) {
		$data=$this->data;
		if ($qqs->isTransactionFinished($data))
			return message::redirectString(message::TRANSACTIONFINISHED);
		$tdata=$qqs->getTransactionData($data,'tdata');
		unset($tdata['emsg']);	
	} else {
		$data=0;		
		$tdata=array();
	}	

	$form=$this->makeForm($this->state);
	$vals=$form->getValueArray();
	$today=date('m/d/Y');

	switch($this->state) {
		case '':
		case 'collecting':
			if ($data == 0)
				$data=$qqs->beginTransaction($originUri);
			$filename=$form->getControl('barcodes')->getTempName(1);
			if ($filename !== False) {
				$pm=new mod_prodmain;
				$tdata['olddiscontinued']=$pm->syncPrep();
				$tdata['nItemsAdded']=0;
				$tdata['nBarcodesUpdated']=0;
				
				$lines=file($filename);
				$bHeader=True;
				$bFirstRule=False;
				$info=array('discontinued'=>False);
				foreach($lines as $ln) {
					$ln=trim($ln);
					if (substr($ln,1,4) == 'Date' && False !== (strpos($ln,'THE PEACH BASKET')) ) {
						// new pages begin with ascii ff
						$bHeader=True;
						$bFirstRule=False;
						continue;
					}
					if ($bHeader || $ln == '') {
						if ($ln == str_repeat('-',132)) {
							if ($bFirstRule)
								$bHeader=False;
							$bFirstRule=True;
						}
						continue;	
					}
					$invid=trim(substr($ln,0,15));
					if ($invid == 'ZZZ')
						break;
					$barcodes=array();	
					foreach (array(43,65,87,109) as $posn) {
						$upc=trim(substr($ln,$posn,20));
						if ($upc != '')
							$barcodes[]=$upc;
					}
					$info['barcodes']=$barcodes;
					if (False !== ($prodid=$pm->invItemExists($invid))) {
						$tdata['nBarcodesUpdated'] += sizeof($barcodes);
						$pm->setProductInfo($prodid,$info);
					} else {
						$tdata['nItemsAdded'] += sizeof($barcodes);
						$pm->addProduct(array('invid'=>$invid,'barcodes'=>$barcodes,'prodhold'=>1,'notes'=>"added by sync $today"));
					}
				}
				$retval=llform::redirectString('gotbarcodes',$data);
			} else {
				$tdata['emsg']='No Barcode File Specified';
				$retval=llform::redirectString('collecting',$data);
			}	
		break;
		
		case 'gotbarcodes':
			$filename=$form->getControl('prices')->getTempName(1);
			if ($filename !== False) {
				$pm=new mod_prodmain;
				$tdata['nPriceItemsAdded']=0;
				$tdata['nPricesUpdated']=0;
				$info=array('discontinued'=>False);

				$lines=file($filename);
				$bHeader=False;
				$invid='';
				for ($ix=0; $ix < sizeof($lines); ++$ix) {
					$ln=$lines[$ix];
					if (strlen(trim($ln)) == 0)
						continue;
					if ($bHeader) {
						if (trim($ln) != str_repeat('-',132))
							continue;
						if (!$bFirstRule) {
							$bFirstRule=True;
							continue;
						}
						$bHeader=False;
						continue;	
					} 	
					if (False !== strpos($ln,'Date') && False !== strpos($ln,"THE PEACH BASKET") && False != strpos($ln,"Report#")) {
						// ran into a header.  skip until we're done
						$bHeader=True;
						$bFirstRule=False;
						continue;
					}
					if ($invid == '') {
						// first line in group has inventory number on it:
						$invid=trim(substr($ln,0,15));
						if ($invid == 'ZZZ')
							break;
						continue;		// we found inventory id.  that's all that's useful about this line
					}		
					$pricestr=trim(substr($ln,61,9));
					$price=(int)(0.5+100*$pricestr);
					if (False !== ($prodid=$pm->invItemExists($invid))) {
						// we could overwrite price without checking, but info on changing prices is nice to have
						$oldinfo=$pm->getProductInfo($prodid);
						if ($oldinfo['baseprice'] != $price) {
							$tdata['nPricesUpdated']++;
							$info['baseprice']=$price;
							$pm->setProductInfo($prodid,$info);
						}
					} else {
						$tdata['nPriceItemsAdded']++;
						$pm->addProduct(array('invid'=>$invid,'baseprice'=>$price,'prodhold'=>1,'notes'=>"added by sync $today"));
					}
					$invid='';
				}
				$retval=llform::redirectString('gotprices',$data);
			} else {
				$tdata['emsg']='No Price File Specified';
				$retval=llform::redirectString('gotbarcodes',$data);
			}	
		break;
		
		case 'gotprices':
			$filename=$form->getControl('status')->getTempName(1);
			if ($filename !== False) {
				$pm=new mod_prodmain;
				$tdata['nNoPriceItemsAdded']=0;
				$tdata['nProductsUpdated']=0;
				$info=array('discontinued'=>False);
				
				$lines=file($filename);
				$prodmain=array();
				$bHeader=True;
				$bFirstRule=False;
				$nGroups=2;
				for ($ix=0; $ix < sizeof($lines); ++$ix) {
					$ln=trim($lines[$ix]);
					if ($bHeader) {
						if ($ln == str_repeat('-',132)) {
							if ($bFirstRule)
								$bHeader=False;
							$bFirstRule=True;
						}
						continue;	
					}
					// groups of four lines:  blank, prod, vendor, data.  If 'prod' is blank, then we're at a header
					$prod=rtrim($lines[$ix+1]);
					$vendor=rtrim($lines[$ix+2]);
					$dataline=rtrim($lines[$ix+3]);
					$ix += 3;
					if (++$nGroups > 12) {
						// ran into bottom of page
						$bHeader=True;
						$bFirstRule=False;
						$nGroups=0;
						continue;
					}
					$invid=trim(substr($prod,0,15));
					if ($invid == 'ZZZ')
						break;
					$info['invdesc']=trim(substr($prod,15,25));
					$info['onhand']=(int)trim(substr($prod,61,5));
					$info['minqty']=(int)trim(substr($prod,89,5));
					$amdate=str_replace(' ','0',substr($prod,109,8));
					$info['firstsold']='20'.substr($amdate,6,2).'-'.substr($amdate,0,2).'-'.substr($amdate,3,2);					
					$amdate=str_replace(' ','0',substr($vendor,109,8));					
					$info['lastsold']='20'.substr($amdate,6,2).'-'.substr($amdate,0,2).'-'.substr($amdate,3,2);					
					if (False !== ($prodid=$pm->invItemExists($invid))) {
						$tdata['nProductsUpdated']++;
						$pm->setProductInfo($prodid,$info);
					} else {
						$tdata['nNoPriceItemsAdded']++;
						$pm->addProduct(array_merge(array('invid'=>$invid,'prodhold'=>1,'notes'=>"added by sync $today"),$info));
					}
					// add sales tax info based on category
					$category=(int)trim(substr($prod,42,3));
					if (False !== array_search($category,array('3','4','5','10','11','13','17'))) {
						$pm->addTax($prodid,'TX',825);
					} else
						$pm->clearTax($prodid,'TX');
					if ($category == 15) {
						$info['discontinued']=True;
						$pm->setProductInfo($prodid,$info);
					}	
				}
				$tdata['nTotalDiscontinued']=$pm->countDiscontinued();
				$tdata['newdiscontinued']=$tdata['nTotalDiscontinued']-$tdata['olddiscontinued'];
				$pm->autoHold();
				
				$retval=llform::redirectString('confirming',$data);
			} else {
				$tdata['emsg']='No Inventory Status File Specified';
				$retval=llform::redirectString('gotprices',$data);
			}	
		break;
		
		case 'confirming':
			$pm=new mod_prodmain;
			if ($form->wasPressed('ok')) {
				$pm->syncCommit();
				$tdata['committed']=True;
			} else {
				$pm->syncRollback();
				$tdata['committed']=False;
			}
			$retval=llform::redirectString('finished',$data);
		break;
	}
	
	if ($data != 0)
		$qqs->setTransactionData($data,"tdata",$tdata);
	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qq,$qqs,$qqi;

	echo "<div{$qqi->idcstr($this->longname)}>";
	
	if ($this->data != 0) {
		$tdata=$qqs->getTransactionData($this->data,"tdata");
		if (isset($tdata['emsg'])) {
			echo "<p>{$tdata['emsg']}</p>";
		}
	}

	switch ($this->state) {
		case 'gotbarcodes':
			echo "<h2>Finished Processing Barcodes</h2>";
			echo "<p>Number of New Items with barcodes Added: {$tdata['nItemsAdded']}</p>";
			echo "<p>Number of barcodes updated: {$tdata['nBarcodesUpdated']}</p>";
		break;
		case 'gotprices':
			echo "<h2>Finished Processing Prices</h2>";
			echo "<p>Number of New Items with barcodes Added: {$tdata['nItemsAdded']}</p>";
			echo "<p>Number of barcodes updated: {$tdata['nBarcodesUpdated']}</p>";
			echo "<p>Number of New Items with no barcodes: {$tdata['nPriceItemsAdded']}</p>";
			echo "<p>Number of prices updated: {$tdata['nPricesUpdated']}</p>";
		break;
		case 'confirming':
			echo "<h2>Finished Processing Prices</h2>";
			echo "<p>Number of New Items with barcodes Added: {$tdata['nItemsAdded']}</p>";
			echo "<p>Number of barcodes updated: {$tdata['nBarcodesUpdated']}</p>";
			echo "<p>Number of New Items with no barcodes: {$tdata['nPriceItemsAdded']}</p>";
			echo "<p>Number of prices updated: {$tdata['nPricesUpdated']}</p>";
			echo "<p>Number of New Items with no prices (should be 0): {$tdata['nNoPriceItemsAdded']}</p>";
			echo "<p>Number of products updated: {$tdata['nProductsUpdated']}</p>";
			echo "<p>Products Newly Discontinued: {$tdata['newdiscontinued']}</p>";
			echo "<p>Total Products Discontinued: {$tdata['nTotalDiscontinued']}</p>";
		break;
		case 'finished':
			if ($tdata['committed']) {
				echo "<h2>Transactions Committed</h2>";
				echo "<p>Good luck with your newly synchronized database!</p>";
			} else {
				echo "<h2>Transactions Rolled Back</h2>";
				echo "<p>State has been restored to that prior to sync attempt.</p>";
			}	
		break;
	}

	if ($this->state != 'finished') {
		$form=$this->makeform($this->state);
		echo $form->getDumpStyleFormOutput($this->state,$this->data);
	}	
	echo "</div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
