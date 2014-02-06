<?php if (FILEGEN != 1) die;
///// Access Unit definition file for shopping browser
class au_ecp_upcindex extends au_base {
////////////////////////////////////////////////////////////////////////////////

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
}


public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$idstore->registerAuthBool('viewitemsonhold',"View Items on Hold",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div:table,td,th','upcindex',$this->getParent()->htmlContainerId());
}


public function processVars($originUri) {
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qqu;
	
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	$prodids=$pm->getAllIds();
	
	// output list

	echo "<div{$qqi->idcstr($this->longname)}>";
	echo "<table><tr><th>UPC</th><th>Product (Click for Info)</th></tr>";
	foreach ($prodids as $id) {
		if (False !== ($info=$pi->getProdInfo($id))) {
			$maininfo=$pm->getProductInfo($id);
			if ($maininfo['discontinued'])
				continue;
			if ($maininfo['prodhold']) {
				if (!$qqs->checkAuth('viewitemsonhold'))
					continue;
				$info['title']=' ***Item on Hold*** '.$info['title'];	
			}
			$href="shop/upc_{$maininfo['barcodes'][0]}";
			echo "<tr>";
			echo "<td>{$maininfo['barcodes'][0]}</td>";
			$info['title']=str_replace('\\\\','~ ',$info['title']);
			echo "<td>".link::anchorHTML($href,"Product Details")."{$info['mfr']}: {$qqu->creolelite2html($info['title'])}</a></td>";
			echo "</tr>";
		}	
	}
	echo "</table></div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
