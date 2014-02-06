<?php if (FILEGEN != 1) die;
class mod_visitorrequest {
//////////////////////////////// class to store and retrieve user-submitted data

// returns id of the action
public function createAction($type,$data) {
	global $qqc;
	$qqc->insert("mod/visitorrequest::create",$type,serialize($data));
}

// returns count of actions
public function countActions($type='') {
	global $qqc;
	return $type == '' ? $qqc->getValue("mod/visitorrequest::count") : $qqc->getValue("mod/visitorrequest::countbyactiondesc",$type);
}

// returns array of personal info and array of actions sorted by date
public function readActions($type='') {
	global $qqc;
	$rows=($type == '') ? $qqc->getRows("mod/visitorrequest::allrequests",-1) : $qqc->getRows("mod/visitorrequest::requestbyactiondesc",-1,$type);
	foreach ($rows as &$row)
		$row['data']=unserialize($row['data']);
	return $rows;	
}

// updates a request with new data.  timestamp unaltered.  used to remove sensitive data from storage without removing record....???
public function updateAction($id,$data) {
	global $qqc;

	$qqc->act("mod/visitorrequest::update",$id,serialize($data));
}

// removes all action data
public function deleteAction($id) {
	global $qqc;
	$qqc->act("mod/visitorrequest::delete",$id);
}


//////////////////////////////// end of class definition ///////////////////////
} ?>
