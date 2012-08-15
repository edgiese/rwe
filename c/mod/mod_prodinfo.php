<?php if (FILEGEN != 1) die;
class mod_prodinfo {
//////////////////////////////// class to store and retrieve product information

// returns id
public function addMfr($mfrName) {
	global $qqc;
	
	return (int)$qqc->insert("mod/prodinfo::addmfr",$mfrName);
}

// no return.  mfr can be int (id) or string (name)
public function removeMfr($mfr) {
	global $qqc;
	
	if (!($id=$this->isMfr($mfr)))
		throw new exception("unknown manufacturer $mfr.  Can't remove it");
	$qqc->act("mod/prodinfo::delmfr",$id);
}

// returns array of manufacturer's names, indexed by id
public function getMfrList() {
	global $qqc;
	
	$mfrs=$qqc->getRows("mod/prodinfo::allMfrs",-1);
	$retval=array();	
	if ($mfrs !== False) {
		foreach ($mfrs as $mfr) {
			$retval[$mfr['id']]=$mfr['name'];
		}
	}	
	return $retval;	
}

// returns id, or False.  mfr can be int (id) or string (name)
public function isMfr($mfr) {
	global $qqc;
	
	if (is_numeric($mfr)) {	
		$row=$qqc->getRows("mod/prodinfo::getMfrById",1,(int)$mfr);
	} else {	
		$row=$qqc->getRows("mod/prodinfo::getMfrByName",1,$mfr);
	}
	return ($row === False) ? False : $row;	
}

public function getMfrText($id) {
	global $qqc;
	
	$row=$qqc->getRows("mod/prodinfo::getMfr",1,$id);
	if ($row === False)
		throw new exception("unknown mfr id $id");
	return $row['name'];		
}

// returns an array of ids having a particular mfr id
public function getMfrIds($mfr) {
	global $qqc;
	
	if (False ===($retval=$qqc->getCols("mod/prodinfo::idsbymfr",-1,(int)$mfr)))
		return array();
	return $retval;	
}

// creates a blank description record for a product
public function initProdDesc($id) {
	global $qqc;
	
	if (False !== ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
		throw new exception("product description record alredy exists for prod $id");
	$qqc->insert("mod/prodinfo::new",$id);
}

// mfr can be int (id) or string (name).  returns True if new Mfr, False if existing.  will automatically add mfr name if necessary
public function setMfr($id,$mfr) {
	global $qqc;
	$retval=False;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
		throw new exception("non-existent product description record for prod $id");
	if ($mfr === '' || $mfr === 0 || (!is_string($mfr) && !is_numeric($mfr)))
		throw new exception("illegal manufacturer name: $mfr");	
	if (!($idmfr=$this->isMfr($mfr))) {
		if (!is_numeric($mfr)) {
			$retval=True;
			$idmfr=$this->addMfr($mfr);
		} else		
			throw new exception("unknown manufacturer number $mfr.  Can't set prod $id to have it");
	}
	$id=(int)$id;
	if ($id <= 0)
		throw new exception("illegal product number");	
	$qqc->act("mod/prodinfo::setmfr",$id,$idmfr);
	return $retval;
}

public function setTitle($id,$title) {
	global $qqu,$qqc;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
		throw new exception("non-existent product description record for prod $id");
	if (!is_string($title))
		throw new exception("title must be string.  its type is ".gettype($title));
	if ($title == '') {
		// don't have blank titles.  delete instead
		if (0 != ($idtitle=$prodrow['idtitle'])) {
			$qqu->deleteText($idtitle);
			$qqc->act("mod/prodinfo::settitle",$id,'0');
		}
	} else {
		// insert or update
		if (0 != ($idtitle=$prodrow['idtitle'])) {
			$qqu->updateText($idtitle,$title);
		} else {
			$idtitle=$qqu->newText(False,$title,util::SRC_PRODTITLE);
			$qqc->act("mod/prodinfo::settitle",$id,$idtitle);
		}		
	}	
}

public function setMainImg($id,$idimage) {
	global $qqc;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id))) {
		$this->initProdDesc($id);
		if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
			throw new exception("non-existent product description record for prod $id");
	}	
	// creating an image object is just to verify that it is a legal image.  constructor will throw an exception if not
	if ($idimage != ($oldid=$prodrow['idimg'])) {
		if ($oldid != 0)
			image::delete($oldid);
		// verify that this is a legal image number.
		if ($idimage != 0)
			$image=new image($idimage);
		$qqc->act("mod/prodinfo::setimg",$id,$idimage);
	}
}

