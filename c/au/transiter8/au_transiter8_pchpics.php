<?php if (FILEGEN != 1) die;
// transaction iterator to poll peach basket competitors for product information
class au_transiter8_pchpics {
////////////////////////////////////////////////////////////////////////////////

private $pics;
function __construct() {
	$pics=file('t/images.txt');
	$this->pics=array();
	foreach($pics as $pic) {
		$pic=trim($pic);
		$i=strpos($pic,'|');
		$this->pics[]=array((int)substr($pic,0,$i),substr($pic,$i+1));
	}	
}

// returns a form object
public function makeform($au) {
	$form=new llform($au,'form');
	$form->addFieldset('d','Grab Pics');
	$form->addControl('d',new ll_edit($form,'startix',4,4,'Starting index'));
	$form->addControl('d',new ll_edit($form,'count',4,4,'Count'));
	$form->addControl('d',new ll_button($form,'iterate','Iterate'));
	return $form;
}

// returns data block, reading form
public function initIterator($au) {
	global $qqc;
	
	$data=array();
	
	$form=$this->makeform($au);
	$vals=$form->getValueArray();
	$ix=$data['ix']=(int)$vals['startix'];
	if ($ix > sizeof($this->pics))	
		$data['status']='finished';
	else {
		$data['status']='iterating';
	}
	$data['i']=0;
	$data['filename']=$this->pics[$ix][0];
	$data['source']=$this->pics[$ix][1];
	$data['maxcount']=(int)$vals['count'];
	$data['message']='';
	$data['picurl']='(none)';	
	return $data;
}

// returns status text 'iterating', 'finished', or 'error'
public function getStatus($data) {
	return $data['status'];
}

// returns text for the status line
public function getStatusLine($data) {
	if ($data['status'] == 'finished')
		return "Done Processing.  Last index Processed was {$data['filename']}";	
	return ("processing #{$data['i']} Index {$data['ix']} Source {$data['source']}  File {$data['filename']}");
}

// returns text for the next log line
public function getLogLine($data) {
	$retval="processed {$data['ix']} pic url {$data['picurl']}";
	if ($data['message'] != '')
		$retval .= " message: {$data['message']}";
	return $retval;	
}

// does the next iteration, updating data block and whatever else needs doing.  returns updated data block
public function iterate($data) {
	global $qqc;

	if ($data['i'] >= $data['maxcount']) {
		$data['status']='finished';
		return $data;
	}
	if ($data['ix'] >= sizeof($this->pics)) {
		$data['status']='finished';
		return $data;
	}
	$ix=$data['ix'];
	$data['filename']=$this->pics[$ix][0];
	$data['source']=$url=$this->pics[$ix][1];
	$data['message']='';
	
	if (False !== ($i=strpos($url,'?osCid')))
		$url=substr($url,0,$i);
	$searchresult1=file_get_contents($url);
	
	$data['i']++;	
	$data['ix']++;
		
	if (preg_match('/<img src="([^"]+)"/is',$searchresult1,$matches)) {
		$urlpic="http://www.naturalgrocers.com/".$matches[1];
		$type=substr($urlpic,-3);		// EXPAND
		$data['picurl']=$urlpic;
		$searchresult=file_get_contents($urlpic);
		$filename='t/naturalgrocers/'.$data['filename'].".$type";
		file_put_contents($filename,$searchresult);
		
		$size=getimagesize($filename);
		if ($size !== False) {
			$width=$size[0];
			$height=$size[1];	
			
			$newheight=$height;
			$newwidth=$width;
			
			$maxheight=300;
			$maxwidth=400;		
			if ($height > $maxheight) {
				$newwidth=(int)(0.5+$width*$maxheight/$height);
				$newheight=$maxheight;
			}
			if ($newwidth > $maxwidth) {
				$newwidth=$maxwidth;
				$newheight=(int)(0.5+$height*$maxwidth/$width);
			}
			
			if ($newwidth != $width || $newheight != $height) {
				switch ($type) {
					case "jpg":
						$image=imagecreatefromjpeg($filename);
					break;
					case "gif":
						$image=imagecreatefromgif($filename);
					break;
					case "png":
						$image=imagecreatefrompng($filename);
					break;
					default:
						return $data;
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
					return $data;
					
				switch ($type) {
					case "jpg":
						imagejpeg($resizedimage,$filename,85);
					break;
					case "gif":
						imagegif($resizedimage,$filename);
					break;
					case "png":
						imagepng($resizedimage,$filename,3);
					break;
				}
			} // if needed to resize			
		} // if could read file size
	} // if image found		
	
	return $data;
}

public function outputForm($au,$state,$transaction) {
	$form=$this->makeform($au);
	echo $form->getDumpStyleFormOutput($state,$transaction);
}

/////////////////////////////////////////////////////////////////// end of class
} ?>
