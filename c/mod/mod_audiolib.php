<?php if (FILEGEN != 1) die;
class mod_audiolib {
//////////////////////////////// class to maintain a podcasting audio library

private $libname;		// name of library

function __construct($name) {
	$this->libname=$name;
}

// returns duration in seconds, or 0 if not a valid sound file
public function getFileDuration($fileobj) {
	require_once('l/getid3/getid3/getid3.php');
	$getid3=new getID3;
	$info=$getid3->analyze($fileobj->getFilename());
	unset($getid3);
	if (isset($info['error'])) {
		infolog("info","getId3 error: {$info['error']}");
		return 0;
	}
	return (int)(0.5+$info['playtime_seconds']);
}

// returns id of the entry
public function createEntry($fileobj,$info1,$info2,$info3,$date,$duration) {
	global $qqc;
	if (0 == ($duration=$this->getFileDuration($fileobj)))
		throw new exception("{$fileobj->getFilename()} is not a valid sound file.  Cannot add to library.");
	return $qqc->insert("mod/audiolib::create",$this->libname,$fileobj->getId(),$info1,$info2,$info3,$date,$duration);
}

// returns array of ($fileid,$info1,$info2,$info3,$recorddate,$duration)
public function getFileInfo($identry) {
	global $qqc;
	$row=$qqc->getRows("mod/audiolib::getFileInfo",1,$identry);
	return $row;
}

// returns array of all ids
public function getAllEntries() {
	global $qqc;
	$rows=$qqc->getRows("mod/audiolib::allRows",-1,$this->libname);
	$retval=array();
	foreach ($rows as $row)
		$retval[]=$row['identry'];
	return $retval;	
}

public function updateEntry($identry,$fileobj,$info1,$info2,$info3,$date) {
	global $qqc;
	if (0 == ($duration=$this->getFileDuration($fileobj)))
		throw new exception("{$fileobj->getFilename()} is not a valid sound file.  Cannot add to library.");
	$qqc->act("mod/audiolib::update",$identry,$fileobj->getId(),$info1,$info2,$info3,$date,$duration);
}

public function deleteEntry($identry,$bDeleteFile=True) {
	global $qqc;

	if ($bDeleteFile) {
		$row=$qqc->getRows("mod/audiolib::getFileInfo",1,$identry);
		if ($row !== False) {
			file::delete($row['fileid']);
		}
	}	
	$qqc->act("mod/audiolib::delete",$identry);
}

//////////////////////////////// end of class definition ///////////////////////
} ?>
