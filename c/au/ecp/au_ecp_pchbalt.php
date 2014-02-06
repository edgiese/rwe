<?php if (FILEGEN != 1) die;
///// Access Unit definition file for peach basket alternative descriptions.  
///// BREAKS DATA ACCESS CONVENTIONS.  MYSQL DEPENDENT.  DEVELOPMENT ONLY.
class au_ecp_pchbalt extends au_base {
////////////////////////////////////////////////////////////////////////////////
public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$idstore->declareHTMLlong($this->longname.'_desc');
}

public function declarestyles($stylegen,$state) {
	global $qqu;
	$stylegen->registerStyledId($this->longname,'div:h2,p,div div','alttext',$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId($this->longname.'_desc',$qqu->getTextRegistrationTags(),'alttext',$this->getParent()->htmlContainerId());
}

public function output($pgen,$brotherid) {
	global $qqi,$qqu,$qq_main_only_dbo;
	
	// CONVENTION BREAKING:
	$qqo=$qq_main_only_dbo;
	

	$prodid=(int)$pgen->getPageExtra();
	if ($prodid <= 0) {
		echo "<div{$qqi->idcstr($this->longname)}>(No Product Specified)</div>";
		return; 	
	}
	$pi=new mod_prodinfo;
	$info=$pi->getProdInfo($prodid);
		

	$sql="select datafunc,datatext,source FROM attribtext WHERE id=$prodid";
	$bFound=False;
	$mfrs=array();
	$titles=array();
	$descs=array();
	$imgs=array();
	$links=array();
	$bFound=False;
	
	foreach ($qqo->query($sql) as $row) {
		$bFound=True;
		switch ($row['datafunc']) {
			case 'mfr':
				$mfrs[$row['source']]=$row['datatext'];
			break;
			case 'title':
				$titles[$row['source']]=$row['datatext'];
			break;
			case 'desc':
				$descs[$row['source']]=$row['datatext'];
			break;
			case 'img':
				$imgs[$row['source']]=$row['datatext'];
			break;
			case 'link':
				if (isset($links[$row['source']]))
					$links[$row['source'.' (2)']]=$row['datatext'];
				else	
					$links[$row['source']]=$row['datatext'];
			break;
		}
	}
	if (!$bFound) {
		echo "<div{$qqi->idcstr($this->longname)}>No Alternative Records.</div>";
		return;
	}
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	
	
	$bLabelPrinted=False;
	foreach ($mfrs as $mfr) {
		if ($mfr != $info['mfr']) {
			if (!$bLabelPrinted) {
				echo "<h2>Alternate Manufacturers</h2>";
				$bLabelPrinted=True;
			}
			echo "<p>$mfr</p>";	
		}
	}

	$bLabelPrinted=False;
	foreach ($titles as $title) {
		if ($title != $info['title']) {
			if (!$bLabelPrinted) {
				echo "<h2>Alternate Titles</h2>";
				$bLabelPrinted=True;
			}
			echo "<p>$title</p>";	
		}
	}

	$dirs=array('v'=>"t/vitacost",'h'=>"t/herbsmd",'t'=>"t/houseofnutrition",'g'=>"t/naturalgrocers",);
	$images=array();
	foreach ($dirs as $src=>$dir) {
		$filetype='';
		if (file_exists("$dir/$prodid.jpg")) {
			$filetype='jpg';
		} else if (file_exists("$dir/$prodid.gif")) {
			$filetype='gif';
		}
		if ($filetype !='')
			$images[]=$srcfilename="$dir/$prodid.$filetype";
	}
	echo "<h2>Images</h2>";
	foreach ($images as $image) {
		echo "<p>$image</p>";
		echo "<br /><img src=\"{$qqi->hrefPrep($image,False,'',idstore::ENCODE_NONE)}\"><br />";
	}
	foreach ($imgs as $source=>$img) {
		echo "<p>image from $source</p>";
		echo "<br /><img src=\"{$img}\"><br />";
	}
	
	foreach ($descs as $source=>$desc) {
		if ($desc != $info['desc']) {
			echo "<h2>Description from $source</h2>";
			echo "<p>Preview:</p>";
			echo "<div{$qqi->idcstr($this->longname.'_desc')}>".$qqu->creole2html($desc)."</div>";
			echo "<p>Source:</p>";
			echo "<div>".nl2br(htmlentities($desc))."</div><hr />";
				
		}
	}
	
	echo "<h2>Links</h2>";
	foreach ($links as $source=>$link) {
		echo "<a target=\"_blank\" href=\"{$link}\">$source<br />";
	}
	echo "</div>";
		
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
