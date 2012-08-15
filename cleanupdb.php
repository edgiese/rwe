<?php if (FILEGEN != 1) die;
// cleanup functions for globals
// these can change from the time they've been created
if ($qqi->isDirty() || ($qqj !== False && $qqj->isDirty()) || ($qqy !== False && $qqy->isDirty())) {
	$qqi->setClean();
	$qqj->setClean();
	$qqy->setClean();
	$qqobjs=array($qqp,$qqi,$qqy,$qqj);
	$fh=fopen($savefile,'w');
	fwrite($fh,serialize($qqobjs));
	fclose($fh);
}
$qqs->saveState();

?>
