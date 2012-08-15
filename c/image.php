<?php if (FILEGEN != 1) die;
class image {
// class definition for images /////////////////////////////////////////////////
private $filename;
private $natheight;
private $natwidth;
private $id;
private $alt;
private $type;
private $scaledfiles;// actual image files.  array[filenum] of array(width,height)
private $scalednum=0;// requested cached file or 0 if using natural size
private $actwidth;	// actual width of cached file to output
private $actheight; // actual height...
private $longname;

function __construct($id,$longname="",$defaultid=0) {
	global $qqc;
	
	$this->longname=$longname;
	if (is_numeric($id))
		$info=$qqc->getRows("image::info",1,(string)$id);
	else	
		$info=$qqc->getRows("image::infofromalt",1,(string)$id);
	if ($info === False) {
		if ($defaultid == 0)
			throw new exception("image not found: $id");
		$info=$qqc->getRows("image::info",1,(string)$defaultid);
		if ($info === False)
			throw new exception("neither image $id nor its specified default $defaultid exists");
		$id=$defaultid;	
	}
	extract($info); //SETS: imageid:4,filename:4,alt:4,type:4,width:2,height:2
	$this->id=$id=$imageid;
	$this->filename=$filename;
	$this->natheight=$height;
	$this->natwidth=$width;
	$this->alt=$alt;
	$this->type=$type;
	// now get all available image sizes
	$info=$qqc->getRows("image::files",-1,(string)$id);
	$this->scaledfiles=array();
	if ($info !== False) {
		// strip out names of columns
		foreach ($info as $row)
			$this->scaledfiles[$row["filenum"]]=array($row["width"],$row["height"]);
	}		
}
public function getId() {return (int)$this->id;}
public function getName() {return $this->alt;}

const SORTBY_NEWESTFIRST = "image::idsbydatedesc";
const SORTBY_OLDESTFIRST = "image::idsbydate";
const SORTBY_NAME = "image::idsbyname"; 

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
	$stylegen->registerStyledId($this->longname,"img",$usage,$parentlong);
	
}

static function createFromFile($filename,$alt,$bOverwrite=True) {
	global $qqc;
	
	$size=getimagesize($filename);
	if ($size === False)
		throw new exception("could not read size of image file $filename");
	$width=$size[0];
	$height=$size[1];	
	$type=substr(image_type_to_extension($size[2]),1);
	if ($type == "jpeg")
		$type="jpg";
	if ($bOverwrite) {
		// check to see if this image exists.  if it doesn't set bOverwrite to False
		$id=$qqc->getRows("image::findfile",1,$filename);
		if ($id !== False) {
			$qqc->act("image::update",$id,$alt,$type,$height,$width);
		} else
			$bOverwrite=False;		// forces creation below
	}
	// don't change to 'else'.  bOverwrite can change in code above
	if (!$bOverwrite) {
		$id=$qqc->insert("image::insert",$filename,$alt,$type,$width,$height);
	}	
	return new image($id);
}

