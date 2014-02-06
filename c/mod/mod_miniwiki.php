<?php if (FILEGEN != 1) die;
class mod_miniwiki {
//////////////////////////////// class to store and retrieve wiki pages

// returns id of the action
public function createPage($name) {
	global $qqc, $qqu;
	if ($qqc->getValue("mod/miniwiki::countpages",$name) > 0)
		throw new exception("page name $name already exists in wiki database");
	$id=$qqu->newText(True,'',util::SRC_MINIWIKI);
	$qqc->insert("mod/miniwiki::create",$name,$id);
}

public function getAllNames() {
	global $qqc;
	return $qqc->getRows("mod/miniwiki::allRows",-1);
}

public function nameExists($name) {
	global $qqc;
	return $qqc->getValue("mod/miniwiki::countpages",$name);
}

public function getTextID($name) {
	global $qqc;
	if (0 == $qqc->getValue("mod/miniwiki::countpages",$name))
		return False;
	return $qqc->getValue("mod/miniwiki::getTextId",$name);
}


//////////////////////////////// end of class definition ///////////////////////
} ?>
