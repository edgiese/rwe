<?php if (FILEGEN != 1) die;
class file {
// class definition for downloadable resource files ////////////////////////////
private $filename;
private $id;
private $type;
private $longname;

function __construct($id,$longname="") {
	global $qqc;
	
	$this->longname=$longname;
	if (is_numeric($id))
		$info=$qqc->getRows("file::info",1,(string)$id);
	else	
		$info=$qqc->getRows("file::infofromalt",1,(string)$id);
	if ($info === False)
		throw new exception("file not found: $id");
	extract($info); //SETS: fileid:4,filename:4,alt:4,type:4
	$this->id=$id=$fileid;
	$this->filename=$filename;
	$this->alt=$alt;
	$this->type=$type;
}

public function getId() {return $this->id;}
public function getFilename() {return $this->filename;}
public function getName() {return $this->alt;}
public function getType() {return $this->type;}
public function getURL($bProtected=False) {
	global $qqi,$qq;
	$protocol=$bProtected ? 'https://' : 'http://';
	return $protocol.$qq['domain'].$qqi->hrefPrep($this->filename,False);
}
public function getLength() {
	return filesize($this->filename);
}

const SORTBY_NEWESTFIRST = "file::idsbydatedesc";
const SORTBY_OLDESTFIRST = "file::idsbydate";
const SORTBY_NAME = "file::idsbyname"; 
const SORTBY_TYPE = "file::idsbytype"; 

static function getAllIds($sortby,$filterstring='') {
	global $qqc;
	
	if ($filterstring != '') {
		$cols=$qqc->getCols($sortby.'filtered',-1,$filterstring.'%');
	} else
		$cols=$qqc->getCols($sortby,-1);
		
	if ($cols === False)
		return array();
	return $cols;	
}

public function setLongName($longname) {
	$this->longname=$longname;
}

public function registerStyled($stylegen,$usage,$parentlong) {
	if ($usage == "")
		$usage="image";
	$stylegen->registerStyledId($this->longname,"a",$usage,$parentlong);
	
}

// returns type
static function getTypeFromFile($filename) {
	// this should be smarter.
	if (False === ($itype=strrpos($filename,'.')))
		return '(none)';
	$type=substr($filename,$itype+1);
	//if (False === array_search($type,array('pdf','doc','mp3')))
	//	return False;
	return $type;	
}

static function createFromFile($filename,$alt,$bOverwrite=True) {
	global $qqc;

	if (False === ($type=self::getTypeFromFile($filename)))
		throw new exception("illegal file type for filename $filename"); 	
	if ($bOverwrite) {
		// check to see if this file exists.  if it doesn't set bOverwrite to False
		$id=$qqc->getRows("file::findfile",1,$filename);
		if ($id !== False) {
			$qqc->act("file::update",$id,$alt,$type);
		} else
			$bOverwrite=False;		// forces creation below
	}
	// don't change to 'else'.  bOverwrite can change in code above
	if (!$bOverwrite) {
		$id=$qqc->insert("file::insert",$filename,$alt,$type);
	}	
	return new file($id);
}

// returns adjusted alt
private static function adjustAlt($alt) {
	// strip off the # sign at the end if it's there
	if (preg_match('/^(.+)( #\\d+)$/',$alt,$matches)) {
		$filterstring=$matches[1];
	} else
		$filterstring=$alt;	
	$ids=self::getAllIds(self::SORTBY_NAME,$filterstring);
	return (sizeof($ids) == 0) ? $alt : $filterstring." #".(sizeof($ids)+1);
}

// returns image object or string with error message
static function createFromUpload($file,$alt='') {
	global $qq,$qqc;

	if (is_object($file)) {
		$filename=$file->getTempName();
		if ($alt == '')
			$alt=$file->getUploadedName();
		$srcfile=$file->getUploadedName();	
	} else {
		$filename=$srcfile=$file;
	}
		
	$type=self::getTypeFromFile($srcfile);

	// make certain that alternate is unique	
	$alt=self::adjustAlt($alt);

	// insert the file to get an id so we can name the file uniquely
	$id=$qqc->insert("file::insert",$filename,$alt,$type);
	$newfile="p/{$qq['project']}/upl/f$id.$type";
	
	copy($filename,$newfile);
	$qqc->act("file::updatefile",$id,$newfile);
	return new file($id);
}

static function delete($id,$bDeleteResource=True) {
	global $qqc;

	if ($bDeleteResource) {	
		$info=$qqc->getRows("file::info",1,(string)$id);
		if ($info === False)
			return;		// no image to delete
		extract($info); //SETS: filename:4,alt:4,type:4
		$bSuccess=unlink($filename);
	}	
	$qqc->act("file::delete",(string)$id);
}

public function getOutput($bIcon=False,$classes=Null,$linktext=Null) {
	global $qq,$qqi,$qqu;
	
	if ($bIcon)
		throw new exception("Feature not implemented yet");
	
	if ($classes == Null) {
		$keyword=($this->longname != "") ? '<a'.$qqi->idcstr($this->longname) : '<a';
	} else {
		$keyword=substr($qqu->startHTML('a',$classes),0,-1);
	}
	if ($linktext == Null)	
		$linktext=($this->alt != "") ? $this->alt : $this->filename;
	$href=$qqi->hrefPrep($this->filename,False,'',idstore::ENCODE_NONE);
	$retval="$keyword href=\"{$href}\">$linktext</a>";
	return $retval;
}

// end of class definition /////////////////////////////////////////////////////
} ?>