public function setDesc($id,$desc) {
	global $qqu,$qqc;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
		throw new exception("non-existent product description record for prod $id");
	if (!is_string($desc))
		throw new exception("description must be string.  its type is ".gettype($desc));
	if ($desc == '') {
		// don't have blank descriptions.  delete instead
		if (0 != ($iddesc=$prodrow['iddesc'])) {
			$qqu->deleteText($iddesc);
			$qqc->act("mod/prodinfo::setdesc",$id,'0');
		}
	} else {
		// insert or update
		if (0 != ($iddesc=$prodrow['iddesc'])) {
			$qqu->updateText($iddesc,$desc);
		} else {
			$iddesc=$qqu->newText(True,$desc,util::SRC_PRODDESC);
			$qqc->act("mod/prodinfo::setdesc",$id,$iddesc);
		}		
	}	
}

public function setShape($id,$shape,$dim1=0,$dim2=0,$dim3=0) {
	global $qqc;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
		throw new exception("non-existent product description record for prod $id");
	if (False === array_search($shape,array('cylinder','rect','huge','none')))
		throw new exception("illegal shape: $shape");
	switch ($shape) {
		case 'cylinder':
			if ($dim1 <= 0 || $dim2 <= 0 ||  $dim3 != 0)
				throw new exception("illegal dimensions for shape $shape: $dim1 $dim2 $dim3");
		break;
		case 'rect':
			if ($dim1 <= 0 || $dim2 <= 0 ||  $dim3 <= 0)
				throw new exception("illegal dimensions for shape $shape: $dim1 $dim2 $dim3");
		break;
		case 'huge':
		case 'none':
			if ($dim1 != 0 || $dim2 != 0 ||  $dim3 != 0)
				throw new exception("illegal dimensions for shape $shape: $dim1 $dim2 $dim3");
		break;
	}	
	$qqc->act("mod/prodinfo::setshape",$id,$shape,$dim1,$dim2,$dim3);
}

public function setWeight($id,$weight) {
	global $qqc;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
		throw new exception("non-existent product description record for prod $id");
	if ($weight < 0)
		throw new exception("illegal weight $weight");
	$qqc->act("mod/prodinfo::setweight",(int)$id,(int)$weight);
}

const FLAG_FRAGILE=0x01;
const FLAG_COLD=0x02;

public function setFlags($id,$flags) {
	global $qqc;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
		throw new exception("non-existent product description record for prod $id");
	if ($flags < 0 || $flags > (self::FLAG_FRAGILE|self::FLAG_COLD))
		throw new exception("illegal flags $flags");
	$qqc->act("mod/prodinfo::setflags",$id,$flags);
}

// returns array or False if not found
public function getProdInfo($id) {
	global $qqu,$qqc;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,(int)$id)))
		return False;
	$retval=array();
	$retval['mfr']=$prodrow['idmfr'] != 0 ? $this->getMfrText($prodrow['idmfr']) : '';
	$retval['title']=$prodrow['idtitle'] != 0 ? $qqu->getEditText($prodrow['idtitle']) : '';
	$retval['desc']=$prodrow['iddesc'] != 0 ? $qqu->getEditText($prodrow['iddesc']) : '';
	$retval['imageid']=$prodrow['idimg'];
	$retval['shape']=$prodrow['shape'];
	$retval['dim1']=$prodrow['dim1'];
	$retval['dim2']=$prodrow['dim2'];
	$retval['dim3']=$prodrow['dim3'];
	$retval['weight']=$prodrow['weight'];
	$retval['flags']=$prodrow['flags'];
	return $retval;
}