// returns True or error message
private static function scaleImage($file,$newfile,$type,$width,$height,$newwidth,$newheight) {
	switch ($type) {
		case "jpg":
			$dir=getcwd();
			$image=imagecreatefromjpeg($file);
		break;
		case "gif":
			$image=imagecreatefromgif($file);
		break;
		case "png":
			$image=imagecreatefrompng($file);
		break;
		default:
			return ("unsupported file type for image: $type");
		break;
	}
	$resizedimage=imagecreatetruecolor($newwidth,$newheight);
	// preserve alpha of original image:
	imagecolortransparent($resizedimage,imagecolorallocate($resizedimage,0,0,0));
	imagealphablending($resizedimage,false);
	imagesavealpha($resizedimage,true);	
//	imagefill($resizedimage,0,0,imagecolorallocatealpha($resizedimage,255,255,255,127));
	// do the resize:
	if (!imagecopyresampled($resizedimage,$image,0,0,0,0,$newwidth,$newheight,$width,$height))
		return "could not resize image '$file' to height=$newheight and width=$newwidth";
		
	switch ($type) {
		case "jpg":
			$dir=getcwd();
			if (!imagejpeg($resizedimage,$newfile,85))
				return "could not write resized image to jpg file: '$newfile' at height=$newheight and width=$newwidth";
		break;
		case "gif":
			if (!imagegif($resizedimage,$newfile))
				return "could not write resized image to gif file: '$newfile' at height=$newheight and width=$newwidth";
		break;
		case "png":
			if (!imagepng($resizedimage,$newfile,3))
				return "could not write resized image to png file: '$newfile' at height=$newheight and width=$newwidth";
		break;
	}
	return True;
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
static function createFromUpload($file,$alt,$maxwidth,$maxheight) {
	global $qq,$qqc;

	if (is_object($file)) {
		$filename=$file->getTempName(1);
		if ($filename === False)
			return 'No file Uploaded';
		if ($alt == '')
			$alt=$file->getUploadedName();
	} else {
		$filename=$file;
	}
		
	$size=getimagesize($filename);
	if ($size === False)
		return "could not read size of uploaded image file $filename";
	$width=$size[0];
	$height=$size[1];	
	$type=substr(image_type_to_extension($size[2]),1);
	
	if ($type == "jpeg")
		$type="jpg";
	
	$newheight=$height;
	$newwidth=$width;		
	if ($height > $maxheight) {
		$newwidth=(int)(0.5+$width*$maxheight/$height);
		$newheight=$maxheight;
	}
	if ($newwidth > $maxwidth) {
		$newwidth=$maxwidth;
		$newheight=(int)(0.5+$height*$maxwidth/$width);
	}

	// make certain that alternate is unique	
	$alt=self::adjustAlt($alt);

	// insert the file to get an id so we can name the file uniquely
	$id=$qqc->insert("image::insert",$filename,$alt,$type,$newwidth,$newheight);
	$newfile="p/{$qq['project']}/upl/i$id.$type";
	
	if ($width == $newwidth && $height == $newheight) {
		// copy the file.  this is better because resizing can change quality of image
		copy($filename,$newfile);
	} else {
		if (True !== ($errmsg=image::scaleImage($filename,$newfile,$type,$width,$height,$newwidth,$newheight))) {
			image::delete($id,False);
			return $errmsg;
		}
		// code to speed up re-dos of scrapbook generator:
		// this is harmless when doing image uploads, but a waste of time:
		copy($newfile,$filename);	
	}	
	$qqc->act("image::updatefile",$id,$newfile);
	
	return new image($id);
}

static function delete($id,$bDeleteResource=True) {
	global $qqc;

	if ($bDeleteResource) {	
		$info=$qqc->getRows("image::info",1,(string)$id);
		if ($info === False)
			return;		// no image to delete
		extract($info); //SETS: filename:4,alt:4,type:4,width:2,height:2
		
		$bSuccess=unlink($filename);
	}	
	$qqc->act("image::delete",(string)$id);
}

public function getNaturalSize() {
	return array($this->natwidth,$this->natheight);
}

public function requestSize($width,$height) {
	global $qq,$qqc;
	
	$width=(int)$width;
	$height=(int)$height;
	
	if ($width == $this->natwidth && $height == $this->natheight) {
		$this->scalednum=0;	// forces a selection of natural file
		return;
	}
	foreach($this->scaledfiles as $filenum=>$file) {
		if ($file[0] == $width && $file[1] == $height) {
			// found an existing match!  that's all we need
			$this->scalednum=$filenum;
			$this->actwidth=$width;
			$this->actheight=$height;
			return;
		}
	}
	// put in record because we will use the new number as the file name
	$newnum=$qqc->insert("image::insertfile",(string)$this->id,$width,$height);
	$newfile="g/scaledimage/{$qq['project']}$newnum.{$this->type}";
	if (True !== ($errmsg=$this->scaleImage($this->filename,$newfile,$this->type,$this->natwidth,$this->natheight,$width,$height)))
		throw new exception($errmsg);	
	$this->actwidth=$width;
	$this->actheight=$height;
	$this->scalednum=$newnum;
	$this->scaledfiles[$newnum]=array($width,$height);
}

public function setMaximumSize($width,$height) {
	$width=(int)$width;
	$height=(int)$height;
	if ($width >= $this->natwidth && $height >= $this->natheight)
		return;		// no need to do anything
	$newwidth=$this->natwidth;
	$newheight=$this->natheight;
	if ($width < $newwidth) {
		$newwidth=$width;
		$newheight=(int)(0.5+$width*$this->natheight/$this->natwidth);
	}
	if ($height < $newheight) {
		$newheight=$height;
		$newwidth=(int)(0.5+$height*$this->natwidth/$this->natheight);
	}
	$this->requestSize($newwidth,$newheight);
}

public function getActualSize() {
	if (0 == $this->scalednum)
		return $this->getNaturalSize();
	return array($this->actwidth,$this->actheight);	
}

public function getInfo() {
	global $qq,$qqi;
	if ($this->scalednum == 0) {
		$height=$this->natheight;
		$width=$this->natwidth;
		$src=$this->filename;
	} else {
		$height=$this->actheight;
		$width=$this->actwidth;
		$src="g/scaledimage/{$qq['project']}{$this->scalednum}.{$this->type}";	
	}
	$srcuri=$qqi->hrefPrep($src,False,'',idstore::ENCODE_NONE);
	return array($srcuri,$width,$height);
}

public function getOutput($mapshortname='',$classes=Null) {
	global $qq,$qqi,$qqu;
	
	$map= ($mapshortname != "") ? " usemap=\"#{$mapshortname}\"" : "";
	if ($classes == Null) {
		$keyword=($this->longname != "") ? '<img'.$qqi->idcstr($this->longname) : '<img';
	} else {
		$keyword=substr($qqu->startHTML('img',$classes),0,-1);
	}	
	$alt=($this->alt != "") ? " alt=\"{$this->alt}\"" : "";
	list($srcuri,$width,$height)=$this->getInfo();
	$retval="$keyword src=\"{$srcuri}\"$alt height=\"{$height}\" width=\"{$width}\"{$map} />";
	return $retval;
}

// end of class definition /////////////////////////////////////////////////////
} ?>
