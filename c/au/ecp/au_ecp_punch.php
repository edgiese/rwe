<?php if (FILEGEN != 1) die;
///// Access Unit definition file for prod edit punch list
class au_ecp_punch extends au_base {
////////////////////////////////////////////////////////////////////////////////
function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);

}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$idstore->registerAuthPage('view punch list',False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div:th,td,table,h2,p,a','essay',$this->getParent()->htmlContainerId());
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qqu;
	
	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	echo "<h2>On Hold</h2>";
	if (False === ($ids=$pm->onHoldItems())) {
		echo "<p>(No Items on Hold)</p>";
	} else {
		echo "<table>";
		foreach ($ids as $id) {
			if (False === ($info=$pi->getProdInfo($id))) {
				$info['mfr']='(unknown)';
				$info['title']='';
			}
			$maininfo=$pm->getProductInfo($id);
			$href="prodedit/{$id}";
			echo "<tr>";
			echo "<td>{$info['mfr']}</td>";
			$title=strlen($info['title']) > 0 ? $info['title'] : "({$maininfo['invdesc']})"; 
			echo "<td>".link::anchorHTML($href,"Edit Product").$qqu->creolelite2html($title)."</a></td>";
			echo "<td>{$maininfo['notes']}</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	echo "<h2>Noted Items</h2>";
	echo "<h2>Items Missing Shipping Data</h2>";
	
	
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