// removes a product description
public function deleteProdInfo($id) {
	global $qqc;
	if (False === ($prodrow=$qqc->getRows("mod/prodinfo::info",1,$id)))
		return;
	$this->setTitle($id,'');
	$this->setMainImg($id,0);
	$this->setDesc($id,'');
	$qqc->act("mod/prodinfo::delete",$id);	
}

// find a product id from a title id or False if no product matches
public function prodIdFromTitleId($textid) {
	global $qqc;
	return $qqc->getRows("mod/prodinfo::prodidfromtitle",1,(string)$textid);	
}

// find a product id from a title id or False if no product matches
public function prodIdFromDescId($textid) {
	global $qqc;
	return $qqc->getRows("mod/prodinfo::prodidfromdesc",1,(string)$textid);	
}

//////////////////////////////////////////////////////// group functions
// returns group id
public function addGroup($category,$description) {
	global $qqc;
	if ($category <= 0)
		throw new exception("category must be positive integer, was $category");
	return (int)$qqc->insert("mod/prodinfo::addgroup",(int)$category,$description);
}

// returns group id or False
public function findGroup($description) {
	global $qqc;
	return $qqc->getRows("mod/prodinfo::findgroup",1,$description);
}

// returns array of (groupid=>description)
public function getAllGroups($category=0) {
	global $qqc;
	if ($category == 0) {
		$rows=$qqc->getRows("mod/prodinfo::allgroups",-1);
	} else {
		$rows=$qqc->getRows("mod/prodinfo::categorygroups",-1,(int)$category);
	}	
	$retval=array();
	if ($rows !== False) {
		foreach($rows as $row)
			$retval[$row['groupid']]=$row['description'];
	}
	return $retval;
}

// returns array of name, number of items.  returns false if group doesn't exist
public function getGroupInfo($groupid) {
	global $qqc;
	$row=$qqc->getRows("mod/prodinfo::groupinfo",1,(int)$groupid);
	if ($row === False)
		return False;
	return array($row['description'],$row['count']);	
}

public function renameGroup($groupid,$newname) {
	global $qqc;
	$qqc->act("mod/prodinfo::renamegroup",(int)$groupid,$newname);
}

public function deleteGroup($groupid) {
	global $qqc;
	$this->emptyGroup($groupid);
	$qqc->act("mod/prodinfo::deletegroup",(int)$groupid);
}

public function addItemToGroup($groupid,$itemid) {
	global $qqc;
	if (!$this->isInGroup($groupid,$itemid))
		$qqc->act("mod/prodinfo::addgroupitem",(int)$groupid,(int)$itemid);
}

public function removeItemFromGroup($groupid,$itemid) {
	global $qqc;
	$qqc->act("mod/prodinfo::removegroupitem",(int)$groupid,(int)$itemid);
}

public function emptyGroup($groupid) {
	global $qqc;
	$qqc->act("mod/prodinfo::emptygroup",(int)$groupid);
}

// returns array of all groups owning an item
public function getOwningGroups($itemid) {
	global $qqc;
	$retval=$qqc->getCols("mod/prodinfo::owninggroups",-1,(int)$itemid);
	if ($retval === False)
		$retval=array();
	return $retval;	
}

// returns description text of first groups owning an item
public function getFirstOwningGroup($itemid,$category) {
	global $qqc;
	$retval=$qqc->getCols("mod/prodinfo::firstowninggroup",1,(int)$itemid,(int)$category);
	if ($retval === False)
		$retval='';
	return $retval;	
}


// returns array of all items in a group
public function getGroupItems($groupid) {
	global $qqc;
	$retval=$qqc->getCols("mod/prodinfo::groupitems",-1,(int)$groupid);
	if ($retval === False)
		$retval=array();
	return $retval;	
}

// returns array of ungrouped items
public function getUngroupedItems() {
	global $qqc;
	$retval=$qqc->getCols("mod/prodinfo::ungroupeditems",-1);
	if ($retval === False)
		$retval=array();
	return $retval;	
}

// returns True if item is in a group, and false if not
public function isInGroup($groupid,$itemid) {
	global $qqc;
	return $qqc->getValue("mod/prodinfo::isitemingroup",(int)$groupid,(int)$itemid);
}

//////////////////////////////// end of class definition ///////////////////////
} ?>
