<?php if (FILEGEN != 1) die;
class mod_proj_hcncrg {
//////////////////// needs council resource guide module ///////////////////////

function __construct() {
	global $qqc;
}

// returns id
public function addItem($title,$description,$keywords,$notes) {
	global $qqc,$qqs;
	$id=$qqc->insert("mod/proj/hcncrg::create",$title,$description,$keywords,$notes);
	$qqc->insert("mod/proj/hcncrg::addhistory",$id,$qqs->getUser(),'Created');
	return $id;
}

// returns master keyword list as an array
public function getKeywords() {
	global $qqc;
	
	$rows=$qqc->getRows("mod/proj/hcncrg::keywords",-1);
	$allkeys=array();
	if ($rows !== False) {
		foreach ($rows as $row) {
			$keywords=$row['keywords'];
			$allkeys=array_unique(array_merge($allkeys,explode(' ',$keywords)));
		}
		sort(&$allkeys);
	}	
	return $allkeys;
}

// returns all ids matching specific criteria in an array
public function getIDs($limitto,$exclude,$matchtext) {
	global $qqc;

	if (is_string($limitto))
		$limitto=$limitto != '' ? explode(" ",trim($limitto)) : array();
	if (is_string($exclude))
		$exclude=$exclude != '' ? explode(" ",trim($exclude)) : array();
	
	$limittos=array();
	foreach ($limitto as $elem) {
		$elem=trim($elem);
		$limittos[]=" $elem ";
	}
	$excludes=array();	
	foreach ($exclude as $elem) {
		$elem=trim($elem);
		$excludes[]=" $elem ";
	}
	$rows=$qqc->getRows("mod/proj/hcncrg::allids",-1);
	$ids=array();
	foreach ($rows as $row) {
		extract($row);		// SETS: $id,$keywords,$title,$desc
		$keywords=trim($keywords);
		$keywords=" $keywords ";
		$excluded=False;
		foreach ($excludes as $elem) {
			if (False !== strpos($keywords,$elem)) {
				$excluded=True;
				break;
			}
		}
		if ($excluded)
			continue;
		$limited=False;
		foreach ($limittos as $elem) {
			if (False === strpos($keywords,$elem)) {
				$limited=True;
				break;
			}
		}
		if ($limited)
			continue;
		if ($matchtext != '' && False === stripos($title,$matchtext) && False === stripos($desc,$matchtext)) {
			continue;
		}	
		$ids[]=$id;
	}
	return $ids;
}


// returns array of (title,keywords,description,notes,historystring)
public function getInfo($id) {
	global $qqc,$qqs;

	$info=$qqc->getRows('mod/proj/hcncrg::info',1,$id);
	if ($info === False)
		throw new exception("required id $id not found in resource guide");
	extract($info); // SETS: title,keywords,desc,notes	
	// build revision history
	$rows=$qqc->getRows('mod/proj/hcncrg::history',-1,$id);	
	$historystring='';
	foreach ($rows as $row) {
		extract($row); // sets: userid,timestamp,changes
		$name=$qqs->getUserProfile($userid,"name");
		$initials=$name->getAbbreviated(data_name::ABBREV_FIRSTINITIALLAST);
		$date=date('m/d/Y',$timestamp);
		$historystring .= "$date $initials: $changes\n";
	}
	return array($title,$keywords,$desc,$notes,$historystring);
}

// updates item information
public function updateItem($id,$title,$keywords,$desc,$notes,$updatedesc) {
	global $qqc,$qqs;
	
	$qqc->act("mod/proj/hcncrg::update",$id,$title,$keywords,$desc,$notes);
	$qqc->insert("mod/proj/hcncrg::addhistory",$id,$qqs->getUser(),$updatedesc);
}

// deletes an item
public function deleteEntry($id) {
	global $qqc;
	$qqc->act("mod/proj/hcncrg::delete",$id);
}

//////////////////////////////// end of module /////////////////////////////////
} ?>
