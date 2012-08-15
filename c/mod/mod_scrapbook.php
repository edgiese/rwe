<?php if (FILEGEN != 1) die;
class mod_scrapbook {
///////// SCRAPBOOK MODULE /////////////////////////////////////////////////////
private $id;

function __construct($scrapbookname) {
	global $qqc;
	
	$id=$qqc->getRows("mod/scrapbook::idfromname",1,$scrapbookname);
	if ($id === False)
		throw new exception("scrapbook name '$scrapbookname' not found in scrapbook table");
	$this->id=$id;	
}

// return array of arrays(idgroup,groupname) or False if there are none
public function getGroups() {
	global $qqu,$qqc;
	
	$retval=$qqc->getRows("mod/scrapbook::groupnames",-1,$this->id);
	if ($retval === False)
		return False;
	// substitute the title of the group extracted from the creole for the id of the creole.  use "(untitled group)" if no group # found

	foreach ($retval as &$row) {
		$creole=$qqu->getEditText($row["groupname"]);
		$row["groupname"]="(untitled group)";
		$lines=explode("\n",$creole);
		foreach ($lines as $line) {
			$line=trim($line);
			if (False !== ereg("^=([^=]*)=$",$line,&$regs)) {
				$row["groupname"]=$regs[1];
				break;
			}
		}
		$row=array($row["idgroup"],$row["groupname"]);
	}
	return $retval;	
}

// returns creole text for a groupname
public function getGroupJournal($id) {
	global $qqu,$qqc;
	
	$idtext=$qqc->getValue("mod/scrapbook::groupjournal",$id);
	return $qqu->getEditText($idtext); 
}

// returns array of array(image,submittedby,submitdate,comment,source) for a group
// if groupname is Null, returns list of uncategorized images
// in either case, function returns False if there are no images
public function getGroupImages($idgroup) {
	global $qqc;
	if ($idgroup == Null) {
		$imageinfo=$qqc->getRows("mod/scrapbook::uncategorized",-1,$this->id,$this->id);
	} else {
		$imageinfo=$qqc->getRows("mod/scrapbook::groupimages",-1,$idgroup);
	}
	$retval=array();
	foreach ($imageinfo as $row) {
		$row["image"]=new image($row["image"]);
		extract($row);
		$retval[]=array($image,$submittedby,$submitdate,$comment,$source);
	}
	return $retval;
}

// deletes a scrapbook entirely.
static function deleteScrapBook($name) {
	global $qqu,$qqc;

	$id=$qqc->getRows("mod/scrapbook::idfromname",1,$name);
	if ($id === False)
		return;		// nothing to delete
	
	// free the text objects associated with this scrapbook
	$groups=$qqc->getRows("mod/scrapbook::groupnames",-1,$id);
	// note that this query returns an odd name for idjournal because it is substituted in another routine
	if ($groups !== False) {
		foreach ($groups as $group) {
			$qqu->deleteText($group["groupname"]);
		}
	}
	
	// free the images and delete the files associated with them
	$imageids=$qqc->getRows("mod/scrapbook::imageids",-1,$id);
	if ($imageids !== False) {
		foreach ($imageids as $row) {
			image::delete($row["idimage"]);
		}
	}
	
	// now free all of the resources for this scrapbook
	$qqc->act("mod/scrapbook::deleteimages",$id);
	$qqc->act("mod/scrapbook::deletegroups",$id);
	$qqc->act("mod/scrapbook::delete",$id);
}

// creates a new scrapbook from a name (description, actually)
static function createNewScrapbook($name) {
	global $qqc;

	if (False === $qqc->getRows("mod/scrapbook::idfromname",1,$name))
		$qqc->insert("mod/scrapbook::create",$name);
	return new mod_scrapbook($name);
}

// adds a new group to a scrapbook
public function addGroup($journal) {
	global $qqc,$qqu;
	
	$idjournal=$qqu->newText(True,$journal,util::SRC_SCRAPBOOK);
	return (int)$qqc->insert("mod/scrapbook::newgroup",$this->id,$idjournal);
}

// add a new image to a scrapbook
public function addImage($image,$idgroup,$submitter,$description,$source) {
	global $qqc;
	
	$idimage=$qqc->insert("mod/scrapbook::newimage",$this->id,$image->getId(),$submitter,$description,$source);
	if ($idgroup != 0) {
		// add this image to the table of contents
		if (0 == $qqc->getValue("mod/scrapbook::countseq",$idgroup))
			$seq=0;
		else
			$seq=1+$qqc->getValue("mod/scrapbook::maxseq",$idgroup);
		
		$qqc->insert("mod/scrapbook::toc",$idgroup,$idimage,$seq);		
	}
}
 

///////// END OF MODULE ////////////////////////////////////////////////////////
} ?>
